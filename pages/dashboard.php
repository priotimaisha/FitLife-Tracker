<?php
// pages/dashboard.php
session_start();
include('../includes/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}

// Check if password needs to be changed
if (isset($_SESSION['password_changed']) && !$_SESSION['password_changed']) {
    header("Location: change_password.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user details with package info
$user_query = mysqli_query($conn, "
    SELECT u.*, p.name as package_name, p.price, p.duration
    FROM users u 
    LEFT JOIN package p ON u.package_id = p.id 
    WHERE u.id = $user_id
");
$user = mysqli_fetch_assoc($user_query);

// Get latest payment info
$payment_query = mysqli_query($conn, "
    SELECT * FROM payments 
    WHERE user_id = $user_id 
    ORDER BY payment_date DESC 
    LIMIT 1
");
$latest_payment = mysqli_fetch_assoc($payment_query);

// Get attendance count for this month
$attendance_query = mysqli_query($conn, "
    SELECT COUNT(*) as attendance_count 
    FROM attendance 
    WHERE user_id = $user_id 

");
$attendance = mysqli_fetch_assoc($attendance_query);

// Get total visits
$total_attendance_query = mysqli_query($conn, "
    SELECT COUNT(*) as total_count 
    FROM attendance 
    WHERE user_id = $user_id
");
$total_attendance = mysqli_fetch_assoc($total_attendance_query);

// Count active packages (paid and not expired)
$active_packages_query = mysqli_query($conn, "
    SELECT COUNT(*) as active_count 
    FROM payments 
    WHERE user_id = $user_id 
    AND status = 'paid' 
    AND expiry_date > CURDATE()
");
$active_packages = mysqli_fetch_assoc($active_packages_query);

// Get today's workout schedule


// Get upcoming payments
$upcoming_payment = null;
if ($latest_payment && $latest_payment['expiry_date'] > date('Y-m-d')) {
    $days_left = (strtotime($latest_payment['expiry_date']) - strtotime(date('Y-m-d'))) / (60 * 60 * 24);
    if ($days_left <= 7) {
        $upcoming_payment = $latest_payment;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - FitLife</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
            --success: #28a745;
            --info: #17a2b8;
            --warning: #ffc107;
            --danger: #dc3545;
            --light: #f8f9fa;
            --dark: #343a40;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        
        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Header Styles */
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 20px;
            color: white;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }
        
        .dashboard-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.1);
            transform: rotate(30deg);
        }
        
        .user-avatar {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin-right: 20px;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
            border-left: 5px solid var(--primary);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .stat-card.attendance { border-left-color: var(--success); }
        .stat-card.package { border-left-color: var(--info); }
        .stat-card.days-left { border-left-color: var(--warning); }
        .stat-card.status { border-left-color: var(--danger); }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }
        
        .stat-icon.attendance { background: rgba(40, 167, 69, 0.1); color: var(--success); }
        .stat-icon.package { background: rgba(23, 162, 184, 0.1); color: var(--info); }
        .stat-icon.days-left { background: rgba(255, 193, 7, 0.1); color: var(--warning); }
        .stat-icon.status { background: rgba(220, 53, 69, 0.1); color: var(--danger); }
        
        /* Main Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        
        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .main-content, .sidebar-content {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }
        
        /* Card Styles */
        .dashboard-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 20px 25px;
            border: none;
        }
        
        .card-header h5 {
            margin: 0;
            font-weight: 600;
        }
        
        .card-body {
            padding: 25px;
        }
        
        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
        }
        
        .action-btn {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 20px 15px;
            text-align: center;
            transition: all 0.3s;
            text-decoration: none;
            color: var(--dark);
        }
        
        .action-btn:hover {
            border-color: var(--primary);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
            text-decoration: none;
            color: var(--dark);
        }
        
        .action-icon {
            font-size: 24px;
            margin-bottom: 10px;
            display: block;
        }
        
        /* Progress Chart */
        .chart-container {
            position: relative;
            height: 200px;
            width: 100%;
        }
        
        /* Alert Styles */
        .alert-card {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border: 2px solid #ffc107;
            border-radius: 15px;
            padding: 20px;
            display: flex;
            align-items: center;
        }
        
        .alert-icon {
            font-size: 24px;
            color: #856404;
            margin-right: 15px;
        }
        
        /* Package Badge */
        .package-badge {
            background: linear-gradient(135deg, var(--info) 0%, #138496 100%);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }

        /* Quick Stats Row */
        .quick-stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            text-align: center;
        }

        .quick-stat-item {
            padding: 15px;
        }

        .quick-stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .quick-stat-label {
            font-size: 0.875rem;
            color: #6c757d;
        }

        @media (max-width: 768px) {
            .quick-stats-row {
                grid-template-columns: 1fr;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">Welcome back, <?= htmlspecialchars($_SESSION['name']) ?>! 👋</h1>
                    <p class="mb-0 opacity-75">Here's your fitness overview for today</p>
                </div>
                <div class="col-md-4 text-right">
                    <div class="d-flex align-items-center justify-content-end">
                        <div class="user-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div>
                            <h5 class="mb-1"><?= htmlspecialchars($_SESSION['name']) ?></h5>
                            <small class="opacity-75">FitLife Member</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card attendance">
                <div class="stat-icon attendance">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <h3><?= $attendance['attendance_count'] ?? 0 ?></h3>
                <p class="text-muted mb-0">This Month's Visits</p>
                <small class="text-success">Total: <?= $total_attendance['total_count'] ?? 0 ?> visits</small>
            </div>
            
            <div class="stat-card package">
                <div class="stat-icon package">
                    <i class="fas fa-dumbbell"></i>
                </div>
                <h3><?= $active_packages['active_count'] ?? 0 ?></h3>
                <p class="text-muted mb-0">My Packages</p>
                <?php if ($user['package_name']): ?>
                    <small class="text-info"><?= htmlspecialchars($user['package_name']) ?></small>
                <?php else: ?>
                   
                <?php endif; ?>
            </div>
            
            <div class="stat-card days-left">
                <div class="stat-icon days-left">
                    <i class="fas fa-clock"></i>
                </div>
                <h3>
                    <?php 
                    if ($latest_payment && $latest_payment['expiry_date'] > date('Y-m-d')) {
                        $days_left = (strtotime($latest_payment['expiry_date']) - strtotime(date('Y-m-d'))) / (60 * 60 * 24);
                        echo (int)$days_left; // Convert to integer
                    } else {
                        echo '0';
                    }
                    ?>
                </h3>
                <p class="text-muted mb-0">Days Remaining</p>
                <small class="text-warning">
                    <?= $latest_payment ? date('M j, Y', strtotime($latest_payment['expiry_date'])) : 'No active package' ?>
                </small>
            </div>
            
            <div class="stat-card status">
                <div class="stat-icon status">
                    <i class="fas fa-fire"></i>
                </div>
                <h3>
                    <?php 
                    if ($latest_payment && $latest_payment['expiry_date'] > date('Y-m-d')) {
                        echo 'Active';
                    } else {
                        echo 'Inactive';
                    }
                    ?>
                </h3>
                <p class="text-muted mb-0">Membership Status</p>
                <small class="text-danger">
                    <?= $latest_payment && $latest_payment['expiry_date'] > date('Y-m-d') ? 'Valid' : 'Renew required' ?>
                </small>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="content-grid">
            <!-- Left Column - Main Content -->
            <div class="main-content">
                <!-- Quick Actions -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h5><i class="fas fa-bolt"></i> Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="quick-actions">
                            <a href="attendance.php" class="action-btn">
                                <i class="fas fa-calendar-check action-icon text-primary"></i>
                                <span>Mark Attendance</span>
                            </a>
                            <a href="my_payments.php" class="action-btn">
                                <i class="fas fa-credit-card action-icon text-success"></i>
                                <span>Make Payment</span>
                            </a>
                           
                            <a href="my_profile.php" class="action-btn">
                                <i class="fas fa-user-edit action-icon text-info"></i>
                                <span>Update Profile</span>
                            </a>
                          
                            <a href="packages.php" class="action-btn">
                                <i class="fas fa-box-open action-icon text-danger"></i>
                                <span>Browse Packages</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- My Current Package -->
               
            </div>

            <!-- Right Column - Sidebar -->
            <div class="sidebar-content">
                <!-- Quick Stats -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h5><i class="fas fa-tachometer-alt"></i> Quick Stats</h5>
                    </div>
                    <div class="card-body">
                        <div class="quick-stats-row">
                            <div class="quick-stat-item">
                                <div class="quick-stat-value text-primary"><?= $total_attendance['total_count'] ?? 0 ?></div>
                                <div class="quick-stat-label">Total Visits</div>
                            </div>
                            <div class="quick-stat-item">
                                <div class="quick-stat-value text-success"><?= $attendance['attendance_count'] ?? 0 ?></div>
                                <div class="quick-stat-label">This Month</div>
                            </div>
                            <div class="quick-stat-item">
                                <div class="quick-stat-value text-warning"><?= $active_packages['active_count'] ?? 0 ?></div>
                                <div class="quick-stat-label">Active Packages</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Progress Chart
        const ctx = document.getElementById('progressChart').getContext('2d');
        const progressChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Completed', 'Remaining'],
                datasets: [{
                    data: [<?= $attendance['attendance_count'] ?? 0 ?>, 20 - (<?= $attendance['attendance_count'] ?? 0 ?>)],
                    backgroundColor: ['#28a745', '#e9ecef'],
                    borderWidth: 0
                }]
            },
            options: {
                cutout: '70%',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        enabled: false
                    }
                }
            }
        });

        // Add some animations
        document.addEventListener('DOMContentLoaded', function() {
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
                card.classList.add('animate__animated', 'animate__fadeInUp');
            });
        });
    </script>
</body>
</html>