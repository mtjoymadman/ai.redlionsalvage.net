<?php
require_once '../config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array('driver', json_decode(file_get_contents("http://{$_SERVER['HTTP_HOST']}/api/get_user_roles.php"), true))) {
    $stock_number = $_POST['stock_number'] ?? '';
    $vin = $_POST['vin'] ?? '';
    $make = $_POST['make'] ?? '';
    $model = $_POST['model'] ?? '';
    $year = $_POST['year'] ?? '';
    $condition = $_POST['condition'] ?? '';
    $weight = $_POST['weight'] ?? '';
    $pickup_truck_id = $_POST['pickup_truck_id'] ?? '';
    $photo = $_FILES['photo']['name'] ?? '';
    $photo_path = $photo ? "/assets/uploads/{$vin}_" . time() . "_" . $photo : null;

    if ($photo) {
        move_uploaded_file($_FILES['photo']['tmp_name'], $_SERVER['DOCUMENT_ROOT'] . $photo_path);
    }

    $stmt = $db->prepare("INSERT INTO pending_vehicles (stock_number, vin, make, model, year, condition, weight, photo, submitted_by, pickup_truck_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssissisi", $stock_number, $vin, $make, $model, $year, $condition, $weight, $photo_path, $_SESSION['employee_id'], $pickup_truck_id);
    $stmt->execute();

    // VPIC Lookup (simplified)
    $vpic_data = file_get_contents("https://vpic.nhtsa.dot.gov/api/vehicles/DecodeVin/{$vin}?format=json");
    $vpic = json_decode($vpic_data, true);
    if ($vpic && !$make) {
        $stmt = $db->prepare("UPDATE pending_vehicles SET make = ?, model = ?, year = ? WHERE vin = ?");
        $stmt->bind_param("ssis", $vpic['Results'][5]['Value'], $vpic['Results'][7]['Value'], $vpic['Results'][9]['Value'], $vin);
        $stmt->execute();
    }

    echo json_encode(['success' => true, 'message' => 'Vehicle submitted for approval']);
} else {
    echo json_encode(['success' => false, 'message' => 'Unauthorized or invalid request']);
}
?>