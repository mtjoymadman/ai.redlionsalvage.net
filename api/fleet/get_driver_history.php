<?php
require_once '../config.php';
header('Content-Type: application/json');
$truck_id = $_GET['truck_id'] ?? 0;
$stmt = $db->prepare("SELECT fva.date_assigned, e.username, fva.unassigned_at FROM fleet_vehicle_assignments fva JOIN employees e ON fva.driver_id = e.id WHERE fva.vehicle_id = ? ORDER BY fva.date_assigned DESC");
$stmt->bind_param("i", $truck_id);
$stmt->execute();
$result = $stmt->get_result();
$history = [];
while ($row = $result->fetch_assoc()) {
    $history[] = $row;
}
echo json_encode($history);
?>