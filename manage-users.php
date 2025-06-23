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

$pending_requests_count = 0;
$pending_result = $conn->query("SELECT COUNT(*) as count FROM extension_requests WHERE status = 'pending'");
if ($pending_result && $row = $pending_result->fetch_assoc()) {
    $pending_requests_count = (int)$row['count'];
}

$users = [];
$result = $conn->query("SELECT * FROM users WHERE user_type != 'admin' ORDER BY full_name ASC");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users | Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <header class="admin-header">
        <h1><i class="fas fa-users"></i> Manage Users</h1>
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
                <li><a href="manage-users.php" class="nav-link active"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="manage-borrow.php" class="nav-link"><i class="fas fa-history"></i> Borrowings</a></li>
                <li><a href="#" class="nav-link"><i class="fas fa-hourglass-half"></i> Extension Requests <?php if($pending_requests_count > 0): ?><span class="notification-badge"><?= $pending_requests_count ?></span><?php endif; ?></a></li>
            </ul>
        </aside>
        <div class="admin-container">
            <main class="admin-main">
                <section class="admin-section active">
                    <h2>Users List</h2>
                    <?php if (empty($users)): ?>
                        <div class="no-data">No users found.</div>
                    <?php else: ?>
                        <div style="overflow-x:auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Type</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['full_name']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td><?= htmlspecialchars($user['user_type']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                    <?php endif; ?>
                </section>
            </main>
        </div>
    </div>
</body>
</html>