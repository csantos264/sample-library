<?php
require_once 'config.php';

echo "<h2>Running Database Fixes for Extension Request System</h2>";

// 1. Create notifications table
echo "<h3>1. Creating notifications table...</h3>";
$sql = "CREATE TABLE IF NOT EXISTS `notifications` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `related_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`notification_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($sql)) {
    echo "✅ Notifications table created successfully<br>";
} else {
    echo "❌ Error creating notifications table: " . $conn->error . "<br>";
}

// 2. Add missing columns to extension_requests table
echo "<h3>2. Adding columns to extension_requests table...</h3>";

// Check if fine_amount column exists
$result = $conn->query("SHOW COLUMNS FROM extension_requests LIKE 'fine_amount'");
if ($result->num_rows == 0) {
    $sql = "ALTER TABLE `extension_requests` ADD COLUMN `fine_amount` decimal(6,2) DEFAULT 0.00 AFTER `status`";
    if ($conn->query($sql)) {
        echo "✅ fine_amount column added successfully<br>";
    } else {
        echo "❌ Error adding fine_amount column: " . $conn->error . "<br>";
    }
} else {
    echo "✅ fine_amount column already exists<br>";
}

// Check if extension_days column exists
$result = $conn->query("SHOW COLUMNS FROM extension_requests LIKE 'extension_days'");
if ($result->num_rows == 0) {
    $sql = "ALTER TABLE `extension_requests` ADD COLUMN `extension_days` int(11) DEFAULT 0 AFTER `fine_amount`";
    if ($conn->query($sql)) {
        echo "✅ extension_days column added successfully<br>";
    } else {
        echo "❌ Error adding extension_days column: " . $conn->error . "<br>";
    }
} else {
    echo "✅ extension_days column already exists<br>";
}

// Check if fine_id column exists
$result = $conn->query("SHOW COLUMNS FROM extension_requests LIKE 'fine_id'");
if ($result->num_rows == 0) {
    $sql = "ALTER TABLE `extension_requests` ADD COLUMN `fine_id` int(11) DEFAULT NULL AFTER `extension_days`";
    if ($conn->query($sql)) {
        echo "✅ fine_id column added successfully<br>";
    } else {
        echo "❌ Error adding fine_id column: " . $conn->error . "<br>";
    }
} else {
    echo "✅ fine_id column already exists<br>";
}

// 3. Update payments table to support extension_fine type
echo "<h3>3. Updating payments table...</h3>";
$sql = "ALTER TABLE `payments` MODIFY COLUMN `payment_type` enum('fine','reservation','ebook','extension_fine') NOT NULL";
if ($conn->query($sql)) {
    echo "✅ Payments table updated successfully<br>";
} else {
    echo "❌ Error updating payments table: " . $conn->error . "<br>";
}

// 4. Add missing columns to borrow_records table
echo "<h3>4. Adding columns to borrow_records table...</h3>";

// Check if fine_paid column exists
$result = $conn->query("SHOW COLUMNS FROM borrow_records LIKE 'fine_paid'");
if ($result->num_rows == 0) {
    $sql = "ALTER TABLE `borrow_records` ADD COLUMN `fine_paid` tinyint(1) DEFAULT 0";
    if ($conn->query($sql)) {
        echo "✅ fine_paid column added successfully<br>";
    } else {
        echo "❌ Error adding fine_paid column: " . $conn->error . "<br>";
    }
} else {
    echo "✅ fine_paid column already exists<br>";
}

// 5. Update reservations table status enum
echo "<h3>5. Updating reservations table...</h3>";
$sql = "ALTER TABLE `reservations` MODIFY COLUMN `status` enum('pending','active','cancelled','fulfilled') DEFAULT 'pending'";
if ($conn->query($sql)) {
    echo "✅ Reservations table updated successfully<br>";
} else {
    echo "❌ Error updating reservations table: " . $conn->error . "<br>";
}

// 6. Add indexes for better performance
echo "<h3>6. Adding indexes...</h3>";

$indexes = [
    "CREATE INDEX IF NOT EXISTS `idx_notifications_user_read` ON `notifications` (`user_id`, `is_read`)",
    "CREATE INDEX IF NOT EXISTS `idx_extension_requests_user_status` ON `extension_requests` (`user_id`, `status`)",
    "CREATE INDEX IF NOT EXISTS `idx_fines_borrow_paid` ON `fines` (`borrow_id`, `paid`)"
];

foreach ($indexes as $index_sql) {
    if ($conn->query($index_sql)) {
        echo "✅ Index created successfully<br>";
    } else {
        echo "❌ Error creating index: " . $conn->error . "<br>";
    }
}

echo "<h3>Database fixes completed!</h3>";
echo "<p><a href='extension-requests.php'>Go to Extension Requests</a></p>";
echo "<p><a href='borrow-book.php'>Go to Borrow Book</a></p>";
?> 