<?php
require_once 'config.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($username)) {
        $_SESSION['employee_id'] = 1; // Placeholder
        $_SESSION['employee_name'] = $username;
        $response['success'] = true;
    } else {
        $response['message'] = 'Invalid credentials';
    }
}

echo json_encode($response);
?>
