<?php
// filepath: c:\xampp\htdocs\WEBDEV_PROJECT\Library-Management-System\add-book.php

session_start();
require_once 'config.php';

// Only allow admins to access this page
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit();
}

$success = '';
$error = '';
$form = [
    'title' => '',
    'author' => '',
    'isbn' => '',
    'category' => '',
    'description' => '',
    'available_copies' => '1'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['title'] = trim($_POST['title'] ?? '');
    $form['author'] = trim($_POST['author'] ?? '');
    $form['isbn'] = trim($_POST['isbn'] ?? '');
    $form['category'] = trim($_POST['category'] ?? '');
    $form['description'] = trim($_POST['description'] ?? '');
    $form['available_copies'] = (int)($_POST['available_copies'] ?? 1);

    if (
        $form['title'] === '' ||
        $form['author'] === '' ||
        $form['isbn'] === '' ||
        $form['category'] === '' ||
        $form['available_copies'] < 1
    ) {
        $error = "Please fill in all required fields and provide at least 1 available copy.";
    } else {
        $stmt = $conn->prepare("INSERT INTO books (title, author, isbn, category, description, available_copies) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssi", $form['title'], $form['author'], $form['isbn'], $form['category'], $form['description'], $form['available_copies']);
        if ($stmt->execute()) {
            header("Location: manage-books.php?msg=added");
            exit();
        } else {
            $error = "Error adding book: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Book | Book Stop Admin</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="add-book-container">
        <a href="manage-books.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Books</a>
        <h2><i class="fas fa-plus"></i> Add New Book</h2>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php elseif ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        <form method="POST" autocomplete="off">
            <div class="form-group">
                <label for="title">Title<span style="color:#b71c1c;">*</span></label>
                <input type="text" id="title" name="title" required value="<?= htmlspecialchars($form['title']) ?>" aria-required="true">
            </div>
            <div class="form-group">
                <label for="author">Author<span style="color:#b71c1c;">*</span></label>
                <input type="text" id="author" name="author" required value="<?= htmlspecialchars($form['author']) ?>" aria-required="true">
            </div>
            <div class="form-group">
                <label for="isbn">ISBN<span style="color:#b71c1c;">*</span></label>
                <input type="text" id="isbn" name="isbn" required value="<?= htmlspecialchars($form['isbn']) ?>" aria-required="true">
            </div>
            <div class="form-group">
                <label for="category">Category<span style="color:#b71c1c;">*</span></label>
                <input type="text" id="category" name="category" required value="<?= htmlspecialchars($form['category']) ?>" aria-required="true">
            </div>
            <div class="form-group">
                <label for="available_copies">Available Copies<span style="color:#b71c1c;">*</span></label>
                <input type="number" id="available_copies" name="available_copies" min="1" required value="<?= htmlspecialchars($form['available_copies']) ?>" aria-required="true">
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description"><?= htmlspecialchars($form['description']) ?></textarea>
            </div>
            <button type="submit" class="button add"><i class="fas fa-plus"></i> Add Book</button>
        </form>
    </div>
</body>
</html>