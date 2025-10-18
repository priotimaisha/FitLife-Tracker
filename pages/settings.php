<?php
// pages/settings.php
session_start();
include('../includes/db.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$adminId = $_SESSION['user_id'] ?? null;
$msg = '';

// fetch admin data
$admin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, name, email FROM users WHERE id=$adminId AND role='admin' LIMIT 1"));
if (!$admin) { echo "Admin not found."; exit; }

// handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;

    if ($password) {
        mysqli_query($conn, "UPDATE users SET name='$name', email='$email', password='$password' WHERE id=$adminId AND role='admin'");
    } else {
        mysqli_query($conn, "UPDATE users SET name='$name', email='$email' WHERE id=$adminId AND role='admin'");
    }
    $_SESSION['name'] = $name;
    $msg = "Settings updated successfully!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Settings - FitLife</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body{ display:flex; background:#f8f9fa; font-family:'Roboto',sans-serif; min-height:100vh; }
        .sidebar{
            width:250px; background:#343a40; color:#fff; position:fixed; height:100vh; padding-top:20px;
        }
        .sidebar h4{ text-align:center; margin-bottom:20px; font-weight:700; }
        .sidebar a{
            color:#adb5bd; display:block; padding:12px 20px; text-decoration:none; margin-bottom:2px; border-left:4px solid transparent;
            transition:0.3s;
        }
        .sidebar a:hover{ background:#495057; border-left:4px solid #007bff; color:#fff; }
        .sidebar a.active{ background:#007bff; border-left:4px solid #fff; color:#fff; }
        .content{ margin-left:250px; padding:30px; width:100%; }
        .card{ box-shadow:0 6px 20px rgba(0,0,0,0.08); border-radius:12px; border:none; margin-bottom:20px; background:white; }
        .card-header{ background:linear-gradient(45deg,#007bff,#0056b3); color:white; border-radius:12px 12px 0 0; }
        .btn-gradient{ background:linear-gradient(45deg,#28a745,#1e7e34); color:white; border:none; border-radius:8px; padding:10px 20px; }
        .btn-gradient:hover{ opacity:0.9; }
        .form-control{ border-radius:8px; border:1px solid #ddd; }
        .form-control:focus{ border-color:#007bff; box-shadow:0 0 0 0.2rem rgba(0,123,255,0.25); }
        .alert{ max-width:600px; margin:20px auto; }
        h2{text-align:center; margin-bottom:30px; font-weight:500; color:#343a40;}
    </style>
</head>
<body>

<div class="sidebar">
    <h4 class="text-center"><i class="fas fa-dumbbell"></i> FitLife Admin</h4>
    <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="manage_members.php"><i class="fas fa-users"></i> Members</a>
    <a href="manage_packages.php"><i class="fas fa-box-open"></i> Packages</a>
    <a href="manage_payments.php"><i class="fas fa-credit-card"></i> Payments</a>
    <a href="manage_attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a>
    <a href="settings.php" class="active"><i class="fas fa-cog"></i> Settings</a>
    <a href="logout.php" class="text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="content">
    <h2><i class="fas fa-user-cog text-primary"></i> Admin Settings</h2>

    <?php if ($msg): ?>
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center">
            <i class="fas fa-check-circle me-2"></i>
            <?= htmlspecialchars($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-user-edit"></i> Update Profile</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label"><i class="fas fa-user"></i> Name</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($admin['name']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label"><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($admin['email']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label"><i class="fas fa-lock"></i> New Password <small class="text-muted">(leave blank to keep current)</small></label>
                    <input type="password" name="password" class="form-control">
                </div>
                <button type="submit" class="btn btn-gradient w-100"><i class="fas fa-save"></i> Update Settings</button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
