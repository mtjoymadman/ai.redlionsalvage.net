<?php
session_start();

header('Content-Type: application/json');
ob_start();

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', '/home/www/ai.redlionsalvage.net/logs/php_errors.log');

error_log("[delete_employee.php] Starting execution");

$conn = new mysqli('localhost', 'salvageyard_ai', '7361dead', 'salvageyard__ai');
if ($conn->connect_error) {
    error_log("[delete_employee.php] Database connection failed: " . $conn->connect_error);
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $conn->connect_error]);
    ob_end_flush();
    exit;
}
error_log("[delete_employee.php] Database connection established");

$data = json_decode(file_get_contents('php://input'), true);
$employee_id = $data['employee_id'] ?? null;

if (!$employee_id) {
    error_log("[delete_employee.php] Missing employee ID");
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Missing employee ID']);
    ob_end_flush();
    exit;
}

error_log("[delete_employee.php] Deleting roles for employee ID=$employee_id");
$stmt = $conn->prepare("DELETE FROM employee_roles WHERE employee_id = ?");
if (!$stmt) {
    error_log("[delete_employee.php] Role delete prepare failed: " . $conn->error);
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Role delete prepare failed: ' . $conn->error]);
    ob_end_flush();
    exit;
}
$stmt->bind_param("i", $employee_id);
if (!$stmt->execute()) {
    error_log("[delete_employee.php] Role delete execute failed: " . $stmt->error);
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Role delete execute failed: ' . $stmt->error]);
    ob_end_flush();
    exit;
}

error_log("[delete_employee.php] Deleting employee ID=$employee_id");
$stmt = $conn->prepare("DELETE FROM employees WHERE id = ?");
if (!$stmt) {
    error_log("[delete_employee.php] Employee delete prepare failed: " . $conn->error);
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Employee delete prepare failed: ' . $conn->error]);
    ob_end_flush();
    exit;
}
$stmt->bind_param("i", $employee_id);
if (!$stmt->execute()) {
    error_log("[delete_employee.php] Employee delete execute failed: " . $stmt->error);
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Employee delete execute failed: ' . $stmt->error]);
    ob_end_flush();
    exit;
}

ob_clean();
echo json_encode(['success' => true]);
error_log("[delete_employee.php] Execution completed successfully");
$conn->close();
ob_end_flush();
?>