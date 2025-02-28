<?php
require_once '../config.php';
header('Content-Type: application/json');
$query = "%" . ($_GET['query'] ?? '') . "%";
$stmt = $db->prepare("SELECT p.id, p.name, p.price FROM parts p JOIN vehicle_parts vp ON p.id = vp.part_id WHERE p.name LIKE ? AND vp.quantity > 0");
$stmt->bind_param("s", $query);
$stmt->execute();
$result = $stmt->get_result();
$parts = [];
while ($row = $result->fetch_assoc()) {
    $parts[] = $row;
}
echo json_encode($parts);
?>