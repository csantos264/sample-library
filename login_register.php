<?php

session_start();
require_once 'config.php';
echo "Database connected<br>";

if (isset($_POST['register'])){
    echo "Register form submitted<br>";
    $name = $_POST ['name'];
    $email = $_POST ['email'];
    $password = password_hash ($_POST ['password'], PASSWORD_DEFAULT);
    $role = $_POST ['role'];
 
$checkEmail = $conn->query("SELECT email FROM users WHERE email = '$email'");
if ($checkEmail-> num_rows > 0) {
    $_SESSION ['register_error'] = 'Email is already registered!';
    $_SESSION ['active_form'] = 'register';
} else {
    $conn->query("INSERT INTO users (name, email, password, role) VALUES ( '$name', '$email', '$password', '$role')");

}

header("Location: index.php");
exit();
}

if (isset($_POST ['login'])) {
    $email = $_POST ['email'];
    $password = $_POST['password'];

    $result = $conn-> query("SELECT * FROM users WHERE email = '$email'");
    if ($result->num_rows > 0) {
        $student = $result-> fetch_assoc();
        if (password_verify($password, $student['password'])) {
            $_SESSION ['name'] = $student ['name'];
            $_SESSION ['email'] = $student ['email'];

            if ($student ['role'] === 'admin') {
                header("Location: admin_page.php");
            } else {
                header("Location: student_page.php");
            }
            exit();
        }
    }
$_SESSION['login_error'] = 'Incorrect email or password';
$_SESSION['active_form'] = 'login';
header("Location: index.php");
exit();

}

?>