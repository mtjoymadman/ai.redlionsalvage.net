<?php
require_once 'config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (in_array('admin', json_decode(file_get_contents("http://{$_SERVER['HTTP_HOST']}/api/get_user_roles.php"), true)) || in_array('baby admin', $roles))) {
    $username = $_POST['username'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $password = $_POST['password'] ?? '';
    $roles = $_POST['roles'] ?? [];

    $stmt = $db->prepare("SELECT id FROM employees WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $employee = $result->fetch_assoc();

    if ($employee) {
        $employee_id = $employee['id'];
        $query = $password ? "UPDATE employees SET full_name = ?, password = ? WHERE id = ?" : "UPDATE employees SET full_name = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $password ? $stmt->bind_param("ssi", $full_name, $hashed_password, $employee_id) : $stmt->bind_param("si", $full_name, $employee_id);
    } else {
        $stmt = $db->prepare("INSERT INTO employees (username, full_name, password) VALUES (?, ?, ?)");
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $stmt->bind_param("sss", $username, $full_name, $hashed_password);
    }
    $stmt->execute();
    $employee_id = $employee_id ?? $db->insert_id;

    $db->query("DELETE FROM employee_roles WHERE employee_id = $employee_id");
    foreach ($roles as $role) {
        $stmt = $db->prepare("INSERT INTO employee_roles (employee_id, role_id) SELECT ?, id FROM roles WHERE role_name = ?");
        $stmt->bind_param("is", $employee_id, $role);
        $stmt->execute();
    }

    echo json_encode(['success' => true, 'message' => 'Employee updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Unauthorized or invalid request']);
}
?>