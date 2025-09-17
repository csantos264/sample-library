<?php
require_once 'config.php';

echo "<h1>Extension Request Flow Test</h1>";

// Find available student users with active borrows
$result = $conn->query("SELECT DISTINCT u.user_id, u.full_name, u.email, br.borrow_id, b.title, br.due_date
                       FROM users u 
                       JOIN borrow_records br ON u.user_id = br.user_id 
                       JOIN books b ON br.book_id = b.book_id 
                       WHERE u.user_type = 'user' 
                       AND br.return_date IS NULL 
                       ORDER BY u.user_id");

echo "<h2>Available Student Users with Active Borrows:</h2>";

if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f5e9e0;'>";
    echo "<th style='padding: 0.5rem;'>User ID</th>";
    echo "<th style='padding: 0.5rem;'>Name</th>";
    echo "<th style='padding: 0.5rem;'>Email</th>";
    echo "<th style='padding: 0.5rem;'>Borrowed Book</th>";
    echo "<th style='padding: 0.5rem;'>Due Date</th>";
    echo "<th style='padding: 0.5rem;'>Can Request Extension</th>";
    echo "</tr>";
    
    while ($row = $result->fetch_assoc()) {
        // Check if already has pending extension
        $stmt = $conn->prepare("SELECT * FROM extension_requests WHERE user_id = ? AND book_id = (SELECT book_id FROM borrow_records WHERE borrow_id = ?) AND status = 'pending'");
        $stmt->bind_param("ii", $row['user_id'], $row['borrow_id']);
        $stmt->execute();
        $already_requested = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        
        $can_request = !$already_requested ? "✅ Yes" : "❌ Already Requested";
        
        echo "<tr>";
        echo "<td style='padding: 0.5rem;'>{$row['user_id']}</td>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($row['full_name']) . "</td>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($row['title']) . "</td>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($row['due_date']) . "</td>";
        echo "<td style='padding: 0.5rem;'>$can_request</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>No student users with active borrows found.</p>";
}

echo "<h2>Current Extension Requests Status:</h2>";
$ext_result = $conn->query("SELECT COUNT(*) as total FROM extension_requests");
$total_requests = $ext_result->fetch_assoc()['total'];

$pending_result = $conn->query("SELECT COUNT(*) as pending FROM extension_requests WHERE status = 'pending'");
$pending_requests = $pending_result->fetch_assoc()['pending'];

echo "<p><strong>Total Extension Requests:</strong> $total_requests</p>";
echo "<p><strong>Pending Requests:</strong> $pending_requests</p>";

echo "<h2>Testing Instructions:</h2>";
echo "<div style='background: #f8f9fa; padding: 1rem; border-radius: 8px; border-left: 4px solid #007bff;'>";
echo "<h3>Step 1: Login as Student</h3>";
echo "<ol>";
echo "<li>Go to <a href='login_register.php' target='_blank'>Login Page</a></li>";
echo "<li>Login with one of the student accounts above</li>";
echo "<li>Go to <a href='borrow-book-simple.php' target='_blank'>Borrowed Books Page</a></li>";
echo "</ol>";

echo "<h3>Step 2: Create Extension Request</h3>";
echo "<ol>";
echo "<li>Find a book you've borrowed</li>";
echo "<li>Click 'Request Extension' button</li>";
echo "<li>Select number of days (7, 14, 21, or 30)</li>";
echo "<li>Submit the request</li>";
echo "</ol>";

echo "<h3>Step 3: Login as Admin</h3>";
echo "<ol>";
echo "<li>Logout from student account</li>";
echo "<li>Login as admin: <strong>testadmin@library.com</strong> / <strong>admin123</strong></li>";
echo "<li>Go to <a href='extension-requests.php' target='_blank'>Extension Requests Page</a></li>";
echo "<li>Press <strong>Ctrl+F5</strong> to force refresh</li>";
echo "</ol>";

echo "<h3>Step 4: Verify New Request Appears</h3>";
echo "<ol>";
echo "<li>You should see the new pending request at the top</li>";
echo "<li>Total count should increase by 1</li>";
echo "<li>Pending count should increase by 1</li>";
echo "</ol>";
echo "</div>";

echo "<h2>Quick Test Links:</h2>";
echo "<p><a href='login_register.php'>Login/Register</a></p>";
echo "<p><a href='borrow-book-simple.php'>Student Borrow Books</a></p>";
echo "<p><a href='extension-requests.php'>Admin Extension Requests</a></p>";
echo "<p><a href='simple_db_check.php'>Check Database Status</a></p>";

echo "<h2>Expected Results:</h2>";
echo "<ul>";
echo "<li>✅ Student can create extension request</li>";
echo "<li>✅ Request appears in database</li>";
echo "<li>✅ Admin can see new request</li>";
echo "<li>✅ Admin can approve/deny request</li>";
echo "<li>✅ Student gets notification</li>";
echo "</ul>";
?> 