<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

require_once 'config.php';

$user = null;
$books_result = null;
$borrowed_books = null;
$success = '';
$error = '';

try {
    
    $user_id = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
    if ($stmt === false) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        throw new Exception("User not found");
    }

    

    
    


    
    if (isset($_POST['update_profile'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $current_section = 'settings';

        if (empty($name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Invalid name or email provided.";
        } else {
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
            $stmt->bind_param("ssi", $name, $email, $user_id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Profile updated successfully.";
                
                $user['name'] = $name;
                $user['email'] = $email;
            } else {
                $_SESSION['error'] = "Error updating profile: " . $stmt->error;
            }
            $stmt->close();
        }
        header("Location: student_page.php?section=" . urlencode($current_section));
        exit();
    }

    
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        $current_section = 'settings';

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $_SESSION['error'] = "All password fields are required.";
        } elseif ($new_password !== $confirm_password) {
            $_SESSION['error'] = "New passwords do not match.";
        } else {
            
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user_data = $result->fetch_assoc();
            $stmt->close();

            if ($user_data && password_verify($current_password, $user_data['password'])) {
                
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashed_password, $user_id);
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Password changed successfully.";
                } else {
                    $_SESSION['error'] = "Error changing password: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $_SESSION['error'] = "Incorrect current password.";
            }
        }
        header("Location: student_page.php?section=" . urlencode($current_section));
        exit();
    }

    if (isset($_POST['request_extension'])) {
        $borrowing_id = (int)$_POST['borrowing_id'];
        $current_section = 'history';

        if ($borrowing_id > 0) {
            // Check if a request already exists
            $stmt_check = $conn->prepare("SELECT id FROM extension_requests WHERE borrowing_id = ? AND status = 'pending'");
            $stmt_check->bind_param("i", $borrowing_id);
            $stmt_check->execute();
            $request_exists = $stmt_check->get_result()->fetch_assoc();
            $stmt_check->close();

            if (!$request_exists) {
                $stmt = $conn->prepare("INSERT INTO extension_requests (borrowing_id, user_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $borrowing_id, $user_id);
                if ($stmt->execute()) {
                    $_SESSION['success'] = 'Extension request submitted successfully.';
                } else {
                    $_SESSION['error'] = 'Failed to submit request.';
                }
                $stmt->close();
            } else {
                $_SESSION['error'] = 'An extension request for this book is already pending.';
            }
        } else {
            $_SESSION['error'] = 'Invalid request.';
        }
        header("Location: student_page.php?section=" . urlencode($current_section));
        exit();
    }

    if (isset($_POST['borrow_book'])) {
        $book_id = (int)$_POST['book_id'];
        $current_section = 'books';

        if ($book_id > 0) {
            $conn->begin_transaction();
            try {
                // Check availability
                $stmt_check = $conn->prepare("SELECT available_copies FROM books WHERE id = ? FOR UPDATE");
                $stmt_check->bind_param("i", $book_id);
                $stmt_check->execute();
                $book_copies = $stmt_check->get_result()->fetch_assoc();
                $stmt_check->close();

                if ($book_copies && $book_copies['available_copies'] > 0) {
                    // Decrease available copies
                    $stmt_update = $conn->prepare("UPDATE books SET available_copies = available_copies - 1 WHERE id = ?");
                    $stmt_update->bind_param("i", $book_id);
                    $stmt_update->execute();
                    $stmt_update->close();

                    // Add to borrowings
                    $borrow_date = date('Y-m-d');
                    $return_date = date('Y-m-d', strtotime('+14 days'));
                    $stmt_insert = $conn->prepare("INSERT INTO borrowings (user_id, book_id, borrow_date, return_date) VALUES (?, ?, ?, ?)");
                    $stmt_insert->bind_param("iiss", $user_id, $book_id, $borrow_date, $return_date);
                    $stmt_insert->execute();
                    $stmt_insert->close();

                    $conn->commit();
                    $_SESSION['success'] = 'Book borrowed successfully!';
                } else {
                    $conn->rollback();
                    $_SESSION['error'] = 'This book is not available for borrowing.';
                }
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['error'] = 'An error occurred during the borrowing process.';
            }
        } else {
            $_SESSION['error'] = 'Invalid book selected.';
        }
        header("Location: student_page.php?section=" . urlencode($current_section));
        exit();
    }

    
    $books_query = "SELECT b.*,
                   CASE 
                       WHEN b.available_copies > 0 THEN 1 
                       ELSE 0 
                   END as is_available
                   FROM books b
                   WHERE b.id NOT IN (
                       SELECT book_id 
                       FROM borrowings 
                       WHERE user_id = ? 
                       AND (returned = 0 OR returned IS NULL)
                   )
                   ORDER BY is_available DESC, b.title ASC";
    $stmt = $conn->prepare($books_query);
    if ($stmt === false) {
        throw new Exception("Database error: " . $conn->error);
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $all_books_result = $stmt->get_result();

    
    $borrowed_query = "SELECT br.id as borrowing_id, b.*, br.borrow_date, br.return_date, br.returned, er.status as extension_status
                     FROM borrowings br 
                     JOIN books b ON br.book_id = b.id 
                     LEFT JOIN extension_requests er ON er.borrowing_id = br.id AND er.status = 'pending'
                     WHERE br.user_id = ? AND (br.returned = 0 OR br.returned IS NULL)";
    $stmt = $conn->prepare($borrowed_query);
    if ($stmt === false) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $borrowed_books = $stmt->get_result();
    $borrowed_books_array = $borrowed_books ? $borrowed_books->fetch_all(MYSQLI_ASSOC) : [];
    
} catch (Exception $e) {
    $error = "An error occurred: " . $e->getMessage();
}

$current_section = 'books'; 
if (isset($_GET['section']) && in_array($_GET['section'], ['books', 'history', 'settings'])) {
    $current_section = $_GET['section'];
}

$books_section_style = ($current_section === 'books') ? 'display: block;' : 'display: none;';
$history_section_style = ($current_section === 'history') ? 'display: block;' : 'display: none;';
$settings_section_style = ($current_section === 'settings') ? 'display: block;' : 'display: none;';

$books_nav_class = ($current_section === 'books') ? 'active' : '';
$history_nav_class = ($current_section === 'history') ? 'active' : '';
$settings_nav_class = ($current_section === 'settings') ? 'active' : '';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Library Management System</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="dashboard.css">

    <style>
        
        .alert {
            padding: 12px 20px;
            margin: 10px 20px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        
        .settings-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 1.5rem;
        }
        .settings-card {
            background: #fff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            display: flex; 
            flex-direction: column; 
        }

        .settings-card form {
            display: flex; 
            flex-direction: column; 
            flex-grow: 1; 
        }
        .settings-card h3 {
            margin-top: 0;
            color: var(--primary);
            border-bottom: 1px solid #eee;
            padding-bottom: 0.75rem;
            margin-bottom: 1.5rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #555;
        }
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .submit-btn {
            background-color: var(--primary);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            width: 100%;
            margin-top: auto; 
        }
        .submit-btn:hover {
            opacity: 0.9;
        }

        .book-actions {
            margin-top: 1rem;
            border-top: 1px solid #eee;
            padding-top: 1rem;
        }

        .action-btn {
            background-color: #3498db;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            width: 100%;
            text-align: center;
        }

        .action-btn:disabled {
            background-color: #bdc3c7;
            cursor: not-allowed;
        }

        .status-tag {
            display: inline-block;
            padding: 0.3rem 0.6rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
            color: white;
        }

        .status-pending { background-color: #f39c12; }
        .status-approved { background-color: #2ecc71; }
        .status-denied { background-color: #e74c3c; }
    </style>
</head>
<body class="dashboard">
    <header class="dashboard-header">
        <h1 style="font-size:1.35rem;"><i class="fas fa-book-reader"></i> Book Stop</h1>
        <a href="login_register.php?logout=1" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            Logout
        </a>
    </header>

    <?php
    
    if (isset($_SESSION['success'])) {
        echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success']) . '</div>';
        unset($_SESSION['success']);
    }
    if (isset($_SESSION['error'])) {
        echo '<div class="alert alert-error">' . htmlspecialchars($_SESSION['error']) . '</div>';
        unset($_SESSION['error']);
    }
    ?>
    
    <div class="dashboard-layout">
        <div class="dashboard-sidebar">
            <div class="user-info">
                <i class="fas fa-user-circle"></i>
                <h3><?php echo htmlspecialchars($user['name']); ?></h3>
                <p>Student</p>
            </div>
            <nav>
                <ul class="sidebar-menu">
                    <li><a href="#" id="nav-books" class="nav-link <?php echo $books_nav_class; ?>"><i class="fas fa-book"></i> <span>Available Books</span></a></li>
                    <li><a href="#" id="nav-history" class="nav-link <?php echo $history_nav_class; ?>"><i class="fas fa-book-reader"></i> <span>My Borrowed Books</span></a></li>
                    <li><a href="#" id="nav-settings" class="nav-link <?php echo $settings_nav_class; ?>"><i class="fas fa-cog"></i> <span>Settings</span></a></li>
                </ul>
            </nav>
        </div>
        <div class="dashboard-main">

            
            <div id="section-books" class="dashboard-section" style="<?php echo $books_section_style; ?>">
                <div class="header" style="margin-top:1.5rem;">
                    <h1>Books</h1>
                </div>
                <?php if ($all_books_result && $all_books_result->num_rows > 0): ?>
                    <div class="books-grid">
                        <?php while ($book = $all_books_result->fetch_assoc()): ?>
                            <div class="book-card">
                                <div class="book-cover">
                                    <i class="fas fa-book-open"></i>
                                </div>
                                <div class="book-details">
                                    <h3 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h3>
                                    <div class="book-meta">
                                        <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($book['author']); ?></span>
                                        <span><i class="fas fa-barcode"></i> <?php echo htmlspecialchars($book['isbn']); ?></span>
                                        <span><i class="fas fa-layer-group"></i> <?php echo (int)$book['available_copies']; ?> available</span>
                                    </div>
                                    <div class="book-actions">
                                        <form method="POST" style="margin:0;">
                                            <input type="hidden" name="book_id" value="<?= (int)$book['id'] ?>">
                                            <button type="submit" name="borrow_book" class="action-btn" <?php echo ($book['available_copies'] <= 0) ? 'disabled' : ''; ?>>
                                                <?php echo ($book['available_copies'] > 0) ? 'Borrow Book' : 'Unavailable'; ?>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state" style="text-align:center;padding:2rem;color:#666;">
                        <i class="fas fa-book-open" style="font-size:3rem;margin-bottom:1rem;color:#999;"></i>
                        <h3 style="margin:0 0 0.5rem;color:#333;">No Books Found</h3>
                        <p style="margin:0;">There are currently no books available in the library.</p>
                    </div>
                <?php endif; ?>
            </div>

           
            <div id="section-history" class="dashboard-section" style="<?php echo $history_section_style; ?>">
                <div class="header" style="margin-top:1.5rem;">
                    <h1>My Borrowed Books</h1>
                </div>
                <?php if (empty($borrowed_books_array)): ?>
                    <div class="empty-state">
                        <i class="fas fa-book-reader"></i>
                        <h3>No Borrowed Books</h3>
                        <p>You haven't borrowed any books yet. Check the "Available Books" section to find your next read!</p>
                    </div>
                <?php else: ?>
                    <div class="books-grid">
                        <?php foreach ($borrowed_books_array as $book): ?>
                            <div class="book-card">
                                <div class="book-cover">
                                    <i class="fas fa-book-open"></i>
                                </div>
                                <div class="book-details">
                                    <h3 class="book-title"><?= htmlspecialchars($book['title']) ?></h3>
                                    <div class="book-meta">
                                        <span><i class="fas fa-user"></i> <?= htmlspecialchars($book['author']) ?></span>
                                        <span><i class="fas fa-barcode"></i> <?= htmlspecialchars($book['isbn']) ?></span>
                                        <span><i class="fas fa-calendar-day"></i> Borrowed: <?= date('M d, Y', strtotime($book['borrow_date'])) ?></span>
                                        <span><i class="fas fa-calendar-check"></i> Due: <?= date('M d, Y', strtotime($book['return_date'])) ?></span>
                                    </div>
                                    <div class="book-actions">
                                        <?php if (isset($book['extension_status']) && $book['extension_status'] === 'pending'): ?>
                                            <button class="action-btn" disabled>Extension Pending</button>
                                        <?php else: ?>
                                            <form method="POST" style="margin:0;">
                                                <input type="hidden" name="borrowing_id" value="<?= (int)$book['borrowing_id'] ?>">
                                                <button type="submit" name="request_extension" class="action-btn">Request Extension</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

          
            <div id="section-settings" class="dashboard-section" style="<?php echo $settings_section_style; ?>">
                <div class="header" style="margin-top:1.5rem;">
                    <h1>Settings</h1>
                </div>

                <div class="settings-container">
                   
                    <div class="settings-card">
                        <h3>Profile Information</h3>
                        <form method="POST">
                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            <button type="submit" name="update_profile" class="submit-btn">Update Profile</button>
                        </form>
                    </div>

                  
                    <div class="settings-card">
                        <h3>Change Password</h3>
                        <form method="POST">
                            <div class="form-group">
                                <label for="current_password">Current Password</label>
                                <input type="password" id="current_password" name="current_password" required>
                            </div>
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password" required>
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" required>
                            </div>
                            <button type="submit" name="change_password" class="submit-btn">Change Password</button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div> 
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
       
        function switchSection(section) {
    
            const newUrl = window.location.pathname + '?section=' + section;
            window.history.pushState({path: newUrl}, '', newUrl);

   
            document.querySelectorAll('.dashboard-section').forEach(sec => {
                sec.style.display = 'none';
            });

 
            const sectionToShow = document.getElementById('section-' + section);
            if (sectionToShow) {
                sectionToShow.style.display = 'block';
            }

            document.querySelectorAll('.nav-link').forEach(nav => nav.classList.remove('active'));
            const activeLink = document.getElementById('nav-' + section);
            if (activeLink) {
                activeLink.classList.add('active');
            }
        }

        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                let section = 'books'; 
                if (this.id === 'nav-history') section = 'history';
                if (this.id === 'nav-settings') section = 'settings';
                switchSection(section);
            });
        });
    });
    </script>
</body>
</html>