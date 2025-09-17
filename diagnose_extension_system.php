<?php
session_start();
require_once 'config.php';

echo "<h1>🔍 Extension Request System Diagnostic</h1>";

// 1. Check Database Connection
echo "<h2>1. Database Connection</h2>";
if (!$conn) {
    echo "<p style='color: red;'>❌ Database connection failed</p>";
    exit();
}
echo "<p style='color: green;'>✅ Database connection successful</p>";

// 2. Check Session Status
echo "<h2>2. Session Status</h2>";
if (!isset($_SESSION['user_id'])) {
    echo "<p style='color: red;'>❌ No user logged in</p>";
    echo "<p><strong>Solution:</strong> <a href='login_register.php'>Login first</a></p>";
} else {
    echo "<p style='color: green;'>✅ User logged in</p>";
    echo "<p><strong>User ID:</strong> {$_SESSION['user_id']}</p>";
    echo "<p><strong>User Type:</strong> " . ($_SESSION['user_type'] ?? 'Not set') . "</p>";
}

// 3. Check Current Extension Requests
echo "<h2>3. Current Extension Requests</h2>";
$result = $conn->query("SELECT COUNT(*) as count FROM extension_requests");
$total_requests = $result->fetch_assoc()['count'];

$pending_result = $conn->query("SELECT COUNT(*) as count FROM extension_requests WHERE status = 'pending'");
$pending_requests = $pending_result->fetch_assoc()['count'];

echo "<p><strong>Total Extension Requests:</strong> $total_requests</p>";
echo "<p><strong>Pending Requests:</strong> $pending_requests</p>";

