<?php
require_once 'config.php';
session_start();

// Optional: Only allow admin access
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit;
}

// Fetch all overdue books for all users
$stmt = $conn->prepare("SELECT u.full_name, b.title, br.borrow_date, br.due_date
    FROM borrow_records br
    JOIN books b ON br.book_id = b.book_id
    JOIN users u ON br.user_id = u.user_id
    WHERE br.due_date < CURDATE() AND br.return_date IS NULL
    ORDER BY br.due_date ASC");
$stmt->execute();
$result = $stmt->get_result();

$overdue_books = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $overdue_books[] = $row;
    }
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Overdue Books | Book Stop Admin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="dashboard-header">
        <h1><i class="fas fa-book-reader"></i> Book Stop - Admin</h1>
        <a href="login_register.php?logout=1" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </header>
    <div class="dashboard-layout">
        <div class="dashboard-sidebar">
            <div class="user-info">
                <i class="fas fa-user-circle"></i>
                <h3><?= htmlspecialchars($_SESSION['full_name'] ?? 'Admin') ?></h3>
                <p>Admin</p>
            </div>
            <nav>
                <ul class="sidebar-menu">
                    <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="manage-users.php"><i class="fas fa-users"></i> Manage Users</a></li>
                    <li><a href="manage-books.php"><i class="fas fa-book"></i> Manage Books</a></li>
                    <li><a href="overdue.php" class="nav-link active"><i class="fas fa-exclamation-triangle"></i> Overdue Books</a></li>
                    <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                </ul>
            </nav>
        </div>
        <div class="dashboard-main">
            <div class="borrow-container">
                <h2 style="margin-bottom:1.5rem; color:#b71c1c; display:flex; align-items:center; gap:0.7rem;">
                    <i class="fas fa-exclamation-triangle"></i> All Overdue Books
                </h2>
                <?php if (count($overdue_books) === 0): ?>
                    <div class="alert alert-success" style="margin-bottom:1.2rem;">There are no overdue books at this time.</div>
                <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table class="reservation-table" style="width:100%; background:#fff; border-radius:12px; box-shadow:0 2px 12px rgba(44,62,80,0.10); border-collapse:separate; border-spacing:0;">
                            <thead>
                                <tr style="background:#f5e9e0;">
                                    <th style="padding:1rem; text-align:center;">Student Name</th>
                                    <th style="padding:1rem; text-align:center;">Book Title</th>
                                    <th style="padding:1rem; text-align:center;">Borrowed On</th>
                                    <th style="padding:1rem; text-align:center;">Due Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($overdue_books as $book): ?>
                                    <tr style="border-bottom:1px solid #f0e6db;">
                                        <td style="padding:1rem; text-align:center;"><?= htmlspecialchars($book['full_name']) ?></td>
                                        <td style="padding:1rem; text-align:center;"><?= htmlspecialchars($book['title']) ?></td>
                                        <td style="padding:1rem; text-align:center;"><?= htmlspecialchars(date('M d, Y', strtotime($book['borrow_date']))) ?></td>
                                        <td style="padding:1rem; text-align:center; color:#b71c1c; font-weight:600;"><?= htmlspecialchars(date('M d, Y', strtotime($book['due_date']))) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>