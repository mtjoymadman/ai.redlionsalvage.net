<?php
require_once '/api/config.php';  // Absolute path from root
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Example authentication (adjust to your DB logic)
    if (!empty($username)) {
        // Simulate DB check
        $_SESSION['employee_id'] = 1;  // Replace with actual ID
        $_SESSION['employee_name'] = $username;  // Replace with actual name
        $response['success'] = true;
    } else {
        $response['message'] = 'Invalid credentials';
    }
}

echo json_encode($response);
?>
