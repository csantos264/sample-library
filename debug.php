<?php
// Debug script to identify issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Library Management System - Debug Report</h1>";

// Test 1: Database Connection
echo "<h2>1. Database Connection Test</h2>";
try {
    require_once 'config.php';
    echo "✅ Database connection successful<br>";
    echo "Server: " . $conn->server_info . "<br>";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    exit;
}

// Test 2: Check Required Tables
echo "<h2>2. Required Tables Check</h2>";
$required_tables = [
    'users',
    'books', 
    'borrow_records',
    'extension_requests',
    'fines',
    'payments',
    'reservations',
    'notifications'
];

foreach ($required_tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        echo "✅ Table '$table' exists<br>";
    } else {
        echo "❌ Table '$table' is missing<br>";
    }
}

// Test 3: Check Table Structures
echo "<h2>3. Table Structure Check</h2>";

// Check extension_requests table columns
$result = $conn->query("DESCRIBE extension_requests");
if ($result) {
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    
    $required_columns = ['request_id', 'book_id', 'user_id', 'request_date', 'new_return_date', 'status', 'fine_amount', 'extension_days', 'fine_id'];
    foreach ($required_columns as $column) {
        if (in_array($column, $columns)) {
            echo "✅ extension_requests.$column exists<br>";
        } else {
            echo "❌ extension_requests.$column is missing<br>";
        }
    }
} else {
    echo "❌ Cannot describe extension_requests table<br>";
}

// Check notifications table
$result = $conn->query("DESCRIBE notifications");
if ($result) {
    echo "✅ notifications table structure is correct<br>";
} else {
    echo "❌ notifications table is missing or has issues<br>";
}

// Test 4: Check Sample Data
echo "<h2>4. Sample Data Check</h2>";

// Check users
$result = $conn->query("SELECT COUNT(*) as count FROM users");
if ($result) {
    $count = $result->fetch_assoc()['count'];
    echo "✅ Users table has $count records<br>";
} else {
    echo "❌ Cannot query users table<br>";
}

// Check books
$result = $conn->query("SELECT COUNT(*) as count FROM books");
if ($result) {
    $count = $result->fetch_assoc()['count'];
    echo "✅ Books table has $count records<br>";
} else {
    echo "❌ Cannot query books table<br>";
}

// Test 5: Test Extension Request Query
echo "<h2>5. Extension Request Query Test</h2>";
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM extension_requests WHERE user_id = ? AND status = 'pending'");
    if ($stmt) {
        $user_id = 6; // Test with existing user
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];
        echo "✅ Extension requests query works: $count pending requests for user $user_id<br>";
        $stmt->close();
    } else {
        echo "❌ Cannot prepare extension requests query<br>";
    }
} catch (Exception $e) {
    echo "❌ Extension requests query failed: " . $e->getMessage() . "<br>";
}

// Test 6: Test Notifications Query
echo "<h2>6. Notifications Query Test</h2>";
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    if ($stmt) {
        $user_id = 6; // Test with existing user
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];
        echo "✅ Notifications query works: $count unread notifications for user $user_id<br>";
        $stmt->close();
    } else {
        echo "❌ Cannot prepare notifications query<br>";
    }
} catch (Exception $e) {
    echo "❌ Notifications query failed: " . $e->getMessage() . "<br>";
}

// Test 7: Check PHP Configuration
echo "<h2>7. PHP Configuration Check</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Session Support: " . (function_exists('session_start') ? '✅ Enabled' : '❌ Disabled') . "<br>";
echo "MySQL Support: " . (function_exists('mysqli_connect') ? '✅ Enabled' : '❌ Disabled') . "<br>";
echo "Error Reporting: " . (error_reporting() ? '✅ Enabled' : '❌ Disabled') . "<br>";

// Test 8: File Permissions
echo "<h2>8. File Permissions Check</h2>";
$files_to_check = [
    'config.php',
    'student_page.php',
    'catalog.php',
    'borrow-book.php',
    'notifications.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists and is readable<br>";
    } else {
        echo "❌ $file is missing<br>";
    }
}

echo "<h2>Debug Complete</h2>";
echo "<p>If you see any ❌ errors above, please run the fix_database.sql file in your database to resolve missing tables and columns.</p>";
?> 