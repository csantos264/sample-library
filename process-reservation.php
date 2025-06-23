<?php
session_start();
require_once 'config.php';

// Ensure the user is an admin
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    $_SESSION['error_msg'] = "You are not authorized to perform this action.";
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservation_id'], $_POST['action'])) {
    $reservation_id = (int)$_POST['reservation_id'];
    $action = $_POST['action'];

    // Fetch reservation details
    $stmt = $conn->prepare("SELECT user_id, book_id, status FROM reservations WHERE reservation_id = ?");
    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $reservation = $result->fetch_assoc();
    $stmt->close();

    $current_status = (!empty($reservation['status']) && trim($reservation['status']) !== '') ? $reservation['status'] : 'pending';
    if (!$reservation || $current_status !== 'pending') {
        $_SESSION['error_msg'] = "Invalid reservation or action already taken.";
        header('Location: reservation-requests.php');
        exit();
    }

    $book_id = $reservation['book_id'];
    $user_id = $reservation['user_id'];

    if ($action === 'approved') {
        // Start a transaction
        $conn->begin_transaction();

        try {
            // 1. Check if the book has available copies
            $stmt = $conn->prepare("SELECT available_copies FROM books WHERE book_id = ? FOR UPDATE");
            $stmt->bind_param("i", $book_id);
            $stmt->execute();
            $book = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($book['available_copies'] < 1) {
                throw new Exception("Cannot approve reservation. The book is currently unavailable.");
            }

            // 2. Update reservation status atomically
            $stmt = $conn->prepare("UPDATE reservations SET status = 'approved' WHERE reservation_id = ? AND (status = 'pending' OR status IS NULL OR TRIM(status) = '')");
            $stmt->bind_param("i", $reservation_id);
            $stmt->execute();
            if ($stmt->affected_rows < 1) {
                throw new Exception("Reservation could not be updated. It may have already been processed or is no longer pending.");
            }
            $stmt->close();

            // 3. Decrement available copies
            $stmt = $conn->prepare("UPDATE books SET available_copies = available_copies - 1 WHERE book_id = ?");
            $stmt->bind_param("i", $book_id);
            $stmt->execute();
            $stmt->close();

            // 4. Create a new borrow record
            $borrow_date = date('Y-m-d');
            $due_date = date('Y-m-d', strtotime('+14 days'));
            $stmt = $conn->prepare("INSERT INTO borrow_records (user_id, book_id, borrow_date, due_date, status) VALUES (?, ?, ?, ?, 'borrowed')");
            $stmt->bind_param("iiss", $user_id, $book_id, $borrow_date, $due_date);
            $stmt->execute();
            $stmt->close();

            // If all queries succeed, commit the transaction
            $conn->commit();
            $_SESSION['success_msg'] = "Reservation #{$reservation_id} approved successfully. The user can now pick up the book.";

        } catch (Exception $e) {
            // If any query fails, roll back the transaction
            $conn->rollback();
            $_SESSION['error_msg'] = "Failed to approve reservation #{$reservation_id}: " . $e->getMessage();
        }

    } elseif ($action === 'denied') {
        // Simply update the status to 'denied' atomically
        $stmt = $conn->prepare("UPDATE reservations SET status = 'denied' WHERE reservation_id = ? AND (status = 'pending' OR status IS NULL OR TRIM(status) = '')");
        $stmt->bind_param("i", $reservation_id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $_SESSION['success_msg'] = "Reservation #{$reservation_id} has been denied.";
        } else {
            $_SESSION['error_msg'] = "Failed to deny reservation #{$reservation_id}. It may have been processed already. DB Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error_msg'] = "Invalid action specified.";
    }
} else {
    $_SESSION['error_msg'] = "Invalid request.";
}

header('Location: reservation-requests.php');
exit();
?>
