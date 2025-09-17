<?php
require_once 'config.php';

echo "<h1>Extension Request System - Standalone Debug</h1>";

// Check database connection
if (!$conn) {
    echo "<p style='color: red;'>❌ Database connection failed</p>";
    exit();
}
echo "<p>✅ Database connection successful</p>";

// Check if extension_requests table exists and has required columns
echo "<h2>1. Database Structure Check</h2>";
$result = $conn->query("SHOW TABLES LIKE 'extension_requests'");
if ($result->num_rows == 0) {
    echo "<p style='color: red;'>❌ extension_requests table does not exist</p>";
} else {
    echo "<p>✅ extension_requests table exists</p>";
    
    // Check columns
    $result = $conn->query("DESCRIBE extension_requests");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    
    echo "<p><strong>Current columns:</strong> " . implode(', ', $columns) . "</p>";
    
    $required_columns = ['request_id', 'book_id', 'user_id', 'request_date', 'new_return_date', 'status', 'fine_amount', 'extension_days', 'fine_id'];
    foreach ($required_columns as $col) {
        if (in_array($col, $columns)) {
            echo "<p>✅ Column '$col' exists</p>";
        } else {
            echo "<p style='color: red;'>❌ Column '$col' missing</p>";
        }
    }
}

// Check if notifications table exists
echo "<h2>2. Notifications Table Check</h2>";
$result = $conn->query("SHOW TABLES LIKE 'notifications'");
if ($result->num_rows == 0) {
    echo "<p style='color: red;'>❌ notifications table does not exist</p>";
} else {
    echo "<p>✅ notifications table exists</p>";
    
    // Check notifications columns
    $result = $conn->query("DESCRIBE notifications");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    echo "<p><strong>Notifications columns:</strong> " . implode(', ', $columns) . "</p>";
}

// Check borrow_records table
echo "<h2>3. Borrow Records Table Check</h2>";
$result = $conn->query("SHOW TABLES LIKE 'borrow_records'");
if ($result->num_rows == 0) {
    echo "<p style='color: red;'>❌ borrow_records table does not exist</p>";
} else {
    echo "<p>✅ borrow_records table exists</p>";
    
    // Check if fine_paid column exists
    $result = $conn->query("SHOW COLUMNS FROM borrow_records LIKE 'fine_paid'");
    if ($result->num_rows == 0) {
        echo "<p style='color: red;'>❌ fine_paid column missing from borrow_records</p>";
    } else {
        echo "<p>✅ fine_paid column exists in borrow_records</p>";
    }
}

// Check sample data
echo "<h2>4. Sample Data Check</h2>";

// Check users
$result = $conn->query("SELECT COUNT(*) as count FROM users");
$user_count = $result->fetch_assoc()['count'];
echo "<p>Users: $user_count</p>";

if ($user_count > 0) {
    $result = $conn->query("SELECT user_id, full_name, user_type FROM users LIMIT 3");
    echo "<p><strong>Sample users:</strong></p>";
    while ($row = $result->fetch_assoc()) {
        echo "- ID: {$row['user_id']}, Name: " . htmlspecialchars($row['full_name']) . ", Type: {$row['user_type']}<br>";
    }
}

// Check books
$result = $conn->query("SELECT COUNT(*) as count FROM books");
$book_count = $result->fetch_assoc()['count'];
echo "<p>Books: $book_count</p>";

if ($book_count > 0) {
    $result = $conn->query("SELECT book_id, title, book_fine FROM books LIMIT 3");
    echo "<p><strong>Sample books:</strong></p>";
    while ($row = $result->fetch_assoc()) {
        echo "- ID: {$row['book_id']}, Title: " . htmlspecialchars($row['title']) . ", Fine: ₱{$row['book_fine']}<br>";
    }
}

// Check borrow records
$result = $conn->query("SELECT COUNT(*) as count FROM borrow_records");
$borrow_count = $result->fetch_assoc()['count'];
echo "<p>Borrow records: $borrow_count</p>";

