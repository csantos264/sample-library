<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit();
}
?>
<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Library Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="admin_dashboard.css">
    <style>
        .nav-links li {
            position: relative;
        }
        .notification-badge {
            background-color: #e74c3c;
            color: white;
            border-radius: 10px;
            padding: 1px 6px;
            font-size: 0.75rem;
            font-weight: bold;
            position: absolute;
            top: 12px;
            right: 12px;
            line-height: 1.2;
        }
        .button.return {
            background-color: #27ae60; /* Green for approve/return */
        }
        .button.return:hover {
            background-color: #2ecc71;
        }
    </style>
</head>
<body>

<aside class="admin-sidebar">
    <div class="user-info">
        <i class="fas fa-user-circle"></i>
        <?php
        $admin_name = 'Admin';
        if (isset($_SESSION['user_id'])) {
            $stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $stmt->bind_result($full_name);
            if ($stmt->fetch()) {
                $admin_name = explode(' ', trim($full_name))[0];
            }
            $stmt->close();
        }
        ?>
        <h3><?php echo htmlspecialchars($admin_name); ?></h3>
        <p>Administrator</p>
    </div>
    <ul class="nav-links">
        <li><a href="#" class="nav-link active" data-section="dashboard"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="#" class="nav-link" data-section="books"><i class="fas fa-book"></i> Books</a></li>
        <li><a href="#" class="nav-link" data-section="users"><i class="fas fa-users"></i> Users</a></li>
        <li><a href="#" class="nav-link" data-section="borrowings"><i class="fas fa-history"></i> Borrowings</a></li>
        <?php $pending_requests_count = $conn->query("SELECT COUNT(*) as count FROM extension_requests WHERE status = 'pending'")->fetch_assoc()['count']; ?>
        <li><a href="#" class="nav-link" data-section="requests"><i class="fas fa-hourglass-half"></i> Extension Requests <?php if($pending_requests_count > 0): ?><span class="notification-badge"><?php echo $pending_requests_count; ?></span><?php endif; ?></a></li>
    </ul>
</aside>

<div class="admin-container">
    <header class="admin-header">
        <h1><i class="fas fa-user-shield"></i> Admin Dashboard</h1>
        <a href="login_register.php?logout=1" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </header>

    <main class="admin-main">
        <?php
        $msg = '';
        $err = '';


