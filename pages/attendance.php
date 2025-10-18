<?php
session_start();
include('../includes/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success = $error = "";

// Get selected month from filter
$selected_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$current_month = date('Y-m');

// Check if attendance already marked for today
$today = date('Y-m-d');
$today_attendance_check = mysqli_query($conn, "
    SELECT * FROM attendance 
    WHERE user_id = $user_id 
    AND DATE(date) = '$today'
");
$already_marked = mysqli_num_rows($today_attendance_check) > 0;

// Mark attendance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_attendance'])) {
    if (!$already_marked) {
        $insert_query = "INSERT INTO attendance (user_id, date, created_at) VALUES ($user_id, '$today', NOW())";
        if (mysqli_query($conn, $insert_query)) {
            $success = "Attendance marked successfully for today!";
            $already_marked = true;
            // Refresh the page to show updated data
            header("Location: attendance.php");
            exit;
        } else {
            $error = "Error marking attendance: " . mysqli_error($conn);
        }
    } else {
        $error = "Attendance already marked for today!";
    }
}

// Get all months with attendance for dropdown
$months_query = mysqli_query($conn, "
    SELECT DISTINCT DATE_FORMAT(date, '%Y-%m') as month 
    FROM attendance 
    WHERE user_id = $user_id 
    ORDER BY month DESC
");

// Get present days count for the selected month
$present_days = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as present_count 
    FROM attendance 
    WHERE user_id = $user_id 
    AND DATE_FORMAT(date, '%Y-%m') = '$selected_month'
"))['present_count'];

// Current month name
$current_month_name = date('F Y', strtotime($selected_month));

// Get current user's attendance records for selected month (only present dates)
$user_attendance_query = mysqli_query($conn, "
    SELECT date 
    FROM attendance 
    WHERE user_id = $user_id 
    AND DATE_FORMAT(date, '%Y-%m') = '$selected_month'
    ORDER BY date DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - FitLife</title>
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
        
        .attendance-container {
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
            border: 2px solid var(--primary);
        }

        .back-button:hover {
            transform: translateX(-5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            background: var(--primary);
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

        /* Today's Attendance Box */
        .today-attendance {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
            margin-bottom: 3rem;
            border: 2px solid var(--primary);
        }

        .today-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .today-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        .today-info h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.25rem;
        }

        .today-info p {
            color: var(--gray);
            margin: 0;
        }

        /* Month Filter */
        .month-filter {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .month-select {
            padding: 0.75rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 0.5rem;
            font-size: 1rem;
            background: white;
            color: var(--dark);
            min-width: 200px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
            border-left: 4px solid var(--primary);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card.present { border-left-color: var(--success); }
        
        .stat-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            opacity: 0.8;
        }
        
        .stat-card.present .stat-icon { color: var(--success); }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: var(--gray);
            font-size: 0.875rem;
            font-weight: 600;
        }

        /* Attendance Action */
        .attendance-action {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 2rem;
            border: none;
            border-radius: 0.75rem;
            font-size: 1.1rem;
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
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--success), #059669);
            color: white;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.4);
        }
        
        .btn:disabled {
            background: var(--gray);
            cursor: not-allowed;
            transform: none;
        }
        
        .btn:disabled:hover {
            transform: none;
            box-shadow: none;
        }

        /* Attendance Table */
        .attendance-table {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .table-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 1.5rem 2rem;
            border: none;
        }
        
        .table-header h3 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
        }
        
        .table-body {
            padding: 0;
        }
        
        .attendance-list {
            list-style: none;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .attendance-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem 2rem;
            border-bottom: 1px solid #e2e8f0;
            transition: background-color 0.2s;
        }
        
        .attendance-item:hover {
            background-color: #f8fafc;
        }
        
        .attendance-item:last-child {
            border-bottom: none;
        }

        .attendance-item.today {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            border-left: 4px solid var(--warning);
        }
        
        .attendance-date {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .date-icon {
            width: 40px;
            height: 40px;
            background: var(--light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
        }

        .attendance-item.today .date-icon {
            background: var(--warning);
            color: white;
        }
        
        .date-info {
            display: flex;
            flex-direction: column;
        }
        
        .date-main {
            font-weight: 600;
            color: var(--dark);
        }
        
        .date-sub {
            font-size: 0.875rem;
            color: var(--gray);
        }
        
        .attendance-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 1rem;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .status-present {
            background: #d1fae5;
            color: #065f46;
        }

        /* Alerts */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 0.75rem;
            margin-bottom: 2rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.75rem;
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
        
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--gray);
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
        }

        @media (max-width: 768px) {
            .header-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .back-button {
                text-align: center;
                justify-content: center;
            }

            .attendance-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .attendance-status {
                align-self: flex-end;
            }

            .month-filter {
                flex-direction: column;
            }

            .month-select {
                width: 100%;
            }

            .today-header {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="attendance-container">
        <!-- Header Actions -->
        <div class="header-actions">
            <a href="dashboard.php" class="back-button">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h1>Attendance Tracker</h1>
            <p>Track your gym visits and maintain your fitness routine</p>
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

       

        <!-- Month Filter -->
        <div class="month-filter">
            <select class="month-select" onchange="location = this.value;">
                <?php 
                // Reset pointer for months query
                mysqli_data_seek($months_query, 0);
                while($month = mysqli_fetch_assoc($months_query)): 
                    $month_value = $month['month'];
                    $month_name = date('F Y', strtotime($month_value));
                    $is_selected = $month_value == $selected_month ? 'selected' : '';
                ?>
                    <option value="attendance.php?month=<?= $month_value ?>" <?= $is_selected ?>>
                        <?= $month_name ?>
                    </option>
                <?php endwhile; ?>
                <!-- Add current month if not in list -->
                <?php if ($selected_month == $current_month && mysqli_num_rows($months_query) == 0): ?>
                    <option value="attendance.php?month=<?= $current_month ?>" selected>
                        <?= date('F Y') ?>
                    </option>
                <?php endif; ?>
            </select>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card present">
                <i class="fas fa-check-circle stat-icon"></i>
                <div class="stat-value"><?= $present_days ?></div>
                <div class="stat-label">Total Visits</div>
            </div>
        </div>

        <!-- Attendance History -->
        <div class="attendance-table">
            <div class="table-header">
                <h3><i class="fas fa-history"></i> Attendance History - <?= $current_month_name ?></h3>
            </div>
            <div class="table-body">
                <?php if (mysqli_num_rows($user_attendance_query) > 0): ?>
                    <ul class="attendance-list">
                        <?php 
                        // Display only dates that exist in database (present dates)
                        mysqli_data_seek($user_attendance_query, 0);
                        while($attendance = mysqli_fetch_assoc($user_attendance_query)):
                            $attendance_date = $attendance['date'];
                            $day_name = date('l', strtotime($attendance_date));
                            $is_today = $attendance_date == $today;
                        ?>
                        <li class="attendance-item <?= $is_today ? 'today' : '' ?>">
                            <div class="attendance-date">
                                <div class="date-icon">
                                    <i class="fas fa-calendar-day"></i>
                                </div>
                                <div class="date-info">
                                    <span class="date-main"><?= date('F j, Y', strtotime($attendance_date)) ?></span>
                                    <span class="date-sub"><?= $day_name ?><?= $is_today ? ' (Today)' : '' ?></span>
                                </div>
                            </div>
                            <div class="attendance-status">
                                <span class="status-badge status-present">
                                    <i class="fas fa-check"></i>
                                    Present
                                </span>
                            </div>
                        </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <h3>No Attendance Records</h3>
                        <p>Your attendance history for <?= $current_month_name ?> will appear here.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Add some interactivity
        document.addEventListener('DOMContentLoaded', function() {
            const attendanceItems = document.querySelectorAll('.attendance-item');
            
            attendanceItems.forEach(item => {
                item.addEventListener('click', function() {
                    this.style.transform = 'scale(0.98)';
                    setTimeout(() => {
                        this.style.transform = 'scale(1)';
                    }, 150);
                });
            });

            // Auto-hide success message after 5 seconds
            const successAlert = document.querySelector('.alert-success');
            if (successAlert) {
                setTimeout(() => {
                    successAlert.style.opacity = '0';
                    setTimeout(() => {
                        successAlert.remove();
                    }, 300);
                }, 5000);
            }
        });
    </script>
</body>
</html>