<?php
require_once '../config.php';
$stmt = $db->prepare("UPDATE breaks SET duration = TIMESTAMPDIFF(MINUTE, start_time, NOW()) WHERE time_log_id = (SELECT id FROM time_logs WHERE employee_id = ? AND clock_out IS NULL ORDER BY clock_in DESC LIMIT 1) AND duration = 0");
$stmt->bind_param("i", $_SESSION['employee_id']);
$stmt->execute();
?>