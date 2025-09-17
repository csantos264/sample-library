<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit();
}

if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    header('Location: manage-users.php');
    exit();
}

$user_id = intval($_GET['user_id']);
$error = '';
$success = '';

// Fetch user data
$stmt = $conn->prepare("SELECT full_name, email, user_type FROM users WHERE user_id = ? AND user_type != 'admin'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($full_name, $email, $user_type);
if (!$stmt->fetch()) {
    $stmt->close();
    header('Location: manage-users.php');
    exit();
}
$stmt->close();

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_full_name = trim($_POST['full_name'] ?? '');
    $new_email = trim($_POST['email'] ?? '');
    $new_user_type = trim($_POST['user_type'] ?? '');

    if ($new_full_name === '' || $new_email === '' || $new_user_type === '') {
        $error = "All fields are required.";
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // Check for email uniqueness
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $stmt->bind_param("si", $new_email, $user_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = "Email already exists.";
        } else {
            $stmt->close();
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, user_type = ? WHERE user_id = ?");
            $stmt->bind_param("sssi", $new_full_name, $new_email, $new_user_type, $user_id);
            if ($stmt->execute()) {
                $success = "User updated successfully.";
                $full_name = $new_full_name;
                $email = $new_email;
                $user_type = $new_user_type;
            } else {
                $error = "Failed to update user.";
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
    <title>Edit User</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .edit-form { max-width: 400px; margin: 40px auto; background: #fff; padding: 30px 30px 20px 30px; border-radius: 8px; box-shadow: 0 2px 8px #eee; }
        .edit-form label { display: block; margin-bottom: 6px; font-weight: bold; }
        .edit-form input, .edit-form select { width: 100%; padding: 8px; margin-bottom: 16px; border-radius: 4px; border: 1px solid #ccc; }
        .edit-form button { background: #3498db; color: #fff; border: none; padding: 10px 22px; border-radius: 4px; cursor: pointer; }
        .edit-form .back-link { margin-left: 10px; color: #555; text-decoration: underline; }
        .edit-form .error { color: #e74c3c; margin-bottom: 10px; }
        .edit-form .success { color: #27ae60; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="edit-form">
        <a href="manage-users.php" style="display:inline-block;margin-bottom:18px;color:#3498db;text-decoration:underline;">
            <i class="fas fa-arrow-left"></i> Back to Users List
        </a>
        <h2><i class="fas fa-edit"></i> Edit User</h2>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <form method="post">
            <label for="full_name">Full Name</label>
            <input type="text" name="full_name" id="full_name" value="<?= htmlspecialchars($full_name) ?>" required>

            <label for="email">Email</label>
            <input type="email" name="email" id="email" value="<?= htmlspecialchars($email) ?>" required>

            <label for="user_type">User Type</label>
            <select name="user_type" id="user_type" required>
                <option value="user" <?= $user_type === 'user' ? 'selected' : '' ?>>user</option>
            </select>

            <button type="submit"><i class="fas fa-save"></i> Save Changes</button>
            <a href="manage-users.php" class="back-link">Cancel</a>
        </form>
    </div>
</body>
</html>