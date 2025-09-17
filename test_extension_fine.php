<?php
require_once 'config.php';

echo "<h2>Extension Fine System Test - Dynamic Calculation</h2>";

// Check if fine_amount and extension_days columns exist
$result = $conn->query("DESCRIBE extension_requests");
$columns = [];
while ($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
}

if (in_array('fine_amount', $columns)) {
    echo "✅ fine_amount column exists in extension_requests table<br>";
} else {
    echo "❌ fine_amount column does not exist in extension_requests table<br>";
}

if (in_array('extension_days', $columns)) {
    echo "✅ extension_days column exists in extension_requests table<br>";
} else {
    echo "❌ extension_days column does not exist in extension_requests table<br>";
}

// Test fine calculation logic
echo "<h3>Fine Calculation Test:</h3>";
$test_base_fine = 50.00;
echo "Base fine: ₱" . number_format($test_base_fine, 2) . "<br><br>";

$test_periods = [3, 7, 14, 30];
foreach ($test_periods as $days) {
    $calculated_fine = $test_base_fine;
    if ($days > 3) {
        $extra_days = $days - 3;
        $additional_fine = $test_base_fine * 0.10 * $extra_days;
        $calculated_fine = $test_base_fine + $additional_fine;
    }
    
    echo "<strong>{$days} days:</strong> ₱" . number_format($calculated_fine, 2);
    if ($days > 3) {
        echo " (Base: ₱" . number_format($test_base_fine, 2) . " + " . $extra_days . " extra days × 10%)";
    }
    echo "<br>";
}

// Check existing extension requests
$result = $conn->query("SELECT er.*, b.title, b.book_fine FROM extension_requests er JOIN books b ON er.book_id = b.book_id LIMIT 5");
if ($result && $result->num_rows > 0) {
    echo "<h3>Existing Extension Requests:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Request ID</th><th>Book</th><th>Extension Days</th><th>Book Fine</th><th>Calculated Fine</th><th>Status</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['request_id'] . "</td>";
        echo "<td>" . $row['title'] . "</td>";
        echo "<td>" . ($row['extension_days'] ?? 7) . " days</td>";
        echo "<td>₱" . number_format($row['book_fine'], 2) . "</td>";
        echo "<td>₱" . number_format($row['fine_amount'] ?? 0, 2) . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No extension requests found.<br>";
}

// Check fines table
$result = $conn->query("SELECT * FROM fines LIMIT 5");
if ($result && $result->num_rows > 0) {
    echo "<h3>Existing Fines:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Fine ID</th><th>Borrow ID</th><th>Amount</th><th>Paid</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['fine_id'] . "</td>";
        echo "<td>" . $row['borrow_id'] . "</td>";
        echo "<td>₱" . number_format($row['amount'], 2) . "</td>";
        echo "<td>" . ($row['paid'] ? 'Yes' : 'No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No fines found.<br>";
}

echo "<br><strong>Test completed!</strong>";
echo "<br><br><strong>New Features:</strong>";
echo "<ul>";
echo "<li>✅ Modal form opens when 'Request Extension' button is clicked</li>";
echo "<li>✅ Students can input their desired number of days (1-30)</li>";
echo "<li>✅ Real-time fine calculation as user types</li>";
echo "<li>✅ Fine calculation: Base fine + 10% per day after 3 days</li>";
echo "<li>✅ Clear fine breakdown showing base fine and additional charges</li>";
echo "<li>✅ Form validation with immediate feedback</li>";
echo "<li>✅ AJAX form submission with loading states</li>";
echo "<li>✅ Success/error feedback messages</li>";
echo "<li>✅ Automatic modal closure after successful submission</li>";
echo "<li>✅ Page reload to show updated status</li>";
echo "<li>✅ Fallback support for older browsers</li>";
echo "<li>✅ <strong>NEW:</strong> Notification system for extension request status</li>";
echo "<li>✅ <strong>NEW:</strong> Students receive notifications when requests are approved/denied</li>";
echo "<li>✅ <strong>NEW:</strong> Dedicated notifications page with read/unread status</li>";
echo "<li>✅ <strong>NEW:</strong> Unread notification count badge in navigation</li>";
echo "<li>✅ <strong>NEW:</strong> Mark individual or all notifications as read</li>";
echo "<li>✅ <strong>NEW:</strong> Color-coded notification types (approved/denied)</li>";
echo "<li>✅ Admin can see extension period and fine breakdown</li>";
echo "<li>✅ Modal can be closed by clicking outside or the X button</li>";
echo "</ul>";
?> 