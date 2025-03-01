<?php
session_start();

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

require_once '../includes/db_connect.php';
$pdo = getPDOConnection();
$stmt = $pdo->prepare("SELECT role FROM employees WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$allowed_roles = ['admin', 'baby_admin']; // Adjust as needed
if (!in_array($user['role'], $allowed_roles)) {
    header("HTTP/1.1 403 Forbidden");
    echo json_encode(['error' => 'Insufficient permissions']);
    exit();
}
?>