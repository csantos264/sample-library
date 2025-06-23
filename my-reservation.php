<?php
// filepath: c:\xampp\htdocs\WEBDEV_PROJECT\Library-Management-System\my-reservation.php

session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// Fetch user's reservations/borrowings
$reservations = [];
$stmt = $conn->prepare("SELECT b.title, br.borrowed_at, br.due_date, br.returned_at
    FROM borrow_records br
    JOIN books b ON br.book_id = b.book_id
    WHERE br.user_id = ?
    ORDER BY br.borrowed_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $reservations[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Reservations | Book Stop</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

</head>
<body>
    <div class="reservation-container">
        <h2 class="reservation-title"><i class="fas fa-calendar-check"></i> My Reservations</h2>
        <?php if (count($reservations) === 0): ?>
            <div class="no-reservations">You have no reservations or borrowings yet.</div>
        <?php else: ?>
            <div style="overflow-x:auto;">
            <table class="reservation-table">
                <thead>
                    <tr>
                        <th>Book Title</th>
                        <th>Borrowed On</th>
                        <th>Due Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($reservations as $row): 
                    $status = $row['returned_at'] 
                        ? 'Returned' 
                        : (strtotime($row['due_date']) < time() ? 'Overdue' : 'Borrowed');
                    $status_class = $row['returned_at'] 
                        ? 'status-returned' 
                        : (strtotime($row['due_date']) < time() ? 'status-overdue' : 'status-borrowed');
                ?>
                    <tr>
                        <td><?= htmlspecialchars($row['title']) ?></td>
                        <td><?= htmlspecialchars(date('M d, Y', strtotime($row['borrowed_at']))) ?></td>
                        <td><?= htmlspecialchars(date('M d, Y', strtotime($row['due_date']))) ?></td>
                        <td><span class="status-badge <?= $status_class ?>"><?= $status ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
        <a href="student_page.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</body>
</html>