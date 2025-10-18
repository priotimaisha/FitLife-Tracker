<?php
session_start();
include('../includes/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get payment statistics
$stats_query = mysqli_query($conn, "
    SELECT 
        COUNT(*) as total_payments,
        SUM(amount) as total_spent,
        AVG(amount) as average_payment,
        MAX(payment_date) as last_payment
    FROM payments 
    WHERE user_id = $user_id AND status = 'paid'
");
$stats = mysqli_fetch_assoc($stats_query);

// Get active package info
$active_package_query = mysqli_query($conn, "
    SELECT p.*, pk.name as package_name, pk.duration
    FROM payments p
    LEFT JOIN packages pk ON p.package_id = pk.id
    WHERE p.user_id = $user_id 
    AND p.status = 'paid'
    AND p.expiry_date > CURDATE()
    ORDER BY p.payment_date DESC 
    LIMIT 1
");
$active_package = mysqli_fetch_assoc($active_package_query);

// Get all payments with package names
$payments_query = mysqli_query($conn, "
    SELECT p.*, pk.name as package_name 
    FROM payments p 
    LEFT JOIN package pk ON p.package_id = pk.id 
    WHERE p.user_id = $user_id 
    ORDER BY p.payment_date DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Payments - FitLife</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background-color: #f9fafb;
            color: #374151;
            line-height: 1.6;
        }
        
        .container {
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
        
        .header {
            margin-bottom: 2rem;
        }
        
        .header h1 {
            font-size: 1.875rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }
        
        .header p {
            color: #6b7280;
        }
        
        .active-package {
            background: linear-gradient(135deg, var(--success), #20c997);
            color: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .active-package h3 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            text-align: center;
            border-left: 4px solid var(--primary);
        }
        
        .stat-card.total { border-left-color: var(--success); }
        .stat-card.spent { border-left-color: var(--info); }
        .stat-card.average { border-left-color: var(--warning); }
        .stat-card.last { border-left-color: var(--danger); }
        
        .stat-icon {
            font-size: 1.5rem;
            margin-bottom: 0.75rem;
            opacity: 0.8;
        }
        
        .stat-card h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        
        .stat-card p {
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #1f2937;
        }
        
        .payments-list {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .payment-item {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 1rem;
            align-items: start;
        }
        
        .payment-item:last-child {
            border-bottom: none;
        }
        
        .payment-info h4 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: #1f2937;
        }

        .package-name {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .payment-meta {
            display: flex;
            gap: 1rem;
            font-size: 0.875rem;
            color: #6b7280;
            flex-wrap: wrap;
        }

        .payment-meta-item {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .payment-amount {
            text-align: right;
        }
        
        .payment-amount .amount {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            color: #1f2937;
        }
        
        .payment-amount .status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-paid {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .status-failed {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        .payment-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.75rem;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #5a6fd8;
            transform: translateY(-1px);
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid #d1d5db;
            color: #374151;
        }
        
        .btn-outline:hover {
            background-color: #f9fafb;
            transform: translateY(-1px);
        }

        .btn-success {
            background-color: var(--success);
            color: white;
        }

        .btn-success:hover {
            background-color: #218838;
            transform: translateY(-1px);
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #6b7280;
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
        
        .help-section {
            margin-top: 3rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .help-card {
            background: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .help-card h3 {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .help-card ul {
            list-style: none;
        }
        
        .help-card li {
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .payment-item {
                grid-template-columns: 1fr;
            }
            
            .payment-amount {
                text-align: left;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .header-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .back-button {
                text-align: center;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header Actions -->
        <div class="header-actions">
            <a href="dashboard.php" class="back-button">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
        </div>

        <!-- Page Header -->
        <div class="header">
            <h1>Payment History</h1>
            <p>View and manage your payment records</p>
        </div>

        <!-- Active Package Banner -->
        <?php if ($active_package): ?>
        <div class="active-package">
            <h3><?= htmlspecialchars($active_package['package_name']) ?></h3>
            <p>Expires on <?= date('F j, Y', strtotime($active_package['expiry_date'])) ?></p>
        </div>
        <?php endif; ?>

        <!-- Payment Statistics -->
        <div class="stats-grid">
            <div class="stat-card total">
                <i class="fas fa-receipt stat-icon"></i>
                <h3><?= $stats['total_payments'] ?? 0 ?></h3>
                <p>Total Payments</p>
            </div>
            
            <div class="stat-card spent">
                <i class="fas fa-money-bill-wave stat-icon"></i>
                <h3><?= number_format($stats['total_spent'] ?? 0, 0) ?>৳</h3>
                <p>Total Spent</p>
            </div>
            
            <div class="stat-card average">
                <i class="fas fa-chart-line stat-icon"></i>
                <h3><?= number_format($stats['average_payment'] ?? 0, 0) ?>৳</h3>
                <p>Average Payment</p>
            </div>
            
            <div class="stat-card last">
                <i class="fas fa-calendar-check stat-icon"></i>
                <h3>
                    <?= $stats['last_payment'] ? date('M j', strtotime($stats['last_payment'])) : 'N/A' ?>
                </h3>
                <p>Last Payment</p>
            </div>
        </div>

        <!-- Payment History -->
        <h2 class="section-title">Payment History</h2>
        <div class="payments-list">
            <?php if (mysqli_num_rows($payments_query) > 0): ?>
                <?php while($payment = mysqli_fetch_assoc($payments_query)): ?>
                <div class="payment-item">
                    <div class="payment-info">
                        <span class="package-name"><?= htmlspecialchars($payment['package_name']) ?></span>
                        <div class="payment-meta">
                            <span class="payment-meta-item">
                                <i class="fas fa-calendar"></i>
                                <?= date('M j, Y', strtotime($payment['payment_date'])) ?>
                            </span>
                            <span class="payment-meta-item">
                                <i class="fas fa-clock"></i>
                                Expires: <?= date('M j, Y', strtotime($payment['expiry_date'])) ?>
                            </span>
                        </div>
                        <div class="payment-actions">
                            <?php if ($payment['status'] == 'pending'): ?>
                                <a href="make_payment.php?payment_id=<?= $payment['id'] ?>" class="btn btn-primary">
                                    <i class="fas fa-credit-card"></i> Complete Payment
                                </a>
                            <?php endif; ?>
                            <?php if (strtotime($payment['expiry_date']) < time() && $payment['status'] == 'paid'): ?>
                                <a href="packages.php" class="btn btn-success">
                                    <i class="fas fa-redo"></i> Renew Package
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="payment-amount">
                        <div class="amount"><?= number_format($payment['amount'], 0) ?>৳</div>
                        <span class="status status-<?= $payment['status'] ?>">
                            <?= ucfirst($payment['status']) ?>
                        </span>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-credit-card"></i>
                    <h3>No Payments Yet</h3>
                    <p class="mb-4">You haven't made any payments yet.</p>
                    <a href="packages.php" class="btn btn-primary">
                        <i class="fas fa-search"></i> Browse Packages
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Help Section -->
        <div class="help-section">
            <div class="help-card">
                <h3><i class="fas fa-question-circle"></i> Need Help?</h3>
                <ul>
                    <li><i class="fas fa-phone"></i> <strong>Phone:</strong> +880 1700000000</li>
                    <li><i class="fas fa-envelope"></i> <strong>Email:</strong> payments@fitlife.com</li>
                    <li><i class="fas fa-clock"></i> <strong>Support Hours:</strong> 9:00 AM - 8:00 PM</li>
                </ul>
            </div>
            
            <div class="help-card">
                <h3><i class="fas fa-lightbulb"></i> Payment Tips</h3>
                <ul>
                    <li><i class="fas fa-check"></i> Keep receipts for reference</li>
                    <li><i class="fas fa-check"></i> Renew package before expiry</li>
                    <li><i class="fas fa-check"></i> Contact support for payment issues</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        // Simple confirmation for actions
        document.addEventListener('DOMContentLoaded', function() {
            const downloadButtons = document.querySelectorAll('a[href*="generate_receipt"]');
            
            downloadButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    // You can add a loading indicator here if needed
                    console.log('Generating receipt...');
                });
            });
        });
    </script>
</body>
</html>