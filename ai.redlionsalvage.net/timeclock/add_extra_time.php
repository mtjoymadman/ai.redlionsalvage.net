<?php
require_once '../config.php';
$extra_time = $_POST['extra_time'] ?? 0;
$stmt = $db->prepare("UPDATE time_logs SET extra_time = ? WHERE employee_id = ? AND clock_out IS NULL ORDER BY clock_in DESC LIMIT 1");
$stmt->bind_param("ii", $extra_time, $_SESSION['employee_id']);
$stmt->execute();
?>