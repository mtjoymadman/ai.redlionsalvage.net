<?php
require_once 'config.php';
if (isset($_SESSION['employee_id'])) {
    $stmt = $db->prepare("UPDATE time_logs SET clock_out = NOW() WHERE employee_id = ? AND clock_out IS NULL");
    $stmt->bind_param("i", $_SESSION['employee_id']);
    $stmt->execute();
    session_destroy();
}
header('Location: /frontend/index.php');
exit;
?>