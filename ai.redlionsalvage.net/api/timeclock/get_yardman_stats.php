<?php
require_once '../config.php';
header('Content-Type: application/json');
$stmt = $db->prepare("SELECT e.username, DATE(vs.first_scan_at) AS date, COUNT(vs.id) AS vehicles_processed, AVG(TIMESTAMPDIFF(MINUTE, vs.first_scan_at, vs.strip_confirmed_at)) AS avg_time FROM vehicle_stripping vs JOIN employees e ON vs.yardman_id = e.id WHERE vs.strip_confirmed_at IS NOT NULL AND DATE(vs.first_scan_at) = CURDATE() GROUP BY e.username");
$stmt->execute();
$result = $stmt->get_result();
$stats = [];
while ($row = $result->fetch_assoc()) {
    $stats[] = $row;
}
echo json_encode($stats);
?>