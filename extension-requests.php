<?php
session_start();
require_once 'config.php';

// Only allow admins
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit();
}

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

// Fetch extension requests
$query = "
    SELECT er.*, b.title, u.full_name
    FROM extension_requests er
    JOIN books b ON er.book_id = b.book_id
    JOIN users u ON er.user_id = u.user_id
    ORDER BY er.request_date DESC
";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Extension Requests</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<header class="admin-header">
    <h1><i class="fas fa-hourglass-half"></i> Extension Requests</h1>
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
            <li><a href="extension-requests.php" class="nav-link active"><i class="fas fa-hourglass-half"></i> Extension Requests</a></li>
        </ul>
    </aside>

    <div class="admin-container">
        <main class="admin-main">
            <section class="admin-section active">
                <h2>Extension Requests</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Request ID</th>
                            <th>User</th>
                            <th>Book</th>
                            <th>New Return Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $row['request_id'] ?></td>
                                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                                    <td><?= htmlspecialchars($row['title']) ?></td>
                                    <td><?= htmlspecialchars($row['new_return_date']) ?></td>
                                    <td>
                                        <?php if ($row['status'] === 'pending'): ?>
                                            <span style="color: #e67e22;">Pending</span>
                                        <?php elseif ($row['status'] === 'approved'): ?>
                                            <span style="color: #2ecc71;">Approved</span>
                                        <?php else: ?>
                                            <span style="color: #e74c3c;">Denied</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($row['status'] === 'pending'): ?>
                                            <form action="process-extension.php" method="post" style="display:inline;">
                                                <input type="hidden" name="request_id" value="<?= $row['request_id'] ?>">
                                                <button type="submit" name="action" value="approve" class="button approve">Approve</button>
                                                <button type="submit" name="action" value="deny" class="button delete">Deny</button>
                                            </form>
                                        <?php else: ?>
                                            <i class="fas fa-check-circle" style="color:gray;"></i>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6">No extension requests found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
</div>
</body>
</html>
