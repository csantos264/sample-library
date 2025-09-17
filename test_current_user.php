<?php
session_start();
require_once 'config.php';

echo "<h1>Current User Extension Request System</h1>";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<div style='background: #ffe6e6; padding: 1rem; border-radius: 8px; border-left: 4px solid #e74c3c;'>";
    echo "<h3>❌ No User Logged In</h3>";
    echo "<p>You need to be logged in to use the extension request system.</p>";
    echo "<p><a href='login_register.php'>Go to Login Page</a></p>";
    echo "</div>";
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$user_type = $_SESSION['user_type'] ?? 'unknown';

echo "<div style='background: #e8f5e8; padding: 1rem; border-radius: 8px; border-left: 4px solid #28a745;'>";
echo "<h3>✅ User Session Active</h3>";
echo "<p><strong>User ID:</strong> $user_id</p>";
echo "<p><strong>User Type:</strong> $user_type</p>";
echo "</div>";

// Get user details
$stmt = $conn->prepare("SELECT full_name, email FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($user) {
    echo "<h2>User Information</h2>";
    echo "<p><strong>Name:</strong> " . htmlspecialchars($user['full_name']) . "</p>";
    echo "<p><strong>Email:</strong> " . htmlspecialchars($user['email']) . "</p>";
} else {
    echo "<p style='color: red;'>❌ User not found in database</p>";
    exit();
}

// Check user's borrowed books
echo "<h2>Your Borrowed Books</h2>";
$stmt = $conn->prepare("SELECT b.title, br.borrow_date, br.due_date, br.return_date, b.book_fine, br.borrow_id, br.fine_paid
                       FROM borrow_records br
                       JOIN books b ON br.book_id = b.book_id
                       WHERE br.user_id = ?
                       ORDER BY br.borrow_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f5e9e0;'>";
    echo "<th style='padding: 0.5rem;'>Book Title</th>";
    echo "<th style='padding: 0.5rem;'>Borrow Date</th>";
    echo "<th style='padding: 0.5rem;'>Due Date</th>";
    echo "<th style='padding: 0.5rem;'>Status</th>";
    echo "<th style='padding: 0.5rem;'>Extension Status</th>";
    echo "<th style='padding: 0.5rem;'>Can Request Extension</th>";
    echo "</tr>";
    
    while ($row = $result->fetch_assoc()) {
        $status = $row['return_date'] ? 'Returned' : (strtotime($row['due_date']) < time() ? 'Overdue' : 'Borrowed');
        $status_color = $row['return_date'] ? '#388e3c' : (strtotime($row['due_date']) < time() ? '#b71c1c' : '#C5832B');
        
        // Check if extension already requested
        $ext_stmt = $conn->prepare("SELECT status FROM extension_requests WHERE user_id = ? AND book_id = (SELECT book_id FROM borrow_records WHERE borrow_id = ?) AND status = 'pending'");
        $ext_stmt->bind_param("ii", $user_id, $row['borrow_id']);
        $ext_stmt->execute();
        $ext_result = $ext_stmt->get_result();
        $ext_status = $ext_result->num_rows > 0 ? 'Pending' : 'None';
        $ext_stmt->close();
        
        $can_request = (!$row['return_date'] && $ext_status === 'None') ? "✅ Yes" : "❌ No";
        
        echo "<tr>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($row['title']) . "</td>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($row['borrow_date']) . "</td>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($row['due_date']) . "</td>";
        echo "<td style='padding: 0.5rem; color: $status_color; font-weight: bold;'>$status</td>";
        echo "<td style='padding: 0.5rem;'>$ext_status</td>";
        echo "<td style='padding: 0.5rem;'>$can_request</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>⚠️ You have no borrowed books.</p>";
}

// Check user's extension requests
echo "<h2>Your Extension Requests</h2>";
$stmt = $conn->prepare("SELECT er.*, b.title 
                       FROM extension_requests er
                       JOIN books b ON er.book_id = b.book_id
                       WHERE er.user_id = ?
                       ORDER BY er.request_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f5e9e0;'>";
    echo "<th style='padding: 0.5rem;'>Request ID</th>";
    echo "<th style='padding: 0.5rem;'>Book</th>";
    echo "<th style='padding: 0.5rem;'>Extension Days</th>";
    echo "<th style='padding: 0.5rem;'>Fine Amount</th>";
    echo "<th style='padding: 0.5rem;'>Status</th>";
    echo "<th style='padding: 0.5rem;'>New Due Date</th>";
    echo "<th style='padding: 0.5rem;'>Request Date</th>";
    echo "</tr>";
    
    while ($row = $result->fetch_assoc()) {
        $status_color = $row['status'] === 'pending' ? '#e67e22' : ($row['status'] === 'approved' ? '#2ecc71' : '#e74c3c');
        
        echo "<tr>";
        echo "<td style='padding: 0.5rem;'>{$row['request_id']}</td>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($row['title']) . "</td>";
        echo "<td style='padding: 0.5rem;'>{$row['extension_days']} days</td>";
        echo "<td style='padding: 0.5rem;'>₱" . number_format($row['fine_amount'], 2) . "</td>";
        echo "<td style='padding: 0.5rem; color: $status_color; font-weight: bold;'>" . ucfirst($row['status']) . "</td>";
        echo "<td style='padding: 0.5rem;'>{$row['new_return_date']}</td>";
        echo "<td style='padding: 0.5rem;'>{$row['request_date']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: blue;'>ℹ️ You have no extension requests.</p>";
}

// Show what the user can do
echo "<h2>What You Can Do</h2>";

if ($user_type === 'admin') {
    echo "<div style='background: #fff3cd; padding: 1rem; border-radius: 8px; border-left: 4px solid #ffc107;'>";
    echo "<h3>👨‍💼 Admin Actions</h3>";
    echo "<ul>";
    echo "<li><a href='extension-requests.php'>View All Extension Requests</a></li>";
    echo "<li><a href='admin_page.php'>Admin Dashboard</a></li>";
    echo "<li>Approve/Deny extension requests</li>";
    echo "<li>Manage books and users</li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div style='background: #d1ecf1; padding: 1rem; border-radius: 8px; border-left: 4px solid #17a2b8;'>";
    echo "<h3>👤 Student Actions</h3>";
    echo "<ul>";
    echo "<li><a href='borrow-book-simple.php'>View Your Borrowed Books</a></li>";
    echo "<li>Request extensions for borrowed books</li>";
    echo "<li><a href='notifications.php'>Check Notifications</a></li>";
    echo "<li><a href='catalog.php'>Browse Books</a></li>";
    echo "</ul>";
    echo "</div>";
}

echo "<h2>Test Extension Request Creation</h2>";
echo "<p>To test creating an extension request:</p>";
echo "<ol>";
echo "<li>Go to <a href='borrow-book-simple.php'>Your Borrowed Books</a></li>";
echo "<li>Find a book you've borrowed (not returned)</li>";
echo "<li>Click 'Request Extension' button</li>";
echo "<li>Select number of days</li>";
echo "<li>Submit the request</li>";
echo "</ol>";

echo "<h2>Quick Links</h2>";
echo "<p><a href='borrow-book-simple.php'>My Borrowed Books</a></p>";
echo "<p><a href='notifications.php'>My Notifications</a></p>";
echo "<p><a href='catalog.php'>Browse Books</a></p>";
if ($user_type === 'admin') {
    echo "<p><a href='extension-requests.php'>Admin Extension Requests</a></p>";
    echo "<p><a href='admin_page.php'>Admin Dashboard</a></p>";
}
echo "<p><a href='login_register.php?logout=1'>Logout</a></p>";
?> 