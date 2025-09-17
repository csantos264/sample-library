<?php
session_start();
require_once 'config.php';

echo "<h1>Extension Request System Debug</h1>";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<p style='color: red;'>❌ No user logged in</p>";
    echo "<p><a href='index.php'>Go to Login</a></p>";
    exit();
}

echo "<p>✅ User logged in: " . $_SESSION['user_id'] . " (" . ($_SESSION['user_type'] ?? 'unknown') . ")</p>";

// Check database connection
if (!$conn) {
    echo "<p style='color: red;'>❌ Database connection failed</p>";
    exit();
}
echo "<p>✅ Database connection successful</p>";

// Check if extension_requests table exists and has required columns
echo "<h2>1. Database Structure Check</h2>";
$result = $conn->query("SHOW TABLES LIKE 'extension_requests'");
if ($result->num_rows == 0) {
    echo "<p style='color: red;'>❌ extension_requests table does not exist</p>";
} else {
    echo "<p>✅ extension_requests table exists</p>";
    
    // Check columns
    $result = $conn->query("DESCRIBE extension_requests");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    
    $required_columns = ['request_id', 'book_id', 'user_id', 'request_date', 'new_return_date', 'status', 'fine_amount', 'extension_days', 'fine_id'];
    foreach ($required_columns as $col) {
        if (in_array($col, $columns)) {
            echo "<p>✅ Column '$col' exists</p>";
        } else {
            echo "<p style='color: red;'>❌ Column '$col' missing</p>";
        }
    }
}

// Check if notifications table exists
echo "<h2>2. Notifications Table Check</h2>";
$result = $conn->query("SHOW TABLES LIKE 'notifications'");
if ($result->num_rows == 0) {
    echo "<p style='color: red;'>❌ notifications table does not exist</p>";
} else {
    echo "<p>✅ notifications table exists</p>";
}

// Check user's borrow records
echo "<h2>3. User's Borrow Records</h2>";
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT br.*, b.title, b.book_fine 
                       FROM borrow_records br 
                       JOIN books b ON br.book_id = b.book_id 
                       WHERE br.user_id = ? AND br.return_date IS NULL");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<p style='color: orange;'>⚠️ No active borrow records found for user</p>";
    echo "<p>You need to borrow a book first to test extension requests.</p>";
} else {
    echo "<p>✅ Found " . $result->num_rows . " active borrow record(s):</p>";
    while ($row = $result->fetch_assoc()) {
        echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 10px 0;'>";
        echo "<strong>Borrow ID:</strong> " . $row['borrow_id'] . "<br>";
        echo "<strong>Book:</strong> " . htmlspecialchars($row['title']) . "<br>";
        echo "<strong>Borrow Date:</strong> " . $row['borrow_date'] . "<br>";
        echo "<strong>Due Date:</strong> " . $row['due_date'] . "<br>";
        echo "<strong>Base Fine:</strong> ₱" . number_format($row['book_fine'], 2) . "<br>";
        
        // Check if extension already requested
        $ext_stmt = $conn->prepare("SELECT * FROM extension_requests WHERE user_id = ? AND book_id = ? AND status = 'pending'");
        $ext_stmt->bind_param("ii", $user_id, $row['book_id']);
        $ext_stmt->execute();
        $ext_result = $ext_stmt->get_result();
        if ($ext_result->num_rows > 0) {
            echo "<strong>Extension Status:</strong> <span style='color: orange;'>Already requested</span><br>";
        } else {
            echo "<strong>Extension Status:</strong> <span style='color: green;'>Can request</span><br>";
        }
        $ext_stmt->close();
        
        echo "</div>";
    }
}
$stmt->close();

// Check existing extension requests
echo "<h2>4. Existing Extension Requests</h2>";
$stmt = $conn->prepare("SELECT er.*, b.title 
                       FROM extension_requests er 
                       JOIN books b ON er.book_id = b.book_id 
                       WHERE er.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<p>No extension requests found for this user.</p>";
} else {
    echo "<p>Found " . $result->num_rows . " extension request(s):</p>";
    while ($row = $result->fetch_assoc()) {
        echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 10px 0;'>";
        echo "<strong>Request ID:</strong> " . $row['request_id'] . "<br>";
        echo "<strong>Book:</strong> " . htmlspecialchars($row['title']) . "<br>";
        echo "<strong>Status:</strong> " . $row['status'] . "<br>";
        echo "<strong>Extension Days:</strong> " . ($row['extension_days'] ?? 'NULL') . "<br>";
        echo "<strong>Fine Amount:</strong> ₱" . number_format($row['fine_amount'] ?? 0, 2) . "<br>";
        echo "<strong>New Return Date:</strong> " . $row['new_return_date'] . "<br>";
        echo "</div>";
    }
}
$stmt->close();

