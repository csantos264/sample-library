<?php
require_once 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrow_id'])) {
    $borrow_id = (int)$_POST['borrow_id'];
    $user_id = $_SESSION['user_id'];

    // Fetch borrow record and book fine
    $stmt = $conn->prepare("SELECT br.*, b.book_fine FROM borrow_records br JOIN books b ON br.book_id = b.book_id WHERE br.borrow_id = ?");
    $stmt->bind_param("i", $borrow_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $record = $result->fetch_assoc();
    $stmt->close();

    if (!$record) {
        $_SESSION['payment_error'] = "Borrow record not found.";
        header("Location: receipt.php?borrow_id=$borrow_id");
        exit;
    }

    if ($record['fine_paid']) {
        $_SESSION['payment_error'] = "Fine already paid.";
        header("Location: receipt.php?borrow_id=$borrow_id");
        exit;
    }

    // Calculate fine
    $fine = 0;
    $days_overdue = 0;
    if (strtotime($record['due_date']) < time() && empty($record['return_date'])) {
        $due_date = new DateTime($record['due_date']);
        $today = new DateTime();
        $days_overdue = $due_date->diff($today)->days;
        $fine = $days_overdue * $record['book_fine'];
    }

    // Insert into fine table
    $stmt = $conn->prepare("INSERT INTO fines (borrow_id, amount, paid) VALUES (?, ?, 1)");
    $stmt->bind_param("id", $borrow_id, $fine);
    $stmt->execute();
    $fine_id = $stmt->insert_id;
    $stmt->close();

    // Insert into payment table
    $payment_type = 'fine';
    $payment_method = 'cash'; // or get from user input if needed
    $status = 'paid';
    $reference_id = $fine_id;
    $stmt = $conn->prepare("INSERT INTO payments (user_id, amount, payment_type, reference_id, payment_method, payment_date, status) VALUES (?, ?, ?, ?, ?, NOW(), ?)");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("idisss", $user_id, $fine, $payment_type, $reference_id, $payment_method, $status);
    if (!$stmt->execute()) {
        die("Execute failed: " . $stmt->error);
    }
    $stmt->close();

    // Update borrow_records
    $return_date = date('Y-m-d');
    $stmt = $conn->prepare("UPDATE borrow_records SET fine_paid = 1, fine_paid_date = NOW(), return_date = ?, status = 'returned' WHERE borrow_id = ?");
    $stmt->bind_param("si", $return_date, $borrow_id);
    $stmt->execute();
    $stmt->close();

    $_SESSION['payment_success'] = "Payment successful. Book returned.";
    header("Location: receipt.php?borrow_id=$borrow_id&paid=1");
    exit;
} else {
    $_SESSION['payment_error'] = "Invalid payment request.";
    header("Location: borrow-book.php");
    exit;
}