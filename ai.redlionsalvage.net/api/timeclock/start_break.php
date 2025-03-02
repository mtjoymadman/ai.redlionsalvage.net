<?php
require_once '../config.php';
$stmt = $db->prepare("INSERT INTO breaks (time_log_id, start_time, duration) SELECT id, NOW(), 0 FROM time_logs WHERE employee_id = ? AND clock_out IS NULL ORDER BY clock_in DESC LIMIT 1");
$stmt->bind_param("i", $_SESSION['employee_id']);
$stmt->execute();
?>