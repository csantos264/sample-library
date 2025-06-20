<?php

session_start();
require_once 'config.php';


function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}


if (isset($_POST['register'])) {
    
    $name = sanitizeInput($_POST['name']);
    $email = filter_var(sanitizeInput($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    $role = 'student';
    
    
    $errors = [];
    if (empty($name) || strlen($name) < 2) {
        $errors[] = 'Name must be at least 2 characters long';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long';
    }
    
    if (empty($errors)) {
        
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $_SESSION['register_error'] = 'Email is already registered!';
        } else {
            
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $hashedPassword, $role);
            
            if ($stmt->execute()) {
                $_SESSION['register_success'] = 'Registration successful! Please login.';
                $_SESSION['active_form'] = 'login';
            } else {
                $_SESSION['register_error'] = 'Registration failed. Please try again.';
                $_SESSION['active_form'] = 'register';
            }
        }
    } else {
        $_SESSION['register_error'] = implode('<br>', $errors);
        $_SESSION['active_form'] = 'register';
    }
    
    header("Location: index.php");
    exit();
}


if (isset($_POST['login'])) {
    $email = filter_var(sanitizeInput($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $_SESSION['login_error'] = 'Please enter both email and password';
        $_SESSION['active_form'] = 'login';
        header("Location: index.php");
        exit();
    }
    
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            
            
            session_regenerate_id(true);
            
            
            $redirect = ($user['role'] === 'admin') ? 'admin_page.php' : 'student_page.php';
            header("Location: $redirect");
            exit();
        }
    }
    
    
    $_SESSION['login_error'] = 'Incorrect email or password';
    $_SESSION['active_form'] = 'login';
    header("Location: index.php");
    exit();
}


if (isset($_GET['logout'])) {
    
    $_SESSION = array();
    
    
    session_destroy();
    
    
    header("Location: index.php");
    exit();
}

?>