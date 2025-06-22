<?php
session_start();

function showMessage($type, $message) {
    if (empty($message)) return '';
    $class = $type === 'error' ? 'error-message' : 'success-message';
    return "<div class='$class'>$message</div>";
}

$register_error = $_SESSION['register_error'] ?? '';
$register_success = $_SESSION['register_success'] ?? '';
unset($_SESSION['register_error'], $_SESSION['register_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Book Stop</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
   <header class="main-header">
        <h1>Book Stop</h1>
        <nav>
            <a href="browse-books.php" class="btn">Books</a>
            <a href="index.php" class="btn">Login</a>
            <a href="register.php" class="btn">Register</a>
        </nav>
    </header>
    <div class="container">
         <div class="form-box active" id="register">
            <form action="login_register.php" method="post">
                <h2>Register</h2>
                <?= showMessage('error', $register_error) ?>
                <?= showMessage('success', $register_success) ?>
                <div class="form-group">
                    <input type="text" name="full_name" placeholder="Full Name" required>
                </div>
                <div class="form-group">
                    <input type="email" name="email" placeholder="Email" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <input type="hidden" name="user_type" value="user">
                <div class="form-group">
                    <button type="submit" name="register" class="btn btn-primary">Register</button>
                </div>
                <p class="form-footer">
                    Already have an account? 
                    <a href="index.php" class="form-toggle">Login</a>
                </p>
            </form>
         </div>
    </div>
</body>
</html>