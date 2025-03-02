<?php
session_start(); // Ensure session is available
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['employee_id'])) {
    error_log("get_user_roles.php: No session employee_id set");
    echo json_encode([]);
    exit;
}

$employee_id = $_SESSION['employee_id'];
error_log("get_user_roles.php: Fetching roles for employee_id: $employee_id");

$stmt = $db->prepare("SELECT r.role_name FROM employee_roles er JOIN roles r ON er.role_id = r.id WHERE er.employee_id = ?");
if (!$stmt) {
    error_log("get_user_roles.php: SQL prepare failed: " . $db->error);
    echo json_encode([]);
    exit;
}
$stmt->bind_param("i", $employee_id);
if (!$stmt->execute()) {
    error_log("get_user_roles.php: SQL execute failed: " . $stmt->error);
    echo json_encode([]);
    exit;
}
$result = $stmt->get_result();
$roles = [];
while ($row = $result->fetch_assoc()) {
    $roles[] = $row['role_name'];
}
error_log("get_user_roles.php: Roles fetched: " . json_encode($roles));
echo json_encode($roles);
?>