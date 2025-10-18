<?php
// pages/manage_payments.php (COMPLETE UPDATED VERSION - ALL IN ONE)
session_start();
include('../includes/db.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';

// Handle Add Payment (SAME FILE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_payment'])) {
    $user_id = intval($_POST['user_id']);
    $package_id = intval($_POST['package_id']);
    $amount = intval($_POST['amount']);
    $payment_date = mysqli_real_escape_string($conn, $_POST['payment_date']);
    
    if ($user_id > 0 && $package_id > 0 && $amount > 0 && !empty($payment_date)) {
        $package_result = mysqli_query($conn, "SELECT duration FROM package WHERE id = $package_id");
        if (mysqli_num_rows($package_result) > 0) {
            $package = mysqli_fetch_assoc($package_result);
            $duration = $package['duration'];
            $expiry_date = date('Y-m-d', strtotime($payment_date . " + $duration days"));
            
            $insert_query = "INSERT INTO payments (user_id, package_id, amount, payment_date, expiry_date, status) 
                            VALUES ($user_id, $package_id, $amount, '$payment_date', '$expiry_date', 'paid')";
            
            if (mysqli_query($conn, $insert_query)) {
                mysqli_query($conn, "UPDATE users SET package_id = $package_id WHERE id = $user_id");
                $success = "Payment added successfully!";
                
                // Refresh the page to show updated data
                header("Location: manage_payments.php?success=" . urlencode($success));
                exit;
            } else {
                $error = "Error adding payment: " . mysqli_error($conn);
            }
        } else {
            $error = "Invalid package selected!";
        }
    } else {
        $error = "Please fill all fields correctly!";
    }
}

// Handle URL parameters for success/error messages
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}

// Handle Delete Payment
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    if (mysqli_query($conn, "DELETE FROM payments WHERE id=$id")) {
        $success = "Payment deleted successfully!";
    } else {
        $error = "Error deleting payment!";
    }
    header("Location: manage_payments.php?success=" . urlencode($success));
    exit;
}

// totals
$totalEarnings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM payments WHERE status='paid'"))['total'] ?? 0;
$month = date('m'); $year = date('Y');
$monthlyEarnings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM payments WHERE MONTH(payment_date)='$month' AND YEAR(payment_date)='$year' AND status='paid'"))['total'] ?? 0;
$todayEarnings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM payments WHERE payment_date=CURDATE() AND status='paid'"))['total'] ?? 0;
$totalPayments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM payments"))['total'] ?? 0;

// chart data
$earningsData = [];
$q = "SELECT DATE_FORMAT(payment_date, '%b %Y') as month, SUM(amount) as total FROM payments WHERE status='paid' GROUP BY YEAR(payment_date), MONTH(payment_date) ORDER BY payment_date DESC LIMIT 6";
$r = mysqli_query($conn, $q);
while ($row = mysqli_fetch_assoc($r)) $earningsData[] = $row;
$earningsData = array_reverse($earningsData);

