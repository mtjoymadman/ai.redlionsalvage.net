<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../../includes/db_connect.php';

$user_id = $_SESSION['user_id'];
$pdo = getPDOConnection();

$stmt = $pdo->prepare("SELECT username, role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$is_admin = in_array($user['role'], ['admin', 'baby_admin']);
$is_driver = ($user['role'] === 'driver');

// Fetch vehicles
$stmt = $pdo->query("SELECT * FROM vehicles");
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fleet Management - YardMaster</title>
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
                    <li class="nav-item">
                        <a class="nav-link active" href="#">Fleet Management</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1>Fleet Management</h1>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fleet Nickname</th>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($vehicles as $vehicle): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($vehicle['id']); ?></td>
                        <td><?php echo htmlspecialchars($vehicle['fleet_nickname']); ?></td>
                        <td><?php echo htmlspecialchars($vehicle['email']); ?></td>
                        <td>
                            <a href="vehicle_details.php?id=<?php echo $vehicle['id']; ?>" class="btn btn-primary btn-sm">View</a>
                            <?php if ($is_admin): ?>
                                <button class="btn btn-secondary btn-sm" onclick="alert('Edit functionality TBD')">Edit</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($is_admin): ?>
            <button class="btn btn-success" onclick="alert('Add vehicle functionality TBD')">Add Vehicle</button>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>