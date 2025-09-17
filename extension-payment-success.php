<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Check if user is not an admin (only regular users can access extension payment success page)
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
    header('Location: admin_page.php');
    exit();
}

$payment_id = $_GET['payment_id'] ?? null;

if (!$payment_id) {
    header('Location: student_page.php');
    exit();
}

// Get payment details
$stmt = $conn->prepare("SELECT p.*, f.borrow_id, b.title FROM payments p 
                       JOIN fines f ON p.reference_id = f.fine_id 
                       JOIN borrow_records br ON f.borrow_id = br.borrow_id 
                       JOIN books b ON br.book_id = b.book_id 
                       WHERE p.payment_id = ? AND p.payment_type = 'extension_fine'");
$stmt->bind_param("i", $payment_id);
$stmt->execute();
$result = $stmt->get_result();
$payment = $result->fetch_assoc();
$stmt->close();

if (!$payment) {
    header('Location: student_page.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Successful | Book Stop</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .success-container {
            max-width: 600px;
            margin: 4rem auto;
            padding: 3rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            text-align: center;
        }
        .success-icon {
            width: 80px;
            height: 80px;
            background: #4caf50;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            color: white;
            font-size: 2.5rem;
        }
        .payment-details {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 12px;
            margin: 2rem 0;
            text-align: left;
        }
        .btn-primary {
            background: #C5832B;
            color: white;
            padding: 1rem 2rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            display: inline-block;
            margin: 0.5rem;
            transition: background 0.3s;
        }
        .btn-primary:hover {
            background: #a66e4a;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
            padding: 1rem 2rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            display: inline-block;
            margin: 0.5rem;
            transition: background 0.3s;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <header class="dashboard-header">
        <h1><i class="fas fa-check-circle"></i> Book Stop</h1>
        <a href="login_register.php?logout=1" class="logout-btn">Logout</a>
    </header>
    
    <div class="success-container">
        <div class="success-icon">
            <i class="fas fa-check"></i>
        </div>
        
        <h1 style="color: #2e7d32; margin-bottom: 1rem;">Payment Successful!</h1>
        <p style="color: #666; font-size: 1.1rem; margin-bottom: 2rem;">
            Your extension payment has been processed successfully. Your extension is now active.
        </p>
        
        <div class="payment-details">
            <h3 style="color: #532c2e; margin-bottom: 1rem;">
                <i class="fas fa-receipt"></i> Payment Details
            </h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div><strong>Book:</strong> <?= htmlspecialchars($payment['title']) ?></div>
                <div><strong>Amount Paid:</strong> ₱<?= number_format($payment['amount'], 2) ?></div>
                <div><strong>Payment Date:</strong> <?= date('M d, Y h:i A', strtotime($payment['payment_date'])) ?></div>
                <div><strong>Payment ID:</strong> #<?= $payment['payment_id'] ?></div>
            </div>
        </div>
        
        <div style="background: #e8f5e8; padding: 1.5rem; border-radius: 8px; margin: 2rem 0;">
            <h4 style="color: #2e7d32; margin-bottom: 0.5rem;">
                <i class="fas fa-info-circle"></i> What's Next?
            </h4>
            <p style="color: #2e7d32; margin: 0;">
                Your book extension has been activated. You can now keep the book for the extended period. 
                Please return it by the new due date to avoid additional fines.
            </p>
        </div>
        
        <div style="margin-top: 2rem;">
            <a href="receipt.php?borrow_id=<?= $payment['borrow_id'] ?>&type=extension" class="btn-primary">
                <i class="fas fa-download"></i> Download Receipt
            </a>
            <a href="student_page.php" class="btn-secondary">
                <i class="fas fa-home"></i> Back to Dashboard
            </a>
        </div>
    </div>
    
    <script>
        // Auto-redirect to dashboard after 10 seconds
        setTimeout(function() {
            window.location.href = 'student_page.php';
        }, 10000);
    </script>
</body>
</html> 