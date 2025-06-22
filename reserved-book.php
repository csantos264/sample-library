<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$user = []; // Fetch user info if needed

// Fetch reserved books for this user
$reserved_books = [];
$stmt = $conn->prepare("SELECT b.title, b.author, b.category, r.reservation_date, r.status 
    FROM reservations r 
    JOIN books b ON r.book_id = b.book_id 
    WHERE r.user_id = ? 
    ORDER BY r.reservation_date DESC");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $reserved_books[] = $row;
    }
    $stmt->close();
} else {
    $error = "Query error: " . htmlspecialchars($conn->error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reserved Books | Book Stop</title>
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
                <h3><?= htmlspecialchars($_SESSION['full_name'] ?? 'Student') ?></h3>
                <p>Student</p>
            </div>
            <nav>
                <ul class="sidebar-menu">
                    <li><a href="student_page.php" class="nav-link">Dashboard</a></li>
                    <li><a href="my-profile.php" class="nav-link">My Profile</a></li>
                    <li><a href="catalog.php" class="nav-link">Browse Books</a></li>
                    <li><a href="borrow-book.php" class="nav-link">Borrow Book</a></li>
                    <li><a href="return-book.php" class="nav-link">Returned Book</a></li>
                    <li><a href="reserved-book.php" class="nav-link active">Reserved Book</a></li>
                    <li><a href="settings.php" class="nav-link">Settings</a></li>
                </ul>
            </nav>
        </div>
        <div class="dashboard-main">
            <div class="card">
                <h2><i class="fas fa-bookmark"></i> My Reserved Books</h2>
                <?php if (isset($error)): ?>
                    <div class="alert alert-error"><?= $error ?></div>
                <?php endif; ?>
                <?php if (count($reserved_books) === 0): ?>
                    <div class="no-reserved">
                        <i class="fas fa-info-circle"></i> You have no reserved books.
                    </div>
                <?php else: ?>
                    <table class="reserved-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Category</th>
                                <th>Reserved On</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reserved_books as $book): ?>
                                <tr>
                                    <td><?= htmlspecialchars($book['title']) ?></td>
                                    <td><?= htmlspecialchars($book['author']) ?></td>
                                    <td><?= htmlspecialchars($book['category']) ?></td>
                                    <td><?= date('M d, Y', strtotime($book['reservation_date'])) ?></td>
                                    <td>
                                        <span class="status-badge <?= strtolower($book['status']) ?>">
                                            <?= ucfirst($book['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>