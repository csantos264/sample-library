<?php
require_once 'config.php';

echo "<h1>Admin Extension Requests Debug</h1>";

// Test the exact query from extension-requests.php
$query = "
    SELECT er.*, b.title, u.full_name
    FROM extension_requests er
    JOIN books b ON er.book_id = b.book_id
    JOIN users u ON er.user_id = u.user_id
    ORDER BY er.request_date DESC
";

echo "<h2>Query:</h2>";
echo "<pre>" . htmlspecialchars($query) . "</pre>";

$result = $conn->query($query);

if (!$result) {
    echo "<p style='color: red;'>❌ Query failed: " . $conn->error . "</p>";
} else {
    echo "<p>✅ Query executed successfully</p>";
    echo "<p>Number of rows: " . $result->num_rows . "</p>";
    
    if ($result->num_rows > 0) {
        echo "<h2>Results:</h2>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Request ID</th><th>User</th><th>Book</th><th>Days</th><th>Fine</th><th>Status</th><th>New Due Date</th><th>Request Date</th></tr>";
        
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
        echo "<p>No extension requests found in the query results.</p>";
    }
}

// Let's also check if there are any extension requests at all
echo "<h2>Direct Extension Requests Check:</h2>";
$simple_query = "SELECT COUNT(*) as count FROM extension_requests";
$simple_result = $conn->query($simple_query);
$count = $simple_result->fetch_assoc()['count'];
echo "<p>Total extension requests in database: $count</p>";

if ($count > 0) {
    $all_requests = $conn->query("SELECT * FROM extension_requests ORDER BY request_date DESC");
    echo "<h3>All Extension Requests:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Request ID</th><th>User ID</th><th>Book ID</th><th>Days</th><th>Fine</th><th>Status</th><th>New Due Date</th><th>Request Date</th></tr>";
    
    while ($row = $all_requests->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['request_id']}</td>";
        echo "<td>{$row['user_id']}</td>";
        echo "<td>{$row['book_id']}</td>";
        echo "<td>{$row['extension_days']}</td>";
        echo "<td>₱" . number_format($row['fine_amount'], 2) . "</td>";
        echo "<td>{$row['status']}</td>";
        echo "<td>{$row['new_return_date']}</td>";
        echo "<td>{$row['request_date']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Check if there are any issues with the JOIN
echo "<h2>JOIN Test:</h2>";

// Test books table
$books_result = $conn->query("SELECT COUNT(*) as count FROM books");
$books_count = $books_result->fetch_assoc()['count'];
echo "<p>Books in database: $books_count</p>";

// Test users table
$users_result = $conn->query("SELECT COUNT(*) as count FROM users");
$users_count = $users_result->fetch_assoc()['count'];
echo "<p>Users in database: $users_count</p>";

// Test if there are any orphaned extension requests
echo "<h2>Orphaned Extension Requests Check:</h2>";
$orphaned_query = "
    SELECT er.request_id, er.user_id, er.book_id
    FROM extension_requests er
    LEFT JOIN books b ON er.book_id = b.book_id
    LEFT JOIN users u ON er.user_id = u.user_id
    WHERE b.book_id IS NULL OR u.user_id IS NULL
";

$orphaned_result = $conn->query($orphaned_query);
if ($orphaned_result && $orphaned_result->num_rows > 0) {
    echo "<p style='color: red;'>❌ Found orphaned extension requests:</p>";
    while ($row = $orphaned_result->fetch_assoc()) {
        echo "<p>Request ID: {$row['request_id']}, User ID: {$row['user_id']}, Book ID: {$row['book_id']}</p>";
    }
} else {
    echo "<p>✅ No orphaned extension requests found</p>";
}

echo "<h2>Navigation:</h2>";
echo "<p><a href='extension-requests.php'>Go to Admin Extension Requests Page</a></p>";
echo "<p><a href='test_extension_working.php'>Test Extension System</a></p>";
?> 