<?php
session_start();
require_once 'config.php';

echo "<h1>Extension Request System Test</h1>";

// Test 1: Check database structure
echo "<h2>1. Database Structure Check</h2>";

$tables = ['extension_requests', 'notifications', 'fines', 'payments'];
foreach ($tables as $table) {
    $result = $conn->query("DESCRIBE $table");
    if ($result) {
        echo "✅ $table table exists<br>";
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        echo "Columns: " . implode(', ', $columns) . "<br><br>";
    } else {
        echo "❌ $table table missing or error: " . $conn->error . "<br><br>";
    }
}

// Test 2: Check extension_requests columns
echo "<h2>2. Extension Requests Columns Check</h2>";
$required_columns = ['fine_amount', 'extension_days', 'fine_id'];
$result = $conn->query("DESCRIBE extension_requests");
$existing_columns = [];
while ($row = $result->fetch_assoc()) {
    $existing_columns[] = $row['Field'];
}

foreach ($required_columns as $column) {
    if (in_array($column, $existing_columns)) {
        echo "✅ $column column exists<br>";
    } else {
        echo "❌ $column column missing<br>";
    }
}

// Test 3: Check sample data
echo "<h2>3. Sample Data Check</h2>";

// Check extension requests
$result = $conn->query("SELECT COUNT(*) as count FROM extension_requests");
$count = $result->fetch_assoc()['count'];
echo "Extension requests: $count<br>";

if ($count > 0) {
    $result = $conn->query("SELECT er.*, b.title, u.full_name 
                           FROM extension_requests er 
                           JOIN books b ON er.book_id = b.book_id 
                           JOIN users u ON er.user_id = u.user_id 
                           LIMIT 3");
    while ($row = $result->fetch_assoc()) {
        echo "- Request ID: {$row['request_id']}, Book: {$row['title']}, User: {$row['full_name']}, Status: {$row['status']}<br>";
        echo "  Fine Amount: " . ($row['fine_amount'] ?? 'NULL') . ", Extension Days: " . ($row['extension_days'] ?? 'NULL') . "<br><br>";
    }
}

// Check notifications
$result = $conn->query("SELECT COUNT(*) as count FROM notifications");
$count = $result->fetch_assoc()['count'];
echo "Notifications: $count<br>";

// Test 4: Test extension request creation logic
echo "<h2>4. Extension Request Logic Test</h2>";

// Get a sample borrow record
$result = $conn->query("SELECT br.borrow_id, br.user_id, br.book_id, b.title, b.book_fine 
                       FROM borrow_records br 
                       JOIN books b ON br.book_id = b.book_id 
                       WHERE br.return_date IS NULL 
                       LIMIT 1");

if ($result && $result->num_rows > 0) {
    $borrow = $result->fetch_assoc();
    echo "Sample borrow record found:<br>";
    echo "- Borrow ID: {$borrow['borrow_id']}<br>";
    echo "- Book: {$borrow['title']}<br>";
    echo "- Base Fine: ₱{$borrow['book_fine']}<br>";
    
    // Test fine calculation
    $extension_days = 7;
    $base_fine = $borrow['book_fine'];
    $calculated_fine = $base_fine;
    
    if ($extension_days > 3) {
        $extra_days = $extension_days - 3;
        $additional_fine = $base_fine * 0.10 * $extra_days;
        $calculated_fine = $base_fine + $additional_fine;
    }
    
    echo "- 7-day extension fine: ₱" . number_format($calculated_fine, 2) . "<br>";
    
    // Test if extension already exists
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM extension_requests WHERE user_id = ? AND book_id = ? AND status = 'pending'");
    $stmt->bind_param("ii", $borrow['user_id'], $borrow['book_id']);
    $stmt->execute();
    $existing_count = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();
    
    echo "- Existing pending requests: $existing_count<br>";
    
} else {
    echo "No active borrow records found for testing<br>";
}

echo "<h2>Test Complete</h2>";
echo "<p><a href='run_database_fixes.php'>Run Database Fixes</a></p>";
echo "<p><a href='extension-requests.php'>Go to Extension Requests</a></p>";
echo "<p><a href='borrow-book.php'>Go to Borrow Book</a></p>";
?> 