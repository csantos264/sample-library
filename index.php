<?php

session_start();

if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
        header('Location: admin_page.php');
        exit();
    } else {
        header('Location: browse-books.php');
        exit();
    }
}

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

$messages = [
    'login_error' => $_SESSION['login_error'] ?? '',
    'register_error' => $_SESSION['register_error'] ?? '',
    'register_success' => $_SESSION['register_success'] ?? ''
];

// Unset session messages after reading them
getUnsetSessionVars(['login_error', 'register_error', 'register_success', 'active_form']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Stop</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="browse-books.css">
</head>
<body>
   <header class="main-header">
        <h1>
            <i class="fas fa-book-reader"></i>
            <a href="welcome-page.php" class="home-btn">Book Stop</a>
        </h1>
        <nav>
            <a href="browse-books.php" class="btn">Books</a>
            <a href="index.php" class="btn">Login</a>
            <a href="register.php" class="btn">Sign Up</a>
        </nav>
    </header>
    <div class="container">
         <div class="form-box active" id="login">
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
                    <a href="register.php" class="form-toggle">Register</a>
                </p>
            </form>
         </div>
    </div>
</body>
</html>