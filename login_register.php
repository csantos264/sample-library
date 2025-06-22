<?php
session_start();
require_once 'config.php';

// LOGOUT
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// LOGIN
if (isset($_POST['login'])) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $stmt = $conn->prepare("SELECT user_id, full_name, email, password, user_type FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['user_type'] = $user['user_type']; // This should be 'admin' or 'user'

            // REDIRECT BASED ON ROLE
            if ($user['user_type'] === 'admin') {
                header("Location: admin_page.php");
            } else {
                header("Location: browse-books.php");
            }
            exit();
        }
    }
    $_SESSION['login_error'] = "Incorrect email or password.";
    header("Location: index.php");
    exit();
}

// REGISTER
if (isset($_POST['register'])) {
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $user_type = $_POST['user_type'] ?? 'user';

    if (strlen($password) < 8) {
        $_SESSION['register_error'] = "Password must be at least 8 characters.";
        header("Location: register.php");
        exit();
    }

    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $_SESSION['register_error'] = "Email already registered.";
        header("Location: register.php");
        exit();
    }
    $stmt->close();

    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, user_type) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $full_name, $email, $hashed, $user_type);
    if ($stmt->execute()) {
        $_SESSION['register_success'] = "Registration successful! Please login.";
        header("Location: register.php");
        exit();
    } else {
        $_SESSION['register_error'] = "Registration failed.";
        header("Location: register.php");
        exit();
    }
}

header("Location: index.php");
exit();