<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// require_once 'config.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    if (!empty($username)) {
        $response['success'] = true;
    } else {
        $response['message'] = 'Username required';
    }
} else {
    $response['message'] = 'Invalid request';
}

echo json_encode($response);
exit;
?>
