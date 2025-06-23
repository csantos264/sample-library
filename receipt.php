<?php
require_once 'config.php';
session_start();

if (!isset($_GET['borrow_id'])) {
    echo "No receipt specified.";
    exit;
}

$borrow_id = (int)$_GET['borrow_id'];

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt | Book Stop</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="receipt-container" style="max-width:500px;margin:60px auto;background:#fff;padding:2rem 2.5rem;border-radius:12px;box-shadow:0 2px 12px rgba(44,62,80,0.10);text-align:center;">
        <h2 style="color:#532c2e;">Payment Receipt</h2>
        <p><strong>Borrower:</strong> <?= htmlspecialchars($record['full_name']) ?></p>
        <p><strong>Book:</strong> <?= htmlspecialchars($record['title']) ?></p>
        <p><strong>Borrowed On:</strong> <?= htmlspecialchars(date('M d, Y', strtotime($record['borrow_date']))) ?></p>
        <p><strong>Due Date:</strong> <?= htmlspecialchars(date('M d, Y', strtotime($record['due_date']))) ?></p>
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
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:1.5rem;gap:10px;">
                <a href="catalog.php" class="btn" style="background:#532c2e;color:#fff;padding:10px 24px;border-radius:6px;text-decoration:none;font-weight:bold;box-shadow:0 2px 6px rgba(44,62,80,0.08);display:inline-block;transition:background 0.2s;min-width:160px;text-align:center;">
                    <i class="fas fa-book"></i> Browse Books
                </a>
                <a href="student_page.php" class="btn" style="background:#a66e4a;color:#fff;padding:10px 24px;border-radius:6px;text-decoration:none;font-weight:bold;box-shadow:0 2px 6px rgba(44,62,80,0.08);display:inline-block;transition:background 0.2s;min-width:160px;text-align:center;">
                    <i class="fas fa-tachometer-alt"></i> Student Dashboard
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>