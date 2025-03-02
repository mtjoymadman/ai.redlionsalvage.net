<?php
require_once '../config.php';
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
$items = $data['items'] ?? [];

if (in_array('office', json_decode(file_get_contents("http://{$_SERVER['HTTP_HOST']}/api/get_user_roles.php"), true))) {
    $total = 0;
    foreach ($items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    $stmt = $db->prepare("INSERT INTO sales (employee_id, total_amount) VALUES (?, ?)");
    $stmt->bind_param("id", $_SESSION['employee_id'], $total);
    $stmt->execute();
    $sale_id = $db->insert_id;

    foreach ($items as $item) {
        $stmt = $db->prepare("INSERT INTO sale_items (sale_id, part_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiid", $sale_id, $item['id'], $item['quantity'], $item['price']);
        $stmt->execute();
        $stmt = $db->prepare("INSERT INTO parts (id, name, price) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), price = VALUES(price)");
        $stmt->bind_param("isd", $item['id'], $item['name'], $item['price']);
        $stmt->execute();
    }

    // Simplified printer/drawer logic
    file_get_contents("http://{$_SERVER['HTTP_HOST']}/api/pos/print_receipt.php?sale_id={$sale_id}");
    file_get_contents("http://{$_SERVER['HTTP_HOST']}/api/pos/open_drawer.php");

    echo json_encode(['success' => true, 'message' => 'Sale recorded']);
} else {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
}
?>