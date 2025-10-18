<?php
// pages/register.php
session_start();
include('../includes/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    // Check if email already exists
    $check = mysqli_query($conn, "SELECT id FROM users WHERE email='$email'");
    if (mysqli_num_rows($check) > 0) {
        $error = "Email already exists!";
    } else {
        // Default password 123456
        $default_password = password_hash('123456', PASSWORD_DEFAULT);
        
        $insert = mysqli_query($conn, "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$default_password', 'user')");
        
        if ($insert) {
            $success = "Member registered successfully! Default password: 123456";
        } else {
            $error = "Registration failed!";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register - FitLife</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4>Register New Member</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="alert alert-info">
                            <strong>Note:</strong> Default password will be set to: <strong>123456</strong>
                        </div>
                        <button type="submit" class="btn btn-primary">Register Member</button>
                        <a href="manage_members.php" class="btn btn-secondary">Back to Members</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>