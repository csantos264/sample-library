<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Check if user is not an admin (only regular users can access notifications page)
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
    header('Location: admin_page.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Mark notification as read if requested
if (isset($_POST['mark_read']) && isset($_POST['notification_id'])) {
    $notification_id = (int)$_POST['notification_id'];
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notification_id, $user_id);
    $stmt->execute();
    $stmt->close();
    header('Location: notifications.php');
    exit();
}

// Mark all notifications as read
if (isset($_POST['mark_all_read'])) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    header('Location: notifications.php');
    exit();
}

// Get user info
$stmt = $conn->prepare("SELECT full_name FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($full_name);
$stmt->fetch();
$stmt->close();

// Get notifications
$stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}
$stmt->close();

// Count unread notifications
$unread_count = 0;
foreach ($notifications as $notification) {
    if (!$notification['is_read']) {
        $unread_count++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notifications | Book Stop</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .notification-item {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid #ddd;
            transition: all 0.3s ease;
        }
        .notification-item:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .notification-item.unread {
            border-left-color: #C5832B;
            background: #fff9f0;
        }
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        .notification-title {
            font-weight: bold;
            color: #532c2e;
            margin: 0;
        }
        .notification-time {
            font-size: 0.8rem;
            color: #666;
        }
        .notification-message {
            color: #333;
            line-height: 1.4;
            margin-bottom: 0.5rem;
        }
        .notification-type {
            display: inline-block;
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        .type-extension_approved {
            background: #e8f5e8;
            color: #2e7d32;
        }
        .type-extension_denied {
            background: #ffebee;
            color: #c62828;
        }
        .type-fine_applied {
            background: #fff3e0;
            color: #f57c00;
        }
        .mark-read-btn {
            background: #C5832B;
            color: white;
            border: none;
            padding: 0.3rem 0.8rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
        }
        .mark-read-btn:hover {
            background: #b37426;
        }
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #ddd;
        }
    </style>
</head>
<body>
    <header class="dashboard-header">
        <h1><i class="fas fa-bell"></i> <a href="student_page.php" class="home-btn" style="color:inherit;text-decoration:none;">Book Stop</a></h1>
        <a href="login_register.php?logout=1" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </header>
    <div class="dashboard-layout">
        <div class="dashboard-sidebar">
            <div class="user-info">
                <i class="fas fa-user-circle"></i>
                <h3><?= htmlspecialchars($full_name ?? 'User') ?></h3>
                <p><?= htmlspecialchars($_SESSION['user_type'] ?? 'User') ?></p>
            </div>
            <nav>
                <ul class="sidebar-menu">
                    <li><a href="student_page.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="my-profile.php" class="nav-link"><i class="fas fa-user"></i> My Profile</a></li>
                    <li><a href="catalog.php" class="nav-link"><i class="fas fa-book"></i> Browse Books</a></li>
                    <li><a href="borrow-book.php" class="nav-link"><i class="fas fa-book-reader"></i> Borrowed Books</a></li>
                    <li><a href="my-reservation.php" class="nav-link"><i class="fas fa-calendar-check"></i> My Reservations</a></li>
                    <li><a href="notifications.php" class="nav-link active"><i class="fas fa-bell"></i> Notifications <?php if ($unread_count > 0): ?><span style="background:#e74c3c;color:#fff;padding:2px 6px;border-radius:10px;font-size:0.7rem;"><?= $unread_count ?></span><?php endif; ?></a></li>
                    <li><a href="settings.php" class="nav-link"><i class="fas fa-cog"></i> Settings</a></li>
                </ul>
            </nav>
        </div>
        <div class="dashboard-main">
            <div class="notifications-container">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h2 style="color: #532c2e; margin: 0;">
                        <i class="fas fa-bell"></i> Notifications
                        <?php if ($unread_count > 0): ?>
                            <span style="background: #e74c3c; color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.8rem; margin-left: 10px;">
                                <?= $unread_count ?> unread
                            </span>
                        <?php endif; ?>
                    </h2>
                    <?php if ($unread_count > 0): ?>
                        <form method="post" style="display: inline;">
                            <button type="submit" name="mark_all_read" class="btn" style="background: #C5832B; color: white; padding: 0.5rem 1rem; border: none; border-radius: 6px; cursor: pointer;">
                                <i class="fas fa-check-double"></i> Mark All as Read
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <?php if (empty($notifications)): ?>
                    <div class="empty-state">
                        <i class="fas fa-bell-slash"></i>
                        <h3>No notifications yet</h3>
                        <p>You'll see notifications here when your extension requests are processed or when there are important updates.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notifications as $notification): ?>
                        <div class="notification-item<?= !$notification['is_read'] ? ' unread' : '' ?>" style="border-left: 4px solid <?= $notification['type'] === 'extension_approved' ? '#2ecc71' : ($notification['type'] === 'extension_denied' ? '#e74c3c' : '#C5832B') ?>; background: <?= $notification['type'] === 'extension_approved' ? '#e8f5e8' : ($notification['type'] === 'extension_denied' ? '#ffebee' : '#fff9f0') ?>;">
                            <div class="notification-header">
                                <h4 class="notification-title">
                                    <?= htmlspecialchars($notification['title']) ?>
                                    <?php if ($notification['type'] === 'extension_approved'): ?>
                                        <span style="color:#2ecc71;font-size:1.1em;margin-left:0.5em;" title="Extension Approved"><i class="fas fa-check-circle"></i></span>
                                    <?php elseif ($notification['type'] === 'extension_denied'): ?>
                                        <span style="color:#e74c3c;font-size:1.1em;margin-left:0.5em;" title="Extension Denied"><i class="fas fa-times-circle"></i></span>
                                    <?php endif; ?>
                                </h4>
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <span class="notification-type" style="background: #eee; color: #532c2e; padding: 0.2rem 0.7rem; border-radius: 12px; font-size: 0.7rem; font-weight: bold; text-transform: uppercase;">
                                        <?= str_replace('_', ' ', $notification['type']) ?>
                                    </span>
                                    <span class="notification-time">
                                        <?= date('M d, Y g:i A', strtotime($notification['created_at'])) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="notification-message">
                                <?= htmlspecialchars($notification['message']) ?>
                            </div>
                            <?php if (!$notification['is_read']): ?>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="notification_id" value="<?= $notification['notification_id'] ?>">
                                    <button type="submit" name="mark_read" class="mark-read-btn">
                                        <i class="fas fa-check"></i> Mark as Read
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html> 