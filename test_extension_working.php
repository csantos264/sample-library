<?php
require_once 'config.php';

echo "<h1>Extension Request System - Working Test</h1>";

// Find a user who has active borrows
$result = $conn->query("SELECT DISTINCT u.user_id, u.full_name, br.borrow_id, b.title, b.book_fine, br.due_date
                       FROM users u 
                       JOIN borrow_records br ON u.user_id = br.user_id 
                       JOIN books b ON br.book_id = b.book_id 
                       WHERE u.user_type = 'user' 
                       AND br.return_date IS NULL 
                       LIMIT 1");

if ($result && $result->num_rows > 0) {
    $data = $result->fetch_assoc();
    echo "<h2>Test Setup</h2>";
    echo "<p><strong>User:</strong> {$data['full_name']} (ID: {$data['user_id']})</p>";
    echo "<p><strong>Book:</strong> {$data['title']} (Fine: ₱{$data['book_fine']})</p>";
    echo "<p><strong>Borrow ID:</strong> {$data['borrow_id']}</p>";
    echo "<p><strong>Due Date:</strong> {$data['due_date']}</p>";
    
    // Check if extension already exists
    $stmt = $conn->prepare("SELECT * FROM extension_requests WHERE user_id = ? AND book_id = (SELECT book_id FROM borrow_records WHERE borrow_id = ?) AND status = 'pending'");
    $stmt->bind_param("ii", $data['user_id'], $data['borrow_id']);
    $stmt->execute();
    $already_requested = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    
    if ($already_requested) {
        echo "<p style='color: orange;'>⚠️ Extension already requested for this book</p>";
    } else {
        echo "<p style='color: green;'>✅ Can request extension</p>";
        
        // Test extension request creation
        if (isset($_POST['create_test_extension'])) {
            $extension_days = (int)$_POST['extension_days'];
            
            // Get book_id from borrow record
            $stmt = $conn->prepare("SELECT book_id FROM borrow_records WHERE borrow_id = ?");
            $stmt->bind_param("i", $data['borrow_id']);
            $stmt->execute();
            $book_id = $stmt->get_result()->fetch_assoc()['book_id'];
            $stmt->close();
            
            // Calculate fine
            $base_fine = $data['book_fine'];
            $calculated_fine = $base_fine;
            
            if ($extension_days > 3) {
                $extra_days = $extension_days - 3;
                $additional_fine = $base_fine * 0.10 * $extra_days;
                $calculated_fine = $base_fine + $additional_fine;
            }
            
            $new_return_date = date('Y-m-d', strtotime($data['due_date'] . ' +' . $extension_days . ' days'));
            
            echo "<h3>Extension Request Details</h3>";
            echo "<p>Extension Days: $extension_days</p>";
            echo "<p>Base Fine: ₱" . number_format($base_fine, 2) . "</p>";
            echo "<p>Calculated Fine: ₱" . number_format($calculated_fine, 2) . "</p>";
            echo "<p>New Due Date: $new_return_date</p>";
            
            // Create extension request
            $stmt = $conn->prepare("INSERT INTO extension_requests (book_id, user_id, request_date, new_return_date, status, fine_amount, extension_days) VALUES (?, ?, NOW(), ?, 'pending', ?, ?)");
            $stmt->bind_param("iisdi", $book_id, $data['user_id'], $new_return_date, $calculated_fine, $extension_days);
            
            if ($stmt->execute()) {
                echo "<p style='color: green;'>✅ Extension request created successfully!</p>";
                echo "<p>Request ID: " . $conn->insert_id . "</p>";
                
                // Create notification
                $notification_title = 'Extension Request Submitted';
                $notification_message = "Your extension request for '{$data['title']}' has been submitted and is pending approval.";
                
                $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, related_id) VALUES (?, ?, ?, 'extension_submitted', ?)");
                $stmt->bind_param("issi", $data['user_id'], $notification_title, $notification_message, $conn->insert_id);
                $stmt->execute();
                $stmt->close();
                
                echo "<p style='color: green;'>✅ Notification created!</p>";
            } else {
                echo "<p style='color: red;'>❌ Failed to create extension request: " . $stmt->error . "</p>";
            }
            $stmt->close();
        } else {
            // Show test form
            echo "<h3>Create Test Extension Request</h3>";
            echo "<form method='post'>";
            echo "<input type='hidden' name='borrow_id' value='{$data['borrow_id']}'>";
            echo "<label>Extension Days: <select name='extension_days'>";
            echo "<option value='7'>7 days</option>";
            echo "<option value='14'>14 days</option>";
            echo "<option value='21'>21 days</option>";
            echo "<option value='30'>30 days</option>";
            echo "</select></label><br><br>";
            echo "<button type='submit' name='create_test_extension'>Create Test Extension Request</button>";
            echo "</form>";
        }
    }
} else {
    echo "<p style='color: red;'>❌ No active borrow records found for testing</p>";
}

// Show current extension requests
echo "<h2>Current Extension Requests</h2>";
$result = $conn->query("SELECT er.*, b.title, u.full_name 
                       FROM extension_requests er 
                       JOIN books b ON er.book_id = b.book_id 
                       JOIN users u ON er.user_id = u.user_id 
                       ORDER BY er.request_date DESC");

if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>User</th><th>Book</th><th>Days</th><th>Fine</th><th>Status</th><th>New Due Date</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['request_id']}</td>";
        echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['title']) . "</td>";
        echo "<td>{$row['extension_days']}</td>";
        echo "<td>₱" . number_format($row['fine_amount'], 2) . "</td>";
        echo "<td>{$row['status']}</td>";
        echo "<td>{$row['new_return_date']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No extension requests found.</p>";
}

echo "<h2>Navigation</h2>";
echo "<p><a href='borrow-book-simple.php'>Go to Simplified Borrow Book</a></p>";
echo "<p><a href='extension-requests.php'>Go to Extension Requests (Admin)</a></p>";
echo "<p><a href='check_user_borrows.php'>Check User Borrow Records</a></p>";
?> 