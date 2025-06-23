<?php
// filepath: c:\xampp\htdocs\WEBDEV_PROJECT\Library-Management-System\edit-book.php

session_start();
require_once 'config.php';

// Only allow admins to access this page
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit();
}

$book_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$book = null;

if ($book_id <= 0) {
    $error = "Invalid book selected.";
} else {
    // Fetch book info with error checking
    $stmt = $conn->prepare("SELECT * FROM books WHERE book_id = ?");
    if (!$stmt) {
        $error = "Database error: " . $conn->error;
    } else {
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result) {
            $book = $result->fetch_assoc();
            if (!$book) {
                $error = "Book not found.";
            }
        } else {
            $error = "Failed to fetch book: " . $stmt->error;
        }
        $stmt->close();
    }

    // If book exists and form submitted
    if ($book && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = trim($_POST['title'] ?? '');
        $author = trim($_POST['author'] ?? '');
        $isbn = trim($_POST['isbn'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $available_copies = (int)($_POST['available_copies'] ?? 0);
        $cover_image = $book['cover_image']; // Keep old image by default

        // Handle file upload
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/covers/';
            // Create directory if it doesn't exist
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = basename($_FILES['cover_image']['name']);
            $target_file = $upload_dir . $file_name;
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            // Basic validation
            $check = getimagesize($_FILES['cover_image']['tmp_name']);
            if ($check === false) {
                $error = "File is not an image.";
            } elseif (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
                $error = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
            } else {
                if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $target_file)) {
                    $cover_image = $file_name; // New image file name
                } else {
                    $error = "Sorry, there was an error uploading your file.";
                }
            }
        }

        if (empty($error) && ($title === '' || $author === '' || $isbn === '' || $category === '' || $available_copies < 0)) {
            $error = "Please fill in all required fields and provide a valid number of copies.";
        }

        if (empty($error)) {
            $stmt = $conn->prepare("UPDATE books SET title=?, author=?, isbn=?, category=?, description=?, available_copies=?, cover_image=? WHERE book_id=?");
            if (!$stmt) {
                $error = "Prepare failed: " . $conn->error;
            } else {
                $stmt->bind_param("sssssisi", $title, $author, $isbn, $category, $description, $available_copies, $cover_image, $book_id);
                if ($stmt->execute()) {
                    header("Location: manage-books.php?msg=updated");
                    exit();
                } else {
                    $error = "Error updating book: " . $stmt->error;
                }
                $stmt->close();
            }
        }

        // Keep form data sticky if there's an error
        if ($error) {
            $book['title'] = $title;
            $book['author'] = $author;
            $book['isbn'] = $isbn;
            $book['category'] = $category;
            $book['description'] = $description;
            $book['available_copies'] = $available_copies;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Book | Admin</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="edit-book-container">
        <a href="manage-books.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Books</a>
        <h2><i class="fas fa-edit"></i> Edit Book</h2>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($book): ?>
        <form method="POST" autocomplete="off" enctype="multipart/form-data">
            <div class="form-group">
                <label for="title">Title<span style="color:#b71c1c;">*</span></label>
                <input type="text" id="title" name="title" required value="<?= htmlspecialchars($book['title']) ?>">
            </div>
            <div class="form-group">
                <label for="author">Author<span style="color:#b71c1c;">*</span></label>
                <input type="text" id="author" name="author" required value="<?= htmlspecialchars($book['author']) ?>">
            </div>
            <div class="form-group">
                <label for="isbn">ISBN<span style="color:#b71c1c;">*</span></label>
                <input type="text" id="isbn" name="isbn" required value="<?= htmlspecialchars($book['isbn']) ?>">
            </div>
            <div class="form-group">
                <label for="category">Category<span style="color:#b71c1c;">*</span></label>
                <input type="text" id="category" name="category" required value="<?= htmlspecialchars($book['category']) ?>">
            </div>
            <div class="form-group">
                <label for="available_copies">Available Copies<span style="color:#b71c1c;">*</span></label>
                <input type="number" id="available_copies" name="available_copies" min="0" required value="<?= htmlspecialchars($book['available_copies']) ?>">
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description"><?= htmlspecialchars($book['description']) ?></textarea>
            </div>
            <div class="form-group">
                <label for="cover_image">Cover Image</label>
                <input type="file" id="cover_image" name="cover_image">
                <?php if (!empty($book['cover_image'])): ?>
                    <p style="margin-top: 8px;">Current: <a href="uploads/covers/<?= htmlspecialchars($book['cover_image']) ?>" target="_blank"><?= htmlspecialchars($book['cover_image']) ?></a></p>
                <?php endif; ?>
            </div>
            <button type="submit" class="button add"><i class="fas fa-save"></i> Save Changes</button>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>
