<?php
require_once '../config.php';
header('Content-Type: application/json');
$result = $db->query("SELECT fv.*, e.username AS assigned_driver, IF(fv.last_weighed_at > NOW() - INTERVAL 1 HOUR, 'In Use', IF(fv.maintenance_due <= NOW(), 'Maintenance', 'Idle')) AS current_status, (SELECT COUNT(*) FROM fleet_vehicle_documents fvd WHERE fvd.truck_id = fv.id AND fvd.status = 'approved' AND fvd.expiration_date <= NOW() + INTERVAL 30 DAY) AS documents_expiring FROM fleet_vehicles fv LEFT JOIN fleet_vehicle_assignments fva ON fv.id = fva.vehicle_id AND fva.unassigned_at IS NULL LEFT JOIN employees e ON fva.driver_id = e.id");
$vehicles = [];
while ($row = $result->fetch_assoc()) {
    $vehicles[] = $row;
}
echo json_encode($vehicles);
?>