<?php
require_once 'config.php';

echo "<h1>User Borrow Records Check</h1>";

// Get all users with their borrow records
$result = $conn->query("SELECT u.user_id, u.full_name, u.user_type, 
                               COUNT(br.borrow_id) as total_borrows,
                               SUM(CASE WHEN br.return_date IS NULL THEN 1 ELSE 0 END) as active_borrows
                        FROM users u 
                        LEFT JOIN borrow_records br ON u.user_id = br.user_id 
                        GROUP BY u.user_id 
                        ORDER BY u.user_id");

if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>User ID</th><th>Name</th><th>Type</th><th>Total Borrows</th><th>Active Borrows</th><th>Actions</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['user_id']}</td>";
        echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
        echo "<td>{$row['user_type']}</td>";
        echo "<td>{$row['total_borrows']}</td>";
        echo "<td>{$row['active_borrows']}</td>";
        echo "<td><a href='?user_id={$row['user_id']}'>View Details</a></td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Show detailed borrow records for a specific user
if (isset($_GET['user_id'])) {
    $user_id = (int)$_GET['user_id'];
    
    echo "<h2>Borrow Records for User ID: $user_id</h2>";
    
    $stmt = $conn->prepare("SELECT br.*, b.title, b.book_fine 
                           FROM borrow_records br 
                           JOIN books b ON br.book_id = b.book_id 
                           WHERE br.user_id = ? 
                           ORDER BY br.borrow_date DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Borrow ID</th><th>Book</th><th>Borrow Date</th><th>Due Date</th><th>Return Date</th><th>Status</th><th>Fine</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            $status = $row['return_date'] ? 'Returned' : (strtotime($row['due_date']) < time() ? 'Overdue' : 'Active');
            $status_color = $row['return_date'] ? 'green' : (strtotime($row['due_date']) < time() ? 'red' : 'blue');
            
            echo "<tr>";
            echo "<td>{$row['borrow_id']}</td>";
            echo "<td>" . htmlspecialchars($row['title']) . "</td>";
            echo "<td>{$row['borrow_date']}</td>";
            echo "<td>{$row['due_date']}</td>";
            echo "<td>" . ($row['return_date'] ?: 'Not returned') . "</td>";
            echo "<td style='color: $status_color;'>$status</td>";
            echo "<td>₱{$row['book_fine']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check extension requests for this user
        echo "<h3>Extension Requests for User ID: $user_id</h3>";
        $stmt = $conn->prepare("SELECT er.*, b.title 
                               FROM extension_requests er 
                               JOIN books b ON er.book_id = b.book_id 
                               WHERE er.user_id = ? 
                               ORDER BY er.request_date DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>Request ID</th><th>Book</th><th>Extension Days</th><th>Fine Amount</th><th>Status</th><th>New Due Date</th></tr>";
            
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>{$row['request_id']}</td>";
                echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                echo "<td>{$row['extension_days']}</td>";
                echo "<td>₱" . number_format($row['fine_amount'], 2) . "</td>";
                echo "<td>{$row['status']}</td>";
                echo "<td>{$row['new_return_date']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No extension requests found for this user.</p>";
        }
    } else {
        echo "<p>No borrow records found for this user.</p>";
    }
    $stmt->close();
}

echo "<h2>Quick Test</h2>";
echo "<p><a href='borrow-book-simple.php'>Go to Simplified Borrow Book Page</a></p>";
echo "<p><a href='fix_extension_system.php'>Run Extension System Fix</a></p>";
?> 