if ($borrow_count > 0) {
    $result = $conn->query("SELECT br.borrow_id, br.user_id, br.book_id, br.due_date, br.return_date, b.title 
                           FROM borrow_records br 
                           JOIN books b ON br.book_id = b.book_id 
                           LIMIT 3");
    echo "<p><strong>Sample borrow records:</strong></p>";
    while ($row = $result->fetch_assoc()) {
        $status = $row['return_date'] ? 'Returned' : 'Active';
        echo "- ID: {$row['borrow_id']}, User: {$row['user_id']}, Book: " . htmlspecialchars($row['title']) . ", Status: $status<br>";
    }
}

// Check extension requests
$result = $conn->query("SELECT COUNT(*) as count FROM extension_requests");
$extension_count = $result->fetch_assoc()['count'];
echo "<p>Extension requests: $extension_count</p>";

if ($extension_count > 0) {
    $result = $conn->query("SELECT er.*, b.title, u.full_name 
                           FROM extension_requests er 
                           JOIN books b ON er.book_id = b.book_id 
                           JOIN users u ON er.user_id = u.user_id 
                           LIMIT 3");
    echo "<p><strong>Sample extension requests:</strong></p>";
    while ($row = $result->fetch_assoc()) {
        echo "- ID: {$row['request_id']}, User: " . htmlspecialchars($row['full_name']) . ", Book: " . htmlspecialchars($row['title']) . ", Status: {$row['status']}<br>";
        echo "  Days: " . ($row['extension_days'] ?? 'NULL') . ", Fine: ₱" . number_format($row['fine_amount'] ?? 0, 2) . "<br>";
    }
}

// Test extension request creation logic
echo "<h2>5. Test Extension Request Creation</h2>";

if (isset($_POST['test_extension'])) {
    $user_id = (int)$_POST['user_id'];
    $book_id = (int)$_POST['book_id'];
    $extension_days = (int)$_POST['extension_days'];
    
    echo "<p>Testing extension request creation...</p>";
    echo "<p>User ID: $user_id</p>";
    echo "<p>Book ID: $book_id</p>";
    echo "<p>Extension Days: $extension_days</p>";
    
    // Get book fine
    $stmt = $conn->prepare("SELECT book_fine FROM books WHERE book_id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $book = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$book) {
        echo "<p style='color: red;'>❌ Book not found</p>";
    } else {
        echo "<p>✅ Book found, fine: ₱{$book['book_fine']}</p>";
        
        // Get borrow record
        $stmt = $conn->prepare("SELECT br.*, b.book_fine FROM borrow_records br JOIN books b ON br.book_id = b.book_id WHERE br.user_id = ? AND br.book_id = ? AND br.return_date IS NULL ORDER BY br.borrow_date DESC LIMIT 1");
        $stmt->bind_param("ii", $user_id, $book_id);
        $stmt->execute();
        $borrow = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$borrow) {
            echo "<p style='color: red;'>❌ No active borrow record found</p>";
        } else {
            echo "<p>✅ Borrow record found</p>";
            
            // Check if already requested
            $stmt = $conn->prepare("SELECT * FROM extension_requests WHERE user_id = ? AND book_id = ? AND status = 'pending'");
            $stmt->bind_param("ii", $user_id, $book_id);
            $stmt->execute();
            $already_requested = $stmt->get_result()->num_rows > 0;
            $stmt->close();
            
            if ($already_requested) {
                echo "<p style='color: orange;'>⚠️ Extension already requested for this book</p>";
            } else {
                // Calculate fine
                $base_fine = $borrow['book_fine'];
                $calculated_fine = $base_fine;
                
                if ($extension_days > 3) {
                    $extra_days = $extension_days - 3;
                    $additional_fine = $base_fine * 0.10 * $extra_days;
                    $calculated_fine = $base_fine + $additional_fine;
                }
                
                $new_return_date = date('Y-m-d', strtotime($borrow['due_date'] . ' +' . $extension_days . ' days'));
                
                echo "<p>Base Fine: ₱" . number_format($base_fine, 2) . "</p>";
                echo "<p>Calculated Fine: ₱" . number_format($calculated_fine, 2) . "</p>";
                echo "<p>New Return Date: $new_return_date</p>";
                
                // Try to insert
                $stmt = $conn->prepare("INSERT INTO extension_requests (book_id, user_id, request_date, new_return_date, status, fine_amount, extension_days) VALUES (?, ?, NOW(), ?, 'pending', ?, ?)");
                $stmt->bind_param("iisdi", $book_id, $user_id, $new_return_date, $calculated_fine, $extension_days);
                
                if ($stmt->execute()) {
                    echo "<p style='color: green;'>✅ Extension request created successfully!</p>";
                    echo "<p>Request ID: " . $conn->insert_id . "</p>";
                } else {
                    echo "<p style='color: red;'>❌ Failed to create extension request: " . $stmt->error . "</p>";
                }
                $stmt->close();
            }
        }
    }
}

// Show test form
echo "<h2>6. Test Extension Request Form</h2>";
echo "<form method='post' style='border: 1px solid #ddd; padding: 20px; margin: 20px 0;'>";

// Get available users
$result = $conn->query("SELECT user_id, full_name FROM users WHERE user_type = 'user' LIMIT 5");
echo "<label>User: <select name='user_id' required>";
echo "<option value=''>Select User</option>";
while ($row = $result->fetch_assoc()) {
    echo "<option value='" . $row['user_id'] . "'>" . htmlspecialchars($row['full_name']) . "</option>";
}
echo "</select></label><br><br>";

// Get available books
$result = $conn->query("SELECT book_id, title FROM books LIMIT 5");
echo "<label>Book: <select name='book_id' required>";
echo "<option value=''>Select Book</option>";
while ($row = $result->fetch_assoc()) {
    echo "<option value='" . $row['book_id'] . "'>" . htmlspecialchars($row['title']) . "</option>";
}
echo "</select></label><br><br>";

echo "<label>Extension Days: <input type='number' name='extension_days' min='1' max='30' value='7' required></label><br><br>";
echo "<button type='submit' name='test_extension'>Test Extension Request</button>";
echo "</form>";

// Show current extension requests
echo "<h2>7. Current Extension Requests</h2>";
$result = $conn->query("SELECT er.*, b.title, u.full_name 
                       FROM extension_requests er 
                       JOIN books b ON er.book_id = b.book_id 
                       JOIN users u ON er.user_id = u.user_id 
                       ORDER BY er.request_date DESC");

if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>User</th><th>Book</th><th>Days</th><th>Fine</th><th>Status</th><th>New Due Date</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['request_id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['title']) . "</td>";
        echo "<td>" . ($row['extension_days'] ?? 'N/A') . "</td>";
        echo "<td>₱" . number_format($row['fine_amount'] ?? 0, 2) . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "<td>" . $row['new_return_date'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No extension requests found.</p>";
}

echo "<h2>8. Navigation</h2>";
echo "<p><a href='fix_extension_system.php'>Run Complete Fix</a></p>";
echo "<p><a href='borrow-book-simple.php'>Go to Simplified Borrow Book</a></p>";
echo "<p><a href='extension-requests.php'>Go to Extension Requests (Admin)</a></p>";
?> 