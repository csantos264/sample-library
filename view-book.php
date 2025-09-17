<?php
session_start();
require_once 'config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "No book selected.";
    exit();
}

$book_id = (int)$_GET['id'];
$stmt = $conn->prepare("SELECT * FROM books WHERE book_id = ?");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$result = $stmt->get_result();
$book = $result->fetch_assoc();
$stmt->close();

if (!$book) {
    echo "Book not found.";
    exit();
}

// Reservation logic
$reserve_success = '';
$reserve_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reserve_book_id'])) {
    if (!isset($_SESSION['user_id'])) {
        $reserve_error = "Please login first before reserving a book!";
    } else {
        $user_id = (int)$_SESSION['user_id'];
        $book_id = (int)$_POST['reserve_book_id'];
        // Check if book exists and is unavailable
        $stmt = $conn->prepare("SELECT title, available_copies FROM books WHERE book_id = ?");
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $book_check = $result->fetch_assoc();
        $stmt->close();
        if (!$book_check) {
            $reserve_error = "Book not found.";
        } elseif ($book_check['available_copies'] > 0) {
            $reserve_error = "This book is currently available to borrow.";
        } else {
            // Check for an existing PENDING reservation
            $stmt = $conn->prepare("SELECT COUNT(reservation_id) FROM reservations WHERE user_id = ? AND book_id = ? AND (status = 'pending' OR status IS NULL OR TRIM(status) = '')");
            $stmt->bind_param("ii", $user_id, $book_id);
            $stmt->execute();
            $stmt->bind_result($reservation_count);
            $stmt->fetch();
            $stmt->close();
            if ($reservation_count > 0) {
                $reserve_error = "You already have a pending reservation for this book.";
            } else {
                // Insert new reservation
                $reservation_date = date('Y-m-d H:i:s');
                $status = 'pending';
                $stmt = $conn->prepare("INSERT INTO reservations (user_id, book_id, reservation_date, status) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiss", $user_id, $book_id, $reservation_date, $status);
                if ($stmt->execute()) {
                    $reserve_success = "You have successfully reserved the book.";
                } else {
                    $reserve_error = "Failed to reserve book. Error: " . $stmt->error;
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
    <title>View Book | Book Stop</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>

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
                <h3><?= htmlspecialchars($user['full_name'] ?? 'Student') ?></h3>
                <p><?= htmlspecialchars($user['user_type'] ?? 'Student') ?></p>
            </div>
            <nav>
                <ul class="sidebar-menu">
                    <li><a href="student_page.php" class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'student_page.php' ? ' active' : '' ?>">Dashboard</a></li>
                    <li><a href="my-profile.php" class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'my-profile.php' ? ' active' : '' ?>">My Profile</a></li>
                    <li><a href="catalog.php" class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'catalog.php' ? ' active' : '' ?>">Browse Books</a></li>
                    <li><a href="borrow-book.php" class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'borrow-book.php' ? ' active' : '' ?>">Borrow Book</a></li>
                    <li><a href="return-book.php" class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'return-book.php' ? ' active' : '' ?>">Returned Book</a></li>
                    <li><a href="settings.php" class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? ' active' : '' ?>">Settings</a></li>
                </ul>
            </nav>
        </div>
        <div class="dashboard-main">
            <div class="browse-container" style="max-width:700px;margin:40px auto;">
                <a href="catalog.php" class="back-link" style="margin-bottom:1.5rem;display:inline-block;">
                    <i class="fas fa-arrow-left"></i> Back to Catalog
                </a>
                <div class="book-card" style="display:flex;align-items:flex-start;gap:2rem;padding:2rem;">
                    <div class="book-cover" style="width:140px;min-width:140px;height:180px;display:flex;align-items:center;justify-content:center;background:#f0f2f5;border-radius:16px;overflow:hidden;">
                        <?php
                            $coverPath = !empty($book['cover_image']) ? $book['cover_image'] : '';
                            // If not an absolute URL, prepend uploads/ if not already present
                            if ($coverPath && !preg_match('/^https?:\/\//', $coverPath) && strpos($coverPath, 'uploads/') !== 0) {
                                $coverPath = 'uploads/covers/' . $coverPath;
                            }
                        ?>
                        <?php if (!empty($coverPath)): ?>
                            <img src="<?= htmlspecialchars($coverPath) ?>" alt="Book Cover"
                                 style="width:100%;height:100%;object-fit:cover;border-radius:16px;"
                                 onerror="this.onerror=null;this.style.display='none';this.parentNode.innerHTML='<i class=\'fas fa-book\' style=\'font-size:3rem;color:#532c2e;\'></i>';">
                        <?php else: ?>
                            <i class="fas fa-book" style="font-size:3rem;color:#532c2e;"></i>
                        <?php endif; ?>
                    </div>
                    <div class="book-details" style="flex:1;display:flex;flex-direction:column;gap:0.7rem;">
                        <h2 class="book-title" style="margin:0 0 0.5rem 0;font-size:2rem;font-weight:700;color:#2c3e50;">
                            <?= htmlspecialchars($book['title']) ?>
                        </h2>
                        <div class="book-meta" style="font-size:1.08rem;color:#444;">
                            <div><strong>ISBN:</strong> <?= htmlspecialchars($book['isbn']) ?></div>
                            <div><strong>Author:</strong> <?= htmlspecialchars($book['author']) ?></div>
                            <div><strong>Category:</strong> <?= htmlspecialchars($book['category']) ?></div>
                            <div><strong>Available Copies:</strong> <?= (int)$book['available_copies'] ?></div>
                            <div><strong>Fine:</strong> <?= isset($book['book_fine']) && $book['book_fine'] !== '' ? '₱' . number_format($book['book_fine'], 2) . ' / day overdue' : 'No fine' ?></div>
                        </div>
                        <div class="book-description" style="margin:0.7rem 0 0.5rem 0;color:#555;">
                            <strong>Description:</strong><br>
                            <?= nl2br(htmlspecialchars($book['description'])) ?>
                        </div>
                        <?php if ($reserve_success): ?>
                            <div class="alert alert-success" style="margin-top:1rem;"> <?= htmlspecialchars($reserve_success) ?> </div>
                        <?php elseif ($reserve_error): ?>
                            <div class="alert alert-error" style="margin-top:1rem;"> <?= htmlspecialchars($reserve_error) ?> </div>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['user_id']) && $book['available_copies'] > 0): ?>
                            <a href="borrow-book.php?id=<?= urlencode($book['book_id']) ?>" class="borrow-btn" style="margin-top: 1rem; width: 100%; max-width: 250px; box-sizing: border-box;">
                                <i class="fas fa-book-reader"></i> Borrow
                            </a>
                        <?php elseif ($book['available_copies'] < 1): ?>
                            <div class="alert alert-error" style="margin-top:1rem;">Not available for borrowing.</div>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <form method="post" style="margin-top:0.7rem;">
                                <input type="hidden" name="reserve_book_id" value="<?= $book['book_id'] ?>">
                                <button type="submit" class="reserve-btn" style="width: 100%; max-width: 250px; background: #C5832B; color: #fff; display: inline-block; text-align: center; padding: 0.7rem 0; border-radius: 6px; font-weight: 600; border: none; font-size: 1rem;">
                                    <i class="fas fa-calendar-plus"></i> Reserve
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>