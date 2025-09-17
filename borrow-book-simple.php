<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Check if user is not an admin (only regular users can access borrow book page)
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
    header('Location: admin_page.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$user = null;

// Get notification counts
$unread_notifications = 0;
$pending_extensions_count = 0;

// Get unread notification count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$unread_notifications = $result->fetch_assoc()['count'];
$stmt->close();

// Get pending extension requests count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM extension_requests WHERE user_id = ? AND status = 'pending'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$pending_extensions_count = $result->fetch_assoc()['count'];
$stmt->close();

// Fetch user info
$stmt = $conn->prepare("SELECT full_name, email, user_type FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    echo "User not found.";
    exit();
}

$success = '';
$error = '';

// Handle extension request submission (simplified)
if (isset($_POST['request_extension']) && isset($_POST['borrow_id']) && isset($_POST['extension_days'])) {
    $borrow_id = (int)$_POST['borrow_id'];
    $extension_days = (int)$_POST['extension_days'];
    
    // Validate extension days (1-30 days)
    if ($extension_days < 1 || $extension_days > 30) {
        $error = "Extension period must be between 1 and 30 days.";
    } else {
        // Get borrow record info with book fine
        $stmt = $conn->prepare("SELECT br.*, b.book_fine, b.book_id FROM borrow_records br JOIN books b ON br.book_id = b.book_id WHERE br.borrow_id = ? AND br.user_id = ?");
        $stmt->bind_param("ii", $borrow_id, $user_id);
        $stmt->execute();
        $borrow = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($borrow && empty($borrow['return_date'])) {
            // Check if already requested
            $stmt = $conn->prepare("SELECT * FROM extension_requests WHERE user_id = ? AND book_id = ? AND status = 'pending'");
            $stmt->bind_param("ii", $user_id, $borrow['book_id']);
            $stmt->execute();
            $already_requested = $stmt->get_result()->num_rows > 0;
            $stmt->close();
            
            if ($already_requested) {
                $error = "You already have a pending extension request for this book.";
            } else {
                // Calculate fine based on extension days
                $base_fine = $borrow['book_fine'];
                $calculated_fine = $base_fine;
                
                if ($extension_days > 3) {
                    $extra_days = $extension_days - 3;
                    $additional_fine = $base_fine * 0.10 * $extra_days; // 10% per day after 3 days
                    $calculated_fine = $base_fine + $additional_fine;
                }
                
                $new_return_date = date('Y-m-d', strtotime($borrow['due_date'] . ' +' . $extension_days . ' days'));
                $stmt = $conn->prepare("INSERT INTO extension_requests (book_id, user_id, request_date, new_return_date, status, fine_amount, extension_days) VALUES (?, ?, NOW(), ?, 'pending', ?, ?)");
                $stmt->bind_param("iisdi", $borrow['book_id'], $user_id, $new_return_date, $calculated_fine, $extension_days);
                
                if ($stmt->execute()) {
                    $fine_message = $calculated_fine > 0 ? " Note: A fine of ₱" . number_format($calculated_fine, 2) . " will be applied if approved." : "";
                    $success = "Extension request submitted for " . $extension_days . " days!" . $fine_message;
                } else {
                    $error = "Failed to submit extension request: " . $stmt->error;
                }
                $stmt->close();
            }
        } else {
            $error = "Invalid borrow record or already returned.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Borrow Book | Book Stop</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <header class="dashboard-header">
        <h1><i class="fas fa-book-reader"></i> <a href="student_page.php" class="home-btn" style="color:inherit;text-decoration:none;">Book Stop</a></h1>
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
                    <li><a href="student_page.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="my-profile.php" class="nav-link"><i class="fas fa-user"></i> My Profile</a></li>
                    <li><a href="catalog.php" class="nav-link"><i class="fas fa-book"></i> Browse Books</a></li>
                    <li><a href="borrow-book.php" class="nav-link active"><i class="fas fa-book-reader"></i> Borrowed Book <?php if ($pending_extensions_count > 0): ?><span style="background:#C5832B;color:#fff;padding:2px 6px;border-radius:10px;font-size:0.7rem;"><?= $pending_extensions_count ?></span><?php endif; ?></a></li>
                    <li><a href="my-reservation.php" class="nav-link"><i class="fas fa-calendar-check"></i> My Reservations</a></li>
                    <li><a href="notifications.php" class="nav-link"><i class="fas fa-bell"></i> Notifications <?php if ($unread_notifications > 0): ?><span style="background:#e74c3c;color:#fff;padding:2px 6px;border-radius:10px;font-size:0.7rem;"><?= $unread_notifications ?></span><?php endif; ?></a></li>
                    <li><a href="settings.php" class="nav-link"><i class="fas fa-cog"></i> Settings</a></li>
                </ul>
            </nav>
        </div>
        <div class="dashboard-main">
            <div class="borrow-container">
                <h2 style="margin-bottom:1.5rem; color:#532c2e; display:flex; align-items:center; gap:0.7rem;">
                    <i class="fas fa-book-reader"></i> Borrowed Book (Simplified)
                </h2>
                <?php if ($success): ?>
                    <div class="alert alert-success" style="margin-bottom:1.2rem;"><?= $success ?></div>
                <?php elseif ($error): ?>
                    <div class="alert alert-error" style="margin-bottom:1.2rem;"><?= $error ?></div>
                <?php endif; ?>
                <a href="catalog.php" class="back-link" style="margin-bottom:2rem; display:inline-block;">&larr; Back to Browse</a>

                <!-- Borrowed Books Table -->
                <div style="margin:2.5rem auto 0 auto; max-width: 1200px; width: 100%;">
                    <h3 style="margin-bottom: 1rem; color: #532c2e; font-size:1.3rem;">My Borrowed Books</h3>
                    <div style="overflow-x:auto;">
                        <table class="reservation-table" style="width:100%; background:#fff; border-radius:12px; box-shadow:0 2px 12px rgba(44,62,80,0.10); border-collapse:separate; border-spacing:0;">
                            <thead>
                                <tr style="background:#f5e9e0;">
                                    <th style="padding:1rem; text-align:center; font-size:1.08rem;">Book Title</th>
                                    <th style="padding:1rem; text-align:center; font-size:1.08rem;">Borrowed On</th>
                                    <th style="padding:1rem; text-align:center; font-size:1.08rem;">Due Date</th>
                                    <th style="padding:1rem; text-align:center; font-size:1.08rem;">Status</th>
                                    <th style="padding:1rem; text-align:center; font-size:1.08rem;">Fine</th>
                                    <th style="padding:1rem; text-align:center; font-size:1.08rem;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                <?php
                $stmt = $conn->prepare("SELECT b.title, br.borrow_date, br.due_date, br.return_date, b.book_fine, br.borrow_id, br.fine_paid
                    FROM borrow_records br
                    JOIN books b ON br.book_id = b.book_id
                    WHERE br.user_id = ?
                    ORDER BY br.borrow_date DESC");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $status = $row['return_date'] ? 'Returned' : (strtotime($row['due_date']) < time() ? 'Overdue' : 'Borrowed');
                        $status_color = $row['return_date'] ? '#388e3c' : (strtotime($row['due_date']) < time() ? '#b71c1c' : '#C5832B');
                        // Fine calculation
                        $fine = '-';
                        $days_overdue = 0;
                        if (!$row['return_date'] && strtotime($row['due_date']) < time() && $row['book_fine'] > 0) {
                            $days_overdue = (new DateTime())->diff(new DateTime($row['due_date']))->days;
                            $fine = '₱' . number_format($days_overdue * $row['book_fine'], 2);
                        } elseif ($row['return_date'] && strtotime($row['due_date']) < strtotime($row['return_date']) && $row['book_fine'] > 0) {
                            $days_overdue = (new DateTime($row['return_date']))->diff(new DateTime($row['due_date']))->days;
                            $fine = '₱' . number_format($days_overdue * $row['book_fine'], 2);
                        }

                        echo "<tr style='border-bottom:1px solid #f0e6db;'>";
                        echo "<td style='padding:1rem; text-align:center;'>" . htmlspecialchars($row['title']) . "</td>";
                        echo "<td style='padding:1rem; text-align:center;'>" . htmlspecialchars(date('M d, Y', strtotime($row['borrow_date']))) . "</td>";
                        echo "<td style='padding:1rem; text-align:center;'>" . htmlspecialchars(date('M d, Y', strtotime($row['due_date']))) . "</td>";
                        echo "<td style='padding:1rem; color: $status_color; font-weight:600; text-align:center;'>" . $status . "</td>";
                        echo "<td style='padding:1rem; text-align:center; color:#b71c1c; font-weight:600;'>" . $fine . "</td>";

                        // Action column
                        if (!$row['return_date']) {
                            // Not yet returned
                            echo "<td style='padding:1rem; text-align:center;'>
                                <form method='post' action='return-book.php' style='display:inline;'>
                                    <input type='hidden' name='borrow_id' value='" . $row['borrow_id'] . "'>
                                    <button type='submit' class='return-btn' onclick='return confirm(\"Return this book?\")'>
                                        <i class='fas fa-undo'></i> Return
                                    </button>
                                </form>";
                            
                            // Extension Request button - Simplified form
                            // Check if already requested
                            $ext_stmt = $conn->prepare("SELECT * FROM extension_requests WHERE user_id = ? AND book_id = (SELECT book_id FROM borrow_records WHERE borrow_id = ?) AND status = 'pending'");
                            $ext_stmt->bind_param("ii", $user_id, $row['borrow_id']);
                            $ext_stmt->execute();
                            $already_requested = $ext_stmt->get_result()->num_rows > 0;
                            $ext_stmt->close();
                            
                            if ($already_requested) {
                                echo "<br><span style='display:inline-block;margin-top:0.5rem;padding:0.3rem 0.8rem;font-size:0.95rem;background:#f5e9e0;color:#b71c1c;border-radius:6px;'>
                                        <i class='fas fa-hourglass-half'></i> Extension Requested
                                    </span>";
                            } else {
                                // Show simple extension form
                                echo "<br><form method='post' style='display:inline-block;margin-top:0.5rem;'>";
                                echo "<input type='hidden' name='borrow_id' value='" . $row['borrow_id'] . "'>";
                                echo "<select name='extension_days' style='padding:0.3rem;margin-right:0.5rem;border-radius:4px;'>";
                                echo "<option value='7'>7 days</option>";
                                echo "<option value='14'>14 days</option>";
                                echo "<option value='21'>21 days</option>";
                                echo "<option value='30'>30 days</option>";
                                echo "</select>";
                                echo "<button type='submit' name='request_extension' style='background:#C5832B;color:#fff;padding:0.3rem 0.8rem;font-size:0.95rem;border-radius:6px;border:none;cursor:pointer;'>
                                        <i class='fas fa-hourglass-half'></i> Request Extension
                                    </button>";
                                echo "</form>";
                            }
                            echo "</td>";
                        } else {
                            // Returned
                            echo "<td style='padding:1rem; text-align:center; color:#388e3c; font-weight:600;'>Returned</td>";
                        }
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6' style='padding:2rem; text-align:center; color:#888;'>No borrowed books found.</td></tr>";
                }
                $stmt->close();
                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</body>
</html> 