<?php
session_start();
require_once 'config.php';

// Only allow admins
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit();
}

// Process flash messages
$success_msg = $_SESSION['success_msg'] ?? null;
$error_msg = $_SESSION['error_msg'] ?? null;
unset($_SESSION['success_msg'], $_SESSION['error_msg']);

// Get admin name
$admin_name = 'Admin';
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT full_name FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->bind_result($full_name);
    if ($stmt->fetch()) {
        $admin_name = explode(' ', trim($full_name))[0];
    }
    $stmt->close();
}

// Fetch ONLY pending reservation requests, including the number of available copies
$query = "
    SELECT r.reservation_id, u.full_name, b.title, r.reservation_date, r.status, b.available_copies
    FROM reservations r
    JOIN users u ON r.user_id = u.user_id
    JOIN books b ON r.book_id = b.book_id
    WHERE r.status = 'pending' OR r.status IS NULL OR TRIM(r.status) = ''
    ORDER BY r.reservation_date DESC
";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reservation Requests</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
<header class="admin-header">
    <h1><i class="fas fa-calendar-check"></i> Reservation Requests</h1>
    <a href="login_register.php?logout=1" class="logout-btn">Logout</a>
</header>
<div class="admin-wrapper">
    <aside class="admin-sidebar">
        <div class="user-info">
            <i class="fas fa-user-circle"></i>
            <h3><?= htmlspecialchars($admin_name) ?></h3>
            <p><?= ucfirst($_SESSION['user_type']) ?></p>
        </div>
        <ul class="nav-links">
            <li><a href="admin_page.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="manage-books.php" class="nav-link"><i class="fas fa-book"></i> Manage Books</a></li>
            <li><a href="manage-users.php" class="nav-link"><i class="fas fa-users"></i> Users</a></li>
            <li><a href="manage-borrow.php" class="nav-link"><i class="fas fa-history"></i> Borrowings</a></li>
            <li><a href="extension-requests.php" class="nav-link"><i class="fas fa-hourglass-half"></i> Extension Requests</a></li>
            <li><a href="reservation-requests.php" class="nav-link active"><i class="fas fa-calendar-check"></i> Reservation Requests</a></li>
        </ul>
    </aside>

    <div class="admin-container">
        <main class="admin-main">

            <section class="admin-section active">
                <h2>Pending Reservations</h2>
                <?php if ($success_msg): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
                <?php endif; ?>
                <?php if ($error_msg): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error_msg) ?></div>
                <?php endif; ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>USER</th>
                            <th>BOOK</th>
                            <th>DATE</th>
                            <th>AVAILABLE</th>
                            <th>STATUS</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $row['reservation_id'] ?></td>
                                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                                    <td><?= htmlspecialchars($row['title']) ?></td>
                                    <td><?= date('M d, Y', strtotime($row['reservation_date'])) ?></td>
                                    <td>
                                        <span class="status-<?= $row['available_copies'] > 0 ? 'available' : 'unavailable' ?>">
                                            <?= $row['available_copies'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-pending">Pending</span>
                                    </td>
                                    <td>
                                        <form action="process-reservation.php" method="post" style="display: flex; gap: 5px; align-items: center;">
                                            <input type="hidden" name="reservation_id" value="<?= $row['reservation_id'] ?>">
                                            <button type="submit" name="action" value="approved"
                                                class="button approve"
                                                <?= ($row['available_copies'] < 1) ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : '' ?>
                                                title="<?= ($row['available_copies'] < 1) ? 'Cannot approve, no copies available' : 'Approve reservation' ?>">
                                                Approve
                                            </button>
                                            <button type="submit" name="action" value="denied"
                                                class="button delete"
                                                onclick="return confirm('Are you sure you want to deny this reservation?');"
                                                title="Deny reservation">
                                                Deny
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7">No reservation requests found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
</div>
</body>
</html>
