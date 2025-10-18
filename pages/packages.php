<?php
session_start();
include('../includes/db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}

// Category filter
$category = isset($_GET['category']) ? $_GET['category'] : 'all';

// Build query based on category
if ($category === 'active') {
    $packages_query = mysqli_query($conn, "
        SELECT * FROM package 
        WHERE status = 'active' 
        ORDER BY price ASC
    ");
} elseif ($category === 'inactive') {
    $packages_query = mysqli_query($conn, "
        SELECT * FROM package 
        WHERE status = 'inactive' 
        ORDER BY price ASC
    ");
} else {
    $packages_query = mysqli_query($conn, "
        SELECT * FROM package 
        ORDER BY 
            CASE status 
                WHEN 'active' THEN 1 
                WHEN 'inactive' THEN 2 
                ELSE 3 
            END,
            price ASC
    ");
}

// Get counts for each category
$count_all = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM package"))['count'];
$count_active = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM package WHERE status = 'active'"))['count'];
$count_inactive = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM package WHERE status = 'inactive'"))['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Packages - FitLife</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #5a67d8;
            --secondary: #764ba2;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
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
        
        .packages-container {
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

        /* Category Filter */
        .category-filter {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 3rem;
            flex-wrap: wrap;
        }

        .category-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: white;
            color: var(--dark);
            text-decoration: none;
            border-radius: 0.5rem;
            font-weight: 600;
            border: 2px solid #e2e8f0;
            transition: all 0.3s;
            cursor: pointer;
        }

        .category-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-color: var(--primary);
            color: var(--primary);
            text-decoration: none;
        }

        .category-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .category-count {
            background: var(--light);
            color: var(--gray);
            padding: 0.25rem 0.5rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .category-btn.active .category-count {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        .packages-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .package-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
        }
        
        .package-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .package-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 2rem;
            text-align: center;
            position: relative;
        }
        
        .package-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .package-badge.active {
            background: var(--success);
        }

        .package-badge.inactive {
            background: var(--danger);
        }
        
        .package-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .package-price {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }
        
        .package-duration {
            font-size: 1rem;
            opacity: 0.9;
        }
        
        .package-body {
            padding: 2rem;
        }
        
        .package-description {
            color: var(--gray);
            margin-bottom: 1.5rem;
            line-height: 1.6;
            text-align: center;
            font-style: italic;
        }
        
        .package-features {
            margin-bottom: 2rem;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .feature-item:last-child {
            border-bottom: none;
        }
        
        .feature-icon {
            color: var(--success);
            font-size: 1rem;
            width: 20px;
        }
        
        .feature-text {
            color: var(--dark);
            font-weight: 500;
        }
        
        .package-footer {
            padding: 0 2rem 2rem;
        }
        
        .btn-join {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            padding: 1rem 2rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            text-decoration: none;
            border-radius: 0.75rem;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }

        .btn-join:disabled {
            background: var(--gray);
            cursor: not-allowed;
            transform: none;
        }

        .btn-join:disabled:hover {
            transform: none;
            box-shadow: none;
        }
        
        .btn-join:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
            color: white;
            text-decoration: none;
        }
        
        .package-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding: 1rem;
            background: var(--light);
            border-radius: 0.5rem;
        }
        
        .meta-item {
            text-align: center;
            flex: 1;
        }
        
        .meta-label {
            font-size: 0.875rem;
            color: var(--gray);
            margin-bottom: 0.25rem;
        }
        
        .meta-value {
            font-size: 1rem;
            font-weight: 600;
            color: var(--dark);
        }
        
        .status-active {
            color: var(--success);
            font-weight: 600;
        }

        .status-inactive {
            color: var(--danger);
            font-weight: 600;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--gray);
            grid-column: 1 / -1;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .packages-grid {
                grid-template-columns: 1fr;
            }
            
            .package-meta {
                flex-direction: column;
                gap: 1rem;
            }
            
            .meta-item {
                text-align: left;
                width: 100%;
                display: flex;
                justify-content: space-between;
            }

            .header-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .back-button {
                text-align: center;
                justify-content: center;
            }

            .category-filter {
                flex-direction: column;
                align-items: stretch;
            }

            .category-btn {
                text-align: center;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="packages-container">
        <!-- Header Actions -->
        <div class="header-actions">
            <a href="dashboard.php" class="back-button">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h1>Choose Your Package</h1>
            <p>Select the perfect plan that fits your fitness journey</p>
        </div>

        <!-- Category Filter -->
        <div class="category-filter">
            <a href="?category=all" class="category-btn <?= $category === 'all' ? 'active' : '' ?>">
                All Packages
                <span class="category-count"><?= $count_all ?></span>
            </a>
            <a href="?category=active" class="category-btn <?= $category === 'active' ? 'active' : '' ?>">
                Active
                <span class="category-count"><?= $count_active ?></span>
            </a>
            <a href="?category=inactive" class="category-btn <?= $category === 'inactive' ? 'active' : '' ?>">
                Inactive
                <span class="category-count"><?= $count_inactive ?></span>
            </a>
        </div>

        <!-- Packages Grid -->
        <div class="packages-grid">
            <?php if (mysqli_num_rows($packages_query) > 0): ?>
                <?php while($package = mysqli_fetch_assoc($packages_query)): ?>
                <div class="package-card">
                    <!-- Package Header -->
                    <div class="package-header">
                        <div class="package-badge <?= $package['status'] ?>">
                            <?= strtoupper($package['status']) ?>
                        </div>
                        <div class="package-name"><?= htmlspecialchars($package['name']) ?></div>
                        <div class="package-price"><?= number_format($package['price'], 0) ?>৳</div>
                        <div class="package-duration"><?= $package['duration'] ?> Days</div>
                    </div>
                    
                    <!-- Package Body -->
                    <div class="package-body">
                        <div class="package-description">
                            <?= htmlspecialchars($package['description']) ?>
                        </div>
                        
                        <!-- Package Meta Information -->
                        <div class="package-meta">
                            <div class="meta-item">
                                <div class="meta-label">Price</div>
                                <div class="meta-value"><?= number_format($package['price'], 0) ?>৳</div>
                            </div>
                            <div class="meta-item">
                                <div class="meta-label">Duration</div>
                                <div class="meta-value"><?= $package['duration'] ?> days</div>
                            </div>
                            <div class="meta-item">
                                <div class="meta-label">Status</div>
                                <div class="meta-value status-<?= $package['status'] ?>">
                                    <?= ucfirst($package['status']) ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Package Features -->
                        <div class="package-features">
                            <div class="feature-item">
                                <i class="fas fa-dumbbell feature-icon"></i>
                                <span class="feature-text">Full Gym Access</span>
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-user-tie feature-icon"></i>
                                <span class="feature-text">Trainer Guidance</span>
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-lock feature-icon"></i>
                                <span class="feature-text">Locker Facility</span>
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-shower feature-icon"></i>
                                <span class="feature-text">Shower Access</span>
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-wifi feature-icon"></i>
                                <span class="feature-text">Free WiFi</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Package Footer -->
                    <div class="package-footer">
                        <?php if ($package['status'] === 'active'): ?>
                            <a href="my_payments.php" class="btn-join">
                                <i class="fas fa-shopping-cart"></i>
                                Join Now
                            </a>
                        <?php else: ?>
                            <button class="btn-join" disabled>
                                <i class="fas fa-clock"></i>
                                Currently Unavailable
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <h3>No Packages Available</h3>
                    <p>No packages found in this category. Please try another category.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Add smooth hover effects
        document.addEventListener('DOMContentLoaded', function() {
            const packageCards = document.querySelectorAll('.package-card');
            
            packageCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });

            // Add active state to category buttons
            const categoryButtons = document.querySelectorAll('.category-btn');
            categoryButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    categoryButtons.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                });
            });
        });
    </script>
</body>
</html>