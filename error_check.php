<?php
// Simple error checking script
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting error check...\n";

// Test database connection
try {
    require_once 'config.php';
    echo "Database connection: OK\n";
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit;
}

// Test basic queries
$queries = [
    "SELECT COUNT(*) as count FROM users" => "Users count",
    "SELECT COUNT(*) as count FROM books" => "Books count", 
    "SELECT COUNT(*) as count FROM extension_requests" => "Extension requests count",
    "SELECT COUNT(*) as count FROM notifications" => "Notifications count"
];

foreach ($queries as $query => $description) {
    try {
        $result = $conn->query($query);
        if ($result) {
            $count = $result->fetch_assoc()['count'];
            echo "$description: $count\n";
        } else {
            echo "$description: ERROR - " . $conn->error . "\n";
        }
    } catch (Exception $e) {
        echo "$description: EXCEPTION - " . $e->getMessage() . "\n";
    }
}

// Test session
session_start();
echo "Session: " . (session_status() === PHP_SESSION_ACTIVE ? "OK" : "ERROR") . "\n";

echo "Error check complete.\n";
?> 