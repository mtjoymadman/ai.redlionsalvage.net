<?php
header('Content-Type: application/json');
require_once '../../includes/db_connect.php';
require_once '../../api/auth.php';

$pdo = getPDOConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['vehicle_id'])) {
            $stmt = $pdo->prepare("SELECT * FROM documents WHERE vehicle_id = ?");
            $stmt->execute([$_GET['vehicle_id']]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        } else {
            $stmt = $pdo->query("SELECT * FROM documents");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("INSERT INTO documents (vehicle_id, type, expiration_date, file_path) VALUES (?, ?, ?, ?)");
        $stmt->execute([$data['vehicle_id'], $data['type'], $data['expiration_date'], $data['file_path']]);
        echo json_encode(['id' => $pdo->lastInsertId()]);
        break;

    default:
        header("HTTP/1.1 405 Method Not Allowed");
        echo json_encode(['error' => 'Method not allowed']);
}
?>