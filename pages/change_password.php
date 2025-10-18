<?php
// pages/change_password.php
session_start();
include('../includes/db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// If password already changed, redirect to dashboard
if (isset($_SESSION['password_changed']) && $_SESSION['password_changed']) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: dashboard.php");
    }
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $user_id = $_SESSION['user_id'];
    
    // Get user data
    $result = mysqli_query($conn, "SELECT password FROM users WHERE id=$user_id");
    $user = mysqli_fetch_assoc($result);
    
    // Verify current password
    if (!password_verify($current_password, $user['password'])) {
        $error = "Current password is incorrect!";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match!";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters long!";
    } else {
        // Hash new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update password and set password_changed to true
        $update = mysqli_query($conn, "UPDATE users SET password='$hashed_password', password_changed=TRUE WHERE id=$user_id");
        
        if ($update) {
            $_SESSION['password_changed'] = true;
            $success = "Password changed successfully!";
            
            // Redirect after 2 seconds
            header("refresh:2;url=dashboard.php");
        } else {
            $error = "Failed to change password!";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Change Password - FitLife</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; }
        .password-container { max-width: 500px; margin: 0 auto; }
        .card { border: none; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
    </style>
</head>
<body>
<div class="container">
    <div class="password-container">
        <div class="card">
            <div class="card-header bg-primary text-white text-center py-4">
                <h3><i class="fas fa-lock"></i> Change Your Password</h3>
                <p class="mb-0">Welcome, <?= htmlspecialchars($_SESSION['name']) ?>!</p>
            </div>
            <div class="card-body p-5">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= $success ?></div>
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Redirecting...</span>
                        </div>
                        <p class="mt-2">Redirecting to dashboard...</p>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Security Notice:</strong> Please change your default password for security reasons.
                    </div>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label><i class="fas fa-key"></i> Current Password</label>
                            <input type="password" name="current_password" class="form-control" placeholder="Enter default password (123456)" required>
                            <small class="form-text text-muted">Default password: <strong>123456</strong></small>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-lock"></i> New Password</label>
                            <input type="password" name="new_password" class="form-control" placeholder="Enter new password" minlength="6" required>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-lock"></i> Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" placeholder="Confirm new password" minlength="6" required>
                        </div>
                        
                        <button type="submit" class="btn btn-success btn-block btn-lg">
                            <i class="fas fa-save"></i> Change Password
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>