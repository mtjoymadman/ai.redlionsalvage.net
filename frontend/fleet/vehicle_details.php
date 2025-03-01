<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../api/auth.php';

$pdo = getPDOConnection();
$id = $_GET['id'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
$stmt->execute([$id]);
$vehicle = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$vehicle) {
    die("Vehicle not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Details - YardMaster</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/fleet.css">
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
                        <a class="nav-link" href="dashboard.php">Fleet Management</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1 class="fleet-header">Vehicle Details: <?php echo htmlspecialchars($vehicle['fleet_nickname']); ?></h1>
        <div class="card">
            <div class="card-body">
                <p><strong>ID:</strong> <?php echo htmlspecialchars($vehicle['id']); ?></p>
                <p><strong>Fleet Nickname:</strong> <?php echo htmlspecialchars($vehicle['fleet_nickname']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($vehicle['email']); ?></p>
                <p><strong>Created:</strong> <?php echo htmlspecialchars($vehicle['created_at']); ?></p>
                <p><strong>Updated:</strong> <?php echo htmlspecialchars($vehicle['updated_at']); ?></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>