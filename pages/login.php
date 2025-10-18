<?php
include('../includes/db.php');
session_start();

// If already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: dashboard.php");
    }
    exit;
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = $_POST['email'];
    $password = $_POST['password'];

    $sql  = "SELECT * FROM users WHERE email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($user = mysqli_fetch_assoc($result)) {
        $stored = $user['password'];

        // 1) Preferred: verify hashed password
        if (password_verify($password, $stored)) {
            // OK — hashed password matches
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['role']    = $user['role'];
            $_SESSION['password_changed'] = $user['password_changed'];
        }
        // 2) Fallback: maybe DB has plain-text password (legacy). Compare plain.
        elseif ($stored === $password) {
            // Plain match — re-hash and update DB (so future logins use hash)
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $upd = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
            mysqli_stmt_bind_param($upd, "si", $newHash, $user['id']);
            mysqli_stmt_execute($upd);
            mysqli_stmt_close($upd);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['role']    = $user['role'];
            $_SESSION['password_changed'] = $user['password_changed'];
        }
        else {
            $error = "Incorrect password.";
        }

        // If session set, redirect by role and password status
        if (isset($_SESSION['user_id'])) {
            // Check if user needs to change password (for regular users only)
            if ($_SESSION['role'] === 'user' && !$user['password_changed']) {
                header("Location: change_password.php");
                exit;
            }
            
            if ($_SESSION['role'] === 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: dashboard.php");
            }
            exit;
        }
    } else {
        $error = "Email not found.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Login - FitLife Tracker</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    body { 
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    .login-container {
        max-width: 400px;
        width: 100%;
        margin: 20px;
    }
    .login-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        overflow: hidden;
    }
    .login-header {
        background: linear-gradient(135deg, #007bff, #0056b3);
        color: white;
        padding: 30px 20px;
        text-align: center;
    }
    .login-body {
        padding: 30px;
    }
    .form-control {
        border-radius: 8px;
        padding: 12px 15px;
        border: 2px solid #e9ecef;
        transition: all 0.3s;
    }
    .form-control:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
    }
    .btn-login {
        background: linear-gradient(135deg, #28a745, #20c997);
        border: none;
        border-radius: 8px;
        padding: 12px;
        font-weight: 600;
        transition: all 0.3s;
    }
    .btn-login:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
    }
    .info-box {
        background: #e7f3ff;
        border-left: 4px solid #007bff;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    .brand-text {
        font-weight: 700;
        font-size: 1.5rem;
    }
  </style>
</head>
<body>
<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <h2><i class="fas fa-dumbbell"></i> <span class="brand-text">FitLife</span></h2>
            <p class="mb-0">Your Fitness Journey Starts Here</p>
        </div>
        
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label class="font-weight-bold"><i class="fas fa-envelope text-primary"></i> Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
                </div>
                
                <div class="form-group">
                    <label class="font-weight-bold"><i class="fas fa-lock text-primary"></i> Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
                </div>

                <button type="submit" class="btn btn-login btn-block text-white">
                    <i class="fas fa-sign-in-alt"></i> Login to Your Account
                </button>
            </form>

        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>