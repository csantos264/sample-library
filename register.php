<?php
session_start();

function showMessage($type, $message) {
    if (empty($message)) return '';
    $class = $type === 'error' ? 'error-message' : 'success-message';
    $icon = $type === 'error' ? "<i class='fas fa-exclamation-circle'></i>" : "<i class='fas fa-check-circle'></i>";
    return "<div class='$class'>$icon$message</div>";
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
    <link rel="stylesheet" href="browse-books.css">
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
     <style>
        .error-message {
            background: #ffeaea;
            color: #c0392b;
            border: 1px solid #e74c3c;
            border-radius: 6px;
            padding: 12px 18px;
            margin-bottom: 18px;
            font-size: 1.05rem;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 2px 8px rgba(231,76,60,0.07);
            max-width: 350px;
            margin-left: auto;
            margin-right: auto;
            animation: shake 0.2s 2;
        }
        .error-message i {
            color: #e74c3c;
            font-size: 1.2em;
        }
        .success-message {
            background: #eafaf1;
            color: #218c4c;
            border: 1px solid #27ae60;
            border-radius: 6px;
            padding: 12px 18px;
            margin-bottom: 18px;
            font-size: 1.05rem;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 2px 8px rgba(39,174,96,0.07);
            max-width: 350px;
            margin-left: auto;
            margin-right: auto;
            animation: pop 0.3s 1;
        }
        .success-message i {
            color: #27ae60;
            font-size: 1.2em;
        }
        @keyframes shake {
            0% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            50% { transform: translateX(5px); }
            75% { transform: translateX(-5px); }
            100% { transform: translateX(0); }
        }
        @keyframes pop {
            0% { transform: scale(0.9); opacity: 0.5; }
            100% { transform: scale(1); opacity: 1; }
        }
    </style>
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
         <div class="form-box active" id="register">
            <form action="login_register.php" method="post">
                <h2>Sign Up</h2>
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
                    <button type="submit" name="register" class="btn btn-primary">Sign Up</button>
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