// /api/employee_management/suspend_employee.php
<?php
session_start();
include '../../config.php';

if (!isset($_SESSION['employee_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$employee_id = $_SESSION['employee_id'];
$stmt = $conn->prepare("SELECT role_id FROM employee_roles WHERE employee_id = ? AND role_id = 1");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
if (!$stmt->get_result()->fetch_assoc()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$id = $_POST['id'];
$suspended = $_POST['suspended'] ? 1 : 0;

$stmt = $conn->prepare("UPDATE employees SET suspended = ? WHERE id = ?");
$stmt->bind_param("ii", $suspended, $id);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to suspend employee']);
}
$conn->close();
?>