// payments list
$payments = mysqli_query($conn, "SELECT p.*, u.name as user_name, pk.name as package_name FROM payments p LEFT JOIN users u ON p.user_id = u.id LEFT JOIN package pk ON p.package_id = pk.id ORDER BY p.payment_date DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Manage Payments - FitLife</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body{display:flex;background:#f8f9fa;min-height:100vh}
        .sidebar{width:250px;background:#343a40;color:#fff;position:fixed;height:100vh;padding-top:20px}
        .sidebar a{color:#fff;display:block;padding:12px 20px;text-decoration:none;transition:0.3s;border-left:4px solid transparent}
        .sidebar a:hover{background:#495057;border-left:4px solid #007bff}
        .sidebar a.active{background:#007bff;border-left:4px solid #fff}
        .content{margin-left:250px;padding:30px;width:100%}
        .card{box-shadow:0 4px 12px rgba(0,0,0,0.1);border-radius:12px;border:none;margin-bottom:20px}
        .stat-card{background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);color:white;border-radius:12px;padding:20px;margin-bottom:20px}
        .stat-card-2{background:linear-gradient(135deg, #f093fb 0%, #f5576c 100%);color:white}
        .stat-card-3{background:linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);color:white}
        .stat-card-4{background:linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);color:white}
        .btn-danger{background:linear-gradient(45deg, #dc3545, #c82333);border:none;border-radius:8px}
        .table-responsive{max-height:600px;overflow-y:auto}
        .payment-status-paid{background:#d4edda;color:#155724;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600}
        .payment-status-pending{background:#fff3cd;color:#856404;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600}
        .payment-amount{background:#28a745;color:white;padding:6px 15px;border-radius:20px;font-weight:600;font-size:14px}
        .chart-container{background:white;padding:20px;border-radius:12px;box-shadow:0 4px 6px rgba(0,0,0,0.1)}
        .modal-header{background:linear-gradient(45deg, #007bff, #0056b3);color:white;border-radius:10px 10px 0 0}
    </style>
</head>
<body>
<div class="sidebar">
    <h4 class="text-center mb-4"><i class="fas fa-dumbbell"></i> FitLife Admin</h4>
    <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="manage_members.php"><i class="fas fa-users"></i> Members</a>
    <a href="manage_packages.php"><i class="fas fa-box-open"></i> Packages</a>
    <a href="manage_payments.php" class="active"><i class="fas fa-credit-card"></i> Payments</a>
    <a href="manage_attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a>
    <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
    <a href="logout.php" class="text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-credit-card text-primary"></i> Payment Management</h2>
        <button class="btn btn-primary" data-toggle="modal" data-target="#addPaymentModal">
            <i class="fas fa-plus-circle"></i> Add New Payment
        </button>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center">
            <i class="fas fa-check-circle me-2"></i>
            <?= htmlspecialchars($success) ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?= htmlspecialchars($error) ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6><i class="fas fa-money-bill-wave"></i> Total Earnings</h6>
                        <h3><?= number_format($totalEarnings, 0) ?>৳</h3>
                    </div>
                    <i class="fas fa-chart-line fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card stat-card-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6><i class="fas fa-calendar-alt"></i> Monthly Earnings</h6>
                        <h3><?= number_format($monthlyEarnings, 0) ?>৳</h3>
                    </div>
                    <i class="fas fa-calendar fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card stat-card-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6><i class="fas fa-sun"></i> Today's Earnings</h6>
                        <h3><?= number_format($todayEarnings, 0) ?>৳</h3>
                    </div>
                    <i class="fas fa-sun fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card stat-card-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6><i class="fas fa-receipt"></i> Total Payments</h6>
                        <h3><?= number_format($totalPayments, 0) ?></h3>
                    </div>
                    <i class="fas fa-receipt fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Earnings Chart -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Earnings Overview (Last 6 Months)</h5>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="earningsChart" height="100"></canvas>
            </div>
        </div>
    </div>

    <!-- Payments List -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-list"></i> All Payments</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th><i class="fas fa-user"></i> Member</th>
                            <th><i class="fas fa-box-open"></i> Package</th>
                            <th><i class="fas fa-money-bill-wave"></i> Amount</th>
                            <th><i class="fas fa-calendar-day"></i> Payment Date</th>
                            <th><i class="fas fa-calendar-times"></i> Expiry Date</th>
                            <th><i class="fas fa-circle"></i> Status</th>
                            <th><i class="fas fa-cogs"></i> Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($p = mysqli_fetch_assoc($payments)): ?>
                        <tr>
                            <td>
                                <div class="font-weight-bold text-primary"><?= htmlspecialchars($p['user_name'] ?? 'N/A') ?></div>
                            </td>
                            <td>
                                <span class="badge badge-info"><?= htmlspecialchars($p['package_name'] ?? 'N/A') ?></span>
                            </td>
                            <td>
                                <span class="payment-amount"><?= number_format($p['amount'], 0) ?>৳</span>
                            </td>
                            <td>
                                <i class="fas fa-calendar text-muted"></i> 
                                <?= date('M j, Y', strtotime($p['payment_date'])) ?>
                            </td>
                            <td>
                                <i class="fas fa-clock text-muted"></i> 
                                <?= date('M j, Y', strtotime($p['expiry_date'])) ?>
                            </td>
                            <td>
                                <span class="payment-status-<?= $p['status'] ?>">
                                    <i class="fas fa-<?= $p['status'] == 'paid' ? 'check' : 'clock' ?>"></i>
                                    <?= ucfirst($p['status']) ?>
                                </span>
                            </td>
                            <td>
                                <a href="manage_payments.php?delete_id=<?= $p['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this payment?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Payment Modal -->
<div class="modal fade" id="addPaymentModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <form method="POST" action="">
      <input type="hidden" name="add_payment" value="1">
      <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Add New Payment</h5>
            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Select Member</label>
                        <select name="user_id" class="form-control" required>
                            <option value="">Choose Member</option>
                            <?php 
                            $users = mysqli_query($conn, "SELECT id, name FROM users WHERE role='user'");
                            while($user = mysqli_fetch_assoc($users)): ?>
                                <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label><i class="fas fa-box-open"></i> Select Package</label>
                        <select name="package_id" class="form-control" required>
                            <option value="">Choose Package</option>
                            <?php 
                            $packages = mysqli_query($conn, "SELECT id, name, price FROM package WHERE status='active'");
                            while($pkg = mysqli_fetch_assoc($packages)): ?>
                                <option value="<?= $pkg['id'] ?>" data-price="<?= $pkg['price'] ?>">
                                    <?= htmlspecialchars($pkg['name']) ?> - <?= $pkg['price'] ?>৳
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label><i class="fas fa-money-bill-wave"></i> Amount (৳)</label>
                        <input type="number" name="amount" class="form-control" placeholder="0" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label><i class="fas fa-calendar-day"></i> Payment Date</label>
                        <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save"></i> Save Payment
            </button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                <i class="fas fa-times"></i> Cancel
            </button>
        </div>
      </div>
    </form>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
const ctx = document.getElementById('earningsChart').getContext('2d');
new Chart(ctx, {
    type:'bar',
    data:{
        labels: <?= json_encode(array_column($earningsData,'month')) ?>,
        datasets:[{
            label:'Earnings (৳)', 
            data: <?= json_encode(array_column($earningsData,'total')) ?>, 
            backgroundColor:'rgba(40, 167, 69, 0.7)',
            borderColor:'rgba(40, 167, 69, 1)',
            borderWidth: 2
        }]
    },
    options:{
        responsive:true, 
        scales:{
            y:{
                beginAtZero:true,
                ticks: {
                    callback: function(value) {
                        return value + '৳';
                    }
                }
            }
        }
    }
});

// Auto-fill amount when package is selected
$('select[name="package_id"]').change(function() {
    var price = $(this).find('option:selected').data('price');
    $('input[name="amount"]').val(price);
});

// Close modal after successful submission
<?php if ($success): ?>
    $('#addPaymentModal').modal('hide');
<?php endif; ?>
</script>
</body>
</html>