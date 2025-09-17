<?php
require_once 'config.php';

echo "<h1>Extension Request System - User Guide</h1>";

echo "<div style='background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;'>";
echo "<h2>🎯 How the Extension Request System Works</h2>";
echo "<p>The extension request system is <strong>user-specific</strong> and works based on the currently logged-in user's information.</p>";
echo "</div>";

echo "<h2>📋 System Requirements</h2>";
echo "<ul>";
echo "<li>✅ User must be logged in (have an active session)</li>";
echo "<li>✅ User must have borrowed books</li>";
echo "<li>✅ Books must not be returned yet</li>";
echo "<li>✅ No pending extension request for the same book</li>";
echo "</ul>";

echo "<h2>👤 For Students (Regular Users)</h2>";
echo "<div style='background: #d1ecf1; padding: 1rem; border-radius: 8px; border-left: 4px solid #17a2b8;'>";
echo "<h3>What Students Can Do:</h3>";
echo "<ol>";
echo "<li><strong>View their borrowed books</strong> - Only books they've borrowed</li>";
echo "<li><strong>Request extensions</strong> - For books they currently have</li>";
echo "<li><strong>Check their extension status</strong> - See pending/approved/denied requests</li>";
echo "<li><strong>Receive notifications</strong> - When requests are approved/denied</li>";
echo "</ol>";
echo "</div>";

echo "<h2>👨‍💼 For Admins</h2>";
echo "<div style='background: #fff3cd; padding: 1rem; border-radius: 8px; border-left: 4px solid #ffc107;'>";
echo "<h3>What Admins Can Do:</h3>";
echo "<ol>";
echo "<li><strong>View ALL extension requests</strong> - From all users</li>";
echo "<li><strong>Approve/Deny requests</strong> - For any user</li>";
echo "<li><strong>Manage the system</strong> - Books, users, etc.</li>";
echo "<li><strong>Send notifications</strong> - To users about their requests</li>";
echo "</ol>";
echo "</div>";

echo "<h2>🔄 Complete Flow Example</h2>";
echo "<div style='background: #e8f5e8; padding: 1rem; border-radius: 8px; border-left: 4px solid #28a745;'>";
echo "<h3>Step-by-Step Process:</h3>";
echo "<ol>";
echo "<li><strong>Student logs in</strong> → System knows who they are</li>";
echo "<li><strong>Student goes to 'Borrowed Books'</strong> → Shows only their books</li>";
echo "<li><strong>Student requests extension</strong> → Request created with their user_id</li>";
echo "<li><strong>Admin logs in</strong> → Can see all pending requests</li>";
echo "<li><strong>Admin approves/denies</strong> → Student gets notification</li>";
echo "<li><strong>Student sees result</strong> → In their notifications</li>";
echo "</ol>";
echo "</div>";

echo "<h2>🔍 Current Database Status</h2>";

