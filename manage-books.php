<?php
session_start();
require_once 'config.php';

// Only allow admins to access this page
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
    if ($stmt->fetch() && !empty($full_name)) {
        $admin_name = explode(' ', trim($full_name))[0];
    }
    $stmt->close();
}

// Notification badge
$pending_requests_count = 0;
$pending_result = $conn->query("SELECT COUNT(*) as count FROM extension_requests WHERE status = 'pending'");
if ($pending_result && $row = $pending_result->fetch_assoc()) {
    $pending_requests_count = (int)$row['count'];
}

// Fetch books
$books = [];
$result = $conn->query("SELECT * FROM books ORDER BY title ASC");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $books[] = $row;
    }
}

// Success/error messages from add/edit/delete
$msg = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'added') $msg = "Book added successfully!";
    elseif ($_GET['msg'] === 'updated') $msg = "Book updated successfully!";
    elseif ($_GET['msg'] === 'deleted') $msg = "Book deleted successfully!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Books | Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <header class="admin-header">
        <h1><i class="fas fa-book"></i> Manage Books</h1>
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
                <li><a href="manage-books.php" class="nav-link active"><i class="fas fa-book"></i> Manage Books</a></li>
                <li><a href="manage-users.php" class="nav-link"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="manage-borrow.php" class="nav-link"><i class="fas fa-history"></i> Borrowings</a></li>
                <li>
                    <a href="extension-requests.php" class="nav-link">
                        <i class="fas fa-hourglass-half"></i> Extension Requests
                        <?php if ($pending_requests_count > 0): ?>
                            <span style="background:#e74c3c;color:#fff;padding:2px 8px;border-radius:12px;font-size:0.9em;margin-left:8px;">
                                <?php echo $pending_requests_count; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
        </aside>
        <div class="admin-container">
            <main class="admin-main">
                <section class="admin-section active">
                    <h2>Books List</h2>

                    <?php if ($msg): ?>
                        <div class="alert success"><i class="fas fa-check-circle"></i> <?= $msg ?></div>
                    <?php endif; ?>

                    <a href="add-book.php" class="button add"><i class="fas fa-plus"></i> Add Book</a>

                    <div style="overflow-x:auto; margin-top:1rem;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>Category</th>
                                    <th>Available</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($books)): ?>
                                    <tr><td colspan="5" class="no-data">No books found in the library.</td></tr>
                                <?php else: foreach ($books as $book): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($book['title']) ?></td>
                                        <td><?= htmlspecialchars($book['author']) ?></td>
                                        <td><?= htmlspecialchars($book['category']) ?></td>
                                        <td><?= (int)$book['available_copies'] ?></td>
                                        <td class="crud-actions">
                                            <a href="edit-book.php?id=<?= (int)$book['book_id'] ?>" class="button edit">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <form action="delete-book.php" method="post" style="display:inline;" onsubmit="return confirm('Delete this book?');">
                                                <input type="hidden" name="id" value="<?= (int)$book['book_id'] ?>">
                                                <button type="submit" class="button delete"><i class="fas fa-trash"></i> Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </main>
        </div>
    </div>
</body>
</html>
