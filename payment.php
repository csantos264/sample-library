<?php
require_once 'config.php';
session_start();

$payment_type = $_GET['type'] ?? 'overdue';
$fine_id = $_GET['fine_id'] ?? null;
$borrow_id = null;

if ($payment_type === 'extension' && $fine_id) {
    // Handle extension payment
    $stmt = $conn->prepare("SELECT f.*, br.book_id, br.user_id, b.title, u.full_name FROM fines f 
                           JOIN borrow_records br ON f.borrow_id = br.borrow_id 
                           JOIN books b ON br.book_id = b.book_id 
                           JOIN users u ON br.user_id = u.user_id 
                           WHERE f.fine_id = ? AND f.paid = 0");
    $stmt->bind_param("i", $fine_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $fine_record = $result->fetch_assoc();
    $stmt->close();
    
    if (!$fine_record) {
        echo "Extension fine not found or already paid.";
        exit;
    }
    
    $borrow_id = $fine_record['borrow_id'];
    $record = [
        'borrow_id' => $fine_record['borrow_id'],
        'title' => $fine_record['title'],
        'full_name' => $fine_record['full_name'],
        'fine_paid' => 0
    ];
    $fine = $fine_record['amount'];
    $is_overdue = false;
    $days_overdue = 0;
} else {
    // Handle regular overdue payment
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
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $payment_type === 'extension' ? 'Extension Payment' : 'Pay Fine' ?> | Book Stop</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="payment-container" style="max-width:400px;margin:60px auto;background:#fff;padding:2.5rem 2.5rem 2rem 2.5rem;border-radius:16px;box-shadow:0 2px 16px rgba(44,62,80,0.13);text-align:center;position:relative;">
        <button onclick="window.location.href='<?= $payment_type === 'extension' ? 'student_page.php' : 'admin_page.php' ?>'" type="button" style="position:absolute;left:16px;top:16px;background:none;border:none;color:#532c2e;font-size:1rem;display:flex;align-items:center;gap:0.5rem;
        cursor:pointer;padding:0.35rem 0.9rem 0.35rem 0.5rem;border-radius:7px;box-shadow:none;transition:background 0.2s;min-height:unset;min-width:unset;line-height:1.1;">
            <span style="display:inline-flex;align-items:center;justify-content:center;width:1.7em;height:1.7em;border-radius:50%;margin-right:0.5em;">
                <i class="fas fa-arrow-left" style="font-size:1.1em;color:#2980b9;"></i>
            </span>
            <span style="font-size:0.98em;">Back to <?= $payment_type === 'extension' ? 'Dashboard' : 'Admin Dashboard' ?></span>
        </button> <br> <br>
        <h2 style="color:#532c2e;margin-bottom:1.2rem;">
            <?= $payment_type === 'extension' ? 'Extension Payment' : 'Pay Overdue Fine' ?>
        </h2>
        <div style="margin-bottom:1.1rem;text-align:left;">
            <p style="margin:0 0 0.3rem 0;"><strong>Borrower:</strong> <span style="color:#333;"><?= htmlspecialchars($record['full_name']) ?></span></p>
            <p style="margin:0 0 0.3rem 0;"><strong>Book:</strong> <span style="color:#333;"><?= htmlspecialchars($record['title']) ?></span></p>
            <?php if ($payment_type === 'extension'): ?>
                <p style="margin:0 0 0.3rem 0;"><strong>Payment Type:</strong> <span style="color:#C5832B;">Extension Fine</span></p>
            <?php endif; ?>
        </div>
        <?php if ($record['fine_paid']): ?>
            <div class="alert alert-success" style="margin-bottom:1.2rem;">Fine already paid.</div>
            <a href="receipt.php?borrow_id=<?= $borrow_id ?>" class="btn" style="margin-top:0.5rem;">View Receipt</a>
        <?php elseif (($is_overdue && $fine > 0) || ($payment_type === 'extension' && $fine > 0)): ?>
            <div class="alert alert-warning" style="margin:1.2rem 0 1.5rem 0;padding:1rem 1.2rem;border-radius:8px;background:#fff7e6;color:#b26a00;font-size:1.08rem;">
                <i class="fas fa-exclamation-triangle" style="margin-right:0.5rem;"></i>
                <?php if ($payment_type === 'extension'): ?>
                    Extension fine amount: <strong style="color:#b71c1c;">₱<?= number_format($fine, 2) ?></strong><br>
                    <span style="color:#532c2e;">Please pay to activate your extension.</span>
                <?php else: ?>
                    You have an overdue fine of <strong style="color:#b71c1c;">₱<?= number_format($fine, 2) ?></strong>.<br>
                    <span style="color:#b26a00;">Overdue by <strong><?= $days_overdue ?></strong> day<?= $days_overdue == 1 ? '' : 's' ?>.</span><br>
                    <span style="color:#532c2e;">Please pay to complete the return.</span>
                <?php endif; ?>
            </div>
            <form method="post" action="process-payment.php" style="margin-top:0.5rem;">
                <input type="hidden" name="borrow_id" value="<?= $borrow_id ?>">
                <?php if ($payment_type === 'extension'): ?>
                    <input type="hidden" name="fine_id" value="<?= $fine_id ?>">
                    <input type="hidden" name="payment_type" value="extension">
                <?php endif; ?>
                <button type="submit" class="pay-btn" style="background:#388e3c;color:#fff;padding:0.8rem 2.2rem;border:none;border-radius:8px;font-weight:700;font-size:1.08rem;cursor:pointer;align-items:center;display:block;margin:0 auto;box-shadow:0 1px 6px rgba(44,62,80,0.07);transition:background 0.2s;">
                    <i class="fas fa-money-bill-wave" style="margin-right:0.5rem;"></i>Pay Fine
                </button>
            </form>
        <?php else: ?>
            <div class="alert alert-info" style="margin-bottom:1.2rem;">No fine due for this record.</div>
            <a href="receipt.php?borrow_id=<?= $borrow_id ?>" class="btn" style="margin-top:0.5rem;">View Receipt</a>
            <a href="<?= $payment_type === 'extension' ? 'student_page.php' : 'borrow-book.php' ?>" class="btn" style="margin-top:0.7rem;background:#C5832B;color:#fff;"><?= $payment_type === 'extension' ? 'Back to Dashboard' : 'View Another Receipt' ?></a>
        <?php endif; ?>
    </div>
</body>
</html>