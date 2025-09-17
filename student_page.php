<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Check if user is not an admin (only regular users can access student page)
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
    header('Location: admin_page.php');
    exit();
}

require_once 'config.php';

$user = null;
$success = '';
$error = '';

// Get unread notification count
$unread_notifications = 0;
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $unread_notifications = $result->fetch_assoc()['count'];
    $stmt->close();
}

// Use correct field names from your database: user_id, full_name, email
try {
    $user_id = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT full_name, email FROM users WHERE user_id = ?");
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

    $pending_extensions_count = 0;
    if (isset($user_id)) {
        $pending_extensions = $conn->prepare("SELECT COUNT(*) as total FROM extension_requests WHERE user_id = ? AND status = 'pending'");
        if ($pending_extensions) {
            $pending_extensions->bind_param("i", $user_id);
            $pending_extensions->execute();
            $pending_extensions_result = $pending_extensions->get_result();
            $pending_extensions_count = $pending_extensions_result ? $pending_extensions_result->fetch_assoc()['total'] : 0;
            $pending_extensions->close();
        }
    }

    if (isset($_POST['update_profile'])) {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $current_section = 'settings';

        if (empty($full_name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Invalid name or email provided.";
        } else {
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ? WHERE user_id = ?");
            $stmt->bind_param("ssi", $full_name, $email, $user_id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Profile updated successfully.";
                $user['full_name'] = $full_name;
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
            $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user_data = $result->fetch_assoc();
            $stmt->close();

            if ($user_data && password_verify($current_password, $user_data['password'])) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
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

} catch (Exception $e) {
    $error = "An error occurred: " . $e->getMessage();
}

$current_section = 'dashboard';
if (isset($_GET['section']) && in_array($_GET['section'], ['dashboard', 'settings'])) {
    $current_section = $_GET['section'];
}

$dashboard_section_style = ($current_section === 'dashboard') ? 'display: block;' : 'display: none;';
$settings_section_style = ($current_section === 'settings') ? 'display: block;' : 'display: none;';

$dashboard_nav_class = ($current_section === 'dashboard') ? 'active' : '';
$settings_nav_class = ($current_section === 'settings') ? 'active' : '';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Library Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">

</head>
<body class="dashboard">
    <header class="dashboard-header">
        <h1><i class="fas fa-book-reader"></i> Book Stop</h1>
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
                <h3><?php echo htmlspecialchars($user['full_name']); ?></h3>
                <p>user</p>
            </div>
            <nav>
                <ul class="sidebar-menu">
                    <li><a href="student_page.php" class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'student_page.php' ? ' active' : '' ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="my-profile.php" class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'my-profile.php' ? ' active' : '' ?>"><i class="fas fa-user"></i> My Profile</a></li>
                    <li><a href="catalog.php" class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'catalog.php' ? ' active' : '' ?>"><i class="fas fa-book"></i> Browse Books</a></li>
                    <li><a href="borrow-book.php" class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'borrow-book.php' ? ' active' : '' ?>"><i class="fas fa-book-reader"></i> Borrowed Books <?php if ($pending_extensions_count > 0): ?><span style="background:#C5832B;color:#fff;padding:2px 6px;border-radius:10px;font-size:0.7rem;"><?= $pending_extensions_count ?></span><?php endif; ?></a></li>
                    <li><a href="my-reservation.php" class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'my-reservation.php' ? ' active' : '' ?>"><i class="fas fa-calendar-check"></i> My Reservations</a></li>
                    <li><a href="notifications.php" class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'notifications.php' ? ' active' : '' ?>"><i class="fas fa-bell"></i> Notifications <?php if ($unread_notifications > 0): ?><span style="background:#e74c3c;color:#fff;padding:2px 6px;border-radius:10px;font-size:0.7rem;"><?= $unread_notifications ?></span><?php endif; ?></a></li>
                    <li><a href="settings.php" class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? ' active' : '' ?>"><i class="fas fa-cog"></i> Settings</a></li>
                </ul>
            </nav>
        </div>
        <div class="dashboard-main">

            <div id="section-dashboard" class="dashboard-section" style="<?php echo $dashboard_section_style; ?>">
                <div class="dashboard-welcome">
                    <h2>Welcome, <?php echo htmlspecialchars($user['full_name']); ?>!</h2>
                    <p><center>This is your student dashboard. Use the sidebar to update your profile or change your password in the settings.</center></p>
                </div>

                <!-- Dashboard Widgets Start -->
                <div style="display: flex; flex-wrap: wrap; gap: 2rem; justify-content: center; margin-top: 2rem;">
                    <!-- My Borrowed Books Widget -->
                    <div style="background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(44,62,80,0.08); padding: 2rem; min-width: 220px; text-align: center;">
                        <i class="fas fa-book-reader fa-2x" style="color: #532c2e;"></i>
                        <h3 style="margin: 1rem 0 0.5rem 0; font-size: 1.2rem;">My Borrowed Books</h3>
                        <?php
                        $borrowed_count = 0;
                        $borrowed = $conn->prepare("SELECT COUNT(*) as total FROM borrow_records WHERE user_id = ?");
                        if ($borrowed) {
                            $borrowed->bind_param("i", $user_id);
                            $borrowed->execute();
                            $borrowed_result = $borrowed->get_result();
                            $borrowed_count = $borrowed_result ? $borrowed_result->fetch_assoc()['total'] : 0;
                            $borrowed->close();
                        } else {
                            echo "<div style='color:#b71c1c;'>Query error: " . htmlspecialchars($conn->error) . "</div>";
                        }
                        ?>
                        <div style="font-size: 2rem; font-weight: bold;"><?php echo $borrowed_count; ?></div>
                    </div>

                    <!-- Overdue Books Widget -->
                    <div style="background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(44,62,80,0.08); padding: 2rem; min-width: 220px; text-align: center;">
                        <i class="fas fa-exclamation-triangle fa-2x" style="color: #b71c1c;"></i>
                        <h3 style="margin: 1rem 0 0.5rem 0; font-size: 1.2rem;">Overdue Books</h3>
                        <?php
                        $overdue_count = 0;
                        $overdue = $conn->prepare("SELECT COUNT(*) as total FROM borrow_records WHERE user_id = ? AND due_date < CURDATE() AND return_date IS NULL");
                        if ($overdue) {
                            $overdue->bind_param("i", $user_id);
                            $overdue->execute();
                            $overdue_result = $overdue->get_result();
                            $overdue_count = $overdue_result ? $overdue_result->fetch_assoc()['total'] : 0;
                            $overdue->close();
                        } else {
                            echo "<div style='color:#b71c1c;'>Query error: " . htmlspecialchars($conn->error) . "</div>";
                        }
                        ?>
                        <div style="font-size: 2rem; font-weight: bold;"><?php echo $overdue_count; ?></div>
                    </div>

                    <!-- Pending Extension Requests Widget -->
                    <div style="background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(44,62,80,0.08); padding: 2rem; min-width: 220px; text-align: center;">
                        <i class="fas fa-clock fa-2x" style="color: #C5832B;"></i>
                        <h3 style="margin: 1rem 0 0.5rem 0; font-size: 1.2rem;">Pending Extensions</h3>
                        <?php
                        $pending_extensions_count = 0;
                        $pending_extensions = $conn->prepare("SELECT COUNT(*) as total FROM extension_requests WHERE user_id = ? AND status = 'pending'");
                        if ($pending_extensions) {
                            $pending_extensions->bind_param("i", $user_id);
                            $pending_extensions->execute();
                            $pending_extensions_result = $pending_extensions->get_result();
                            $pending_extensions_count = $pending_extensions_result ? $pending_extensions_result->fetch_assoc()['total'] : 0;
                            $pending_extensions->close();
                        } else {
                            echo "<div style='color:#b71c1c;'>Query error: " . htmlspecialchars($conn->error) . "</div>";
                        }
                        ?>
                        <div style="font-size: 2rem; font-weight: bold;"><?php echo $pending_extensions_count; ?></div>
                    </div>
                </div>
                <!-- Dashboard Widgets End -->

                <!-- Pending Extension Payments -->
                <?php
                $pending_extensions = $conn->prepare("SELECT f.fine_id, f.amount, b.title, br.borrow_date, br.due_date 
                                                    FROM fines f 
                                                    JOIN borrow_records br ON f.borrow_id = br.borrow_id 
                                                    JOIN books b ON br.book_id = b.book_id 
                                                    WHERE br.user_id = ? AND f.paid = 0 
                                                    AND f.fine_id IN (
                                                        SELECT er.fine_id FROM extension_requests er 
                                                        WHERE er.user_id = ? AND er.status = 'approved'
                                                    )");
                if ($pending_extensions) {
                    $pending_extensions->bind_param("ii", $user_id, $user_id);
                    $pending_extensions->execute();
                    $pending_result = $pending_extensions->get_result();
                    
                    if ($pending_result && $pending_result->num_rows > 0) {
                        echo '<div style="margin: 2rem auto; max-width: 900px;">
                            <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 1.5rem; margin-bottom: 2rem;">
                                <h3 style="color: #856404; margin-bottom: 1rem;">
                                    <i class="fas fa-exclamation-triangle"></i> Pending Extension Payments
                                </h3>
                                <p style="color: #856404; margin-bottom: 1rem;">
                                    You have approved extension requests that require payment to activate.
                                </p>';
                        
                        while ($extension = $pending_result->fetch_assoc()) {
                            echo '<div style="background: white; border-radius: 6px; padding: 1rem; margin-bottom: 1rem; border-left: 4px solid #C5832B;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <strong>' . htmlspecialchars($extension['title']) . '</strong><br>
                                        <small style="color: #666;">Due: ' . date('M d, Y', strtotime($extension['due_date'])) . '</small>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="font-size: 1.2rem; font-weight: bold; color: #C5832B;">
                                            ₱' . number_format($extension['amount'], 2) . '
                                        </div>
                                        <a href="payment.php?fine_id=' . $extension['fine_id'] . '&type=extension" 
                                           style="background: #C5832B; color: white; padding: 0.5rem 1rem; border-radius: 4px; text-decoration: none; font-size: 0.9rem;">
                                            <i class="fas fa-credit-card"></i> Pay Now
                                        </a>
                                    </div>
                                </div>
                            </div>';
                        }
                        
                        echo '</div></div>';
                    }
                    $pending_extensions->close();
                }
                ?>

                <!-- Pending Extension Requests -->
                <?php
                $pending_requests = $conn->prepare("SELECT er.*, b.title, br.due_date 
                                                  FROM extension_requests er 
                                                  JOIN books b ON er.book_id = b.book_id 
                                                  JOIN borrow_records br ON er.book_id = br.book_id AND er.user_id = br.user_id
                                                  WHERE er.user_id = ? AND er.status = 'pending' 
                                                  ORDER BY er.request_date DESC");
                if ($pending_requests) {
                    $pending_requests->bind_param("i", $user_id);
                    $pending_requests->execute();
                    $requests_result = $pending_requests->get_result();
                    
                    if ($requests_result && $requests_result->num_rows > 0) {
                        echo '<div style="margin: 2rem auto; max-width: 900px;">
                            <div style="background: #e3f2fd; border: 1px solid #bbdefb; border-radius: 8px; padding: 1.5rem; margin-bottom: 2rem;">
                                <h3 style="color: #1565c0; margin-bottom: 1rem;">
                                    <i class="fas fa-clock"></i> Pending Extension Requests
                                </h3>
                                <p style="color: #1565c0; margin-bottom: 1rem;">
                                    Your extension requests are being reviewed by the administrator.
                                </p>';
                        
                        while ($request = $requests_result->fetch_assoc()) {
                            echo '<div style="background: white; border-radius: 6px; padding: 1rem; margin-bottom: 1rem; border-left: 4px solid #2196f3;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <strong>' . htmlspecialchars($request['title']) . '</strong><br>
                                        <small style="color: #666;">
                                            Requested: ' . date('M d, Y', strtotime($request['request_date'])) . ' | 
                                            Extension: ' . $request['extension_days'] . ' days | 
                                            Current Due: ' . date('M d, Y', strtotime($request['due_date'])) . '
                                        </small>';
                            
                            if ($request['fine_amount'] > 0) {
                                echo '<br><small style="color: #C5832B; font-weight: bold;">
                                    Fine if approved: ₱' . number_format($request['fine_amount'], 2) . '
                                </small>';
                            }
                            
                            echo '</div>
                                    <div style="text-align: right;">
                                        <span style="background: #2196f3; color: white; padding: 0.3rem 0.8rem; border-radius: 12px; font-size: 0.8rem; font-weight: bold;">
                                            Pending Review
                                        </span>
                                    </div>
                                </div>
                            </div>';
                        }
                        
                        echo '</div></div>';
                    }
                    $pending_requests->close();
                }
                ?>

                <!-- Recent Activity Table -->
                <div style="margin: 3rem auto 0 auto; max-width: 900px;">
                    <h3 style="margin-bottom: 1rem; color: #532c2e;">Recent Borrowing Activity</h3>
                    <div style="overflow-x:auto;">
                        <table class="reservation-table" style="width:100%; background:#fff; border-radius:8px; box-shadow:0 2px 8px rgba(44,62,80,0.08); border-collapse:collapse;">
                            <thead>
                                <tr style="background:#f5e9e0;">
                                    <th style="padding:0.75rem; text-align:center;">Book Title</th>
                                    <th style="padding:0.75rem; text-align:center;">Borrowed On</th>
                                    <th style="padding:0.75rem; text-align:center;">Due Date</th>
                                    <th style="padding:0.75rem; text-align:center;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $recent = $conn->prepare("SELECT b.title, br.borrow_date, br.due_date, br.return_date
                                    FROM borrow_records br
                                    JOIN books b ON br.book_id = b.book_id
                                    WHERE br.user_id = ?
                                    ORDER BY br.borrow_date DESC
                                    LIMIT 5");
                                if ($recent) {
                                    $recent->bind_param("i", $user_id);
                                    $recent->execute();
                                    $recent_result = $recent->get_result();
                                    if ($recent_result && $recent_result->num_rows > 0) {
                                        while ($row = $recent_result->fetch_assoc()) {
                                            $status = $row['return_date'] ? 'Returned' : (strtotime($row['due_date']) < time() ? 'Overdue' : 'Borrowed');
                                            $status_color = $row['return_date'] ? '#388e3c' : (strtotime($row['due_date']) < time() ? '#b71c1c' : '#C5832B');
                                            echo "<tr>
                                                <td style='padding:0.75rem; text-align:center;'>" . htmlspecialchars($row['title']) . "</td>
                                                <td style='padding:0.75rem; text-align:center;'>" . htmlspecialchars(date('M d, Y', strtotime($row['borrow_date']))) . "</td>
                                                <td style='padding:0.75rem; text-align:center;'>" . htmlspecialchars(date('M d, Y', strtotime($row['due_date']))) . "</td>
                                                <td style='padding:0.75rem; color: $status_color; font-weight:600; text-align:center;'>" . $status . "</td>
                                            </tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='4' style='padding:1.5rem; text-align:center; color:#888;'>No recent activity.</td></tr>";
                                    }
                                    $recent->close();
                                } else {
                                    echo "<tr><td colspan='4' style='padding:1.5rem; text-align:center; color:#b71c1c;'>Query error: " . htmlspecialchars($conn->error) . "</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
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
                                <label for="full_name">Full Name</label>
                                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
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
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                // Let the browser handle navigation (no preventDefault)
            });
        });
    });
    </script>
</body>
</html>