<?php
require_once '../config.php';
header('Content-Type: application/json');
$stmt = $db->prepare("SELECT DATE(clock_in) AS date, extra_time FROM time_logs WHERE employee_id = ? AND extra_time > 0");
$stmt->bind_param("i", $_SESSION['employee_id']);
$stmt->execute();
$result = $stmt->get_result();
$extra_times = [];
while ($row = $result->fetch_assoc()) {
    $extra_times[] = $row;
}
echo json_encode($extra_times);
?>