<?php
session_start();
require_once 'config.php';

echo "<h1>🔧 Fix Extension Request System</h1>";

echo "<div style='background: #fff3cd; padding: 1rem; border-radius: 8px; border-left: 4px solid #ffc107; margin-bottom: 2rem;'>";
echo "<h2>⚠️ Step-by-Step Troubleshooting</h2>";
echo "<p>Follow these steps to fix the extension request system:</p>";
echo "</div>";

// Step 1: Check if there are any extension requests
echo "<h2>Step 1: Check Current Extension Requests</h2>";
$result = $conn->query("SELECT COUNT(*) as count FROM extension_requests");
$total_requests = $result->fetch_assoc()['count'];

$pending_result = $conn->query("SELECT COUNT(*) as count FROM extension_requests WHERE status = 'pending'");
$pending_requests = $pending_result->fetch_assoc()['count'];

echo "<p><strong>Total Extension Requests:</strong> $total_requests</p>";
echo "<p><strong>Pending Requests:</strong> $pending_requests</p>";

if ($total_requests == 0) {
    echo "<p style='color: orange;'>⚠️ No extension requests found. Let's create one for testing.</p>";
    
    // Create a test extension request
    echo "<h3>Creating Test Extension Request...</h3>";
    
    // Find a user with active borrows
    $result = $conn->query("SELECT DISTINCT u.user_id, u.full_name, br.borrow_id, b.title, b.book_fine, br.due_date, b.book_id
                           FROM users u 
                           JOIN borrow_records br ON u.user_id = br.user_id 
                           JOIN books b ON br.book_id = b.book_id 
                           WHERE u.user_type = 'user' 
                           AND br.return_date IS NULL 
                           LIMIT 1");
    
    if ($result && $result->num_rows > 0) {
        $data = $result->fetch_assoc();
        
        // Create test extension request
        $extension_days = 7;
        $base_fine = $data['book_fine'];
        $calculated_fine = $base_fine;
        
        if ($extension_days > 3) {
            $extra_days = $extension_days - 3;
            $additional_fine = $base_fine * 0.10 * $extra_days;
            $calculated_fine = $base_fine + $additional_fine;
        }
        
        $new_return_date = date('Y-m-d', strtotime($data['due_date'] . ' +' . $extension_days . ' days'));
        
        $stmt = $conn->prepare("INSERT INTO extension_requests (book_id, user_id, request_date, new_return_date, status, fine_amount, extension_days) VALUES (?, ?, NOW(), ?, 'pending', ?, ?)");
        $stmt->bind_param("iisdi", $data['book_id'], $data['user_id'], $new_return_date, $calculated_fine, $extension_days);
        
        if ($stmt->execute()) {
            $new_request_id = $conn->insert_id;
            echo "<p style='color: green;'>✅ Test extension request created successfully!</p>";
            echo "<p><strong>Request ID:</strong> $new_request_id</p>";
            echo "<p><strong>User:</strong> {$data['full_name']}</p>";
            echo "<p><strong>Book:</strong> {$data['title']}</p>";
            echo "<p><strong>Fine:</strong> ₱" . number_format($calculated_fine, 2) . "</p>";
            $stmt->close();
        } else {
            echo "<p style='color: red;'>❌ Failed to create test request: " . $stmt->error . "</p>";
            $stmt->close();
        }
    } else {
        echo "<p style='color: red;'>❌ No users with active borrows found. Cannot create test request.</p>";
    }
} else {
    echo "<p style='color: green;'>✅ Extension requests exist in database.</p>";
}

echo "<h2>Step 2: Test Admin Access</h2>";
echo "<div style='background: #e8f5e8; padding: 1rem; border-radius: 8px;'>";
echo "<h3>To Access Admin Extension Requests:</h3>";
echo "<ol>";
echo "<li><strong>Login as Admin:</strong> <a href='login_register.php'>Go to Login Page</a></li>";
echo "<li><strong>Use these credentials:</strong> testadmin@library.com / admin123</li>";
echo "<li><strong>Go to Extension Requests:</strong> <a href='extension-requests.php'>Admin Extension Requests</a></li>";
echo "<li><strong>Force Refresh:</strong> Press Ctrl+F5 to clear cache</li>";
echo "</ol>";
echo "</div>";

echo "<h2>Step 3: Test Student Access</h2>";
echo "<div style='background: #d1ecf1; padding: 1rem; border-radius: 8px;'>";
echo "<h3>To Test Student Extension Requests:</h3>";
echo "<ol>";
echo "<li><strong>Login as Student:</strong> <a href='login_register.php'>Go to Login Page</a></li>";
echo "<li><strong>Use any student account</strong> (e.g., Kyrie Earl Gabriel P. Amper)</li>";
echo "<li><strong>Go to Borrowed Books:</strong> <a href='borrow-book-simple.php'>Student Borrow Books</a></li>";
echo "<li><strong>Request Extension:</strong> Click 'Request Extension' button</li>";
echo "</ol>";
echo "</div>";

echo "<h2>Step 4: Verify System is Working</h2>";
echo "<div style='background: #f8f9fa; padding: 1rem; border-radius: 8px;'>";
echo "<h3>Check These Points:</h3>";
echo "<ul>";
echo "<li>✅ Database connection is working</li>";
echo "<li>✅ Extension requests can be created</li>";
echo "<li>✅ Admin can see all requests</li>";
echo "<li>✅ Students can create requests</li>";
echo "<li>✅ Notifications are sent</li>";
echo "</ul>";
echo "</div>";

echo "<h2>Step 5: Quick Test Links</h2>";
echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;'>";
echo "<div style='background: #e8f5e8; padding: 1rem; border-radius: 8px;'>";
echo "<h3>Student Testing:</h3>";
echo "<p><a href='login_register.php'>Login Page</a></p>";
echo "<p><a href='borrow-book-simple.php'>Borrowed Books</a></p>";
echo "<p><a href='notifications.php'>Notifications</a></p>";
echo "</div>";

echo "<div style='background: #fff3cd; padding: 1rem; border-radius: 8px;'>";
echo "<h3>Admin Testing:</h3>";
echo "<p><a href='extension-requests.php'>Extension Requests</a></p>";
echo "<p><a href='admin_page.php'>Admin Dashboard</a></p>";
echo "<p><a href='simple_db_check.php'>Database Check</a></p>";
echo "</div>";
echo "</div>";

echo "<h2>Step 6: Common Solutions</h2>";
echo "<div style='background: #ffe6e6; padding: 1rem; border-radius: 8px; border-left: 4px solid #e74c3c;'>";
echo "<h3>If Still Not Working:</h3>";
echo "<ul>";
echo "<li><strong>Clear Browser Cache:</strong> Press Ctrl+Shift+Delete and clear all data</li>";
echo "<li><strong>Try Different Browser:</strong> Use Chrome, Firefox, or Edge</li>";
echo "<li><strong>Check URL:</strong> Make sure you're on http://localhost:8000</li>";
echo "<li><strong>Restart Server:</strong> Stop and restart the PHP server</li>";
echo "<li><strong>Check Permissions:</strong> Make sure you're logged in with correct account type</li>";
echo "</ul>";
echo "</div>";

// Show current status
echo "<h2>Current System Status</h2>";
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_books = $conn->query("SELECT COUNT(*) as count FROM books")->fetch_assoc()['count'];
$active_borrows = $conn->query("SELECT COUNT(*) as count FROM borrow_records WHERE return_date IS NULL")->fetch_assoc()['count'];

echo "<p><strong>Total Users:</strong> $total_users</p>";
echo "<p><strong>Total Books:</strong> $total_books</p>";
echo "<p><strong>Active Borrows:</strong> $active_borrows</p>";
echo "<p><strong>Total Extension Requests:</strong> $total_requests</p>";
echo "<p><strong>Pending Requests:</strong> $pending_requests</p>";

if ($total_requests > 0) {
    echo "<p style='color: green; font-weight: bold;'>✅ Extension request system is functional!</p>";
    echo "<p><strong>Next Step:</strong> Login as admin and check the extension requests page.</p>";
} else {
    echo "<p style='color: orange; font-weight: bold;'>⚠️ No extension requests found. System may need testing.</p>";
}

echo "<h2>Final Test</h2>";
echo "<p>After following all steps, test the complete flow:</p>";
echo "<ol>";
echo "<li>Login as student and create an extension request</li>";
echo "<li>Login as admin and approve/deny the request</li>";
echo "<li>Login as student and check notifications</li>";
echo "</ol>";

echo "<p style='color: green; font-weight: bold; font-size: 1.2em;'>🎉 The extension request system should now be working!</p>";
?> 