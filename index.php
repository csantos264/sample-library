<?php

session_start();


$messages = [
    'login_error' => $_SESSION['login_error'] ?? '',
    'register_error' => $_SESSION['register_error'] ?? '',
    'register_success' => $_SESSION['register_success'] ?? ''
];

$activeForm = $_SESSION['active_form'] ?? 'login';


getUnsetSessionVars(['login_error', 'register_error', 'register_success', 'active_form']);


function getUnsetSessionVars($keys) {
    $values = [];
    foreach ($keys as $key) {
        $values[$key] = $_SESSION[$key] ?? '';
        unset($_SESSION[$key]);
    }
    return $values;
}

function showMessage($type, $message) {
    if (empty($message)) return '';
    $class = $type === 'error' ? 'error-message' : 'success-message';
    return "<div class='$class'>$message</div>";
}

function isActiveForm($formName, $activeForm) {
    return $formName === $activeForm ? 'active' : '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Stop</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
         <div class="form-box <?= isActiveForm('login', $activeForm); ?>" id="login">
            <form action="login_register.php" method="post">
                <h2>Login</h2>
                <?= showMessage('error', $messages['login_error']) ?>
                <div class="form-group">
                    <input type="email" name="email" placeholder="Email" required 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <div class="form-group">
                    <button type="submit" name="login" class="btn btn-primary">Login</button>
                </div>
                <p class="form-footer">
                    Don't have an account? 
                    <a href="" onClick="showForm('register')" class="form-toggle">Register</a>
                </p>
            </form>
         </div>
         <div class="form-box <?= isActiveForm('register', $activeForm); ?>" id="register">
            <form action="login_register.php" method="post">
                <h2>Register</h2>
                <?= showMessage('error', $messages['register_error']) ?>
                <?= showMessage('success', $messages['register_success']) ?>
                <div class="form-group">
                    <input type="text" name="name" placeholder="Name" required>
                </div>
                <div class="form-group">
                    <input type="email" name="email" placeholder="Email" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <input type="hidden" name="role" value="student">
                <div class="form-group">
                    <button type="submit" name="register" class="btn btn-primary">Register</button>
                </div>
                <p class="form-footer">
                    Already have an account? 
                    <a href="" onClick="showForm('login')" class="form-toggle">Login</a>
                </p>
                <input type="password" name="password" placeholder="Password" required>
                <select name="role" required>
                    <option value="">--Select Role--</option>
                    <option value="student">Student</option>
                    <option value="admin">Admin</option>
                </select>
                <button type="submit" name="register">Register</button>
                <p>Already have an account? <a href="#" onClick="showForm('login')">Login</a></p>
            </form>
         </div>
    </div>
   <script src="script.js"></script>
</body>
</html>