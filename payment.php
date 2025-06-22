<?php
require_once 'config.php';
session_start();

if (!isset($_GET['borrow_id']) && !isset($_POST['borrow_id'])) {
    echo "No borrow record specified.";
    exit;
}

$borrow_id = isset($_POST['borrow_id']) ? (int)$_POST['borrow_id'] : (int)$_GET['borrow_id'];

// Fetch borrow record and fine info
$stmt = $conn->prepare("SELECT br.*, b.title, b.book_fine, br.due_date, u.full_name FROM borrow_records br JOIN books b ON br.book_id = b.book_id JOIN users u ON br.user_id = u.user_id WHERE br.borrow_id = ?");
$stmt->bind_param("i", $borrow_id);
$stmt->execute();
$result = $stmt->get_result();
$record = $result->fetch_assoc();
$stmt->close();

if (!$record) {
    echo "Borrow record not found.";
    exit;
}

// Calculate fine and overdue days
$fine = 0;
$days_overdue = 0;
$is_overdue = false;
if (empty($record['fine_paid']) && strtotime($record['due_date']) < time() && empty($record['return_date'])) {
    $due_date = new DateTime($record['due_date']);
    $today = new DateTime();
    $days_overdue = $due_date->diff($today)->days;
    $fine = $days_overdue * $record['book_fine'];
    $is_overdue = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pay Fine | Book Stop</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="payment-container" style="max-width:400px;margin:60px auto;background:#fff;padding:2rem 2.5rem;border-radius:12px;box-shadow:0 2px 12px rgba(44,62,80,0.10);text-align:center;">
        <h2 style="color:#532c2e;">Pay Overdue Fine</h2>
        <p><strong>Borrower:</strong> <?= htmlspecialchars($record['full_name']) ?></p>
        <p><strong>Book:</strong> <?= htmlspecialchars($record['title']) ?></p>
        <?php if ($record['fine_paid']): ?>
            <div class="alert alert-success">Fine already paid.</div>
            <a href="receipt.php?borrow_id=<?= $borrow_id ?>" class="btn" style="margin-top:1.5rem;">View Receipt</a>
        <?php elseif ($is_overdue && $fine > 0): ?>
            <div class="alert alert-warning" style="margin:1.2rem 0;">
                You have an overdue fine of <strong>₱<?= number_format($fine, 2) ?></strong>.<br>
                <span>Overdue by <strong><?= $days_overdue ?></strong> day<?= $days_overdue == 1 ? '' : 's' ?>.</span><br>
                Please pay to complete the return.
            </div>
            <form method="post" action="process-payment.php">
                <input type="hidden" name="borrow_id" value="<?= $borrow_id ?>">
                <button type="submit" class="pay-btn" style="background:#388e3c;color:#fff;padding:0.7rem 2rem;border:none;border-radius:8px;font-weight:700;font-size:1rem;cursor:pointer;align-items:center;display:block;margin:0 auto;">
                    Pay Fine
                </button>
            </form>
        <?php else: ?>
            <div class="alert alert-info">No fine due for this record.</div>
            <a href="receipt.php?borrow_id=<?= $borrow_id ?>" class="btn" style="margin-top:1.5rem;">View Receipt</a>
            <a href="borrow-book.php" class="btn" style="margin-top:1rem;">View Another Receipt</a>
            <?php endif; ?>
    </div>
</body>
</html>