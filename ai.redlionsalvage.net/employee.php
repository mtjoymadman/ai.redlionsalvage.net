<?php
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['employee_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}
$roles = json_decode(file_get_contents("http://{$_SERVER['HTTP_HOST']}/api/get_user_roles.php?employee_id=" . $_SESSION['employee_id']), true);
if (!in_array('admin', $roles) && !in_array('baby admin', $roles)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'add':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }
        $username = $_POST['username'] ?? '';
        $full_name = $_POST['full_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $role_id = $_POST['role_id'] ?? '';

        if (empty($username) || empty($full_name) || empty($email) || empty($password) || empty($role_id)) {
            echo json_encode(['success' => false, 'message' => 'All fields are required']);
            exit;
        }

        $stmt = $db->prepare("INSERT INTO employees (username, full_name, password, email) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $full_name, $password, $email);
        if ($stmt->execute()) {
            $employee_id = $db->insert_id;
            $stmt = $db->prepare("INSERT INTO employee_roles (employee_id, role_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $employee_id, $role_id);
            $stmt->execute();
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
        }
        break;

    case 'toggle_suspend':
        $employee_id = $_GET['id'] ?? 0;
        $suspend = $_GET['suspend'] ?? 0;
        if (!$employee_id || !in_array($suspend, [0, 1])) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            exit;
        }
        $stmt = $db->prepare("UPDATE employees SET suspended = ?, last_status_change = NOW() WHERE id = ?");
        $stmt->bind_param("ii", $suspend, $employee_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Employee not found or no change']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
?>