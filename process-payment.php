<?php
require_once 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrow_id'])) {
    $borrow_id = (int)$_POST['borrow_id'];
    $user_id = $_SESSION['user_id'];
    $payment_type = $_POST['payment_type'] ?? 'overdue';
    $fine_id = $_POST['fine_id'] ?? null;

    if ($payment_type === 'extension' && $fine_id) {
        // Handle extension payment
        $stmt = $conn->prepare("SELECT f.*, br.book_id, br.user_id, b.title FROM fines f 
                               JOIN borrow_records br ON f.borrow_id = br.borrow_id 
                               JOIN books b ON br.book_id = b.book_id 
                               WHERE f.fine_id = ? AND f.paid = 0");
        $stmt->bind_param("i", $fine_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $fine_record = $result->fetch_assoc();
        $stmt->close();

        if (!$fine_record) {
            $_SESSION['payment_error'] = "Extension fine not found or already paid.";
            header("Location: student_page.php");
            exit;
        }

        $fine = $fine_record['amount'];
        $book_title = $fine_record['title'];
        
        // Update fine record to mark as paid
        $stmt = $conn->prepare("UPDATE fines SET paid = 1 WHERE fine_id = ?");
        $stmt->bind_param("i", $fine_id);
        $stmt->execute();
        $stmt->close();

        // Insert into payment table
        $payment_type_db = 'extension_fine';
        $payment_method = 'cash';
        $status = 'paid';
        $stmt = $conn->prepare("INSERT INTO payments (user_id, amount, payment_type, reference_id, payment_method, payment_date, status) VALUES (?, ?, ?, ?, ?, NOW(), ?)");
        $stmt->bind_param("idisss", $user_id, $fine, $payment_type_db, $fine_id, $payment_method, $status);
        $stmt->execute();
        $payment_id = $conn->insert_id;
        $stmt->close();

        // Update borrow record
        $stmt = $conn->prepare("UPDATE borrow_records SET fine_paid = 1, fine_paid_date = NOW() WHERE borrow_id = ?");
        $stmt->bind_param("i", $borrow_id);
        $stmt->execute();
        $stmt->close();

        // Create success notification
        $notification_title = "Extension Payment Successful";
        $notification_message = "Your payment of ₱" . number_format($fine, 2) . " for the extension of '{$book_title}' has been processed successfully.";
        
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, related_id) VALUES (?, ?, ?, 'extension_payment_success', ?)");
        $stmt->bind_param("issi", $user_id, $notification_title, $notification_message, $fine_id);
        $stmt->execute();
        $stmt->close();

        // Clear session data if it exists
        if (isset($_SESSION['extension_payment'])) {
            unset($_SESSION['extension_payment']);
        }

        $_SESSION['payment_success'] = "Extension payment successful. Your extension is now active.";
        header("Location: extension-payment-success.php?payment_id=$payment_id");
        exit;
    } else {
        // Handle regular overdue payment
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
        $payment_type_db = 'fine';
        $payment_method = 'cash'; // or get from user input if needed
        $status = 'paid';
        $reference_id = $fine_id;
        $stmt = $conn->prepare("INSERT INTO payments (user_id, amount, payment_type, reference_id, payment_method, payment_date, status) VALUES (?, ?, ?, ?, ?, NOW(), ?)");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("idisss", $user_id, $fine, $payment_type_db, $reference_id, $payment_method, $status);
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
    }
} else {
    $_SESSION['payment_error'] = "Invalid payment request.";
    header("Location: borrow-book.php");
    exit;
}