if ($total_requests > 0) {
    echo "<h3>Latest Extension Requests:</h3>";
    $result = $conn->query("SELECT er.*, b.title, u.full_name 
                           FROM extension_requests er 
                           JOIN books b ON er.book_id = b.book_id 
                           JOIN users u ON er.user_id = u.user_id 
                           ORDER BY er.request_date DESC 
                           LIMIT 3");
    
    if ($result && $result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f5e9e0;'>";
        echo "<th style='padding: 0.5rem;'>ID</th>";
        echo "<th style='padding: 0.5rem;'>User</th>";
        echo "<th style='padding: 0.5rem;'>Book</th>";
        echo "<th style='padding: 0.5rem;'>Status</th>";
        echo "<th style='padding: 0.5rem;'>Date</th>";
        echo "</tr>";
        
        while ($row = $result->fetch_assoc()) {
            $status_color = $row['status'] === 'pending' ? '#e67e22' : ($row['status'] === 'approved' ? '#2ecc71' : '#e74c3c');
            echo "<tr>";
            echo "<td style='padding: 0.5rem;'>{$row['request_id']}</td>";
            echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($row['full_name']) . "</td>";
            echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($row['title']) . "</td>";
            echo "<td style='padding: 0.5rem; color: $status_color; font-weight: bold;'>{$row['status']}</td>";
            echo "<td style='padding: 0.5rem;'>{$row['request_date']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}

// 4. Check Available Users for Testing
echo "<h2>4. Available Users for Testing</h2>";
$result = $conn->query("SELECT u.user_id, u.full_name, u.user_type, 
                               COUNT(br.borrow_id) as active_borrows
                        FROM users u 
                        LEFT JOIN borrow_records br ON u.user_id = br.user_id AND br.return_date IS NULL
                        GROUP BY u.user_id, u.full_name, u.user_type
                        ORDER BY u.user_type DESC, u.user_id");

if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f5e9e0;'>";
    echo "<th style='padding: 0.5rem;'>ID</th>";
    echo "<th style='padding: 0.5rem;'>Name</th>";
    echo "<th style='padding: 0.5rem;'>Type</th>";
    echo "<th style='padding: 0.5rem;'>Active Borrows</th>";
    echo "<th style='padding: 0.5rem;'>Can Test</th>";
    echo "</tr>";
    
    while ($row = $result->fetch_assoc()) {
        $can_test = ($row['user_type'] === 'user' && $row['active_borrows'] > 0) ? "✅ Yes" : "❌ No";
        $type_color = $row['user_type'] === 'admin' ? '#ffc107' : '#17a2b8';
        
        echo "<tr>";
        echo "<td style='padding: 0.5rem;'>{$row['user_id']}</td>";
        echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($row['full_name']) . "</td>";
        echo "<td style='padding: 0.5rem; color: $type_color; font-weight: bold;'>{$row['user_type']}</td>";
        echo "<td style='padding: 0.5rem;'>{$row['active_borrows']}</td>";
        echo "<td style='padding: 0.5rem;'>$can_test</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 5. Test Extension Request Creation
echo "<h2>5. Test Extension Request Creation</h2>";
if (isset($_POST['test_create_request'])) {
    // Find a user who can create an extension request
    $result = $conn->query("SELECT DISTINCT u.user_id, u.full_name, br.borrow_id, b.title, b.book_fine, br.due_date, b.book_id
                           FROM users u 
                           JOIN borrow_records br ON u.user_id = br.user_id 
                           JOIN books b ON br.book_id = b.book_id 
                           WHERE u.user_type = 'user' 
                           AND br.return_date IS NULL 
                           AND NOT EXISTS (
                               SELECT 1 FROM extension_requests er 
                               WHERE er.user_id = u.user_id 
                               AND er.book_id = b.book_id 
                               AND er.status = 'pending'
                           )
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
        echo "<p style='color: orange;'>⚠️ No users available for testing (all have pending requests or no active borrows)</p>";
    }
} else {
    echo "<form method='post'>";
    echo "<button type='submit' name='test_create_request' style='background: #007bff; color: white; padding: 0.5rem 1rem; border: none; border-radius: 4px; cursor: pointer;'>Create Test Extension Request</button>";
    echo "</form>";
}

// 6. Check Admin Access
echo "<h2>6. Admin Access Test</h2>";
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
    echo "<p style='color: green;'>✅ You are logged in as admin</p>";
    echo "<p><a href='extension-requests.php'>Go to Extension Requests Page</a></p>";
} else {
    echo "<p style='color: orange;'>⚠️ You are not logged in as admin</p>";
    echo "<p><strong>Admin Login:</strong> testadmin@library.com / admin123</p>";
    echo "<p><a href='login_register.php'>Login Page</a></p>";
}

// 7. Common Issues and Solutions
echo "<h2>7. Common Issues & Solutions</h2>";
echo "<div style='background: #f8f9fa; padding: 1rem; border-radius: 8px;'>";
echo "<h3>Issue: Can't see extension requests on admin page</h3>";
echo "<ul>";
echo "<li><strong>Solution 1:</strong> Make sure you're logged in as admin</li>";
echo "<li><strong>Solution 2:</strong> Press Ctrl+F5 to force refresh the page</li>";
echo "<li><strong>Solution 3:</strong> Clear browser cache</li>";
echo "<li><strong>Solution 4:</strong> Try a different browser</li>";
echo "</ul>";

echo "<h3>Issue: Can't create extension requests</h3>";
echo "<ul>";
echo "<li><strong>Solution 1:</strong> Make sure you're logged in as a student</li>";
echo "<li><strong>Solution 2:</strong> Check if you have borrowed books</li>";
echo "<li><strong>Solution 3:</strong> Check if you already have a pending request for that book</li>";
echo "<li><strong>Solution 4:</strong> Make sure the book hasn't been returned</li>";
echo "</ul>";
echo "</div>";

// 8. Quick Actions
echo "<h2>8. Quick Actions</h2>";
echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;'>";
echo "<div style='background: #e8f5e8; padding: 1rem; border-radius: 8px;'>";
echo "<h3>Student Actions:</h3>";
echo "<p><a href='login_register.php'>Login as Student</a></p>";
echo "<p><a href='borrow-book-simple.php'>View Borrowed Books</a></p>";
echo "<p><a href='notifications.php'>Check Notifications</a></p>";
echo "</div>";

echo "<div style='background: #fff3cd; padding: 1rem; border-radius: 8px;'>";
echo "<h3>Admin Actions:</h3>";
echo "<p><a href='extension-requests.php'>View Extension Requests</a></p>";
echo "<p><a href='admin_page.php'>Admin Dashboard</a></p>";
echo "<p><a href='simple_db_check.php'>Check Database</a></p>";
echo "</div>";
echo "</div>";

echo "<h2>9. System Status Summary</h2>";
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_books = $conn->query("SELECT COUNT(*) as count FROM books")->fetch_assoc()['count'];
$total_borrows = $conn->query("SELECT COUNT(*) as count FROM borrow_records WHERE return_date IS NULL")->fetch_assoc()['count'];

echo "<p><strong>Total Users:</strong> $total_users</p>";
echo "<p><strong>Total Books:</strong> $total_books</p>";
echo "<p><strong>Active Borrows:</strong> $total_borrows</p>";
echo "<p><strong>Total Extension Requests:</strong> $total_requests</p>";
echo "<p><strong>Pending Requests:</strong> $pending_requests</p>";

if ($total_requests > 0 && $pending_requests > 0) {
    echo "<p style='color: green; font-weight: bold;'>✅ Extension request system is working!</p>";
} else {
    echo "<p style='color: orange; font-weight: bold;'>⚠️ No pending extension requests found</p>";
}
?> 