<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once "../config.php";

// Get pagination parameters from query string
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

// Prepare query with pagination
$sql = "SELECT * FROM vehicles LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

$vehicles = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $vehicles[] = [
            'id' => $row['id'],
            'make' => $row['make'],
            'model' => $row['model'],
            'mileage' => $row['mileage']
        ];
    }
}

// Add pagination metadata
$response = [
    'vehicles' => $vehicles,
    'total' => $conn->query("SELECT COUNT(*) as total FROM vehicles")->fetch_assoc()['total'],
    'limit' => $limit,
    'offset' => $offset
];

echo json_encode($response);

$stmt->close();
$conn->close();
?>