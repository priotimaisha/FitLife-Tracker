<?php
// pages/add_payment.php
session_start();
include('../includes/db.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id']);
    $package_id = intval($_POST['package_id']);
    $amount = intval($_POST['amount']);
    $payment_date = mysqli_real_escape_string($conn, $_POST['payment_date']);
    
    // Validate inputs
    if ($user_id > 0 && $package_id > 0 && $amount > 0 && !empty($payment_date)) {
        
        // Get package duration to calculate expiry date
        $package_result = mysqli_query($conn, "SELECT duration FROM packages WHERE id = $package_id");
        if (mysqli_num_rows($package_result) > 0) {
            $package = mysqli_fetch_assoc($package_result);
            $duration = $package['duration'];
            
            // Calculate expiry date
            $expiry_date = date('Y-m-d', strtotime($payment_date . " + $duration days"));
            
            // Insert payment
            $insert_query = "INSERT INTO payments (user_id, package_id, amount, payment_date, expiry_date, status) 
                            VALUES ($user_id, $package_id, $amount, '$payment_date', '$expiry_date', 'paid')";
            
            if (mysqli_query($conn, $insert_query)) {
                // Update user's current package
                mysqli_query($conn, "UPDATE users SET package_id = $package_id WHERE id = $user_id");
                
                $success = "Payment added successfully!";
                header("Location: manage_payments.php?success=" . urlencode($success));
                exit;
            } else {
                $error = "Error adding payment: " . mysqli_error($conn);
            }
        } else {
            $error = "Invalid package selected!";
        }
    } else {
        $error = "Please fill all fields correctly!";
    }
}

// If there's an error, redirect back with error message
if (!empty($error)) {
    header("Location: manage_payments.php?error=" . urlencode($error));
    exit;
}

// If somehow accessed directly without POST, redirect back
header("Location: manage_payments.php");
exit;
?>