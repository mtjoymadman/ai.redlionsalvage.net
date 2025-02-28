<?php
require_once '../config.php';
header('Content-Type: application/json');
$result = $db->query("SELECT id, username, full_name, suspended, last_status_change FROM employees ORDER BY username");
$employees = [];
while ($row = $result->fetch_assoc()) {
    $employees[] = $row;
}
echo json_encode($employees);
?>