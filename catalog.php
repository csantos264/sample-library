<?php
// filepath: c:\xampp\htdocs\WEBDEV_PROJECT\Library-Management-System\catalog.php

session_start();
require_once 'config.php';

// Fetch all books
$books = [];
$result = $conn->query("SELECT * FROM books ORDER BY title ASC");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $books[] = $row;
    }
}

// Handle borrow action
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrow_book_id'])) {
    if (!isset($_SESSION['user_id'])) {
        $error = "Please login first before borrowing a book!";
    } else {
        $user_id = (int)$_SESSION['user_id'];
        $book_id = (int)$_POST['borrow_book_id'];

        // Check if book exists and is available
        $stmt = $conn->prepare("SELECT title, available_copies FROM books WHERE book_id = ?");
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $book = $result->fetch_assoc();
        $stmt->close();

        if (!$book) {
            $error = "Book not found.";
        } elseif ($book['available_copies'] < 1) {
            $error = "Sorry, this book is currently not available.";
        } else {
            // Check if user already borrowed this book and hasn't returned it
            $stmt = $conn->prepare("SELECT * FROM borrow_records WHERE user_id = ? AND book_id = ? AND return_date IS NULL");
            $stmt->bind_param("ii", $user_id, $book_id);
            $stmt->execute();
            $already_borrowed = $stmt->get_result()->num_rows > 0;
            $stmt->close();

            if ($already_borrowed) {
                $error = "You have already borrowed this book and not returned it yet.";
            } else {
                // Borrow the book
                $borrow_date = date('Y-m-d');
                $due_date = date('Y-m-d', strtotime('+14 days'));
                $status = 'borrowed';
                $stmt = $conn->prepare("INSERT INTO borrow_records (user_id, book_id, borrow_date, due_date, status) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iisss", $user_id, $book_id, $borrow_date, $due_date, $status);
                if ($stmt->execute()) {
                    // Decrease available copies
                    $stmt2 = $conn->prepare("UPDATE books SET available_copies = available_copies - 1 WHERE book_id = ?");
                    $stmt2->bind_param("i", $book_id);
                    $stmt2->execute();
                    $stmt2->close();
                    $success = "You have successfully borrowed <b>" . htmlspecialchars($book['title']) . "</b>! Due date: <b>" . date('M d, Y', strtotime($due_date)) . "</b>.";
                } else {
                    $error = "Failed to borrow the book. Please try again.";
                }
                $stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book Catalog | Book Stop</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header class="dashboard-header">
        <h1><i class="fas fa-book-reader"></i> Book Stop</h1>
        <a href="login_register.php?logout=1" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </header>
    <div class="dashboard-layout">
        <div class="dashboard-sidebar">
            <div class="user-info">
                <i class="fas fa-user-circle"></i>
                <h3>
                    <?= htmlspecialchars($user['full_name'] ?? 'Student') ?>
                </h3>
                <p>
                    <?= htmlspecialchars($user['user_type'] ?? 'Student') ?>
                </p>
            </div>
            <nav>
                <ul class="sidebar-menu">
                    <li><a href="student_page.php" class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'student_page.php' ? ' active' : '' ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="my-profile.php" class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'my-profile.php' ? ' active' : '' ?>"><i class="fas fa-user"></i> My Profile</a></li>
                    <li><a href="catalog.php" class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'catalog.php' ? ' active' : '' ?>"><i class="fas fa-book"></i> Browse Books</a></li>
                    <li><a href="borrow-book.php" class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'borrow-book.php' ? ' active' : '' ?>"><i class="fas fa-book-reader"></i> Borrow Book</a></li>
                    <li><a href="settings.php" class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? ' active' : '' ?>"><i class="fas fa-cog"></i> Settings</a></li>
                </ul>
            </nav>
        </div>
        <div class="dashboard-main">
            <div class="browse-container">
                <h2>Browse Our Collection</h2>
                <?php if ($success): ?>
                    <div class="alert alert-success" style="margin-bottom:1.2rem;"><?= $success ?></div>
                <?php elseif ($error): ?>
                    <div class="alert alert-error" style="margin-bottom:1.2rem;"><?= $error ?></div>
                <?php endif; ?>
                <?php if (count($books) === 0): ?>
                    <div class="no-books">No books found in the catalog.</div>
                <?php else: ?>
                    <div class="books-grid">
                        <?php foreach ($books as $book): ?>
                            <div class="book-card">
                                <div class="book-cover">
                                    <?php
                                        // Prefer relative path for uploads (e.g., uploads/covers/filename.jpg)
                                        $coverPath = !empty($book['cover_image']) ? $book['cover_image'] : '';
                                        // If not an absolute URL, prepend uploads/ if not already present
                                        if ($coverPath && !preg_match('/^https?:\/\//', $coverPath) && strpos($coverPath, 'uploads/') !== 0) {
                                            $coverPath = 'uploads/covers/' . $coverPath;
                                        }
                                    ?>
                                    <?php if (!empty($coverPath)): ?>
                                        <img src="<?= htmlspecialchars($coverPath) ?>" alt="Book Cover" style="width:100%;height:100%;object-fit:cover;border-radius:16px;">
                                    <?php else: ?>
                                        <i class="fas fa-book"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="book-details">
                                    <div class="book-title"><?= htmlspecialchars($book['title']) ?></div>
                                    <div class="book-meta">
                                        <span><i class="fas fa-user"></i><?= htmlspecialchars($book['author']) ?></span>
                                        <span><i class="fas fa-barcode"></i><?= htmlspecialchars($book['isbn'] ?? '') ?></span>
                                        <span><i class="fas fa-tag"></i>
                                            <?php
                                                if (isset($book['book_fine']) && $book['book_fine'] !== '') {
                                                    echo 'Fine: ₱' . number_format($book['book_fine'], 2) . ' / day overdue';
                                                } else {
                                                    echo 'No fine';
                                                }
                                            ?>
                                        </span>
                                    </div>
                                    <div class="book-actions" style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="borrow_book_id" value="<?= $book['book_id'] ?>">
                                            <button type="submit" class="borrow-btn">Borrow</button>
                                        </form>
                                        <a href="view-book.php?id=<?= urlencode($book['book_id']) ?>" class="view-btn">View</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>