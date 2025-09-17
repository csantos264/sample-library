<?php
session_start();
require_once 'config.php';

echo "<h1>Admin Access Test</h1>";

// Check session
echo "<h2>Session Status:</h2>";
if (isset($_SESSION['user_id'])) {
    echo "<p>✅ User ID: {$_SESSION['user_id']}</p>";
    echo "<p>User Type: " . ($_SESSION['user_type'] ?? 'Not set') . "</p>";
    
    // Check if user exists in database
    $stmt = $conn->prepare("SELECT full_name, user_type FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo "<p>✅ User found in database: {$user['full_name']} ({$user['user_type']})</p>";
        
        if ($user['user_type'] === 'admin') {
            echo "<p style='color: green;'>✅ User is an admin - can access extension requests page</p>";
        } else {
            echo "<p style='color: red;'>❌ User is not an admin - cannot access extension requests page</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ User not found in database</p>";
    }
    $stmt->close();
} else {
    echo "<p style='color: red;'>❌ No user session found</p>";
}

// Show all admin users
echo "<h2>Admin Users in Database:</h2>";
$result = $conn->query("SELECT user_id, full_name, email FROM users WHERE user_type = 'admin'");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['user_id']}</td>";
        echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No admin users found.</p>";
}

// Test extension requests query
echo "<h2>Extension Requests Test:</h2>";
$query = "
    SELECT er.*, b.title, u.full_name
    FROM extension_requests er
    JOIN books b ON er.book_id = b.book_id
    JOIN users u ON er.user_id = u.user_id
    ORDER BY er.request_date DESC
";

$result = $conn->query($query);
if ($result) {
    echo "<p>✅ Extension requests query works</p>";
    echo "<p>Found {$result->num_rows} extension requests</p>";
    
    if ($result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>User</th><th>Book</th><th>Status</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['request_id']}</td>";
            echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['title']) . "</td>";
            echo "<td>{$row['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} else {
    echo "<p style='color: red;'>❌ Extension requests query failed: " . $conn->error . "</p>";
}

echo "<h2>Navigation:</h2>";
echo "<p><a href='login_register.php'>Login/Register</a></p>";
echo "<p><a href='extension-requests.php'>Extension Requests Page</a></p>";
echo "<p><a href='admin_page.php'>Admin Dashboard</a></p>";

// If not logged in as admin, provide login links
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    echo "<h2>Login as Admin:</h2>";
    echo "<p>You need to be logged in as an admin to view extension requests.</p>";
    echo "<p>Available admin users:</p>";
    $result = $conn->query("SELECT user_id, full_name, email FROM users WHERE user_type = 'admin' LIMIT 3");
    if ($result && $result->num_rows > 0) {
        echo "<ul>";
        while ($row = $result->fetch_assoc()) {
            echo "<li>{$row['full_name']} (ID: {$row['user_id']})</li>";
        }
        echo "</ul>";
    }
}
?> 