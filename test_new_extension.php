<?php
require_once 'config.php';

echo "<h1>Test New Extension Request Creation</h1>";

// Find a user with active borrows
$result = $conn->query("SELECT DISTINCT u.user_id, u.full_name, br.borrow_id, b.title, b.book_fine, br.due_date
                       FROM users u 
                       JOIN borrow_records br ON u.user_id = br.user_id 
                       JOIN books b ON br.book_id = b.book_id 
                       WHERE u.user_type = 'user' 
                       AND br.return_date IS NULL 
                       LIMIT 1");

if ($result && $result->num_rows > 0) {
    $data = $result->fetch_assoc();
    echo "<p>✅ Found test user: {$data['full_name']} (ID: {$data['user_id']})</p>";
    echo "<p>Book: {$data['title']} (Fine: ₱{$data['book_fine']})</p>";
    echo "<p>Borrow ID: {$data['borrow_id']}, Due Date: {$data['due_date']}</p>";
    
    // Check if extension already exists
    $stmt = $conn->prepare("SELECT * FROM extension_requests WHERE user_id = ? AND book_id = (SELECT book_id FROM borrow_records WHERE borrow_id = ?) AND status = 'pending'");
    $stmt->bind_param("ii", $data['user_id'], $data['borrow_id']);
    $stmt->execute();
    $already_requested = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    
    if ($already_requested) {
        echo "<p style='color: orange;'>⚠️ Extension already requested for this book</p>";
    } else {
        echo "<p style='color: green;'>✅ Can create new extension request</p>";
        
        // Simulate creating a new extension request
        $extension_days = 7;
        $base_fine = $data['book_fine'];
        $calculated_fine = $base_fine;
        
        if ($extension_days > 3) {
            $extra_days = $extension_days - 3;
            $additional_fine = $base_fine * 0.10 * $extra_days;
            $calculated_fine = $base_fine + $additional_fine;
        }
        
        $new_return_date = date('Y-m-d', strtotime($data['due_date'] . ' +' . $extension_days . ' days'));
        
        // Get book_id from borrow record
        $stmt = $conn->prepare("SELECT book_id FROM borrow_records WHERE borrow_id = ?");
        $stmt->bind_param("i", $data['borrow_id']);
        $stmt->execute();
        $book_result = $stmt->get_result();
        $book_data = $book_result->fetch_assoc();
        $stmt->close();
        
        if ($book_data) {
            // Create the extension request
            $stmt = $conn->prepare("INSERT INTO extension_requests (book_id, user_id, request_date, new_return_date, status, fine_amount, extension_days) VALUES (?, ?, NOW(), ?, 'pending', ?, ?)");
            $stmt->bind_param("iisdi", $book_data['book_id'], $data['user_id'], $new_return_date, $calculated_fine, $extension_days);
            
            if ($stmt->execute()) {
                $new_request_id = $conn->insert_id;
                echo "<p style='color: green;'>✅ New extension request created successfully!</p>";
                echo "<p>Request ID: $new_request_id</p>";
                echo "<p>Extension Days: $extension_days</p>";
                echo "<p>Calculated Fine: ₱" . number_format($calculated_fine, 2) . "</p>";
                echo "<p>New Due Date: $new_return_date</p>";
                
                // Verify it appears in admin query
                echo "<h2>Verifying Admin Query:</h2>";
                $admin_query = "
                    SELECT er.*, b.title, u.full_name
                    FROM extension_requests er
                    JOIN books b ON er.book_id = b.book_id
                    JOIN users u ON er.user_id = u.user_id
                    ORDER BY er.request_date DESC
                ";
                
                $admin_result = $conn->query($admin_query);
                if ($admin_result) {
                    echo "<p>✅ Admin query executed successfully</p>";
                    echo "<p>Total extension requests: {$admin_result->num_rows}</p>";
                    
                    if ($admin_result->num_rows > 0) {
                        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
                        echo "<tr><th>Request ID</th><th>User</th><th>Book</th><th>Days</th><th>Fine</th><th>Status</th><th>Request Date</th></tr>";
                        
                        while ($row = $admin_result->fetch_assoc()) {
                            $highlight = $row['request_id'] == $new_request_id ? 'background-color: #e8f5e8;' : '';
                            echo "<tr style='$highlight'>";
                            echo "<td>{$row['request_id']}</td>";
                            echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                            echo "<td>{$row['extension_days']}</td>";
                            echo "<td>₱" . number_format($row['fine_amount'], 2) . "</td>";
                            echo "<td>{$row['status']}</td>";
                            echo "<td>{$row['request_date']}</td>";
                            echo "</tr>";
                        }
                        echo "</table>";
                        
                        if ($admin_result->num_rows > 4) {
                            echo "<p style='color: green;'>✅ New request appears in admin query results</p>";
                        } else {
                            echo "<p style='color: red;'>❌ New request does not appear in admin query</p>";
                        }
                    }
                } else {
                    echo "<p style='color: red;'>❌ Admin query failed: " . $conn->error . "</p>";
                }
                
            } else {
                echo "<p style='color: red;'>❌ Failed to create extension request: " . $stmt->error . "</p>";
            }
            $stmt->close();
        } else {
            echo "<p style='color: red;'>❌ Could not find book_id for borrow record</p>";
        }
    }
} else {
    echo "<p style='color: red;'>❌ No users with active borrows found for testing</p>";
}

echo "<h2>Navigation:</h2>";
echo "<p><a href='extension-requests.php'>Go to Admin Extension Requests Page</a></p>";
echo "<p><a href='borrow-book-simple.php'>Go to Student Borrow Book Page</a></p>";
echo "<p><a href='simple_db_check.php'>Check Database</a></p>";
?> 