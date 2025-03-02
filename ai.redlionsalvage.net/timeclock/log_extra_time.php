// /api/timeclock/log_extra_time.php
<?php
session_start();
include '../../config.php';

if (!isset($_SESSION['employee_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$employee_id = $_SESSION['employee_id'];
$data = json_decode(file_get_contents('php://input'), true);
$date = $data['date'];
$hours = $data['hours'];
$description = $data['description'];

$stmt = $conn->prepare("INSERT INTO extra_time_logs (employee_id, date, hours, description) VALUES (?, ?, ?, ?)");
$stmt->bind_param("isds", $employee_id, $date, $hours, $description);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to log extra time']);
}
$conn->close();
?>