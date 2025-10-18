<?php
// pages/manage_packages.php
session_start();
include('../includes/db.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$error = $success = '';

// Add Package
if (isset($_POST['add_package'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $status = in_array($_POST['status'], ['active','inactive']) ? $_POST['status'] : 'active';
    $price = intval($_POST['price']); // Changed to integer
    $duration = intval($_POST['duration']);
    
    if ($name && $price >= 0) {
        mysqli_query($conn, "INSERT INTO package (name, description, price, duration, status) VALUES ('$name','$description',$price,$duration,'$status')");
        $success = "Package added successfully!";
    } else {
        $error = "Please fill all required fields correctly.";
    }
}

// Update Package
if (isset($_POST['update_package'])) {
    $id = intval($_POST['id']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $status = in_array($_POST['status'], ['active','inactive']) ? $_POST['status'] : 'active';
    $price = intval($_POST['price']); // Changed to integer
    $duration = intval($_POST['duration']);
    
    mysqli_query($conn, "UPDATE package SET name='$name', description='$description', price=$price, duration=$duration, status='$status' WHERE id=$id");
    $success = "Package updated successfully!";
}

// Delete Package
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    mysqli_query($conn, "DELETE FROM package WHERE id=$id");
    $success = "Package deleted successfully!";
    header("Location: manage_packages.php");
    exit;
}

$result = mysqli_query($conn, "SELECT * FROM package ORDER BY id DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Manage Packages - FitLife</title>
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
        .btn-primary{background:linear-gradient(45deg, #007bff, #0056b3);border:none;border-radius:8px}
        .btn-success{background:linear-gradient(45deg, #28a745, #1e7e34);border:none;border-radius:8px}
        .btn-danger{background:linear-gradient(45deg, #dc3545, #c82333);border:none;border-radius:8px}
        .btn-add{background:linear-gradient(45deg, #17a2b8, #138496);border:none;border-radius:8px;padding:10px 20px}
        .action-btn{width:85px;margin:2px;font-size:12px}
        .table-responsive{max-height:600px;overflow-y:auto}
        .status-active{background:#d4edda;color:#155724;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600}
        .status-inactive{background:#f8d7da;color:#721c24;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600}
        .price-tag{background:#28a745;color:white;padding:6px 15px;border-radius:20px;font-weight:600;font-size:14px}
        .duration-badge{background:#6c757d;color:white;padding:4px 10px;border-radius:15px;font-size:12px}
        .modal-header{background:linear-gradient(45deg, #007bff, #0056b3);color:white;border-radius:10px 10px 0 0}
        .form-control{border-radius:8px;border:1px solid #ddd}
        .form-control:focus{border-color:#007bff;box-shadow:0 0 0 0.2rem rgba(0,123,255,0.25)}
        .taka-sign{font-family:Arial, sans-serif;font-weight:bold}
    </style>
</head>
<body>
<div class="sidebar">
    <h4 class="text-center mb-4"><i class="fas fa-dumbbell"></i> FitLife Admin</h4>
    <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="manage_members.php"><i class="fas fa-users"></i> Members</a>
    <a href="manage_packages.php" class="active"><i class="fas fa-box-open"></i> Packages</a>
    <a href="manage_payments.php"><i class="fas fa-credit-card"></i> Payments</a>
    <a href="manage_attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a>
    <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
    <a href="logout.php" class="text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-box-open text-primary"></i> Manage Packages</h2>
        <button class="btn btn-add text-white" data-toggle="modal" data-target="#addModal">
            <i class="fas fa-plus-circle"></i> Add New Package
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
            <h5 class="mb-0"><i class="fas fa-list"></i> Packages List</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th><i class="fas fa-hashtag"></i> ID</th>
                            <th><i class="fas fa-tag"></i> Package Name</th>
                            <th><i class="fas fa-info-circle"></i> Description</th>
                            <th><i class="fas fa-money-bill-wave"></i> Price</th>
                            <th><i class="fas fa-calendar-day"></i> Duration</th>
                            <th><i class="fas fa-circle"></i> Status</th>
                            <th><i class="fas fa-cogs"></i> Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($p = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><strong>#<?= $p['id'] ?></strong></td>
                            <td>
                                <div class="font-weight-bold text-primary"><?= htmlspecialchars($p['name']) ?></div>
                            </td>
                            <td>
                                <small class="text-muted"><?= htmlspecialchars($p['description'] ?? 'No description') ?></small>
                            </td>
                            <td>
                                <span class="price-tag">
                                    <i class="fas fa-money-bill-wave"></i> 
                                    <?= number_format($p['price'], 0) ?>৳
                                </span>
                            </td>
                            <td>
                                <span class="duration-badge"><?= $p['duration'] ?> days</span>
                            </td>
                            <td>
                                <span class="<?= $p['status'] == 'active' ? 'status-active' : 'status-inactive' ?>">
                                    <i class="fas fa-<?= $p['status'] == 'active' ? 'check' : 'times' ?>"></i>
                                    <?= ucfirst($p['status']) ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary action-btn" data-toggle="modal" data-target="#editModal<?= $p['id'] ?>">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <a href="manage_packages.php?delete_id=<?= $p['id'] ?>" class="btn btn-sm btn-danger action-btn" onclick="return confirm('Are you sure you want to delete this package?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>

                        <!-- Edit Modal -->
                        <div class="modal fade" id="editModal<?= $p['id'] ?>" tabindex="-1" role="dialog">
                          <div class="modal-dialog modal-lg" role="document">
                            <form method="POST">
                              <input type="hidden" name="id" value="<?= $p['id'] ?>">
                              <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Package</h5>
                                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label><i class="fas fa-tag"></i> Package Name</label>
                                                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($p['name']) ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label><i class="fas fa-money-bill-wave"></i> Price (৳)</label>
                                                <input type="number" name="price" class="form-control" value="<?= $p['price'] ?>" min="0" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label><i class="fas fa-calendar-day"></i> Duration (days)</label>
                                                <input type="number" name="duration" class="form-control" value="<?= $p['duration'] ?>" min="1" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label><i class="fas fa-circle"></i> Status</label>
                                                <select name="status" class="form-control">
                                                  <option value="active" <?= $p['status']=='active'?'selected':'' ?>>Active</option>
                                                  <option value="inactive" <?= $p['status']=='inactive'?'selected':'' ?>>Inactive</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label><i class="fas fa-info-circle"></i> Description</label>
                                        <textarea name="description" class="form-control" rows="3" placeholder="Package description..."><?= htmlspecialchars($p['description'] ?? '') ?></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" name="update_package" class="btn btn-success">
                                        <i class="fas fa-save"></i> Update Package
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

<!-- Add Package Modal -->
<div class="modal fade" id="addModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <form method="POST">
      <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Add New Package</h5>
            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Package Name</label>
                        <input type="text" name="name" class="form-control" placeholder="Enter package name" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label><i class="fas fa-money-bill-wave"></i> Price (৳)</label>
                        <input type="number" name="price" class="form-control" placeholder="0" min="0" required>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label><i class="fas fa-calendar-day"></i> Duration (days)</label>
                        <input type="number" name="duration" class="form-control" placeholder="30" min="1" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label><i class="fas fa-circle"></i> Status</label>
                        <select name="status" class="form-control">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label><i class="fas fa-info-circle"></i> Description</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Enter package description..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button type="submit" name="add_package" class="btn btn-success">
                <i class="fas fa-plus"></i> Add Package
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