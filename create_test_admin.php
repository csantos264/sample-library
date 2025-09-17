<?php
require_once 'config.php';

echo "<h1>Create Test Admin Account</h1>";

// Check if test admin already exists
$stmt = $conn->prepare("SELECT user_id FROM users WHERE email = 'testadmin@library.com'");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<p>✅ Test admin account already exists</p>";
    $user = $result->fetch_assoc();
    echo "<p>Admin ID: {$user['user_id']}</p>";
} else {
    // Create test admin account
    $full_name = "Test Admin";
    $email = "testadmin@library.com";
    $password = "admin123";
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $user_type = "admin";
    
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, user_type, registered_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssss", $full_name, $email, $hashed_password, $user_type);
    
    if ($stmt->execute()) {
        echo "<p>✅ Test admin account created successfully</p>";
        echo "<p>Admin ID: " . $conn->insert_id . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to create admin account: " . $stmt->error . "</p>";
    }
    $stmt->close();
}

echo "<h2>Test Admin Login Credentials:</h2>";
echo "<p><strong>Email:</strong> testadmin@library.com</p>";
echo "<p><strong>Password:</strong> admin123</p>";

echo "<h2>Steps to Access Extension Requests:</h2>";
echo "<ol>";
echo "<li>Go to <a href='login_register.php'>Login Page</a></li>";
echo "<li>Login with the credentials above</li>";
echo "<li>Go to <a href='extension-requests.php'>Extension Requests Page</a></li>";
echo "</ol>";

echo "<h2>Direct Links:</h2>";
echo "<p><a href='login_register.php'>Login Page</a></p>";
echo "<p><a href='extension-requests.php'>Extension Requests (requires admin login)</a></p>";
echo "<p><a href='admin_page.php'>Admin Dashboard (requires admin login)</a></p>";
?> 