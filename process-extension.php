<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    header("Location: index.php");
    exit();
}

$request_id = (int)($_POST['request_id'] ?? 0);
$action = $_POST['action'] ?? '';
$fine_amount = (float)($_POST['fine_amount'] ?? 0);

if ($request_id && in_array($action, ['approve', 'deny'])) {
    $new_status = ($action === 'approve') ? 'approved' : 'denied';

    // Get extension request details for notification
    $stmt = $conn->prepare("SELECT er.*, b.title, u.full_name, u.user_id FROM extension_requests er JOIN books b ON er.book_id = b.book_id JOIN users u ON er.user_id = u.user_id WHERE er.request_id = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $extension_request = $result->fetch_assoc();
    $stmt->close();

    if ($extension_request) {
        // Update extension request status
        $stmt = $conn->prepare("UPDATE extension_requests SET status = ? WHERE request_id = ?");
        $stmt->bind_param("si", $new_status, $request_id);
        $stmt->execute();
        $stmt->close();

        // Create notification for the user
        $notification_type = ($action === 'approve') ? 'extension_approved' : 'extension_denied';
        $notification_title = ($action === 'approve') ? 'Extension Request Approved' : 'Extension Request Denied';
        
        if ($action === 'approve') {
            $notification_message = "Your extension request for '{$extension_request['title']}' has been approved. New due date: " . date('M d, Y', strtotime($extension_request['new_return_date'])) . ". Fine amount: ₱" . number_format($fine_amount, 2) . ". Please complete the payment to activate your extension.";
        } else {
            $notification_message = "Your extension request for '{$extension_request['title']}' has been denied. Please return the book by the original due date.";
        }

        $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, related_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isssi", $extension_request['user_id'], $notification_title, $notification_message, $notification_type, $request_id);
        $stmt->execute();
        $stmt->close();

        // If approved, create a pending fine record and update borrow record
        if ($action === 'approve' && $fine_amount > 0) {
            // Find the corresponding borrow record
            $stmt = $conn->prepare("SELECT borrow_id FROM borrow_records WHERE user_id = ? AND book_id = ? AND return_date IS NULL ORDER BY borrow_date DESC LIMIT 1");
            $stmt->bind_param("ii", $extension_request['user_id'], $extension_request['book_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $borrow_record = $result->fetch_assoc();
            $stmt->close();

            if ($borrow_record) {
                // Create fine record with pending status
                $stmt = $conn->prepare("INSERT INTO fines (borrow_id, amount, paid) VALUES (?, ?, 0)");
                $stmt->bind_param("id", $borrow_record['borrow_id'], $fine_amount);
                $stmt->execute();
                $fine_id = $conn->insert_id;
                $stmt->close();

                // Update extension request with fine_id
                $stmt = $conn->prepare("UPDATE extension_requests SET fine_id = ? WHERE request_id = ?");
                $stmt->bind_param("ii", $fine_id, $request_id);
                $stmt->execute();
                $stmt->close();

                // Update borrow record to mark fine as unpaid
                $stmt = $conn->prepare("UPDATE borrow_records SET fine_paid = 0 WHERE borrow_id = ?");
                $stmt->bind_param("i", $borrow_record['borrow_id']);
                $stmt->execute();
                $stmt->close();

                // Update borrow record's due_date to the new_return_date from the extension request
                $stmt = $conn->prepare("UPDATE borrow_records SET due_date = ? WHERE borrow_id = ?");
                $stmt->bind_param("si", $extension_request['new_return_date'], $borrow_record['borrow_id']);
                $stmt->execute();
                $stmt->close();

                // Store extension approval info for payment processing
                $_SESSION['extension_payment'] = [
                    'fine_id' => $fine_id,
                    'borrow_id' => $borrow_record['borrow_id'],
                    'amount' => $fine_amount,
                    'book_title' => $extension_request['title'],
                    'extension_days' => $extension_request['extension_days'],
                    'new_due_date' => $extension_request['new_return_date']
                ];
            }
        }

        // Redirect based on user type
        if ($_SESSION['user_type'] === 'admin') {
            header('Location: extension-requests.php?success=1');
        } else {
            // For students, redirect to payment page if approved, otherwise back to dashboard
            if ($action === 'approve' && $fine_amount > 0) {
                header('Location: extension-payment.php');
            } else {
                header('Location: student_page.php?extension_processed=1');
            }
        }
        exit();
    }
}

header("Location: extension-requests.php");
exit();