if (isset($_POST['add_book'])) {
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $isbn = trim($_POST['isbn'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $total = (int)($_POST['total_copies'] ?? 1);
    if ($title && $author && $isbn && $category && $total > 0) {
        $stmt = $conn->prepare("INSERT INTO books (title, author, isbn, category, total_copies, available_copies) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssii", $title, $author, $isbn, $category, $total, $total);
        if ($stmt->execute()) $msg = "Book added successfully.";
        else $err = "Failed to add book: " . $stmt->error;
        $stmt->close();
    } else {
        $err = "All fields are required and total copies must be positive.";
    }
}


if (isset($_POST['edit_book'])) {
    $id = (int)$_POST['book_id'];
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $isbn = trim($_POST['isbn'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $total = (int)($_POST['total_copies'] ?? 1);
    if ($id > 0 && $title && $author && $isbn && $category && $total > 0) {
        $stmt_old = $conn->prepare("SELECT total_copies, available_copies FROM books WHERE id=?");
        $stmt_old->bind_param("i", $id);
        $stmt_old->execute();
        $old = $stmt_old->get_result()->fetch_assoc();
        $stmt_old->close();

        $diff = $total - ($old['total_copies'] ?? 0);
        $new_avail = max(0, ($old['available_copies'] ?? 0) + $diff);

        $stmt = $conn->prepare("UPDATE books SET title=?, author=?, isbn=?, category=?, total_copies=?, available_copies=? WHERE id=?");
        $stmt->bind_param("ssssiii", $title, $author, $isbn, $category, $total, $new_avail, $id);
        if ($stmt->execute()) $msg = "Book updated successfully.";
        else $err = "Failed to update book: " . $stmt->error;
        $stmt->close();
    } else {
        $err = "All fields are required and total copies must be positive.";
    }
}


if (isset($_POST['delete_book'])) {
    $id = (int)$_POST['book_id'];
    $stmt = $conn->prepare("DELETE FROM books WHERE id=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) $msg = "Book deleted successfully.";
    else $err = "Error deleting book: " . $stmt->error;
    $stmt->close();
}


if (isset($_POST['delete_user'])) {
    $id = (int)$_POST['user_id'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) $msg = "User deleted successfully.";
    else $err = "Error deleting user: " . $stmt->error;
    $stmt->close();
}


if (isset($_POST['return_borrowing'])) {
    $borrowing_id = (int)$_POST['borrowing_id'];
    $conn->begin_transaction();
    try {
        $stmt1 = $conn->prepare("SELECT book_id FROM borrowings WHERE id = ? AND returned = 0");
        $stmt1->bind_param("i", $borrowing_id);
        $stmt1->execute();
        $result = $stmt1->get_result();
        if ($book_data = $result->fetch_assoc()) {
            $book_id = $book_data['book_id'];
            $stmt1->close();

            $stmt2 = $conn->prepare("UPDATE borrowings SET returned = 1 WHERE id = ?");
            $stmt2->bind_param("i", $borrowing_id);
            $stmt2->execute();
            $stmt2->close();

            $stmt3 = $conn->prepare("UPDATE books SET available_copies = available_copies + 1 WHERE id = ?");
            $stmt3->bind_param("i", $book_id);
            $stmt3->execute();
            $stmt3->close();

            $conn->commit();
            $msg = "Book returned successfully.";
        } else {
            $stmt1->close();
            $conn->rollback();
            $err = "Borrowing record not found or book already returned.";
        }
    } catch (Exception $e) {
        $conn->rollback();
        $err = "Database transaction failed: " . $e->getMessage();
    }
}


if (isset($_POST['approve_request'])) {
    $request_id = (int)$_POST['request_id'];
    $borrowing_id = (int)$_POST['borrowing_id'];
    $conn->begin_transaction();
    try {
        
        $stmt1 = $conn->prepare("UPDATE extension_requests SET status = 'approved' WHERE id = ?");
        $stmt1->bind_param("i", $request_id);
        $stmt1->execute();
        $stmt1->close();

        
        $stmt2 = $conn->prepare("UPDATE borrowings SET return_date = DATE_ADD(return_date, INTERVAL 7 DAY) WHERE id = ?");
        $stmt2->bind_param("i", $borrowing_id);
        $stmt2->execute();
        $stmt2->close();

        $conn->commit();
        $msg = "Extension request approved successfully. Due date extended by 7 days.";
    } catch (Exception $e) {
        $conn->rollback();
        $err = "Database transaction failed: " . $e->getMessage();
    }
}


if (isset($_POST['deny_request'])) {
    $request_id = (int)$_POST['request_id'];
    $stmt = $conn->prepare("UPDATE extension_requests SET status = 'denied' WHERE id = ?");
    $stmt->bind_param("i", $request_id);
    if ($stmt->execute()) {
        $msg = "Extension request denied.";
    } else {
        $err = "Failed to process request: " . $stmt->error;
    }
    $stmt->close();
}
        ?>

        <?php if ($msg): ?><div class="alert success"><i class="fas fa-check-circle"></i> <?php echo $msg; ?></div><?php endif; ?>
        <?php if ($err): ?><div class="alert error"><i class="fas fa-exclamation-circle"></i> <?php echo $err; ?></div><?php endif; ?>

        <?php
        // Fetch stats for the dashboard
        $total_books = $conn->query("SELECT COUNT(*) as count FROM books")->fetch_assoc()['count'];
        $total_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE role != 'admin'")->fetch_assoc()['count'];
        $issued_books = $conn->query("SELECT COUNT(*) as count FROM borrowings WHERE returned = 0")->fetch_assoc()['count'];
        $overdue_books = $conn->query("SELECT COUNT(*) as count FROM borrowings WHERE returned = 0 AND return_date < CURDATE()")->fetch_assoc()['count'];
        ?>
        <section id="section-dashboard" class="admin-section active">
            <h2>Dashboard</h2>
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

        <section id="section-books" class="admin-section">
            <h2>Books Management</h2>
            <button class="button add" data-modal="addBookModal"><i class="fas fa-plus"></i> Add Book</button>
            <table>
                <thead>
                    <tr><th>ID</th><th>Title</th><th>Author</th><th>ISBN</th><th>Category</th><th>Available</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php $books = $conn->query("SELECT * FROM books ORDER BY id DESC");
                if ($books && $books->num_rows > 0):
                    while ($book = $books->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo (int)$book['id']; ?></td>
                        <td><?php echo htmlspecialchars($book['title']); ?></td>
                        <td><?php echo htmlspecialchars($book['author']); ?></td>
                        <td><?php echo htmlspecialchars($book['isbn']); ?></td>
                        <td><?php echo htmlspecialchars($book['category']); ?></td>
                        <td><?php echo (int)$book['available_copies']; ?> / <?php echo (int)$book['total_copies']; ?></td>
                        <td>
                            <button class="button edit" onclick="showEditBookModal(<?php echo (int)$book['id']; ?>, '<?php echo htmlspecialchars(addslashes($book['title'])); ?>', '<?php echo htmlspecialchars(addslashes($book['author'])); ?>', '<?php echo htmlspecialchars(addslashes($book['isbn'])); ?>', '<?php echo htmlspecialchars(addslashes($book['category'])); ?>', <?php echo (int)$book['total_copies']; ?>)"><i class="fas fa-edit"></i> Edit</button>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="book_id" value="<?php echo (int)$book['id']; ?>">
                                <button type="submit" name="delete_book" class="button delete" onclick="return confirm('Delete this book?')"><i class="fas fa-trash"></i> Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="7" class="no-data">No books found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </section>

        <section id="section-users" class="admin-section">
            <h2>Users Management</h2>
            <table>
                <thead>
                    <tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php $users = $conn->query("SELECT * FROM users ORDER BY id DESC");
                if ($users && $users->num_rows > 0):
                    while ($user = $users->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo (int)$user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['role']); ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>">
                                <button type="submit" name="delete_user" class="button delete" onclick="return confirm('Delete this user?')"><i class="fas fa-trash"></i> Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="5" class="no-data">No users found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </section>

        <section id="section-borrowings" class="admin-section">
            <h2>Borrowings Overview</h2>
            <table>
                <thead>
                    <tr><th>ID</th><th>User</th><th>Book</th><th>Borrowed</th><th>Due</th><th>Returned</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php $borrowings = $conn->query("SELECT br.*, u.name as user_name, b.title as book_title FROM borrowings br JOIN users u ON br.user_id = u.id JOIN books b ON br.book_id = b.id ORDER BY br.id DESC");
                if ($borrowings && $borrowings->num_rows > 0):
                    while ($br = $borrowings->fetch_assoc()):
                        $is_overdue = (!$br['returned'] && strtotime($br['return_date']) < strtotime(date('Y-m-d'))); ?>
                    <tr<?php if ($is_overdue) echo ' style="background:#f8d7da;"'; ?>>
                        <td><?php echo (int)$br['id']; ?></td>
                        <td><?php echo htmlspecialchars($br['user_name']); ?></td>
                        <td><?php echo htmlspecialchars($br['book_title']); ?></td>
                        <td><?php echo htmlspecialchars($br['borrow_date']); ?></td>
                        <td><?php echo htmlspecialchars($br['return_date']); ?><?php if ($is_overdue): ?><span style="color:var(--danger);font-weight:bold;"> (Overdue)</span><?php endif; ?></td>
                        <td><?php echo $br['returned'] ? 'Yes' : 'No'; ?></td>
                        <td>
                            <?php if (!$br['returned']): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="borrowing_id" value="<?php echo (int)$br['id']; ?>">
                                <button type="submit" name="return_borrowing" class="button return">Mark as Returned</button>
                            </form>
                            <?php else: ?>
                            <span>-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="7" class="no-data">No borrowings found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </section>
        <section id="section-requests" class="admin-section">
            <h2>Pending Extension Requests</h2>
            <table>
                <thead>
                    <tr><th>Request ID</th><th>Student</th><th>Book Title</th><th>Current Due Date</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php
                $requests_query = "SELECT er.id, er.borrowing_id, u.name as user_name, b.title as book_title, br.return_date
                                   FROM extension_requests er
                                   JOIN borrowings br ON er.borrowing_id = br.id
                                   JOIN users u ON br.user_id = u.id
                                   JOIN books b ON br.book_id = b.id
                                   WHERE er.status = 'pending' ORDER BY er.id DESC";
                $requests = $conn->query($requests_query);
                if ($requests && $requests->num_rows > 0):
                    while ($req = $requests->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo (int)$req['id']; ?></td>
                        <td><?php echo htmlspecialchars($req['user_name']); ?></td>
                        <td><?php echo htmlspecialchars($req['book_title']); ?></td>
                        <td><?php echo htmlspecialchars(date('M d, Y', strtotime($req['return_date']))); ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="request_id" value="<?php echo (int)$req['id']; ?>">
                                <input type="hidden" name="borrowing_id" value="<?php echo (int)$req['borrowing_id']; ?>">
                                <button type="submit" name="approve_request" class="button return">Approve</button>
                            </form>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="request_id" value="<?php echo (int)$req['id']; ?>">
                                <button type="submit" name="deny_request" class="button delete">Deny</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="5" class="no-data">No pending extension requests.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>
</div>


<div id="addBookModal" class="modal">
    <div class="modal-content">
        <form method="POST">
            <h3>Add New Book</h3>
            <div class="modal-form-group"><label>Title</label><input name="title" required></div>
            <div class="modal-form-group"><label>Author</label><input name="author" required></div>
            <div class="modal-form-group"><label>ISBN</label><input name="isbn" required></div>
            <div class="modal-form-group"><label>Category</label><input name="category" required></div>
            <div class="modal-form-group"><label>Total Copies</label><input name="total_copies" type="number" min="1" required></div>
            <div class="modal-actions">
                <button type="button" class="button cancel" data-dismiss="modal">Cancel</button>
                <button type="submit" name="add_book" class="button submit">Add Book</button>
            </div>
        </form>
    </div>
</div>

<div id="editBookModal" class="modal">
    <div class="modal-content">
        <form method="POST">
            <h3>Edit Book</h3>
            <input type="hidden" name="book_id" id="editBookId">
            <div class="modal-form-group"><label>Title</label><input name="title" id="editBookTitle" required></div>
            <div class="modal-form-group"><label>Author</label><input name="author" id="editBookAuthor" required></div>
            <div class="modal-form-group"><label>ISBN</label><input name="isbn" id="editBookISBN" required></div>
            <div class="modal-form-group"><label>Category</label><input name="category" id="editBookCategory" required></div>
            <div class="modal-form-group"><label>Total Copies</label><input name="total_copies" id="editBookTotal" type="number" min="1" required></div>
            <div class="modal-actions">
                <button type="button" class="button cancel" data-dismiss="modal">Cancel</button>
                <button type="submit" name="edit_book" class="button submit">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const navLinks = document.querySelectorAll('.nav-link');
    const sections = document.querySelectorAll('.admin-section');
    const modals = document.querySelectorAll('.modal');


    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const sectionId = link.getAttribute('data-section');

            navLinks.forEach(nav => nav.classList.remove('active'));
            link.classList.add('active');

            sections.forEach(sec => {
                if (sec.id === 'section-' + sectionId) {
                    sec.classList.add('active');
                } else {
                    sec.classList.remove('active');
                }
            });
        });
    });


    document.querySelectorAll('[data-modal]').forEach(button => {
        button.addEventListener('click', () => {
            const modalId = button.getAttribute('data-modal');
            document.getElementById(modalId).classList.add('show');
        });
    });


    modals.forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal') || e.target.closest('[data-dismiss="modal"]')) {
                modal.classList.remove('show');
            }
        });
    });
    document.addEventListener('keydown', e => {
        if (e.key === "Escape") {
            modals.forEach(modal => modal.classList.remove('show'));
        }
    });
});

function showEditBookModal(id, title, author, isbn, category, total) {
    const modal = document.getElementById('editBookModal');
    modal.querySelector('#editBookId').value = id;
    modal.querySelector('#editBookTitle').value = title;
    modal.querySelector('#editBookAuthor').value = author;
    modal.querySelector('#editBookISBN').value = isbn;
    modal.querySelector('#editBookCategory').value = category;
    modal.querySelector('#editBookTotal').value = total;
    modal.classList.add('show');
}

</script>

</body>
</html>