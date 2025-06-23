<?php
// filepath: c:\xampp\htdocs\WEBDEV_PROJECT\Library-Management-System\catalog.php

session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$user = null;

// Fetch user info
$stmt = $conn->prepare("SELECT full_name, email, user_type FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    echo "User not found.";
    exit();
}

// Fetch all books and their pending reservation counts
$books = [];
$query = "
    SELECT b.*, (SELECT COUNT(*) FROM reservations WHERE book_id = b.book_id AND status = 'pending') as pending_reservations
    FROM books b
    ORDER BY b.title ASC
";
$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $books[] = $row;
    }
}

// Process flash messages from session
$success = '';
$error = '';
if (isset($_SESSION['success_msg'])) {
    $success = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}
if (isset($_SESSION['error_msg'])) {
    $error = $_SESSION['error_msg'];
    unset($_SESSION['error_msg']);
}

// Handle borrow action
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

// Handle reserve action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reserve_book_id'])) {
    if (!isset($_SESSION['user_id'])) {
        $error = "Please login first before reserving a book!";
    } else {
        $user_id = (int)$_SESSION['user_id'];
        $book_id = (int)$_POST['reserve_book_id'];

        // Check if book exists and is unavailable
        $stmt = $conn->prepare("SELECT title, available_copies FROM books WHERE book_id = ?");
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $book = $result->fetch_assoc();
        $stmt->close();

        if (!$book) {
            $_SESSION['error_msg'] = "Book not found.";
            header('Location: catalog.php');
            exit();
        } elseif ($book['available_copies'] > 0) {
            $_SESSION['error_msg'] = "This book is currently available to borrow.";
            header('Location: catalog.php');
            exit();
        } else {
            // 1. Check for an existing PENDING reservation (robust check)
            $stmt = $conn->prepare("SELECT COUNT(reservation_id) FROM reservations WHERE user_id = ? AND book_id = ? AND (status = 'pending' OR status IS NULL OR TRIM(status) = '')");
            $stmt->bind_param("ii", $user_id, $book_id);
            $stmt->execute();
            $stmt->bind_result($reservation_count);
            $stmt->fetch();
            $stmt->close();

            if ($reservation_count > 0) {
                $_SESSION['error_msg'] = "You can only reserve once.";
                header('Location: catalog.php');
                exit();
            } else {
                // 2. Insert new reservation with 'pending' status
                $reservation_date = date('Y-m-d H:i:s');
                $status = 'pending';
                $stmt = $conn->prepare("INSERT INTO reservations (user_id, book_id, reservation_date, status) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiss", $user_id, $book_id, $reservation_date, $status);
                if ($stmt->execute()) {
                    $_SESSION['success_msg'] = "You have successfully reserved the book.";
                } else {
                    $_SESSION['error_msg'] = "Failed to reserve book. Error: " . $stmt->error;
                }
                $stmt->close();
                header('Location: catalog.php');
                exit();
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
    <style>
        .reserve-btn {
            background-color: #8c6b6b; /* A muted brown for reserve */
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-family: inherit;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        .reserve-btn:hover {
            background-color: #735555;
        }
        .book-actions form {
            margin: 0;
            display: inline-block;
        }
    </style>
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
                <h3><?= htmlspecialchars($user['full_name'] ?? 'User') ?></h3>
                <p><?= htmlspecialchars($user['user_type'] ?? 'User') ?></p>
            </div>
            <nav>
                <ul class="sidebar-menu">
                    <li><a href="student_page.php" class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'student_page.php' ? ' active' : '' ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="my-profile.php" class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'my-profile.php' ? ' active' : '' ?>"><i class="fas fa-user"></i> My Profile</a></li>
                    <li><a href="catalog.php" class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'catalog.php' ? ' active' : '' ?>"><i class="fas fa-book"></i> Browse Books</a></li>
                                        <li><a href="borrow-book.php" class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'borrow-book.php' ? ' active' : '' ?>"><i class="fas fa-book-reader"></i> Borrow Book</a></li>
                    <li><a href="my-reservation.php" class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'my-reservation.php' ? ' active' : '' ?>"><i class="fas fa-calendar-check"></i> My Reservations</a></li>
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
                                        // If not an absolute URL, prepend uploads/if not already present
                                        if ($coverPath && !preg_match('/^https?:\/\//', $coverPath) && strpos($coverPath, 'uploads/') !== 0) {
                                            $coverPath = 'uploads/covers/' . $coverPath;
                                        }
                                    ?>
                                    <?php if (!empty($coverPath)): ?>
                                        <img src="<?= htmlspecialchars($coverPath) ?>" alt="Book Cover">
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
                                        <span><i class="fas fa-layer-group"></i> Available: <b><?= (int)$book['available_copies'] ?></b></span>
                                        <?php if ((int)$book['available_copies'] == 0 && (int)$book['pending_reservations'] > 0): ?>
                                            <span><i class="fas fa-clock"></i> Reserved: <b><?= (int)$book['pending_reservations'] ?></b></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="book-actions">
                                        <?php if ((int)$book['available_copies'] > 0): ?>
                                            <form method="post">
                                                <input type="hidden" name="borrow_book_id" value="<?= $book['book_id'] ?>">
                                                <button type="submit" class="borrow-btn">Borrow</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="post" action="catalog.php" class="reserve-form" onsubmit="this.querySelector('button[type=submit]').disabled = true;">
                                             <input type="hidden" name="reserve_book_id" value="<?= (int)$book['book_id'] ?>">
                                             <button type="submit" class="reserve-btn"><i class="fas fa-calendar-plus"></i> Reserve</button>
                                         </form>
                                        <?php endif; ?>
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