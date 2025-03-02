<?php
require_once '../config.php';
header('Content-Type: application/json');
$result = $db->query("SELECT pv.id, pv.vin, pv.make, pv.model, pv.year, pv.condition, pv.weight, e.username AS submitted_by, pv.pickup_truck_id FROM pending_vehicles pv JOIN employees e ON pv.submitted_by = e.id");
$vehicles = [];
while ($row = $result->fetch_assoc()) {
    $vehicles[] = $row;
}
echo json_encode($vehicles);
?>