// Test extension request creation
echo "<h2>5. Test Extension Request Creation</h2>";
if (isset($_POST['test_extension'])) {
    $borrow_id = (int)$_POST['borrow_id'];
    $extension_days = (int)$_POST['extension_days'];
    
    echo "<p>Testing extension request creation...</p>";
    echo "<p>Borrow ID: $borrow_id</p>";
    echo "<p>Extension Days: $extension_days</p>";
    
    // Get borrow record
    $stmt = $conn->prepare("SELECT br.*, b.book_fine, b.book_id FROM borrow_records br JOIN books b ON br.book_id = b.book_id WHERE br.borrow_id = ? AND br.user_id = ?");
    $stmt->bind_param("ii", $borrow_id, $user_id);
    $stmt->execute();
    $borrow = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$borrow) {
        echo "<p style='color: red;'>❌ Borrow record not found</p>";
    } else {
        echo "<p>✅ Borrow record found: " . htmlspecialchars($borrow['book_id']) . "</p>";
        
        // Check if already requested
        $stmt = $conn->prepare("SELECT * FROM extension_requests WHERE user_id = ? AND book_id = ? AND status = 'pending'");
        $stmt->bind_param("ii", $user_id, $borrow['book_id']);
        $stmt->execute();
        $already_requested = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        
        if ($already_requested) {
            echo "<p style='color: orange;'>⚠️ Extension already requested for this book</p>";
        } else {
            // Calculate fine
            $base_fine = $borrow['book_fine'];
            $calculated_fine = $base_fine;
            
            if ($extension_days > 3) {
                $extra_days = $extension_days - 3;
                $additional_fine = $base_fine * 0.10 * $extra_days;
                $calculated_fine = $base_fine + $additional_fine;
            }
            
            $new_return_date = date('Y-m-d', strtotime($borrow['due_date'] . ' +' . $extension_days . ' days'));
            
            echo "<p>Base Fine: ₱" . number_format($base_fine, 2) . "</p>";
            echo "<p>Calculated Fine: ₱" . number_format($calculated_fine, 2) . "</p>";
            echo "<p>New Return Date: $new_return_date</p>";
            
            // Try to insert
            $stmt = $conn->prepare("INSERT INTO extension_requests (book_id, user_id, request_date, new_return_date, status, fine_amount, extension_days) VALUES (?, ?, NOW(), ?, 'pending', ?, ?)");
            $stmt->bind_param("iisdi", $borrow['book_id'], $user_id, $new_return_date, $calculated_fine, $extension_days);
            
            if ($stmt->execute()) {
                echo "<p style='color: green;'>✅ Extension request created successfully!</p>";
                echo "<p>Request ID: " . $conn->insert_id . "</p>";
            } else {
                echo "<p style='color: red;'>❌ Failed to create extension request: " . $stmt->error . "</p>";
            }
            $stmt->close();
        }
    }
}

// Show test form if user has borrow records
$stmt = $conn->prepare("SELECT br.borrow_id, b.title 
                       FROM borrow_records br 
                       JOIN books b ON br.book_id = b.book_id 
                       WHERE br.user_id = ? AND br.return_date IS NULL");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<h2>6. Test Extension Request Form</h2>";
    echo "<form method='post'>";
    echo "<select name='borrow_id' required>";
    echo "<option value=''>Select a book to extend</option>";
    while ($row = $result->fetch_assoc()) {
        echo "<option value='" . $row['borrow_id'] . "'>" . htmlspecialchars($row['title']) . "</option>";
    }
    echo "</select><br><br>";
    echo "<label>Extension Days: <input type='number' name='extension_days' min='1' max='30' value='7' required></label><br><br>";
    echo "<button type='submit' name='test_extension'>Test Extension Request</button>";
    echo "</form>";
}
$stmt->close();

echo "<h2>7. Navigation</h2>";
echo "<p><a href='borrow-book.php'>Go to Borrow Book Page</a></p>";
echo "<p><a href='extension-requests.php'>Go to Extension Requests (Admin)</a></p>";
echo "<p><a href='run_database_fixes.php'>Run Database Fixes</a></p>";
echo "<p><a href='test_extension_fixes.php'>Run Extension Fixes Test</a></p>";
?> 