<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    header("Location: index.php");
    exit();
}

$request_id = (int)($_POST['request_id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($request_id && in_array($action, ['approve', 'deny'])) {
    $new_status = ($action === 'approve') ? 'approved' : 'denied';

    $stmt = $conn->prepare("UPDATE extension_requests SET status = ? WHERE request_id = ?");
    $stmt->bind_param("si", $new_status, $request_id);
    $stmt->execute();
    $stmt->close();
}

header("Location: extension-requests.php");
exit();
