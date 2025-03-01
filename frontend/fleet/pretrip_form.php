<?php
session_start();
require_once '../../includes/db_connect.php';

// Check if driver is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'driver') {
    header("Location: ../login.php");
    exit();
}

$pdo = getPDOConnection();
$user_id = $_SESSION['user_id'];

// Fetch assigned vehicles
$stmt = $pdo->prepare("SELECT v.id, v.fleet_nickname FROM vehicles v JOIN vehicle_assignments va ON v.id = va.vehicle_id WHERE va.driver_id = ?");
$stmt->execute([$user_id]);
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch latest pre-trip inspection for each vehicle
$valid_inspections = [];
foreach ($vehicles as $vehicle) {
    $stmt = $pdo->prepare("SELECT expiration_date FROM pretrip_inspections WHERE vehicle_id = ? ORDER BY inspection_date DESC LIMIT 1");
    $stmt->execute([$vehicle['id']]);
    $inspection = $stmt->fetch(PDO::FETCH_ASSOC);
    $valid_inspections[$vehicle['id']] = $inspection && strtotime($inspection['expiration_date']) > time();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicle_id = $_POST['vehicle_id'];

    // Include FPDF
    require_once '../../lib/fpdf.php';

    // Generate PDF
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'Pre-Trip Inspection Report', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, 'Vehicle: ' . $vehicles[array_search($vehicle_id, array_column($vehicles, 'id'))]['fleet_nickname'], 0, 1);
    $pdf->Cell(0, 10, 'Driver: ' . $_SESSION['username'], 0, 1);
    $pdf->Cell(0, 10, 'Date: ' . date('Y-m-d H:i:s'), 0, 1);
    $pdf->Ln(10);

    // Sample DOT-compliant inspection items (expand as needed)
    $inspection_items = [
        'Brakes' => $_POST['brakes'],
        'Tires' => $_POST['tires'],
        'Lights' => $_POST['lights'],
        'Steering' => $_POST['steering'],
        'Horn' => $_POST['horn'],
        'Wipers' => $_POST['wipers']
    ];
    foreach ($inspection_items as $item => $status) {
        $pdf->Cell(0, 10, "$item: $status", 0, 1);
    }

    $notes = $_POST['notes'];
    if (!empty($notes)) {
        $pdf->Ln(5);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'Notes:', 0, 1);
        $pdf->SetFont('Arial', '', 12);
        $pdf->MultiCell(0, 10, $notes);
    }

    $pdf_path = "uploads/pretrip_{$vehicle_id}_" . time() . '.pdf';
    $pdf->Output('F', $pdf_path);

    // Store in database
    $stmt = $pdo->prepare("INSERT INTO pretrip_inspections (vehicle_id, driver_id, document_path) VALUES (?, ?, ?)");
    $stmt->execute([$vehicle_id, $user_id, $pdf_path]);

    header("Location: ../dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pre-Trip Inspection - YardMaster</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">YardMaster</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="../dashboard.php">Dashboard</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1>Pre-Trip Inspection</h1>
        <?php foreach ($vehicles as $vehicle): ?>
            <?php if (!$valid_inspections[$vehicle['id']]): ?>
                <form method="POST" class="mb-4">
                    <input type="hidden" name="vehicle_id" value="<?php echo $vehicle['id']; ?>">
                    <h3><?php echo htmlspecialchars($vehicle['fleet_nickname']); ?></h3>
                    <div class="mb-3">
                        <label for="brakes" class="form-label">Brakes</label>
                        <select class="form-select" name="brakes" required>
                            <option value="Pass">Pass</option>
                            <option value="Fail">Fail</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="tires" class="form-label">Tires</label>
                        <select class="form-select" name="tires" required>
                            <option value="Pass">Pass</option>
                            <option value="Fail">Fail</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="lights" class="form-label">Lights</label>
                        <select class="form-select" name="lights" required>
                            <option value="Pass">Pass</option>
                            <option value="Fail">Fail</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="steering" class="form-label">Steering</label>
                        <select class="form-select" name="steering" required>
                            <option value="Pass">Pass</option>
                            <option value="Fail">Fail</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="horn" class="form-label">Horn</label>
                        <select class="form-select" name="horn" required>
                            <option value="Pass">Pass</option>
                            <option value="Fail">Fail</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="wipers" class="form-label">Wipers</label>
                        <select class="form-select" name="wipers" required>
                            <option value="Pass">Pass</option>
                            <option value="Fail">Fail</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit Inspection</button>
                </form>
            <?php else: ?>
                <p class="text-success">Current pre-trip inspection for <?php echo htmlspecialchars($vehicle['fleet_nickname']); ?> is valid.</p>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>