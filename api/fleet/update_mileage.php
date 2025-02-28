<?php
require_once '../config.php';
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? 0;
$mileage = $data['mileage'] ?? 0;

$stmt = $db->prepare("UPDATE fleet_vehicles SET mileage = ? WHERE id = ?");
$stmt->bind_param("ii", $mileage, $id);
$stmt->execute();

echo json_encode(['success' => true]);
?>