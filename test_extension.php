<?php
// Test script for extension request functionality
session_start();
require_once 'config.php';

echo "<h1>Extension Request Test</h1>";

// Test 1: Check if we can query extension requests
echo "<h2>Test 1: Extension Requests Query</h2>";
try {
    $stmt = $conn->prepare("SELECT er.*, b.title FROM extension_requests er 
                           JOIN books b ON er.book_id = b.book_id 
                           WHERE er.user_id = ? AND er.status = 'pending'");
    if ($stmt) {
        $user_id = 6; // Test user
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->num_rows;
        echo "✅ Found $count pending extension requests for user $user_id<br>";
        
        if ($count > 0) {
            echo "<h3>Pending Requests:</h3>";
            while ($row = $result->fetch_assoc()) {
                echo "- Book: " . htmlspecialchars($row['title']) . "<br>";
                echo "  Request Date: " . $row['request_date'] . "<br>";
                echo "  New Due Date: " . $row['new_return_date'] . "<br>";
                echo "  Fine Amount: ₱" . number_format($row['fine_amount'] ?? 0, 2) . "<br>";
                echo "  Extension Days: " . ($row['extension_days'] ?? 'N/A') . "<br><br>";
            }
        }
        $stmt->close();
    } else {
        echo "❌ Cannot prepare extension requests query<br>";
    }
} catch (Exception $e) {
    echo "❌ Extension requests query failed: " . $e->getMessage() . "<br>";
}

// Test 2: Check notifications
echo "<h2>Test 2: Notifications Query</h2>";
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    if ($stmt) {
        $user_id = 6; // Test user
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];
        echo "✅ Found $count unread notifications for user $user_id<br>";
        $stmt->close();
    } else {
        echo "❌ Cannot prepare notifications query<br>";
    }
} catch (Exception $e) {
    echo "❌ Notifications query failed: " . $e->getMessage() . "<br>";
}

// Test 3: Check pending extension payments
echo "<h2>Test 3: Pending Extension Payments</h2>";
try {
    $stmt = $conn->prepare("SELECT f.fine_id, f.amount, b.title, br.borrow_date, br.due_date 
                           FROM fines f 
                           JOIN borrow_records br ON f.borrow_id = br.borrow_id 
                           JOIN books b ON br.book_id = b.book_id 
                           WHERE br.user_id = ? AND f.paid = 0 
                           AND f.fine_id IN (
                               SELECT er.fine_id FROM extension_requests er 
                               WHERE er.user_id = ? AND er.status = 'approved'
                           )");
    if ($stmt) {
        $user_id = 6; // Test user
        $stmt->bind_param("ii", $user_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->num_rows;
        echo "✅ Found $count pending extension payments for user $user_id<br>";
        
        if ($count > 0) {
            echo "<h3>Pending Payments:</h3>";
            while ($row = $result->fetch_assoc()) {
                echo "- Book: " . htmlspecialchars($row['title']) . "<br>";
                echo "  Amount: ₱" . number_format($row['amount'], 2) . "<br>";
                echo "  Due Date: " . $row['due_date'] . "<br><br>";
            }
        }
        $stmt->close();
    } else {
        echo "❌ Cannot prepare pending payments query<br>";
    }
} catch (Exception $e) {
    echo "❌ Pending payments query failed: " . $e->getMessage() . "<br>";
}

echo "<h2>Test Complete</h2>";
echo "<p>If you see any ❌ errors, please run the fix_database.sql file in your database.</p>";
?> 