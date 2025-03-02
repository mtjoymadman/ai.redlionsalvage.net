<?php
require_once '../config.php';
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? 0;
$action = $data['action'] ?? '';

if (in_array('admin', json_decode(file_get_contents("http://{$_SERVER['HTTP_HOST']}/api/get_user_roles.php"), true))) {
    if ($action === 'approve') {
        $stmt = $db->prepare("INSERT INTO vehicles (stock_number, vin, make, model, year) SELECT stock_number, vin, make, model, year FROM pending_vehicles WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt = $db->prepare("DELETE FROM pending_vehicles WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        // Simplified NMVTIS submission placeholder
        echo json_encode(['success' => true, 'message' => 'Vehicle approved and added to inventory']);
    } else {
        $stmt = $db->prepare("DELETE FROM pending_vehicles WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo json_encode(['success' => true, 'message' => 'Vehicle rejected']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
}
?>