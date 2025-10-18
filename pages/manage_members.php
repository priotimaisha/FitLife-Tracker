<?php
// pages/manage_members.php
session_start();
include('../includes/db.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';

// Add New Member
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_member'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = password_hash('123456', PASSWORD_DEFAULT); // Default password

    // Check unique email
    $chk = mysqli_query($conn, "SELECT id FROM users WHERE email='$email'");
    if (mysqli_num_rows($chk) == 0) {
        mysqli_query($conn, "INSERT INTO users (name, email, password, role, created_at) VALUES ('$name', '$email', '$password', 'user', NOW())");
        $success = "New member added successfully! Default password: 123456";
    } else {
        $error = "Email already exists!";
    }
}

// Delete Member
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    mysqli_query($conn, "DELETE FROM users WHERE id=$delete_id AND role='user'");
    $success = "Member deleted successfully!";
    header("Location: manage_members.php");
    exit;
}

// Update Member
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_member'])) {
    $id = intval($_POST['id']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    // Check unique email
    $chk = mysqli_query($conn, "SELECT id FROM users WHERE email='$email' AND id != $id");
    if (mysqli_num_rows($chk) == 0) {
        mysqli_query($conn, "UPDATE users SET name='$name', email='$email' WHERE id=$id AND role='user'");
        $success = "Member updated successfully!";
    } else {
        $error = "Email already used by another user!";
    }
}

// Reset Password
if (isset($_GET['reset_password'])) {
    $user_id = intval($_GET['reset_password']);
    $new_password = password_hash('123456', PASSWORD_DEFAULT);
    mysqli_query($conn, "UPDATE users SET password='$new_password', password_changed=FALSE WHERE id=$user_id");
    $success = "Password reset successfully! New password: 123456";
    header("Location: manage_members.php");
    exit;
}

// Fetch members with password status
$result = mysqli_query($conn, "SELECT id, name, email, password_changed, created_at FROM users WHERE role='user' ORDER BY id DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Manage Members - FitLife</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body{display:flex;background:#f4f6f9;min-height:100vh}
        .sidebar{width:250px;background:#343a40;color:#fff;position:fixed;height:100vh;padding-top:20px}
        .sidebar a{color:#fff;display:block;padding:12px 20px;text-decoration:none;transition:0.3s;border-left:4px solid transparent}
        .sidebar a:hover{background:#495057;border-left:4px solid #007bff}
        .sidebar a.active{background:#007bff;border-left:4px solid #fff}
        .content{margin-left:250px;padding:30px;width:100%}
        .card{box-shadow:0 4px 12px rgba(0,0,0,0.1);border-radius:12px;border:none;margin-bottom:20px}
        .btn-add{background:linear-gradient(45deg, #28a745, #20c997);border:none;border-radius:8px;padding:10px 20px}
        .action-btn{width:85px;margin:2px;font-size:12px}
        .btn-reset{background:linear-gradient(45deg, #ffc107, #e0a800);border:none;border-radius:8px;color:white;font-size:12px;width:85px;margin:2px}
        .table-responsive{max-height:600px;overflow-y:auto}
        .password-status-changed{background:#d4edda;color:#155724;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600}
        .password-status-default{background:#f8d7da;color:#721c24;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600}
    </style>
</head>
<body>
<div class="sidebar">
    <h4 class="text-center mb-4"><i class="fas fa-dumbbell"></i> FitLife Admin</h4>
    <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="manage_members.php" class="active"><i class="fas fa-users"></i> Members</a>
    <a href="manage_packages.php"><i class="fas fa-box-open"></i> Packages</a>
    <a href="manage_payments.php"><i class="fas fa-credit-card"></i> Payments</a>
    <a href="manage_attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a>
    <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
    <a href="logout.php" class="text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-users text-primary"></i> Manage Members</h2>
        <button class="btn btn-add text-white" data-toggle="modal" data-target="#addModal">
            <i class="fas fa-plus-circle"></i> Add New Member
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

    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-list"></i> Members List</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th><i class="fas fa-hashtag"></i> ID</th>
                            <th><i class="fas fa-user"></i> Name</th>
                            <th><i class="fas fa-envelope"></i> Email</th>
                            <th><i class="fas fa-lock"></i> Password Status</th>
                            <th><i class="fas fa-calendar-plus"></i> Joined</th>
                            <th><i class="fas fa-cogs"></i> Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($u = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><strong>#<?= $u['id'] ?></strong></td>
                            <td>
                                <div class="font-weight-bold text-primary"><?= htmlspecialchars($u['name']) ?></div>
                            </td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td>
                                <span class="<?= $u['password_changed'] ? 'password-status-changed' : 'password-status-default' ?>">
                                    <i class="fas fa-<?= $u['password_changed'] ? 'check' : 'exclamation-triangle' ?>"></i>
                                    <?= $u['password_changed'] ? 'Changed' : 'Default' ?>
                                </span>
                            </td>
                            <td><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary action-btn" data-toggle="modal" data-target="#editModal<?= $u['id'] ?>">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <a href="manage_members.php?reset_password=<?= $u['id'] ?>" class="btn btn-reset btn-sm" onclick="return confirm('Reset password to 123456?')">
                                    <i class="fas fa-key"></i> Reset PW
                                </a>
                                <a href="manage_members.php?delete_id=<?= $u['id'] ?>" class="btn btn-sm btn-danger action-btn" onclick="return confirm('Are you sure you want to delete this member?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>

                        <!-- Edit Modal -->
                        <div class="modal fade" id="editModal<?= $u['id'] ?>" tabindex="-1" role="dialog">
                          <div class="modal-dialog" role="document">
                            <form method="POST" action="manage_members.php">
                              <input type="hidden" name="id" value="<?= $u['id'] ?>">
                              <div class="modal-content">
                                <div class="modal-header bg-primary text-white">
                                    <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Member</h5>
                                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                </div>
                                <div class="modal-body">
                                  <div class="form-group">
                                      <label><i class="fas fa-user"></i> Name</label>
                                      <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($u['name']) ?>" required>
                                  </div>
                                  <div class="form-group">
                                      <label><i class="fas fa-envelope"></i> Email</label>
                                      <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($u['email']) ?>" required>
                                  </div>
                                  <div class="alert alert-info">
                                      <i class="fas fa-info-circle"></i> 
                                      Password status: <strong><?= $u['password_changed'] ? 'Changed by user' : 'Default (123456)' ?></strong>
                                  </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" name="update_member" class="btn btn-success">
                                        <i class="fas fa-save"></i> Update
                                    </button>
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                </div>
                              </div>
                            </form>
                          </div>
                        </div>

                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Member Modal -->
<div class="modal fade" id="addModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <form method="POST" action="manage_members.php">
      <div class="modal-content">
        <div class="modal-header bg-success text-white">
            <h5 class="modal-title"><i class="fas fa-user-plus"></i> Add New Member</h5>
            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
              <label><i class="fas fa-user"></i> Full Name</label>
              <input type="text" name="name" class="form-control" placeholder="Enter member's full name" required>
          </div>
          <div class="form-group">
              <label><i class="fas fa-envelope"></i> Email Address</label>
              <input type="email" name="email" class="form-control" placeholder="Enter email address" required>
          </div>
          <div class="alert alert-info">
              <i class="fas fa-info-circle"></i> 
              <strong>Default password:</strong> 123456<br>
              <small>User will be forced to change password on first login</small>
          </div>
        </div>
        <div class="modal-footer">
            <button type="submit" name="add_member" class="btn btn-success">
                <i class="fas fa-plus"></i> Add Member
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
</body>
</html>