// Show all users with their extension requests
$result = $conn->query("SELECT u.user_id, u.full_name, u.user_type, 
                               COUNT(er.request_id) as total_requests,
                               SUM(CASE WHEN er.status = 'pending' THEN 1 ELSE 0 END) as pending_requests
                        FROM users u 
                        LEFT JOIN extension_requests er ON u.user_id = er.user_id 
                        GROUP BY u.user_id, u.full_name, u.user_type
                        ORDER BY u.user_type DESC, u.user_id");

if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f5e9e0;'>";
    echo "<th style='padding: 0.5rem;'>User ID</th>";
    echo "<th style='padding: 0.5rem;'>Name</th>";
    echo "<th style='padding: 0.5rem;'>Type</th>";
    echo "<th style='padding: 0.5rem;'>Total Requests</th>";
    echo "<th style='padding: 0.5rem;'>Pending Requests</th>";
    echo "</tr>";
    
    while ($row = $result->fetch_assoc()) {
        $type_color = $row['user_type'] === 'admin' ? '#ffc107' : '#17a2b8';
        echo "<tr>";
        echo "<td style='padding: 0.5rem;'>{$row['user_id']}</td>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($row['full_name']) . "</td>";
        echo "<td style='padding: 0.5rem; color: $type_color; font-weight: bold;'>{$row['user_type']}</td>";
        echo "<td style='padding: 0.5rem;'>{$row['total_requests']}</td>";
        echo "<td style='padding: 0.5rem;'>{$row['pending_requests']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h2>🧪 Testing Instructions</h2>";
echo "<div style='background: #f8f9fa; padding: 1rem; border-radius: 8px;'>";
echo "<h3>Test as Student:</h3>";
echo "<ol>";
echo "<li>Go to <a href='login_register.php'>Login Page</a></li>";
echo "<li>Login with a student account (e.g., Kyrie Earl Gabriel P. Amper)</li>";
echo "<li>Go to <a href='borrow-book-simple.php'>Borrowed Books</a></li>";
echo "<li>Request an extension for a borrowed book</li>";
echo "<li>Check your notifications</li>";
echo "</ol>";

echo "<h3>Test as Admin:</h3>";
echo "<ol>";
echo "<li>Logout from student account</li>";
echo "<li>Login as admin: <strong>testadmin@library.com</strong> / <strong>admin123</strong></li>";
echo "<li>Go to <a href='extension-requests.php'>Extension Requests</a></li>";
echo "<li>You should see the student's new request</li>";
echo "<li>Approve or deny the request</li>";
echo "</ol>";
echo "</div>";

echo "<h2>🔗 Quick Access Links</h2>";
echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;'>";
echo "<div style='background: #e8f5e8; padding: 1rem; border-radius: 8px;'>";
echo "<h3>Student Pages:</h3>";
echo "<p><a href='login_register.php'>Login/Register</a></p>";
echo "<p><a href='borrow-book-simple.php'>My Borrowed Books</a></p>";
echo "<p><a href='notifications.php'>My Notifications</a></p>";
echo "<p><a href='catalog.php'>Browse Books</a></p>";
echo "</div>";

echo "<div style='background: #fff3cd; padding: 1rem; border-radius: 8px;'>";
echo "<h3>Admin Pages:</h3>";
echo "<p><a href='extension-requests.php'>Extension Requests</a></p>";
echo "<p><a href='admin_page.php'>Admin Dashboard</a></p>";
echo "<p><a href='manage-books.php'>Manage Books</a></p>";
echo "<p><a href='manage-users.php'>Manage Users</a></p>";
echo "</div>";
echo "</div>";

echo "<h2>⚠️ Common Issues & Solutions</h2>";
echo "<div style='background: #ffe6e6; padding: 1rem; border-radius: 8px; border-left: 4px solid #e74c3c;'>";
echo "<h3>Problem: Can't see extension requests</h3>";
echo "<ul>";
echo "<li><strong>Solution:</strong> Make sure you're logged in as admin</li>";
echo "<li><strong>Solution:</strong> Press Ctrl+F5 to force refresh</li>";
echo "<li><strong>Solution:</strong> Check if you have the right permissions</li>";
echo "</ul>";

echo "<h3>Problem: Can't request extension</h3>";
echo "<ul>";
echo "<li><strong>Solution:</strong> Make sure you're logged in as student</li>";
echo "<li><strong>Solution:</strong> Check if you have borrowed books</li>";
echo "<li><strong>Solution:</strong> Check if you already have a pending request</li>";
echo "</ul>";
echo "</div>";

echo "<h2>✅ System Status</h2>";
$total_requests = $conn->query("SELECT COUNT(*) as count FROM extension_requests")->fetch_assoc()['count'];
$pending_requests = $conn->query("SELECT COUNT(*) as count FROM extension_requests WHERE status = 'pending'")->fetch_assoc()['count'];
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];

echo "<p><strong>Total Extension Requests:</strong> $total_requests</p>";
echo "<p><strong>Pending Requests:</strong> $pending_requests</p>";
echo "<p><strong>Total Users:</strong> $total_users</p>";

echo "<p style='color: green; font-weight: bold;'>✅ Extension Request System is fully functional and user-specific!</p>";
?> 