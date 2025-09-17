<?php
require_once 'config.php';

echo "<h1>Database Structure and Data Check</h1>";

// Check database connection
if (!$conn) {
    echo "<p style='color: red;'>❌ Database connection failed</p>";
    exit();
}
echo "<p>✅ Database connection successful</p>";

// 1. Check extension_requests table structure
echo "<h2>1. Extension Requests Table Structure</h2>";
$result = $conn->query("DESCRIBE extension_requests");
if ($result) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 2. Check extension_requests data
echo "<h2>2. Extension Requests Data</h2>";
$result = $conn->query("SELECT er.*, b.title, u.full_name 
                       FROM extension_requests er 
                       JOIN books b ON er.book_id = b.book_id 
                       JOIN users u ON er.user_id = u.user_id 
                       ORDER BY er.request_date DESC");

if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>User</th><th>Book</th><th>Days</th><th>Fine</th><th>Status</th><th>New Due Date</th><th>Request Date</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['request_id']}</td>";
        echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['title']) . "</td>";
        echo "<td>{$row['extension_days']}</td>";
        echo "<td>₱" . number_format($row['fine_amount'], 2) . "</td>";
        echo "<td>{$row['status']}</td>";
        echo "<td>{$row['new_return_date']}</td>";
        echo "<td>{$row['request_date']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No extension requests found.</p>";
}

// 3. Check notifications table
echo "<h2>3. Notifications Table</h2>";
$result = $conn->query("SELECT COUNT(*) as count FROM notifications");
$count = $result->fetch_assoc()['count'];
echo "<p>Total notifications: $count</p>";

if ($count > 0) {
    $result = $conn->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 5");
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>User ID</th><th>Title</th><th>Type</th><th>Is Read</th><th>Created</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['notification_id']}</td>";
        echo "<td>{$row['user_id']}</td>";
        echo "<td>" . htmlspecialchars($row['title']) . "</td>";
        echo "<td>{$row['type']}</td>";
        echo "<td>" . ($row['is_read'] ? 'Yes' : 'No') . "</td>";
        echo "<td>{$row['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 4. Check borrow_records with active loans
echo "<h2>4. Active Borrow Records</h2>";
$result = $conn->query("SELECT br.*, b.title, b.book_fine, u.full_name 
                       FROM borrow_records br 
                       JOIN books b ON br.book_id = b.book_id 
                       JOIN users u ON br.user_id = u.user_id 
                       WHERE br.return_date IS NULL 
                       ORDER BY br.borrow_date DESC");

if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Borrow ID</th><th>User</th><th>Book</th><th>Borrow Date</th><th>Due Date</th><th>Fine</th><th>Fine Paid</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $status = strtotime($row['due_date']) < time() ? 'Overdue' : 'Active';
        $status_color = strtotime($row['due_date']) < time() ? 'red' : 'green';
        
        echo "<tr>";
        echo "<td>{$row['borrow_id']}</td>";
        echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['title']) . "</td>";
        echo "<td>{$row['borrow_date']}</td>";
        echo "<td>{$row['due_date']}</td>";
        echo "<td>₱{$row['book_fine']}</td>";
        echo "<td>" . ($row['fine_paid'] ? 'Yes' : 'No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No active borrow records found.</p>";
}

// 5. Check users
echo "<h2>5. Users</h2>";
$result = $conn->query("SELECT user_id, full_name, email, user_type, registered_at FROM users ORDER BY user_id");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Type</th><th>Registered</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['user_id']}</td>";
        echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td>{$row['user_type']}</td>";
        echo "<td>{$row['registered_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 6. Check books
echo "<h2>6. Books</h2>";
$result = $conn->query("SELECT book_id, title, author, isbn, book_fine, available_copies FROM books ORDER BY book_id");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Title</th><th>Author</th><th>ISBN</th><th>Fine</th><th>Available</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['book_id']}</td>";
        echo "<td>" . htmlspecialchars($row['title']) . "</td>";
        echo "<td>" . htmlspecialchars($row['author']) . "</td>";
        echo "<td>{$row['isbn']}</td>";
        echo "<td>₱{$row['book_fine']}</td>";
        echo "<td>{$row['available_copies']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 7. Check fines table
echo "<h2>7. Fines Table</h2>";
$result = $conn->query("SELECT f.*, br.borrow_id, u.full_name, b.title 
                       FROM fines f 
                       JOIN borrow_records br ON f.borrow_id = br.borrow_id 
                       JOIN users u ON br.user_id = u.user_id 
                       JOIN books b ON br.book_id = b.book_id 
                       ORDER BY f.fine_id DESC");

if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Fine ID</th><th>Borrow ID</th><th>User</th><th>Book</th><th>Amount</th><th>Paid</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['fine_id']}</td>";
        echo "<td>{$row['borrow_id']}</td>";
        echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['title']) . "</td>";
        echo "<td>₱" . number_format($row['amount'], 2) . "</td>";
        echo "<td>" . ($row['paid'] ? 'Yes' : 'No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No fines found.</p>";
}

// 8. Summary
echo "<h2>8. Database Summary</h2>";
$tables = ['users', 'books', 'borrow_records', 'extension_requests', 'notifications', 'fines'];
foreach ($tables as $table) {
    $result = $conn->query("SELECT COUNT(*) as count FROM $table");
    $count = $result->fetch_assoc()['count'];
    echo "<p><strong>$table:</strong> $count records</p>";
}

echo "<h2>9. Navigation</h2>";
echo "<p><a href='test_extension_working.php'>Test Extension System</a></p>";
echo "<p><a href='borrow-book-simple.php'>Simplified Borrow Book</a></p>";
echo "<p><a href='extension-requests.php'>Extension Requests (Admin)</a></p>";
?> 