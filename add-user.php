<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $user_type = trim($_POST['user_type'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($full_name === '' || $email === '' || $user_type === '' ) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // Check for email uniqueness
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = "Email already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt->close();
            $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, user_type) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $full_name, $email, $hashed_password, $user_type);
            if ($stmt->execute()) {
                $success = "User added successfully.";
                // Clear form fields
                $full_name = $email = $user_type = '';
            } else {
                $error = "Failed to add user.";
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add User</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .add-form { max-width: 400px; margin: 40px auto; background: #fff; padding: 30px 30px 20px 30px; border-radius: 8px; box-shadow: 0 2px 8px #eee; }
        .add-form label { display: block; margin-bottom: 6px; font-weight: bold; }
        .add-form input, .add-form select { width: 100%; padding: 8px; margin-bottom: 16px; border-radius: 4px; border: 1px solid #ccc; }
        .add-form button { background: #2ecc71; color: #fff; border: none; padding: 10px 22px; border-radius: 4px; cursor: pointer; }
        .add-form .back-link { margin-left: 10px; color: #555; text-decoration: underline; }
        .add-form .error { color: #e74c3c; margin-bottom: 10px; }
        .add-form .success { color: #27ae60; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="add-form">
        <a href="manage-users.php" style="display:inline-block;margin-bottom:18px;color:#3498db;text-decoration:underline;">
            <i class="fas fa-arrow-left"></i> Back to Users List
        </a>
        <h2><i class="fas fa-user-plus"></i> Add User</h2>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <form method="post">
            <label for="full_name">Full Name</label>
            <input type="text" name="full_name" id="full_name" value="<?= htmlspecialchars($full_name ?? '') ?>" required>

            <label for="email">Email</label>
            <input type="email" name="email" id="email" value="<?= htmlspecialchars($email ?? '') ?>" required>

            <label for="user_type">User Type</label>
            <select name="user_type" id="user_type" required>
                <option value="">Select type</option>
                <option value="user" <?= (isset($user_type) && $user_type === 'user') ? 'selected' : '' ?>>user</option>
            </select>


            <button type="submit"><i class="fas fa-save"></i> Add User</button>
            <a href="manage-users.php" class="back-link">Cancel</a>
        </form>
    </div>
</body>
</html>