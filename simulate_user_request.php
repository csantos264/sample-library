<?php
require_once 'config.php';

echo "<h1>Simulate User Extension Request</h1>";

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
    
    echo "<h2>Step 1: Found User to Create Extension Request</h2>";
    echo "<p>✅ <strong>User:</strong> {$data['full_name']} (ID: {$data['user_id']})</p>";
    echo "<p>✅ <strong>Book:</strong> {$data['title']} (Fine: ₱{$data['book_fine']})</p>";
    echo "<p>✅ <strong>Borrow ID:</strong> {$data['borrow_id']}</p>";
    echo "<p>✅ <strong>Due Date:</strong> {$data['due_date']}</p>";
    
    // Get current extension request count
    $before_count = $conn->query("SELECT COUNT(*) as count FROM extension_requests")->fetch_assoc()['count'];
    $before_pending = $conn->query("SELECT COUNT(*) as count FROM extension_requests WHERE status = 'pending'")->fetch_assoc()['count'];
    
    echo "<h2>Step 2: Current Extension Requests Status</h2>";
    echo "<p><strong>Total Requests:</strong> $before_count</p>";
    echo "<p><strong>Pending Requests:</strong> $before_pending</p>";
    
    // Simulate creating extension request
    echo "<h2>Step 3: Creating Extension Request</h2>";
    
    $extension_days = 14; // 14 days extension
    $base_fine = $data['book_fine'];
    $calculated_fine = $base_fine;
    
    if ($extension_days > 3) {
        $extra_days = $extension_days - 3;
        $additional_fine = $base_fine * 0.10 * $extra_days; // 10% per day after 3 days
        $calculated_fine = $base_fine + $additional_fine;
    }
    
    $new_return_date = date('Y-m-d', strtotime($data['due_date'] . ' +' . $extension_days . ' days'));
    
    // Create the extension request
    $stmt = $conn->prepare("INSERT INTO extension_requests (book_id, user_id, request_date, new_return_date, status, fine_amount, extension_days) VALUES (?, ?, NOW(), ?, 'pending', ?, ?)");
    $stmt->bind_param("iisdi", $data['book_id'], $data['user_id'], $new_return_date, $calculated_fine, $extension_days);
    
    if ($stmt->execute()) {
        $new_request_id = $conn->insert_id;
        echo "<p style='color: green;'>✅ <strong>Extension request created successfully!</strong></p>";
        echo "<p><strong>Request ID:</strong> $new_request_id</p>";
        echo "<p><strong>Extension Days:</strong> $extension_days</p>";
        echo "<p><strong>Calculated Fine:</strong> ₱" . number_format($calculated_fine, 2) . "</p>";
        echo "<p><strong>New Due Date:</strong> $new_return_date</p>";
        
        $stmt->close();
        
        // Get updated counts
        $after_count = $conn->query("SELECT COUNT(*) as count FROM extension_requests")->fetch_assoc()['count'];
        $after_pending = $conn->query("SELECT COUNT(*) as count FROM extension_requests WHERE status = 'pending'")->fetch_assoc()['count'];
        
        echo "<h2>Step 4: Updated Extension Requests Status</h2>";
        echo "<p><strong>Total Requests:</strong> $after_count (was $before_count)</p>";
        echo "<p><strong>Pending Requests:</strong> $after_pending (was $before_pending)</p>";
        
        if ($after_count > $before_count) {
            echo "<p style='color: green;'>✅ <strong>Total count increased by " . ($after_count - $before_count) . "</strong></p>";
        }
        
        if ($after_pending > $before_pending) {
            echo "<p style='color: green;'>✅ <strong>Pending count increased by " . ($after_pending - $before_pending) . "</strong></p>";
        }
        
        // Test admin query
        echo "<h2>Step 5: Testing Admin Page Query</h2>";
        $admin_query = "
            SELECT er.*, b.title, u.full_name
            FROM extension_requests er
            JOIN books b ON er.book_id = b.book_id
            JOIN users u ON er.user_id = u.user_id
            ORDER BY er.request_date DESC
        ";
        
        $admin_result = $conn->query($admin_query);
        if ($admin_result) {
            echo "<p>✅ <strong>Admin query executed successfully</strong></p>";
            echo "<p><strong>Total results:</strong> {$admin_result->num_rows}</p>";
            
            // Show the latest requests
            echo "<h3>Latest Extension Requests (Admin View):</h3>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr style='background: #f5e9e0;'>";
            echo "<th style='padding: 0.5rem;'>Request ID</th>";
            echo "<th style='padding: 0.5rem;'>User</th>";
            echo "<th style='padding: 0.5rem;'>Book</th>";
            echo "<th style='padding: 0.5rem;'>Days</th>";
            echo "<th style='padding: 0.5rem;'>Fine</th>";
            echo "<th style='padding: 0.5rem;'>Status</th>";
            echo "<th style='padding: 0.5rem;'>Request Date</th>";
            echo "</tr>";
            
            $count = 0;
            while ($row = $admin_result->fetch_assoc() && $count < 3) {
                $highlight = $row['request_id'] == $new_request_id ? 'background-color: #e8f5e8;' : '';
                echo "<tr style='$highlight'>";
                echo "<td style='padding: 0.5rem;'>{$row['request_id']}</td>";
                echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($row['full_name']) . "</td>";
                echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($row['title']) . "</td>";
                echo "<td style='padding: 0.5rem;'>{$row['extension_days']}</td>";
                echo "<td style='padding: 0.5rem;'>₱" . number_format($row['fine_amount'], 2) . "</td>";
                echo "<td style='padding: 0.5rem;'>{$row['status']}</td>";
                echo "<td style='padding: 0.5rem;'>{$row['request_date']}</td>";
                echo "</tr>";
                $count++;
            }
            echo "</table>";
            
            if ($admin_result->num_rows > $before_count) {
                echo "<p style='color: green;'>✅ <strong>New request appears in admin query results!</strong></p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Admin query failed: " . $conn->error . "</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Failed to create extension request: " . $stmt->error . "</p>";
        $stmt->close();
    }
    
} else {
    echo "<p style='color: red;'>❌ No users found who can create extension requests (all have pending requests or no active borrows)</p>";
}

echo "<h2>Next Steps:</h2>";
echo "<div style='background: #f8f9fa; padding: 1rem; border-radius: 8px; border-left: 4px solid #28a745;'>";
echo "<h3>To Test the Complete Flow:</h3>";
echo "<ol>";
echo "<li><strong>Login as Admin:</strong> <a href='login_register.php'>Login Page</a> (testadmin@library.com / admin123)</li>";
echo "<li><strong>Go to Extension Requests:</strong> <a href='extension-requests.php'>Admin Extension Requests</a></li>";
echo "<li><strong>Force Refresh:</strong> Press Ctrl+F5 to see the new request</li>";
echo "<li><strong>Verify:</strong> You should see the new pending request at the top</li>";
echo "</ol>";
echo "</div>";

echo "<h2>Quick Links:</h2>";
echo "<p><a href='extension-requests.php'>Admin Extension Requests Page</a></p>";
echo "<p><a href='borrow-book-simple.php'>Student Borrow Books Page</a></p>";
echo "<p><a href='simple_db_check.php'>Check Database Status</a></p>";
?> 