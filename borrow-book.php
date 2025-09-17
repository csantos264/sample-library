<?php
// filepath: c:\xampp\htdocs\WEBDEV_PROJECT\Library-Management-System\borrow-book.php

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
$book_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$success = '';
$error = '';

if ($book_id <= 0) {

} else {
    // Check if book exists and is available
    $stmt = $conn->prepare("SELECT title, available_copies FROM books WHERE book_id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $book = $result->fetch_assoc();
    $stmt->close();

    if (!$book) {
        $error = "Book not found.";
    } elseif ($book['available_copies'] < 1) {
        $error = "Sorry, this book is currently not available.";
    } else {
        // Check if user already borrowed this book and hasn't returned it
        $stmt = $conn->prepare("SELECT * FROM borrow_records WHERE user_id = ? AND book_id = ? AND return_date IS NULL");
        $stmt->bind_param("ii", $user_id, $book_id);
        $stmt->execute();
        $already_borrowed = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        if ($already_borrowed) {
            $error = "You have already borrowed this book and not returned it yet.";
        } else {
            // Borrow the book
            $borrow_date = date('Y-m-d');
            $due_date = date('Y-m-d', strtotime('+14 days'));
            $status = 'borrowed';
            $stmt = $conn->prepare("INSERT INTO borrow_records (user_id, book_id, borrow_date, due_date, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iisss", $user_id, $book_id, $borrow_date, $due_date, $status);
            if ($stmt->execute()) {
                // Decrease available copies
                $stmt2 = $conn->prepare("UPDATE books SET available_copies = available_copies - 1 WHERE book_id = ?");
                $stmt2->bind_param("i", $book_id);
                $stmt2->execute();
                $stmt2->close();
                $success = "You have successfully borrowed <b>" . htmlspecialchars($book['title']) . "</b>! Due date: <b>" . date('M d, Y', strtotime($due_date)) . "</b>.";
            } else {
                $error = "Error borrowing book: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Handle extension request submission
if (isset($_POST['request_extension']) && isset($_POST['borrow_id'])) {
    $borrow_id = (int)$_POST['borrow_id'];
    $extension_days = (int)($_POST['extension_days'] ?? 7); // Default to 7 days if not specified
    
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
            // Check if already requested - fix: check for borrow_id instead of book_id
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
                    $error = "Failed to submit extension request.";
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
    <script>
        function openExtensionForm(borrowId, baseFine, bookTitle) {
            // Create modal overlay
            const modal = document.createElement('div');
            modal.id = 'extensionModal';
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 1000;
            `;
            
            // Create modal content
            const modalContent = document.createElement('div');
            modalContent.style.cssText = `
                background: white;
                padding: 2rem;
                border-radius: 12px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.3);
                max-width: 500px;
                width: 90%;
                position: relative;
            `;
            
            modalContent.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h3 style="margin: 0; color: #532c2e;">Request Extension</h3>
                    <button onclick="closeExtensionForm()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #666;">&times;</button>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <strong>Book:</strong> ${bookTitle}
                </div>
                
                <form method="post" id="extensionForm" onsubmit="handleFormSubmit(event)">
                    <input type="hidden" name="borrow_id" value="${borrowId}">
                    
                    <div style="margin-bottom: 1.5rem;">
                        <label for="extension_days" style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: #532c2e;">
                            Number of Days to Extend:
                        </label>
                        <input type="number" id="extension_days" name="extension_days" min="1" max="30" value="7" 
                               style="width: 100%; padding: 0.8rem; border: 2px solid #ddd; border-radius: 6px; font-size: 1rem;"
                               onchange="calculateFine(${baseFine})" onkeyup="calculateFine(${baseFine})">
                        <small style="color: #666;">Enter a number between 1 and 30 days</small>
                    </div>
                    
                    <div style="margin-bottom: 1.5rem; padding: 1rem; background: #f8f9fa; border-radius: 6px;">
                        <div style="margin-bottom: 0.5rem;">
                            <strong>Fine Calculation:</strong>
                        </div>
                        <div id="fineBreakdown" style="font-size: 0.9rem; color: #666;">
                            Calculating...
                        </div>
                        <div style="margin-top: 0.5rem; font-size: 1.1rem; font-weight: bold; color: #e74c3c;">
                            Total Fine: <span id="totalFine">₱0.00</span>
                        </div>
                    </div>
                    
                    <div id="submitFeedback" style="display: none; margin-bottom: 1rem; padding: 1rem; border-radius: 6px; text-align: center;"></div>
                    
                    <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                        <button type="button" onclick="closeExtensionForm()" 
                                style="padding: 0.8rem 1.5rem; border: 2px solid #ddd; background: white; color: #666; border-radius: 6px; cursor: pointer;">
                            Cancel
                        </button>
                        <button type="submit" name="request_extension" id="submitBtn"
                                style="padding: 0.8rem 1.5rem; background: #C5832B; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">
                            Submit Request
                        </button>
                    </div>
                </form>
            `;
            
            modal.appendChild(modalContent);
            document.body.appendChild(modal);
            
            // Calculate initial fine
            calculateFine(baseFine);
        }
        
        function calculateFine(baseFine) {
            const daysInput = document.getElementById('extension_days');
            const days = parseInt(daysInput.value) || 0;
            const breakdownDiv = document.getElementById('fineBreakdown');
            const totalFineSpan = document.getElementById('totalFine');
            
            if (days < 1 || days > 30) {
                breakdownDiv.innerHTML = '<span style="color: #e74c3c;">Please enter a valid number of days (1-30)</span>';
                totalFineSpan.textContent = '₱0.00';
                return;
            }
            
            let calculatedFine = baseFine;
            let breakdown = `Base fine: ₱${baseFine.toFixed(2)}`;
            
            if (days > 3) {
                const extraDays = days - 3;
                const additionalFine = baseFine * 0.10 * extraDays;
                calculatedFine = baseFine + additionalFine;
                breakdown += `<br>Extra days (${extraDays} days × 10%): ₱${additionalFine.toFixed(2)}`;
            } else {
                breakdown += '<br>No additional charges (3 days or less)';
            }
            
            breakdownDiv.innerHTML = breakdown;
            totalFineSpan.textContent = `₱${calculatedFine.toFixed(2)}`;
        }
        
        function closeExtensionForm() {
            const modal = document.getElementById('extensionModal');
            if (modal) {
                modal.remove();
            }
        }
        
        function handleFormSubmit(event) {
            event.preventDefault();
            
            const form = event.target;
            const submitBtn = document.getElementById('submitBtn');
            const feedbackDiv = document.getElementById('submitFeedback');
            const formData = new FormData(form);
            
            // Validate form data
            const extensionDays = formData.get('extension_days');
            if (!extensionDays || extensionDays < 1 || extensionDays > 30) {
                feedbackDiv.style.display = 'block';
                feedbackDiv.style.background = '#fff3e0';
                feedbackDiv.style.color = '#f57c00';
                feedbackDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Please enter a valid number of days (1-30).';
                return;
            }
            
            // Disable submit button and show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
            
            // Show loading feedback
            feedbackDiv.style.display = 'block';
            feedbackDiv.style.background = '#e3f2fd';
            feedbackDiv.style.color = '#1976d2';
            feedbackDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting your extension request...';
            
            // Check if fetch is supported
            if (typeof fetch !== 'undefined') {
                // Submit form via AJAX
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    // Check if submission was successful (look for success message in response)
                    if (data.includes('Extension request submitted')) {
                        showSuccessFeedback();
                    } else {
                        showErrorFeedback();
                    }
                })
                .catch(error => {
                    showErrorFeedback();
                });
            } else {
                // Fallback for older browsers - submit form normally
                showSuccessFeedback();
                setTimeout(() => {
                    form.submit();
                }, 1000);
            }
        }
        
        function showSuccessFeedback() {
            const submitBtn = document.getElementById('submitBtn');
            const feedbackDiv = document.getElementById('submitFeedback');
            
            // Show success message
            feedbackDiv.style.background = '#e8f5e8';
            feedbackDiv.style.color = '#2e7d32';
            feedbackDiv.innerHTML = '<i class="fas fa-check-circle"></i> Extension request submitted successfully!';
            
            // Update submit button
            submitBtn.innerHTML = '<i class="fas fa-check"></i> Submitted';
            submitBtn.style.background = '#2e7d32';
            
            // Close modal after 2 seconds
            setTimeout(() => {
                closeExtensionForm();
                // Reload page to show updated status
                window.location.reload();
            }, 2000);
        }
        
        function showErrorFeedback() {
            const submitBtn = document.getElementById('submitBtn');
            const feedbackDiv = document.getElementById('submitFeedback');
            
            // Show error message
            feedbackDiv.style.background = '#ffebee';
            feedbackDiv.style.color = '#c62828';
            feedbackDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Error submitting request. Please try again.';
            
            // Re-enable submit button
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Submit Request';
        }
        
        // Close modal when clicking outside
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('extensionModal');
            if (modal && event.target === modal) {
                closeExtensionForm();
            }
        });
    </script>
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
                    <li><a href="student_page.php" class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'student_page.php' ? ' active' : '' ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="my-profile.php" class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'my-profile.php' ? ' active' : '' ?>"><i class="fas fa-user"></i> My Profile</a></li>
                    <li><a href="catalog.php" class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'catalog.php' ? ' active' : '' ?>"><i class="fas fa-book"></i> Browse Books</a></li>
                    <li><a href="borrow-book.php" class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'borrow-book.php' ? ' active' : '' ?>"><i class="fas fa-book-reader"></i> Borrowed Book <?php if ($pending_extensions_count > 0): ?><span style="background:#C5832B;color:#fff;padding:2px 6px;border-radius:10px;font-size:0.7rem;"><?= $pending_extensions_count ?></span><?php endif; ?></a></li>
                    <li><a href="my-reservation.php" class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'my-reservation.php' ? ' active' : '' ?>"><i class="fas fa-calendar-check"></i> My Reservations</a></li>
                    <li><a href="notifications.php" class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'notifications.php' ? ' active' : '' ?>"><i class="fas fa-bell"></i> Notifications <?php if ($unread_notifications > 0): ?><span style="background:#e74c3c;color:#fff;padding:2px 6px;border-radius:10px;font-size:0.7rem;"><?= $unread_notifications ?></span><?php endif; ?></a></li>
                    <li><a href="settings.php" class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? ' active' : '' ?>"><i class="fas fa-cog"></i> Settings</a></li>
                </ul>
            </nav>
        </div>
        <div class="dashboard-main">
            <div class="borrow-container">
                <h2 style="margin-bottom:1.5rem; color:#532c2e; display:flex; align-items:center; gap:0.7rem;">
                    <i class="fas fa-book-reader"></i> Borrowed Book
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
                            // If overdue and not paid, show pay fine button
                            if (strtotime($row['due_date']) < time() && $row['book_fine'] > 0 && empty($row['fine_paid'])) {
                                echo "<br><a href='payment.php?borrow_id=" . $row['borrow_id'] . "' class='btn' style='margin-top:0.5rem;display:inline-block;padding:0.3rem 0.8rem;font-size:0.95rem;background:#b71c1c;color:#fff;border-radius:6px;text-decoration:none;'>
                                        <i class='fas fa-money-bill-wave'></i> Pay Fine
                                    </a>";
                            }
                            // Extension Request button
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
                                echo "<br><button type='button' class='btn' style='background:#C5832B;color:#fff;padding:0.3rem 0.8rem;font-size:0.95rem;border-radius:6px;margin-top:0.5rem;' onclick='openExtensionForm(" . $row['borrow_id'] . ", " . $row['book_fine'] . ", \"" . htmlspecialchars($row['title']) . "\")'>
                                        <i class='fas fa-hourglass-half'></i> Request Extension
                                    </button>";
                            }
                            echo "</td>";
                        } else {
                            // Returned
                            $fine_paid = ($row['book_fine'] > 0 && strtotime($row['due_date']) < strtotime($row['return_date']));
                            if ($fine_paid) {
                                echo "<td style='padding:1rem; text-align:center;'>
                                        <span style='color:#388e3c; font-weight:600;'>Returned<br><span style='color:#b71c1c; font-weight:500;'>Fine Paid</span></span><br>
                                        <a href='receipt.php?borrow_id=" . $row['borrow_id'] . "' class='btn' style='margin-top:0.5rem;display:inline-block;padding:0.3rem 0.8rem;font-size:0.95rem;background:#532c2e;color:#fff;border-radius:6px;text-decoration:none;'>
                                            <i class='fas fa-receipt'></i> View Receipt
                                        </a>
                                    </td>";
                            } else {
                                echo "<td style='padding:1rem; text-align:center; color:#388e3c; font-weight:600;'>
                                        Returned<br>
                                        <a href='receipt.php?borrow_id=" . $row['borrow_id'] . "' class='btn' style='margin-top:0.5rem;display:inline-block;padding:0.3rem 0.8rem;font-size:0.95rem;background:#532c2e;color:#fff;border-radius:6px;text-decoration:none;'>
                                            <i class='fas fa-receipt'></i> View Receipt
                                        </a>
                                    </td>";
                            }
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