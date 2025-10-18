<?php
session_start();
include('../includes/db.php');

// শুধুমাত্র এডমিন অ্যাক্সেস চেক
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// ---------------- ডাইনামিক ডাটা ফেচ ----------------
// Total Members
$totalMembers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role='user'"))['count'];

// Active Packages
$activePackages = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM packages WHERE status='active'"))['count'] ?? 0;

// Monthly Earnings (current month)
$month = date('m');
$year = date('Y');
$monthlyEarnings = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT SUM(amount) as total FROM payments WHERE MONTH(payment_date)='$month' AND YEAR(payment_date)='$year'")
)['total'] ?? 0;

// Attendance Today
$today = date('Y-m-d');
$attendanceToday = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) as count FROM attendance WHERE date='$today'")
)['count'] ?? 0;

// Earnings Data for Chart (last 6 months)
$earningsData = [];
$query = "SELECT DATE_FORMAT(payment_date, '%b %Y') as month, SUM(amount) as total 
          FROM payments 
          GROUP BY YEAR(payment_date), MONTH(payment_date) 
          ORDER BY payment_date DESC LIMIT 6";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
    $earningsData[] = $row;
}
$earningsData = array_reverse($earningsData);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - FitLife Tracker</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { display: flex; background: #f4f6f9; }
        .sidebar {
            width: 250px; background: #343a40; color: white;
            height: 100vh; position: fixed; padding-top: 20px;
        }
        .sidebar h4 { text-align: center; margin-bottom: 20px; }
        .sidebar a {
            color: white; display: block; padding: 12px; text-decoration: none;
        }
        .sidebar a:hover { background: #495057; }
        .content { margin-left: 250px; padding: 20px; width: 100%; }
        .card { border-radius: 10px; }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <h4>FitLife Admin</h4>
    <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="manage_members.php"><i class="fas fa-users"></i> Members</a>
    <a href="manage_packages.php"><i class="fas fa-box"></i> Packages</a>
    <a href="manage_payments.php"><i class="fas fa-dollar-sign"></i> Earnings</a>
    <a href="manage_attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a>
    <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<!-- Content -->
<div class="content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Admin Dashboard</h2>
        <span>Welcome, <?= $_SESSION['name']; ?></span>
    </div>

    <!-- Stats Cards -->
    <div class="row">
        <div class="col-md-3">
            <div class="card text-white bg-primary mb-3">
                <div class="card-body">
                    <h5>Total Members</h5>
                    <h3><?= $totalMembers; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success mb-3">
                <div class="card-body">
                    <h5>Active Packages</h5>
                    <h3><?= $activePackages; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning mb-3">
                <div class="card-body">
                    <h5>Monthly Earnings</h5>
                    <h3>$<?= number_format($monthlyEarnings, 2); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-danger mb-3">
                <div class="card-body">
                    <h5>Attendance Today</h5>
                    <h3><?= $attendanceToday; ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Earnings Chart -->
    <div class="card mt-4">
        <div class="card-body">
            <h5>Monthly Earnings (Last 6 Months)</h5>
            <canvas id="earningsChart"></canvas>
        </div>
    </div>
</div>

<script>
    const ctx = document.getElementById('earningsChart').getContext('2d');
    const earningsChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($earningsData, 'month')); ?>,
            datasets: [{
                label: 'Earnings ($)',
                data: <?= json_encode(array_column($earningsData, 'total')); ?>,
                borderColor: 'rgba(75, 192, 192, 1)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true } }
        }
    });
</script>

</body>
</html>
