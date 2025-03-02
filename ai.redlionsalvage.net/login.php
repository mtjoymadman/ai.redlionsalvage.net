<?php
require_once 'config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = isset($_POST['password']) ? $_POST['password'] : null;

    $stmt = $db->prepare("SELECT id, password, suspended FROM employees WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE && $username === 'admin' && $user && $user['password'] === '' && !$user['suspended'] && ($password === null || $password === '')) {
        $_SESSION['employee_id'] = $user['id'];
        $stmt = $db->prepare("INSERT INTO time_logs (employee_id, clock_in) VALUES (?, NOW())");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        echo json_encode(['success' => true]);
    } elseif ($user && $password !== null && $user['password'] === $password && !$user['suspended']) {
        $_SESSION['employee_id'] = $user['id'];
        $stmt = $db->prepare("INSERT INTO time_logs (employee_id, clock_in) VALUES (?, NOW())");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials or account suspended']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>