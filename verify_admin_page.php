<?php
session_start();
require_once 'config.php';

echo "<h1>Verify Admin Extension Requests Page</h1>";

// Simulate admin session for testing
$_SESSION['user_id'] = 12; // Test Admin
$_SESSION['user_type'] = 'admin';

// Use the exact same query as extension-requests.php
$query = "
    SELECT er.*, b.title, u.full_name
    FROM extension_requests er
    JOIN books b ON er.book_id = b.book_id
    JOIN users u ON er.user_id = u.user_id
    ORDER BY er.request_date DESC
";

echo "<h2>Admin Extension Requests Query Results:</h2>";
echo "<p><strong>Query:</strong> " . htmlspecialchars($query) . "</p>";

$result = $conn->query($query);

if (!$result) {
    echo "<p style='color: red;'>❌ Query failed: " . $conn->error . "</p>";
} else {
    echo "<p>✅ Query executed successfully</p>";
    echo "<p><strong>Total extension requests found:</strong> {$result->num_rows}</p>";
    
    if ($result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-top: 1rem;'>";
        echo "<tr style='background: #f5e9e0;'>";
        echo "<th style='padding: 0.5rem;'>Request ID</th>";
        echo "<th style='padding: 0.5rem;'>User</th>";
        echo "<th style='padding: 0.5rem;'>Book</th>";
        echo "<th style='padding: 0.5rem;'>Extension Period</th>";
        echo "<th style='padding: 0.5rem;'>New Return Date</th>";
        echo "<th style='padding: 0.5rem;'>Extension Fine</th>";
        echo "<th style='padding: 0.5rem;'>Status</th>";
        echo "<th style='padding: 0.5rem;'>Request Date</th>";
        echo "</tr>";
        
        while ($row = $result->fetch_assoc()) {
            $status_color = $row['status'] === 'pending' ? '#e67e22' : ($row['status'] === 'approved' ? '#2ecc71' : '#e74c3c');
            
            echo "<tr>";
            echo "<td style='padding: 0.5rem;'>{$row['request_id']}</td>";
            echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($row['full_name']) . "</td>";
            echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($row['title']) . "</td>";
            echo "<td style='padding: 0.5rem; color: #2980b9; font-weight: bold;'>{$row['extension_days']} days</td>";
            echo "<td style='padding: 0.5rem;'>" . htmlspecialchars($row['new_return_date']) . "</td>";
            echo "<td style='padding: 0.5rem; color: #e74c3c; font-weight: bold;'>₱" . number_format($row['fine_amount'], 2) . "</td>";
            echo "<td style='padding: 0.5rem; color: $status_color; font-weight: bold;'>" . ucfirst($row['status']) . "</td>";
            echo "<td style='padding: 0.5rem;'>{$row['request_date']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h3>Summary:</h3>";
        echo "<ul>";
        echo "<li><strong>Total Requests:</strong> {$result->num_rows}</li>";
        
        // Count by status
        $result->data_seek(0);
        $pending = 0;
        $approved = 0;
        $denied = 0;
        while ($row = $result->fetch_assoc()) {
            if ($row['status'] === 'pending') $pending++;
            elseif ($row['status'] === 'approved') $approved++;
            elseif ($row['status'] === 'denied') $denied++;
        }
        
        echo "<li><strong>Pending:</strong> $pending</li>";
        echo "<li><strong>Approved:</strong> $approved</li>";
        echo "<li><strong>Denied:</strong> $denied</li>";
        echo "</ul>";
        
    } else {
        echo "<p style='color: red;'>No extension requests found.</p>";
    }
}

echo "<h2>Navigation:</h2>";
echo "<p><a href='extension-requests.php'>Go to Admin Extension Requests Page</a></p>";
echo "<p><a href='test_new_extension.php'>Create Another Test Extension Request</a></p>";
echo "<p><a href='simple_db_check.php'>Check Database</a></p>";

echo "<h2>Instructions:</h2>";
echo "<ol>";
echo "<li>Make sure you're logged in as admin (testadmin@library.com / admin123)</li>";
echo "<li>Go to the Extension Requests page</li>";
echo "<li>Press Ctrl+F5 to force refresh the page</li>";
echo "<li>You should see " . ($result ? $result->num_rows : 0) . " extension requests</li>";
echo "</ol>";
?> 