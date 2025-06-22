<?php

require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrow_id'])) {
    $borrow_id = (int)$_POST['borrow_id'];

    // Get borrow record and book info
    $stmt = $conn->prepare("SELECT br.*, b.book_fine, br.due_date FROM borrow_records br JOIN books b ON br.book_id = b.book_id WHERE br.borrow_id = ?");
    $stmt->bind_param("i", $borrow_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $record = $result->fetch_assoc();
    $stmt->close();

    if ($record) {
        $is_overdue = false;
        $fine = 0;
        $days_overdue = 0;
        $today = new DateTime();
        $due_date = new DateTime($record['due_date']);

        if ($today > $due_date && $record['book_fine'] > 0) {
            $days_overdue = $today->diff($due_date)->days;
            $fine = $days_overdue * $record['book_fine'];
            $is_overdue = true;
        }

        if ($is_overdue && $fine > 0) {
            // Redirect to payment page
            header("Location: payment.php?borrow_id=$borrow_id&amount=$fine");
            exit;
        } else {
            // Process return as usual, update status to 'returned'
            $return_date = date('Y-m-d');
            $stmt = $conn->prepare("UPDATE borrow_records SET return_date = ?, status = 'returned' WHERE borrow_id = ?");
            $stmt->bind_param("si", $return_date, $borrow_id);
            $stmt->execute();
            $stmt->close();
            // Redirect to receipt or borrowed books page
            header("Location: receipt.php?borrow_id=$borrow_id");
            exit;
        }
    }
}

// No direct access
header('Location: borrow-book.php');
exit;