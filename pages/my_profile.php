<?php
session_start();
include('../includes/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_query = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
$user = mysqli_fetch_assoc($user_query);

$success = $error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    
    // Check if email already exists for other users
    $email_check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email' AND id != $user_id");
    if (mysqli_num_rows($email_check) > 0) {
        $error = "Email already exists! Please use a different email.";
    } else {
        $update_query = "UPDATE users SET name='$name', email='$email', phone='$phone', address='$address' WHERE id=$user_id";
        
        if (mysqli_query($conn, $update_query)) {
            $_SESSION['name'] = $name;
            $success = "Profile updated successfully!";
            // Refresh user data
            $user_query = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
            $user = mysqli_fetch_assoc($user_query);
        } else {
            $error = "Error updating profile: " . mysqli_error($conn);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (password_verify($current_password, $user['password'])) {
        if ($new_password === $confirm_password) {
            if (strlen($new_password) >= 6) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                mysqli_query($conn, "UPDATE users SET password='$hashed_password' WHERE id=$user_id");
                $success = "Password changed successfully!";
            } else {
                $error = "Password must be at least 6 characters long";
            }
        } else {
            $error = "New passwords do not match";
        }
    } else {
        $error = "Current password is incorrect";
    }
}

// Logout functionality
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - FitLife</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #5a67d8;
            --secondary: #764ba2;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --light: #f8fafc;
            --dark: #1e293b;
            --gray: #64748b;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            color: var(--dark);
        }
        
        .profile-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        /* Header Actions */
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: white;
            color: var(--primary);
            text-decoration: none;
            border-radius: 0.5rem;
            font-weight: 600;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        }

        .back-button:hover {
            transform: translateX(-5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            color: var(--primary);
            text-decoration: none;
        }

        .logout-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: var(--danger);
            color: white;
            text-decoration: none;
            border-radius: 0.5rem;
            font-weight: 600;
            box-shadow: 0 2px 10px rgba(239, 68, 68, 0.3);
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }

        .logout-button:hover {
            background: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
            color: white;
            text-decoration: none;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }
        
        .page-header p {
            color: var(--gray);
            font-size: 1.1rem;
        }
        
        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
        }
        
        @media (max-width: 768px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }

            .header-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .back-button, .logout-button {
                text-align: center;
                justify-content: center;
            }
        }
        
        .profile-sidebar {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            text-align: center;
            height: fit-content;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: white;
            font-size: 2.5rem;
        }
        
        .profile-info h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }
        
        .profile-info p {
            color: var(--gray);
            margin-bottom: 0.25rem;
        }
        
        .member-since {
            background: var(--light);
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-top: 1rem;
            font-size: 0.875rem;
            color: var(--gray);
        }
        
        .profile-content {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        
        .profile-card {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--light);
        }
        
        .card-header i {
            color: var(--primary);
            font-size: 1.25rem;
        }
        
        .card-header h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark);
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* Action buttons container */
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <!-- Header Actions -->
        <div class="header-actions">
            <a href="dashboard.php" class="back-button">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
            
            <a href="?logout=true" class="logout-button" onclick="return confirm('Are you sure you want to logout?')">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h1>Profile Settings</h1>
            <p>Manage your account information and preferences</p>
        </div>

        <!-- Notifications -->
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= $success ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <div class="profile-grid">
            <!-- Sidebar -->
            <div class="profile-sidebar">
                <div class="profile-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="profile-info">
                    <h3><?= htmlspecialchars($user['name']) ?></h3>
                    <p><?= htmlspecialchars($user['email']) ?></p>
                    <p><?= htmlspecialchars($user['phone'] ?? 'Not provided') ?></p>
                    <div class="member-since">
                        <i class="fas fa-calendar"></i>
                        Member since <?= date('M Y', strtotime($user['created_at'])) ?>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="profile-content">
                <!-- Personal Information -->
                <div class="profile-card">
                    <div class="card-header">
                        <i class="fas fa-user-edit"></i>
                        <h3>Personal Information</h3>
                    </div>
                    <form method="POST">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input type="text" id="name" name="name" class="form-control" 
                                       value="<?= htmlspecialchars($user['name']) ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" class="form-control" 
                                       value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" class="form-control" 
                                       value="<?= htmlspecialchars($user['phone'] ?? '') ?>" 
                                       placeholder="Enter your phone number">
                            </div>
                            
                            <div class="form-group">
                                <label for="address">Address</label>
                                <input type="text" id="address" name="address" class="form-control" 
                                       value="<?= htmlspecialchars($user['address'] ?? '') ?>" 
                                       placeholder="Enter your address">
                            </div>
                        </div>
                        
                        <div class="action-buttons">
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Profile
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Security Settings -->
                <div class="profile-card">
                    <div class="card-header">
                        <i class="fas fa-shield-alt"></i>
                        <h3>Security Settings</h3>
                    </div>
                    <form method="POST">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="current_password">Current Password</label>
                                <input type="password" id="current_password" name="current_password" 
                                       class="form-control" placeholder="Enter current password">
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password" 
                                       class="form-control" placeholder="Enter new password">
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" 
                                       class="form-control" placeholder="Confirm new password">
                            </div>
                        </div>
                        
                        <div class="action-buttons">
                            <button type="submit" name="change_password" class="btn btn-primary">
                                <i class="fas fa-key"></i> Change Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const profileForm = document.querySelector('form[method="POST"]');
            const passwordForm = document.querySelector('input[name="change_password"]');
            
            if (profileForm) {
                profileForm.addEventListener('submit', function(e) {
                    const name = document.getElementById('name').value.trim();
                    const email = document.getElementById('email').value.trim();
                    
                    if (!name || !email) {
                        e.preventDefault();
                        alert('Please fill in all required fields!');
                        return false;
                    }
                });
            }
            
            const changePasswordBtn = document.querySelector('button[name="change_password"]');
            if (changePasswordBtn) {
                changePasswordBtn.closest('form').addEventListener('submit', function(e) {
                    const currentPassword = document.getElementById('current_password').value;
                    const newPassword = document.getElementById('new_password').value;
                    const confirmPassword = document.getElementById('confirm_password').value;
                    
                    if (!currentPassword || !newPassword || !confirmPassword) {
                        e.preventDefault();
                        alert('Please fill in all password fields!');
                        return false;
                    }
                    
                    if (newPassword !== confirmPassword) {
                        e.preventDefault();
                        alert('New passwords do not match!');
                        return false;
                    }
                    
                    if (newPassword.length < 6) {
                        e.preventDefault();
                        alert('Password must be at least 6 characters long!');
                        return false;
                    }
                });
            }

            // Logout confirmation
            const logoutButton = document.querySelector('.logout-button');
            if (logoutButton) {
                logoutButton.addEventListener('click', function(e) {
                    if (!confirm('Are you sure you want to logout?')) {
                        e.preventDefault();
                    }
                });
            }
        });
    </script>
</body>
</html>