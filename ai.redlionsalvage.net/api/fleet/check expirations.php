// /api/fleet/check_expirations.php
<?php
include '../../config.php';

$thirty_days = date('Y-m-d', strtotime('+30 days'));
$stmt = $conn->prepare("SELECT vd.*, fv.make, fv.model FROM vehicle_documents vd JOIN fleet_vehicles fv ON vd.vehicle_id = fv.id WHERE vd.expiration_date <= ?");
$stmt->bind_param("s", $thirty_days);
$stmt->execute();
$result = $stmt->get_result();

$expiring = [];
while ($row = $result->fetch_assoc()) {
    $expiring[] = $row;
}

if (!empty($expiring)) {
    $message = "The following documents are expiring soon:\n";
    foreach ($expiring as $doc) {
        $message .= "Vehicle: {$doc['make']} {$doc['model']}, Document: {$doc['document_type']}, Expires: {$doc['expiration_date']}\n";
    }
    mail($notification_email, "Red Lion Salvage AI - Expiring Documents", $message, "From: no-reply@yourdomain.com");
}
$conn->close();
?>