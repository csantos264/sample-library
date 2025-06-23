<?php
require_once 'config.php';

$books = [];
$result = $conn->query("SELECT title, author, isbn, cover_image FROM books ORDER BY title ASC");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $books[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Browse Books | Book Stop</title>
    <link rel="stylesheet" href="browse-books.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .custom-alert {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #f5e9e0;
            color: #532c2e;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            z-index: 1000;
            display: flex;
            align-items: center;
        }
        .custom-alert-msg {
            margin-right: 1rem;
        }
        .custom-alert-close {
            background: transparent;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            color: #532c2e;
        }
    </style>
    <script>
        function requireLogin(event) {
            event.preventDefault();
            let alertBox = document.createElement('div');
            alertBox.className = 'custom-alert';
            alertBox.innerHTML = `
                <span class="custom-alert-msg">
                    <i class="fas fa-info-circle"></i>
                    Please login first before borrowing a book!
                </span>
                <button class="custom-alert-close" onclick="this.parentElement.remove()">×</button>
            `;
            document.body.appendChild(alertBox);
            setTimeout(() => {
                if (alertBox.parentElement) alertBox.remove();
            }, 3000);
        }
    </script>
</head>
<body>
    <header class="main-header">
        <h1><i class="fas fa-book-reader"></i><a href="welcome-page.php" class="home-btn">Book Stop</a></h1>
        <nav>
            <a href="browse-books.php" class="btn">Books</a>
            <a href="index.php" class="btn">Login</a>
            <a href="register.php" class="btn">Sign Up</a>
        </nav>
    </header>
    <main>
        <div class="browse-container">
            <h2>Browse Our Collection</h2>
            <?php if (count($books) > 0): ?>
                <div class="books-grid">
                    <?php foreach ($books as $book): ?>
                        <div class="book-card">
                            <div class="book-cover">
                                <?php if (!empty($book['cover_image']) && file_exists('uploads/covers/' . $book['cover_image'])): ?>
                                    <img src="uploads/covers/<?php echo htmlspecialchars($book['cover_image']); ?>" alt="Book Cover">
                                <?php else: ?>
                                    <i class="fas fa-book"></i>
                                <?php endif; ?>
                            </div>
                            <div class="book-details">
                                <div class="book-title"><?php echo htmlspecialchars($book['title']); ?></div>
                                <div class="book-meta">
                                    <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($book['author']); ?></span>
                                    <span><i class="fas fa-barcode"></i> <?php echo htmlspecialchars($book['isbn']); ?></span>
                                </div>
                                <button class="borrow-btn" onclick="requireLogin(event)">Borrow</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="no-books">No books available at the moment.</p>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
