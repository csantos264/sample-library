<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Check if user is not an admin (only regular users can access extension payment page)
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
    header('Location: admin_page.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if user has a pending extension payment
if (!isset($_SESSION['extension_payment'])) {
    header('Location: student_page.php');
    exit();
}

$payment_info = $_SESSION['extension_payment'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Extension Payment | Book Stop</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <header class="dashboard-header">
        <h1><i class="fas fa-credit-card"></i> Book Stop</h1>
        <a href="login_register.php?logout=1" class="logout-btn">Logout</a>
    </header>
    
    <div class="dashboard-layout">
        <div class="dashboard-main">
            <div style="max-width: 800px; margin: 2rem auto; padding: 2rem; background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                <h2 style="color: #532c2e; margin-bottom: 2rem; text-align: center;">
                    <i class="fas fa-credit-card"></i> Extension Payment
                </h2>
                
                <!-- Extension Summary -->
                <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; border-left: 4px solid #C5832B;">
                    <h3 style="margin-bottom: 1rem; color: #532c2e;">
                        <i class="fas fa-info-circle"></i> Extension Details
                    </h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div><strong>Book:</strong> <?= htmlspecialchars($payment_info['book_title']) ?></div>
                        <div><strong>Extension Period:</strong> <?= $payment_info['extension_days'] ?> days</div>
                        <div><strong>New Due Date:</strong> <?= date('M d, Y', strtotime($payment_info['new_due_date'])) ?></div>
                        <div><strong>Fine Amount:</strong> ₱<?= number_format($payment_info['amount'], 2) ?></div>
                    </div>
                </div>
                
                <!-- Payment Summary -->
                <div style="background: #e8f5e8; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; text-align: center;">
                    <div style="font-size: 2rem; font-weight: bold; color: #2e7d32; margin-bottom: 0.5rem;">
                        ₱<?= number_format($payment_info['amount'], 2) ?>
                    </div>
                    <div>Total Amount to Pay</div>
                </div>
                
                <p style="text-align: center; color: #666; margin-bottom: 2rem;">
                    Your extension has been approved! Please complete the payment to activate your new due date.
                </p>
                
                <div style="text-align: center;">
                    <a href="payment.php?fine_id=<?= $payment_info['fine_id'] ?>&type=extension" 
                       style="background: #2e7d32; color: white; padding: 1rem 2rem; border-radius: 6px; text-decoration: none; font-weight: bold; display: inline-block;">
                        <i class="fas fa-lock"></i> Proceed to Payment
                    </a>
                    <br><br>
                    <a href="student_page.php" 
                       style="background: #666; color: white; padding: 0.8rem 1.5rem; border-radius: 6px; text-decoration: none; display: inline-block;">
                        <i class="fas fa-times"></i> Cancel Payment
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 