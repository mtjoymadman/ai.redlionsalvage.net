// /api/fleet/assign_driver.php
<?php
session_start();
include '../../config.php';

if (!isset($_SESSION['employee_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$employee_id = $_SESSION['employee_id'];
$stmt = $conn->prepare("SELECT role_id FROM employee_roles WHERE employee_id = ? AND role_id = 1");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
if (!$stmt->get_result()->fetch_assoc()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$vehicle_id = $data['vehicle_id'];
$driver_id = $data['driver_id'];

$stmt = $conn->prepare("UPDATE fleet_vehicle_assignments SET unassigned_at = NOW() WHERE vehicle_id = ? AND unassigned_at IS NULL");
$stmt->bind_param("i", $vehicle_id);
$stmt->execute();

$stmt = $conn->prepare("INSERT INTO fleet_vehicle_assignments (vehicle_id, driver_id) VALUES (?, ?)");
$stmt->bind_param("ii", $vehicle_id, $driver_id);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to assign driver']);
}
$conn->close();
?>