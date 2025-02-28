<?php
require_once '../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['employee_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}
$employee_id = $_SESSION['employee_id'];

// Fetch roles directly from database
$stmt = $db->prepare("SELECT r.role_name FROM employee_roles er JOIN roles r ON er.role_id = r.id WHERE er.employee_id = ?");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();
$roles = [];
while ($row = $result->fetch_assoc()) {
    $roles[] = $row['role_name'];
}
$stmt->close();
error_log("record_purchase.php: Roles fetched: " . json_encode($roles));

if (!in_array('office', $roles) && !in_array('admin', $roles) && !in_array('baby admin', $roles)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$material = $input['material'] ?? '';
$weight = $input['weight'] ?? 0;
$price = $input['price'] ?? 0;

if (empty($material) || $weight <= 0 || $price < 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$stmt = $db->prepare("INSERT INTO scrap_purchases (employee_id, material, weight, price_per_lb, purchase_date) VALUES (?, ?, ?, ?, NOW())");
$stmt->bind_param("isdd", $employee_id, $material, $weight, $price);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Purchase recorded successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
}
$stmt->close();
?>