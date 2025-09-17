<?php
require_once 'config.php';

$output = [];

$output[] = "=== DATABASE CHECK RESULTS ===";
$output[] = "";

// Check connection
if (!$conn) {
    $output[] = "❌ Database connection failed";
    file_put_contents('db_check_results.txt', implode("\n", $output));
    exit();
}
$output[] = "✅ Database connection successful";
$output[] = "";

// 1. Extension Requests Table Structure
$output[] = "1. EXTENSION_REQUESTS TABLE STRUCTURE:";
$result = $conn->query("DESCRIBE extension_requests");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $output[] = "  - {$row['Field']}: {$row['Type']} (Default: {$row['Default']})";
    }
}
$output[] = "";

// 2. Extension Requests Data
$output[] = "2. EXTENSION_REQUESTS DATA:";
$result = $conn->query("SELECT er.*, b.title, u.full_name 
                       FROM extension_requests er 
                       JOIN books b ON er.book_id = b.book_id 
                       JOIN users u ON er.user_id = u.user_id 
                       ORDER BY er.request_date DESC");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $output[] = "  - ID: {$row['request_id']}, User: {$row['full_name']}, Book: {$row['title']}";
        $output[] = "    Days: {$row['extension_days']}, Fine: ₱{$row['fine_amount']}, Status: {$row['status']}";
        $output[] = "    New Due: {$row['new_return_date']}, Requested: {$row['request_date']}";
        $output[] = "";
    }
} else {
    $output[] = "  No extension requests found.";
    $output[] = "";
}

// 3. Active Borrow Records
$output[] = "3. ACTIVE BORROW RECORDS:";
$result = $conn->query("SELECT br.*, b.title, b.book_fine, u.full_name 
                       FROM borrow_records br 
                       JOIN books b ON br.book_id = b.book_id 
                       JOIN users u ON br.user_id = u.user_id 
                       WHERE br.return_date IS NULL 
                       ORDER BY br.borrow_date DESC");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $status = strtotime($row['due_date']) < time() ? 'OVERDUE' : 'ACTIVE';
        $output[] = "  - Borrow ID: {$row['borrow_id']}, User: {$row['full_name']}, Book: {$row['title']}";
        $output[] = "    Borrow Date: {$row['borrow_date']}, Due Date: {$row['due_date']}, Status: $status";
        $output[] = "    Fine: ₱{$row['book_fine']}, Fine Paid: " . ($row['fine_paid'] ? 'Yes' : 'No');
        $output[] = "";
    }
} else {
    $output[] = "  No active borrow records found.";
    $output[] = "";
}

// 4. Users Summary
$output[] = "4. USERS SUMMARY:";
$result = $conn->query("SELECT user_id, full_name, user_type FROM users ORDER BY user_id");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $output[] = "  - ID: {$row['user_id']}, Name: {$row['full_name']}, Type: {$row['user_type']}";
    }
}
$output[] = "";

// 5. Books Summary
$output[] = "5. BOOKS SUMMARY:";
$result = $conn->query("SELECT book_id, title, book_fine, available_copies FROM books ORDER BY book_id");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $output[] = "  - ID: {$row['book_id']}, Title: {$row['title']}, Fine: ₱{$row['book_fine']}, Available: {$row['available_copies']}";
    }
}
$output[] = "";

// 6. Notifications
$output[] = "6. NOTIFICATIONS:";
$result = $conn->query("SELECT COUNT(*) as count FROM notifications");
$count = $result->fetch_assoc()['count'];
$output[] = "  Total notifications: $count";

if ($count > 0) {
    $result = $conn->query("SELECT notification_id, user_id, title, type, is_read, created_at FROM notifications ORDER BY created_at DESC LIMIT 3");
    while ($row = $result->fetch_assoc()) {
        $output[] = "  - ID: {$row['notification_id']}, User: {$row['user_id']}, Title: {$row['title']}";
        $output[] = "    Type: {$row['type']}, Read: " . ($row['is_read'] ? 'Yes' : 'No') . ", Created: {$row['created_at']}";
    }
}
$output[] = "";

// 7. Fines
$output[] = "7. FINES:";
$result = $conn->query("SELECT COUNT(*) as count FROM fines");
$count = $result->fetch_assoc()['count'];
$output[] = "  Total fines: $count";

if ($count > 0) {
    $result = $conn->query("SELECT f.*, br.borrow_id, u.full_name, b.title 
                           FROM fines f 
                           JOIN borrow_records br ON f.borrow_id = br.borrow_id 
                           JOIN users u ON br.user_id = u.user_id 
                           JOIN books b ON br.book_id = b.book_id 
                           ORDER BY f.fine_id DESC LIMIT 3");
    while ($row = $result->fetch_assoc()) {
        $output[] = "  - Fine ID: {$row['fine_id']}, Borrow ID: {$row['borrow_id']}, User: {$row['full_name']}";
        $output[] = "    Book: {$row['title']}, Amount: ₱{$row['amount']}, Paid: " . ($row['paid'] ? 'Yes' : 'No');
    }
}
$output[] = "";

// 8. Table Counts
$output[] = "8. TABLE RECORD COUNTS:";
$tables = ['users', 'books', 'borrow_records', 'extension_requests', 'notifications', 'fines'];
foreach ($tables as $table) {
    $result = $conn->query("SELECT COUNT(*) as count FROM $table");
    $count = $result->fetch_assoc()['count'];
    $output[] = "  $table: $count records";
}
$output[] = "";

// 9. Test Extension Request Creation
$output[] = "9. TESTING EXTENSION REQUEST CREATION:";
$result = $conn->query("SELECT DISTINCT u.user_id, u.full_name, br.borrow_id, b.title, b.book_fine, br.due_date
                       FROM users u 
                       JOIN borrow_records br ON u.user_id = br.user_id 
                       JOIN books b ON br.book_id = b.book_id 
                       WHERE u.user_type = 'user' 
                       AND br.return_date IS NULL 
                       LIMIT 1");

if ($result && $result->num_rows > 0) {
    $data = $result->fetch_assoc();
    $output[] = "  Found test user: {$data['full_name']} (ID: {$data['user_id']})";
    $output[] = "  Book: {$data['title']} (Fine: ₱{$data['book_fine']})";
    $output[] = "  Borrow ID: {$data['borrow_id']}, Due Date: {$data['due_date']}";
    
    // Check if extension already exists
    $stmt = $conn->prepare("SELECT * FROM extension_requests WHERE user_id = ? AND book_id = (SELECT book_id FROM borrow_records WHERE borrow_id = ?) AND status = 'pending'");
    $stmt->bind_param("ii", $data['user_id'], $data['borrow_id']);
    $stmt->execute();
    $already_requested = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    
    if ($already_requested) {
        $output[] = "  ⚠️ Extension already requested for this book";
    } else {
        $output[] = "  ✅ Can request extension for this book";
    }
} else {
    $output[] = "  ❌ No active borrow records found for testing";
}

$output[] = "";
$output[] = "=== END OF DATABASE CHECK ===";

// Write to file
file_put_contents('db_check_results.txt', implode("\n", $output));
echo "Database check completed. Check db_check_results.txt for results.";
?> 