<?php
header('Content-Type: application/json');
require_once '../../includes/db_connect.php';
require_once '../../api/auth.php';

$pdo = getPDOConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
        } else {
            $stmt = $pdo->query("SELECT * FROM vehicles");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("INSERT INTO vehicles (fleet_nickname, email) VALUES (?, ?)");
        $stmt->execute([$data['fleet_nickname'], $data['email']]);
        echo json_encode(['id' => $pdo->lastInsertId()]);
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("UPDATE vehicles SET fleet_nickname = ?, email = ? WHERE id = ?");
        $stmt->execute([$data['fleet_nickname'], $data['email'], $data['id']]);
        echo json_encode(['status' => 'updated']);
        break;

    case 'DELETE':
        $id = $_GET['id'];
        $stmt = $pdo->prepare("DELETE FROM vehicles WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['status' => 'deleted']);
        break;

    default:
        header("HTTP/1.1 405 Method Not Allowed");
        echo json_encode(['error' => 'Method not allowed']);
}
?>