<?php
require_once '../config.php';
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
$employee_id = $data['employee_id'] ?? 0;
$suspend = $data['suspend'] ?? false;

if (in_array('admin', json_decode(file_get_contents("http://{$_SERVER['HTTP_HOST']}/api/get_user_roles.php"), true))) {
    $stmt = $db->prepare("UPDATE employees SET suspended = ?, last_status_change = NOW() WHERE id = ?");
    $stmt->bind_param("ii", $suspend, $employee_id);
    $stmt->execute();
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
}
?>