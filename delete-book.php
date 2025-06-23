<?php
// filepath: c:\xampp\htdocs\WEBDEV_PROJECT\Library-Management-System\delete-book.php

session_start();
require_once 'config.php';

// Only allow admins to access this page
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit();
}

// Accept book_id from POST (form submission)
$book_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$success = '';
$error = '';

if ($book_id <= 0) {
    $error = "Invalid book selected.";
} else {
    // Fetch book info
    $stmt = $conn->prepare("SELECT title FROM books WHERE book_id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $book = $result->fetch_assoc();
    $stmt->close();

    if (!$book) {
        $error = "Book not found.";
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Delete book
        $stmt = $conn->prepare("DELETE FROM books WHERE book_id = ?");
        $stmt->bind_param("i", $book_id);
        if ($stmt->execute()) {
            header("Location: manage-books.php?msg=deleted");
            exit();
        } else {
            $error = "Error deleting book: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delete Book | Book Stop Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="delete-container">
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
            <a href="manage-books.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Books</a>
        <?php elseif ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
            <a href="manage-books.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Books</a>
        <?php else: ?>
            <h2><i class="fas fa-trash"></i> Delete Book</h2>
            <p>Are you sure you want to delete the book<br>
                <strong style="color:#532c2e;"><?= htmlspecialchars($book['title']) ?></strong>?</p>
            <form method="POST">
                <input type="hidden" name="id" value="<?= (int)$book_id ?>">
                <button type="submit" class="delete-btn"><i class="fas fa-trash"></i> Delete</button>
                <a href="manage-books.php" class="cancel-btn">Cancel</a>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>