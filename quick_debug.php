<?php
require_once 'config.php';

$output = [];

$output[] = "=== Extension Request System Debug ===";

// Check database connection
if (!$conn) {
    $output[] = "❌ Database connection failed";
    exit();
}
$output[] = "✅ Database connection successful";

// Check extension_requests table
$result = $conn->query("SHOW TABLES LIKE 'extension_requests'");
if ($result->num_rows == 0) {
    $output[] = "❌ extension_requests table does not exist";
} else {
    $output[] = "✅ extension_requests table exists";
    
    // Check columns
    $result = $conn->query("DESCRIBE extension_requests");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    
    $output[] = "Current columns: " . implode(', ', $columns);
    
    $required_columns = ['fine_amount', 'extension_days', 'fine_id'];
    foreach ($required_columns as $col) {
        if (in_array($col, $columns)) {
            $output[] = "✅ Column '$col' exists";
        } else {
            $output[] = "❌ Column '$col' missing";
        }
    }
}

// Check notifications table
$result = $conn->query("SHOW TABLES LIKE 'notifications'");
if ($result->num_rows == 0) {
    $output[] = "❌ notifications table does not exist";
} else {
    $output[] = "✅ notifications table exists";
}

// Check borrow_records fine_paid column
$result = $conn->query("SHOW COLUMNS FROM borrow_records LIKE 'fine_paid'");
if ($result->num_rows == 0) {
    $output[] = "❌ fine_paid column missing from borrow_records";
} else {
    $output[] = "✅ fine_paid column exists in borrow_records";
}

// Check sample data
$result = $conn->query("SELECT COUNT(*) as count FROM users");
$user_count = $result->fetch_assoc()['count'];
$output[] = "Users: $user_count";

$result = $conn->query("SELECT COUNT(*) as count FROM books");
$book_count = $result->fetch_assoc()['count'];
$output[] = "Books: $book_count";

$result = $conn->query("SELECT COUNT(*) as count FROM borrow_records");
$borrow_count = $result->fetch_assoc()['count'];
$output[] = "Borrow records: $borrow_count";

$result = $conn->query("SELECT COUNT(*) as count FROM extension_requests");
$extension_count = $result->fetch_assoc()['count'];
$output[] = "Extension requests: $extension_count";

// Test extension request creation
$output[] = "=== Testing Extension Request Creation ===";

// Get a sample user and book
$result = $conn->query("SELECT user_id, full_name FROM users WHERE user_type = 'user' LIMIT 1");
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $output[] = "Sample user: {$user['user_id']} - {$user['full_name']}";
    
    $result = $conn->query("SELECT book_id, title, book_fine FROM books LIMIT 1");
    if ($result->num_rows > 0) {
        $book = $result->fetch_assoc();
        $output[] = "Sample book: {$book['book_id']} - {$book['title']} (Fine: ₱{$book['book_fine']})";
        
        // Check if user has borrowed this book
        $stmt = $conn->prepare("SELECT br.*, b.book_fine FROM borrow_records br JOIN books b ON br.book_id = b.book_id WHERE br.user_id = ? AND br.book_id = ? AND br.return_date IS NULL");
        $stmt->bind_param("ii", $user['user_id'], $book['book_id']);
        $stmt->execute();
        $borrow = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($borrow) {
            $output[] = "✅ User has borrowed this book";
            
            // Check if extension already requested
            $stmt = $conn->prepare("SELECT * FROM extension_requests WHERE user_id = ? AND book_id = ? AND status = 'pending'");
            $stmt->bind_param("ii", $user['user_id'], $book['book_id']);
            $stmt->execute();
            $already_requested = $stmt->get_result()->num_rows > 0;
            $stmt->close();
            
            if ($already_requested) {
                $output[] = "⚠️ Extension already requested for this book";
            } else {
                $output[] = "✅ Can request extension";
                
                // Test creating extension request
                $extension_days = 7;
                $base_fine = $book['book_fine'];
                $calculated_fine = $base_fine;
                
                if ($extension_days > 3) {
                    $extra_days = $extension_days - 3;
                    $additional_fine = $base_fine * 0.10 * $extra_days;
                    $calculated_fine = $base_fine + $additional_fine;
                }
                
                $new_return_date = date('Y-m-d', strtotime($borrow['due_date'] . ' +' . $extension_days . ' days'));
                
                $output[] = "Base Fine: ₱" . number_format($base_fine, 2);
                $output[] = "Calculated Fine: ₱" . number_format($calculated_fine, 2);
                $output[] = "New Return Date: $new_return_date";
                
                // Try to insert
                $stmt = $conn->prepare("INSERT INTO extension_requests (book_id, user_id, request_date, new_return_date, status, fine_amount, extension_days) VALUES (?, ?, NOW(), ?, 'pending', ?, ?)");
                $stmt->bind_param("iisdi", $book['book_id'], $user['user_id'], $new_return_date, $calculated_fine, $extension_days);
                
                if ($stmt->execute()) {
                    $output[] = "✅ Extension request created successfully!";
                    $output[] = "Request ID: " . $conn->insert_id;
                } else {
                    $output[] = "❌ Failed to create extension request: " . $stmt->error;
                }
                $stmt->close();
            }
        } else {
            $output[] = "❌ User has not borrowed this book";
        }
    } else {
        $output[] = "❌ No books found";
    }
} else {
    $output[] = "❌ No users found";
}

// Write output to file
file_put_contents('debug_output.txt', implode("\n", $output));
echo "Debug completed. Check debug_output.txt for results.";
?> 