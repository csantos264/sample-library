<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit();
}


$admin_name = 'Admin';
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT full_name FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->bind_result($full_name);
    if ($stmt->fetch() && !empty($full_name)) {
        $admin_name = explode(' ', trim($full_name))[0];
    }
    $stmt->close();
}


$total_books = 0;
$total_users = 0;
$issued_books = 0;
$overdue_books = 0;

if ($result = $conn->query("SELECT COUNT(*) as count FROM books")) {
    $total_books = (int)$result->fetch_assoc()['count'];
}
if ($result = $conn->query("SELECT COUNT(*) as count FROM users WHERE user_type != 'admin'")) {
    $total_users = (int)$result->fetch_assoc()['count'];
}
if ($result = $conn->query("SELECT COUNT(*) as count FROM borrow_records WHERE return_date IS NULL")) {
    $issued_books = (int)$result->fetch_assoc()['count'];
}
// Only count as overdue if there is no approved extension with a new due date in the future
if ($result = $conn->query("SELECT COUNT(*) as count FROM borrow_records br WHERE br.return_date IS NULL AND br.due_date < CURDATE() AND NOT EXISTS (SELECT 1 FROM extension_requests er WHERE er.book_id = br.book_id AND er.user_id = br.user_id AND er.status = 'approved' AND er.new_return_date > CURDATE())")) {
    $overdue_books = (int)$result->fetch_assoc()['count'];
}


$pending_requests_count = 0;
$pending_result = $conn->query("SELECT COUNT(*) as count FROM extension_requests WHERE status = 'pending'");
if ($pending_result && $row = $pending_result->fetch_assoc()) {
    $pending_requests_count = (int)$row['count'];
}

// Fetch pending reservation count
$pending_reservations_count = 0;
$pending_reservations_result = $conn->query("SELECT COUNT(*) as count FROM reservations WHERE status = 'pending'");
if ($pending_reservations_result && $row = $pending_reservations_result->fetch_assoc()) {
    $pending_reservations_count = (int)$row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Library Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    
    <header class="admin-header">
        <h1><i class="fas fa-user-shield"></i> Admin Dashboard
            <span style="font-size:1rem; margin-left:15px;">
                <i class="fas fa-clock"></i> Extension Requests: 
                <span style="background:#e74c3c;color:#fff;padding:2px 8px;border-radius:12px;font-weight:bold;">
                    <?= $pending_requests_count ?>
                </span>
            </span>
            <span style="font-size:1rem; margin-left:15px;">
                <i class="fas fa-calendar-check"></i> Reservation Requests: 
                <span style="background:#3498db;color:#fff;padding:2px 8px;border-radius:12px;font-weight:bold;">
                    <?= $pending_reservations_count ?>
                </span>
            </span>
        </h1>
        <a href="login_register.php?logout=1" class="logout-btn" aria-label="Logout">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </header>

    <div class="admin-wrapper">
        <aside class="admin-sidebar" aria-label="Sidebar">
            <div class="user-info">
                <i class="fas fa-user-circle"></i>
                <h3><?php echo htmlspecialchars($admin_name); ?></h3>
                <p><?php echo ucfirst($_SESSION['user_type']); ?></p>
            </div>
            <ul class="nav-links">
                <li><a href="#" class="nav-link active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="manage-books.php" class="nav-link"><i class="fas fa-book"></i> Manage Books</a></li>
                <li><a href="manage-users.php" class="nav-link"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="manage-borrow.php" class="nav-link"><i class="fas fa-history"></i> Borrowings</a></li>
                <li>
                    <a href="extension-requests.php" class="nav-link">
                        <i class="fas fa-hourglass-half"></i> Extension Requests
                        <?php if ($pending_requests_count > 0): ?>
                            <span style="background:#e74c3c;color:#fff;padding:2px 8px;border-radius:12px;font-size:0.9em;margin-left:8px;">
                                <?= $pending_requests_count ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a href="reservation-requests.php" class="nav-link">
                        <i class="fas fa-calendar-check"></i> Reservation Requests
                        <?php if ($pending_reservations_count > 0): ?>
                            <span style="background:#3498db;color:#fff;padding:2px 8px;border-radius:12px;font-size:0.9em;margin-left:8px;">
                                <?= $pending_reservations_count ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
        </aside>

        <div class="admin-container">
            <main class="admin-main">
                <section id="section-dashboard" class="admin-section active">
                    <h2>Dashboard</h2>
                    <div class="welcome-message">
                        <span style="font-size:1.2rem;">👋 Welcome back, <strong><?php echo htmlspecialchars($admin_name); ?></strong>! Manage your library efficiently from your dashboard.</span>
                    </div>
                    <div class="stats-container">
                        <div class="stat-card books">
                            <div class="icon"><i class="fas fa-book"></i></div>
                            <div class="info">
                                <h3><?php echo $total_books; ?></h3>
                                <p>Total Books</p>
                            </div>
                        </div>
                        <div class="stat-card users">
                            <div class="icon"><i class="fas fa-users"></i></div>
                            <div class="info">
                                <h3><?php echo $total_users; ?></h3>
                                <p>Total Users</p>
                            </div>
                        </div>
                        <div class="stat-card issued">
                            <div class="icon"><i class="fas fa-book-reader"></i></div>
                            <div class="info">
                                <h3><?php echo $issued_books; ?></h3>
                                <p>Books Issued</p>
                            </div>
                        </div>
                        <div class="stat-card overdue">
                            <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
                            <div class="info">
                                <h3><?php echo $overdue_books; ?></h3>
                                <p>Overdue Books</p>
                            </div>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </div>
</body>
</html>