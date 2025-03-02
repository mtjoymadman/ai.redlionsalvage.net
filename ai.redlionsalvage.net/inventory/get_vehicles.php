// /api/inventory/get_vehicles.php
<?php
session_start();
include '../../config.php';

if (!isset($_SESSION['employee_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$result = $conn->query("SELECT * FROM vehicles WHERE status_id = 1");
$vehicles = [];
while ($row = $result->fetch_assoc()) {
    $vehicles[] = $row;
}
echo json_encode(['success' => true, 'vehicles' => $vehicles]);
$conn->close();
?>