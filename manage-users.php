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

// Fetch pending reservation count
$pending_reservations_count = 0;
$pending_reservations_result = $conn->query("SELECT COUNT(*) as count FROM reservations WHERE status = 'pending'");
if ($pending_reservations_result && $row = $pending_reservations_result->fetch_assoc()) {
    $pending_reservations_count = (int)$row['count'];
}

// Handle search
$search = '';
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_type != 'admin' AND (full_name LIKE ? OR email LIKE ?) ORDER BY full_name ASC");
    $like = "%$search%";
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();
    $users = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
    $stmt->close();
} else {
    $users = [];
    $result = $conn->query("SELECT * FROM users WHERE user_type != 'admin' ORDER BY full_name ASC");
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
}

// Handle delete user
if (isset($_POST['delete_user_id'])) {
    $delete_id = intval($_POST['delete_user_id']);
    // Prevent admin deletion
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ? AND user_type != 'admin'");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();
    // Refresh to update the list
    header("Location: manage-users.php");
    exit();
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
                <section class="admin-section active">
                    <h2>Users List</h2>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <form method="get" style="display:flex;gap:10px;align-items:center;margin:0;">
            <input type="text" name="search" placeholder="Search by name or email..." value="<?= htmlspecialchars($search) ?>" style="padding:6px 10px;width:220px;border-radius:4px;border:1px solid #ccc;">
            <button type="submit" style="padding:6px 16px;border:none;background:#3498db;color:#fff;border-radius:4px;cursor:pointer;">
                <i class="fas fa-search"></i> Search
            </button>
            <?php if (!empty($search)): ?>
                <a href="manage-users.php" style="margin-left:10px;color:#e74c3c;text-decoration:underline;">Clear</a>
            <?php endif; ?>
        </form>
        <a href="add-user.php" style="padding:8px 18px;background:#2ecc71;color:#fff;border-radius:4px;text-decoration:none;">
            <i class="fas fa-user-plus"></i> Add User
        </a>
    </div>
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
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['full_name']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= htmlspecialchars($user['user_type']) ?></td>
                    <td>
                        <a href="edit-user.php?user_id=<?= $user['user_id'] ?>" style="background:#f1c40f;color:#fff;padding:6px 12px;border-radius:4px;text-decoration:none;margin-right:6px;">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                            <input type="hidden" name="delete_user_id" value="<?= $user['user_id'] ?>">
                            <button type="submit" style="background:#e74c3c;color:#fff;border:none;padding:6px 12px;border-radius:4px;cursor:pointer;">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                    </td>
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