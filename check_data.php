<?php
require_once 'config.php';

echo "=== Extension Requests Data ===\n";
$result = $conn->query("SELECT er.*, b.title, u.full_name 
                       FROM extension_requests er 
                       JOIN books b ON er.book_id = b.book_id 
                       JOIN users u ON er.user_id = u.user_id 
                       ORDER BY er.request_date DESC");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "Request ID: " . $row['request_id'] . "\n";
        echo "User: " . $row['full_name'] . "\n";
        echo "Book: " . $row['title'] . "\n";
        echo "Status: " . $row['status'] . "\n";
        echo "Request Date: " . $row['request_date'] . "\n";
        echo "New Return Date: " . $row['new_return_date'] . "\n";
        echo "Fine Amount: " . ($row['fine_amount'] ?? 'NULL') . "\n";
        echo "Extension Days: " . ($row['extension_days'] ?? 'NULL') . "\n";
        echo "Fine ID: " . ($row['fine_id'] ?? 'NULL') . "\n";
        echo "---\n";
    }
} else {
    echo "No extension requests found or error: " . $conn->error . "\n";
}

echo "\n=== Notifications Data ===\n";
$result = $conn->query("SELECT n.*, u.full_name 
                       FROM notifications n 
                       JOIN users u ON n.user_id = u.user_id 
                       ORDER BY n.created_at DESC");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "Notification ID: " . $row['notification_id'] . "\n";
        echo "User: " . $row['full_name'] . "\n";
        echo "Title: " . $row['title'] . "\n";
        echo "Message: " . $row['message'] . "\n";
        echo "Type: " . ($row['type'] ?? 'NULL') . "\n";
        echo "Is Read: " . ($row['is_read'] ? 'Yes' : 'No') . "\n";
        echo "Created: " . $row['created_at'] . "\n";
        echo "---\n";
    }
} else {
    echo "No notifications found or error: " . $conn->error . "\n";
}

echo "\n=== Pending Extension Requests Count ===\n";
$result = $conn->query("SELECT COUNT(*) as count FROM extension_requests WHERE status = 'pending'");
if ($result) {
    $count = $result->fetch_assoc()['count'];
    echo "Pending extension requests: $count\n";
}

echo "\n=== Unread Notifications Count ===\n";
$result = $conn->query("SELECT COUNT(*) as count FROM notifications WHERE is_read = 0");
if ($result) {
    $count = $result->fetch_assoc()['count'];
    echo "Unread notifications: $count\n";
}
?> 