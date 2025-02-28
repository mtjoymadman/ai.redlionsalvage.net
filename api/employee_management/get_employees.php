<?php
session_start();

header('Content-Type: application/json');
ob_start();

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', '/home/www/ai.redlionsalvage.net/logs/php_errors.log');

error_log("[get_employees.php] Starting execution");

$conn = new mysqli('localhost', 'salvageyard_ai', '7361dead', 'salvageyard__ai');
if ($conn->connect_error) {
    error_log("[get_employees.php] Database connection failed: " . $conn->connect_error);
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $conn->connect_error]);
    ob_end_flush();
    exit;
}
error_log("[get_employees.php] Database connection established");

$stmt = $conn->prepare("SELECT id, username, suspended FROM employees");
if (!$stmt) {
    error_log("[get_employees.php] Select prepare failed: " . $conn->error);
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Select prepare failed: ' . $conn->error]);
    ob_end_flush();
    exit;
}
if (!$stmt->execute()) {
    error_log("[get_employees.php] Select execute failed: " . $stmt->error);
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Select execute failed: ' . $stmt->error]);
    ob_end_flush();
    exit;
}
$result = $stmt->get_result();
$employees = [];
while ($row = $result->fetch_assoc()) {
    $employee_id = $row['id'];
    $stmt_roles = $conn->prepare("SELECT r.role_name FROM employee_roles er JOIN roles r ON er.role_id = r.id WHERE er.employee_id = ?");
    if (!$stmt_roles) {
        error_log("[get_employees.php] Role select prepare failed: " . $conn->error);
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Role select prepare failed: ' . $conn->error]);
        ob_end_flush();
        exit;
    }
    $stmt_roles->bind_param("i", $employee_id);
    if (!$stmt_roles->execute()) {
        error_log("[get_employees.php] Role select execute failed: " . $stmt_roles->error);
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Role select execute failed: ' . $stmt_roles->error]);
        ob_end_flush();
        exit;
    }
    $roles_result = $stmt_roles->get_result();
    $roles = [];
    while ($role_row = $roles_result->fetch_assoc()) {
        $roles[] = $role_row['role_name'];
    }
    $row['roles'] = $roles;
    $employees[] = $row;
}

ob_clean();
echo json_encode(['success' => true, 'employees' => $employees]);
error_log("[get_employees.php] Execution completed successfully");
$conn->close();
ob_end_flush();
?>