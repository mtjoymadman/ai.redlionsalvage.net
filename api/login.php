<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// require_once '/api/config.php';
// session_start();

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($username)) {
        $response['success'] = true;
    } else {
        $response['message'] = 'Username is required';
    }
} else {
    $response['message'] = 'Invalid request method';
}

echo json_encode($response);
exit;
?>
