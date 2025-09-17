<?php
require_once 'config.php';
session_start();

if (!isset($_GET['borrow_id'])) {
    echo "No receipt specified.";
    exit;
}

$borrow_id = (int)$_GET['borrow_id'];
$receipt_type = $_GET['type'] ?? 'regular';

if ($receipt_type === 'extension') {
    // Handle extension payment receipt
    $stmt = $conn->prepare("SELECT br.*, b.title, u.full_name, f.amount as fine_amount, f.fine_id, p.payment_date 
                           FROM borrow_records br 
                           JOIN books b ON br.book_id = b.book_id 
                           JOIN users u ON br.user_id = u.user_id 
                           JOIN fines f ON br.borrow_id = f.borrow_id 
                           JOIN payments p ON f.fine_id = p.reference_id 
                           WHERE br.borrow_id = ? AND p.payment_type = 'extension_fine' 
                           ORDER BY p.payment_date DESC LIMIT 1");
    $stmt->bind_param("i", $borrow_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $record = $result->fetch_assoc();
    $stmt->close();

    if (!$record) {
        echo "Extension payment receipt not found.";
        exit;
    }

    $fine = $record['fine_amount'];
    $payment_date = $record['payment_date'];
} else {
    // Handle regular payment receipt
    // Fetch borrow record and related info
    $stmt = $conn->prepare("SELECT br.*, b.title, b.book_fine, u.full_name FROM borrow_records br 
        JOIN books b ON br.book_id = b.book_id 
        JOIN users u ON br.user_id = u.user_id 
        WHERE br.borrow_id = ?");
    $stmt->bind_param("i", $borrow_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $record = $result->fetch_assoc();
    $stmt->close();

    if (!$record) {
        echo "Receipt not found.";
        exit;
    }

    // Calculate fine and overdue days
    $fine = 0;
    $days_overdue = 0;
    if (!empty($record['fine_paid']) && strtotime($record['due_date']) < strtotime($record['return_date']) && $record['book_fine'] > 0) {
        $due_date = new DateTime($record['due_date']);
        $return_date = new DateTime($record['return_date']);
        $days_overdue = $due_date->diff($return_date)->days;
        $fine = $days_overdue * $record['book_fine'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $receipt_type === 'extension' ? 'Extension Payment Receipt' : 'Payment Receipt' ?> | Book Stop</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="receipt-container" style="max-width:500px;margin:60px auto;background:#fff;padding:2rem 2.5rem;border-radius:12px;box-shadow:0 2px 12px rgba(44,62,80,0.10);text-align:center;">
        <h2 style="color:#532c2e;">
            <?= $receipt_type === 'extension' ? 'Extension Payment Receipt' : 'Payment Receipt' ?>
        </h2>
        <p><strong>Borrower:</strong> <?= htmlspecialchars($record['full_name']) ?></p>
        <p><strong>Book:</strong> <?= htmlspecialchars($record['title']) ?></p>
        <p><strong>Borrowed On:</strong> <?= htmlspecialchars(date('M d, Y', strtotime($record['borrow_date']))) ?></p>
        <p><strong>Due Date:</strong> <?= htmlspecialchars(date('M d, Y', strtotime($record['due_date']))) ?></p>
        
        <?php if ($receipt_type === 'extension'): ?>
            <p><strong>Payment Type:</strong> <span style="color:#C5832B;">Extension Fine</span></p>
            <p><strong>Paid On:</strong> <?= htmlspecialchars(date('M d, Y h:i A', strtotime($payment_date))) ?></p>
            <div class="alert alert-success" style="margin:1.2rem 0;background:#e8f5e8;color:#2e7d32;padding:1rem;border-radius:8px;">
                <i class="fas fa-check-circle"></i> Extension Payment Successful
            </div>
            <p><strong>Extension Fine Amount:</strong> ₱<?= number_format($fine, 2) ?></p>
            <p style="color:#666;font-style:italic;">Your extension has been activated successfully!</p>
        <?php else: ?>
            <p><strong>Returned On:</strong> <?= htmlspecialchars(date('M d, Y', strtotime($record['return_date']))) ?></p>
            <?php if (!empty($record['fine_paid'])): ?>
                <div class="alert alert-success" style="margin:1.2rem 0;">
                    <i class="fas fa-check-circle"></i> Fine Paid
                </div>
                <p><strong>Days Overdue:</strong> <?= $days_overdue ?></p>
                <p><strong>Fine Amount:</strong> ₱<?= number_format($fine, 2) ?></p>
                <p><strong>Paid On:</strong> <?= htmlspecialchars(date('M d, Y h:i A', strtotime($record['fine_paid_date']))) ?></p>
            <?php else: ?>
                <div class="alert alert-info" style="margin:1.2rem 0;">
                    <i class="fas fa-info-circle"></i> No fine was paid for this transaction.
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:1.5rem;gap:10px;">
            <a href="catalog.php" class="btn" style="background:#532c2e;color:#fff;padding:10px 24px;border-radius:6px;text-decoration:none;font-weight:bold;box-shadow:0 2px 6px rgba(44,62,80,0.08);display:inline-block;transition:background 0.2s;min-width:160px;text-align:center;">
                <i class="fas fa-book"></i> Browse Books
            </a>
            <a href="student_page.php" class="btn" style="background:#a66e4a;color:#fff;padding:10px 24px;border-radius:6px;text-decoration:none;font-weight:bold;box-shadow:0 2px 6px rgba(44,62,80,0.08);display:inline-block;transition:background 0.2s;min-width:160px;text-align:center;">
                <i class="fas fa-tachometer-alt"></i> User Dashboard
            </a>
        </div>
    </div>
</body>
</html>