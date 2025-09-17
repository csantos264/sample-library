<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Check if user is not an admin (only regular users can access my reservations page)
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
    header('Location: admin_page.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$user = null;

// Fetch user info
$stmt = $conn->prepare("SELECT full_name, user_type FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    echo "User not found.";
    exit();
}

// Fetch user's reservations
$reservations = [];
$stmt = $conn->prepare("SELECT b.title, r.reservation_date, r.status
    FROM reservations r
    JOIN books b ON r.book_id = b.book_id
    WHERE r.user_id = ?
    ORDER BY r.reservation_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $reservations[] = $row;
}
$stmt->close();

// Fetch pending extension requests count for the user
$pending_extensions_count = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM extension_requests WHERE user_id = ? AND status = 'pending'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $pending_extensions_count = (int)$row['cnt'];
}
$stmt->close();

// Fetch unread notifications count for the user
$unread_notifications = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $unread_notifications = (int)$row['cnt'];
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Reservations | Book Stop</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
                    <li><a href="borrow-book.php" class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'borrow-book.php' ? ' active' : '' ?>"><i class="fas fa-book-reader"></i> Borrowed Books</a></li>
                    <li><a href="my-reservation.php" class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'my-reservation.php' ? ' active' : '' ?>"><i class="fas fa-calendar-check"></i> My Reservations</a></li>
                    <li><a href="notifications.php" class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'notifications.php' ? ' active' : '' ?>"><i class="fas fa-bell"></i> Notifications <?php if ($unread_notifications > 0): ?><span style="background:#e74c3c;color:#fff;padding:2px 6px;border-radius:10px;font-size:0.7rem;"><?= $unread_notifications ?></span><?php endif; ?></a></li>
                    <li><a href="settings.php" class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? ' active' : '' ?>"><i class="fas fa-cog"></i> Settings</a></li>
                </ul>
            </nav>
        </div>
        <div class="dashboard-main">
            <div class="reservation-container">
                <h2 style="margin-bottom:1.5rem; color:#532c2e; display:flex; align-items:center; gap:0.7rem; font-weight: bold; margin-top: 4%; padding-left: 3%;">
                    <i class="fas fa-book-reader"></i> My Reservations
                </h2>
                <a href="student_page.php" class="back-link" style="display: inline-block; margin-bottom: 1.5rem; padding: 0.6rem 1.2rem; background-color: #532c2e; color: #fff; text-decoration: none; border-radius: 8px; font-weight: 500; transition: background-color 0.3s; margin-left: 5%;">&larr; Back to Dashboard</a>

                <div style="margin:2.5rem auto 0 auto; max-width: 1200px; width: 100%;">
                    <div style="overflow-x:auto;">
                        <table class="reservation-table" style="width:100%; background:#fff; border-radius:12px; box-shadow:0 2px 12px rgba(44,62,80,0.10); border-collapse:separate; border-spacing:0;">
                            <thead>
                                <tr style="background:#f5e9e0;">
                                    <th style="padding:1rem; text-align:center; font-size:1.08rem;">Book Title</th>
                                    <th style="padding:1rem; text-align:center; font-size:1.08rem;">Reservation Date</th>
                                    <th style="padding:1rem; text-align:center; font-size:1.08rem;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($reservations)) : ?>
                                <tr><td colspan="3" style="padding:2rem; text-align:center; color:#888;">No reservations found.</td></tr>
                            <?php else : ?>
                                <?php foreach ($reservations as $row) : ?>
                                    <tr style='border-bottom:1px solid #f0e6db;'>
                                        <td style='padding:1rem; text-align:center;'><?= htmlspecialchars($row['title']) ?></td>
                                        <td style='padding:1rem; text-align:center;'><?= htmlspecialchars(date('M d, Y', strtotime($row['reservation_date']))) ?></td>
                                                                                <td style='padding:1rem; text-align:center;'>
                                            <?php 
                                                $status = !empty($row['status']) ? $row['status'] : 'pending';
                                                $status_class = strtolower(htmlspecialchars($status));
                                                $status_text = htmlspecialchars(ucfirst($status));
                                            ?>
                                            <span class="status-badge status-<?= $status_class ?>"><?= $status_text ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>