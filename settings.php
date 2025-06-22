<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$success = '';
$error = '';

// Fetch user info
$stmt = $conn->prepare("SELECT full_name, email FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Handle profile update
if (isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);

    if (empty($full_name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid name or email provided.";
    } else {
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ? WHERE user_id = ?");
        $stmt->bind_param("ssi", $full_name, $email, $user_id);
        if ($stmt->execute()) {
            $success = "Profile updated successfully.";
            $user['full_name'] = $full_name;
            $user['email'] = $email;
        } else {
            $error = "Error updating profile: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All password fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } else {
        $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_data = $result->fetch_assoc();
        $stmt->close();

        if ($user_data && password_verify($current_password, $user_data['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->bind_param("si", $hashed_password, $user_id);
            if ($stmt->execute()) {
                $success = "Password changed successfully.";
            } else {
                $error = "Error changing password: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = "Incorrect current password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Settings | Book Stop</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar Start -->
        <div class="dashboard-sidebar">
            <div class="user-info">
                <i class="fas fa-user-circle"></i>
                <h3>
                    <?php if (isset($user['full_name'])): ?>
                        <?= htmlspecialchars($user['full_name']) ?>
                    <?php else: ?>
                        Student
                    <?php endif; ?>
                </h3>
                <p>Student</p>
            </div>
            <nav>
                <ul class="sidebar-menu">
                    <li><a href="student_page.php"><i class="fas fa-tachometer-alt" style="margin-right:8px;"></i>Dashboard</a></li>
                    <li><a href="settings.php" class="active"><i class="fas fa-cog" style="margin-right:8px;"></i>Settings</a></li>
                    <li><a href="my-profile.php"><i class="fas fa-user" style="margin-right:8px;"></i>My Profile</a></li>
                    <li><a href="borrow-book.php"><i class="fas fa-book-reader" style="margin-right:8px;"></i>Borrow Book</a></li>
                    <li><a href="login_register.php?logout=1" style="color:#a66e4a;"><i class="fas fa-sign-out-alt" style="margin-right:8px;"></i>Logout</a></li>
                </ul>
            </nav>
        </div>
        <!-- Sidebar End -->

        <!-- Main Content Start -->
        <div style="flex:1;">
            <div class="settings-header">
               <h2> Book Stop </h2>
            </div>
            <div class="settings-container">
                <div class="settings-card">
                    <h3><i class="fas fa-user"></i> Profile Information</h3>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <?php elseif ($error): ?>
                        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="form-group">
                            <label for="full_name">Full Name</label>
                            <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                        <button type="submit" name="update_profile" class="submit-btn">Update Profile</button>
                    </form>
                </div>
                <div class="settings-card">
                    <h3><i class="fas fa-key"></i> Change Password</h3>
                    <form method="POST">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" required>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" name="change_password" class="submit-btn">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
        <!-- Main Content End -->
    </div>
</body>
</html>