<?php
// pages/manage_attendance.php
session_start();
include('../includes/db.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$msg = '';

// Save attendance
if (isset($_POST['save_attendance'])) {
    $date = mysqli_real_escape_string($conn, $_POST['attendance_date']);
    $statuses = $_POST['status'] ?? [];
    foreach ($statuses as $user_id => $status) {
        $uid = intval($user_id);
        $st = ($status === 'present') ? 'present' : 'absent';
        mysqli_query($conn, "DELETE FROM attendance WHERE user_id=$uid AND date='$date'");
        mysqli_query($conn, "INSERT INTO attendance (user_id, date, status) VALUES ($uid, '$date', '$st')");
    }
    $msg = "Attendance saved successfully!";
}

// Delete
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    mysqli_query($conn, "DELETE FROM attendance WHERE id=$id");
    header("Location: manage_attendance.php");
    exit;
}

// Fetch members and records
$members = mysqli_query($conn, "SELECT id, name FROM users WHERE role='user' ORDER BY name ASC");
$records = mysqli_query($conn, "SELECT a.*, u.name FROM attendance a LEFT JOIN users u ON a.user_id=u.id ORDER BY a.date DESC, u.name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Attendance - FitLife</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .sidebar{
            width:250px; background:#343a40; color:#fff; position:fixed; height:100vh; padding-top:20px;
        }
        .sidebar a{
            color:#adb5bd; display:block; padding:10px 20px; text-decoration:none; margin-bottom:5px; border-radius:5px;
        }
        .sidebar a:hover{ background:#495057; color:#fff; }
        .sidebar a.active{ background:#007bff; color:#fff; }
        .content{ margin-left:260px; padding:30px; }
        .card{ box-shadow:0 4px 12px rgba(0,0,0,0.05); border-radius:12px; border:none; margin-bottom:20px; }
        .btn-primary{ background:linear-gradient(45deg, #007bff, #0056b3); border:none; border-radius:8px; }
        .btn-danger{ background:linear-gradient(45deg, #dc3545, #c82333); border:none; border-radius:8px; }
        .table-responsive{ max-height:600px; overflow-y:auto; }
        .badge-present{ background:#28a745; }
        .badge-absent{ background:#dc3545; }
        .form-control{ border-radius:8px; border:1px solid #ddd; }
        .form-control:focus{ border-color:#007bff; box-shadow:0 0 0 0.2rem rgba(0,123,255,0.25); }
        .modal-header{ background:linear-gradient(45deg, #007bff, #0056b3); color:white; border-radius:10px 10px 0 0; }
        .btn-add{ background:linear-gradient(45deg,#17a2b8,#138496); border:none; border-radius:8px; padding:10px 20px; color:#fff; }
    </style>
</head>
<body>

<div class="sidebar">
    <h4 class="text-center mb-4"><i class="fas fa-dumbbell"></i> FitLife Admin</h4>
    <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="manage_members.php"><i class="fas fa-users"></i> Members</a>
    <a href="manage_packages.php"><i class="fas fa-box-open"></i> Packages</a>
    <a href="manage_payments.php"><i class="fas fa-credit-card"></i> Payments</a>
    <a href="manage_attendance.php" class="active"><i class="fas fa-calendar-check"></i> Attendance</a>
    <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
    <a href="logout.php" class="text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-calendar-check text-primary"></i> Manage Attendance</h2>
        <button class="btn btn-add" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus-circle"></i> Mark Attendance</button>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center">
            <i class="fas fa-check-circle me-2"></i>
            <?= htmlspecialchars($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Attendance Records -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-list"></i> Attendance Records</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Date</th>
                            <th>Member</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($r = mysqli_fetch_assoc($records)): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['date']) ?></td>
                            <td><?= htmlspecialchars($r['name'] ?? 'N/A') ?></td>
                            <td>
                                <span class="badge <?= $r['status']=='present'?'badge-present':'badge-absent' ?>">
                                    <?= ucfirst($r['status']) ?>
                                </span>
                            </td>
                            <td>
                                <a href="manage_attendance.php?delete_id=<?= $r['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this record?')">
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

<!-- Add Attendance Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form method="POST">
      <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Mark Attendance</h5>
            <button type="button" class="btn-close text-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <div class="mb-3">
                <label for="attendance_date" class="form-label">Date</label>
                <input type="date" id="attendance_date" name="attendance_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($m = mysqli_fetch_assoc($members)): ?>
                            <tr>
                                <td><?= htmlspecialchars($m['name']) ?></td>
                                <td>
                                    <select name="status[<?= $m['id'] ?>]" class="form-select">
                                        <option value="present">Present</option>
                                        <option value="absent">Absent</option>
                                    </select>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="modal-footer">
            <button type="submit" name="save_attendance" class="btn btn-primary"><i class="fas fa-save"></i> Save Attendance</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times"></i> Cancel</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
