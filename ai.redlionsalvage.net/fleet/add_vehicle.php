<?php
require_once '../config.php';
header('Content-Type: application/json');
$make = $_POST['make'] ?? '';
$model = $_POST['model'] ?? '';
$year = $_POST['year'] ?? '';
$license_plate = $_POST['license_plate'] ?? '';
$vin = $_POST['vin'] ?? '';
$has_scales = isset($_POST['has_scales']) ? 1 : 0;
$mileage = $_POST['mileage'] ?? 0;

$stmt = $db->prepare("INSERT INTO fleet_vehicles (make, model, year, license_plate, vin, has_scales, mileage) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssisssi", $make, $model, $year, $license_plate, $vin, $has_scales, $mileage);
$stmt->execute();

echo json_encode(['success' => true, 'message' => 'Vehicle added successfully']);
?>