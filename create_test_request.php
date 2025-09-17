<?php
require_once 'config.php';

echo "<h1>Create Test Extension Request</h1>";

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
    
    echo "<h2>Creating Test Extension Request</h2>";
    echo "<p><strong>User:</strong> {$data['full_name']}</p>";
    echo "<p><strong>Book:</strong> {$data['title']}</p>";
    echo "<p><strong>Due Date:</strong> {$data['due_date']}</p>";
    
    // Create test extension request
    $extension_days = 10;
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
        echo "<div style='background: #e8f5e8; padding: 1rem; border-radius: 8px; border-left: 4px solid #28a745;'>";
        echo "<h3>✅ Test Extension Request Created Successfully!</h3>";
        echo "<p><strong>Request ID:</strong> $new_request_id</p>";
        echo "<p><strong>User:</strong> {$data['full_name']}</p>";
        echo "<p><strong>Book:</strong> {$data['title']}</p>";
        echo "<p><strong>Extension Days:</strong> $extension_days</p>";
        echo "<p><strong>Calculated Fine:</strong> ₱" . number_format($calculated_fine, 2) . "</p>";
        echo "<p><strong>New Due Date:</strong> $new_return_date</p>";
        echo "<p><strong>Status:</strong> <span style='color: #e67e22; font-weight: bold;'>PENDING</span></p>";
        echo "</div>";
        
        $stmt->close();
        
        // Show updated counts
        $total_requests = $conn->query("SELECT COUNT(*) as count FROM extension_requests")->fetch_assoc()['count'];
        $pending_requests = $conn->query("SELECT COUNT(*) as count FROM extension_requests WHERE status = 'pending'")->fetch_assoc()['count'];
        
        echo "<h3>Updated System Status</h3>";
        echo "<p><strong>Total Extension Requests:</strong> $total_requests</p>";
        echo "<p><strong>Pending Requests:</strong> $pending_requests</p>";
        
        echo "<div style='background: #fff3cd; padding: 1rem; border-radius: 8px; border-left: 4px solid #ffc107; margin-top: 1rem;'>";
        echo "<h3>🎯 Next Steps:</h3>";
        echo "<ol>";
        echo "<li><strong>Login as Admin:</strong> <a href='login_register.php'>Go to Login Page</a></li>";
        echo "<li><strong>Use credentials:</strong> testadmin@library.com / admin123</li>";
        echo "<li><strong>Go to Extension Requests:</strong> <a href='extension-requests.php'>Admin Extension Requests</a></li>";
        echo "<li><strong>Force Refresh:</strong> Press Ctrl+F5</li>";
        echo "<li><strong>You should see:</strong> The new pending request at the top</li>";
        echo "</ol>";
        echo "</div>";
        
    } else {
        echo "<p style='color: red;'>❌ Failed to create test request: " . $stmt->error . "</p>";
        $stmt->close();
    }
} else {
    echo "<p style='color: orange;'>⚠️ No users available for testing (all have pending requests or no active borrows)</p>";
    echo "<p>All users either have pending extension requests or no active borrows.</p>";
}

echo "<h2>Quick Links</h2>";
echo "<p><a href='extension-requests.php'>Admin Extension Requests</a></p>";
echo "<p><a href='borrow-book-simple.php'>Student Borrow Books</a></p>";
echo "<p><a href='simple_db_check.php'>Check Database</a></p>";
?> 