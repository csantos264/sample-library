<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

require_once 'config.php';

$user = null;
$success = '';
$error = '';

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
                                        <li><a href="borrow-book.php" class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'borrow-book.php' ? ' active' : '' ?>"><i class="fas fa-book-reader"></i> Borrow Book</a></li>
                    <li><a href="my-reservation.php" class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'my-reservation.php' ? ' active' : '' ?>"><i class="fas fa-calendar-check"></i> My Reservations</a></li>
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
                </div>
                <!-- Dashboard Widgets End -->

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