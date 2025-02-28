<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../includes/db_connect.php';

$user_id = $_SESSION['user_id'];
$pdo = getPDOConnection();

$stmt = $pdo->prepare("SELECT username, email, role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$is_admin = ($user['role'] === 'admin');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - YardMaster</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        #fleet-section { display: none; margin-top: 20px; }
    </style>
</head>
<body>
    <?php include '../includes/nav.php'; ?>

    <div class="container mt-4">
        <h1>Welcome, <?php echo htmlspecialchars($user['username']); ?>!</h1>
        <p>Email: <?php echo htmlspecialchars($user['email']); ?></p>
        <p>Role: <?php echo htmlspecialchars($user['role']); ?></p>

        <?php if ($is_admin): ?>
            <div class="card mt-4">
                <div class="card-header">
                    <h3>Admin Dashboard</h3>
                </div>
                <div class="card-body">
                    <button type="button" id="fleet-btn" class="btn btn-primary">Fleet Management</button>
                    <button type="button" class="btn btn-secondary">User Management</button>
                </div>
            </div>
            <div id="fleet-section" class="card mt-4">
                <div class="card-header">
                    <h4>Fleet Vehicles</h4>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Vehicle ID</th>
                                <th>Type</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="fleet-table-body">
                        </tbody>
                    </table>
                    <div id="fleet-pagination"></div>
                </div>
            </div>
        <?php else: ?>
            <div class="card mt-4">
                <div class="card-body">
                    <p>This is your user dashboard. Check your profile or contact an admin for more details.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
    <script>
        console.log('Dashboard page loaded');

        document.getElementById('fleet-btn').addEventListener('click', function(event) {
            event.preventDefault();
            console.log('Fleet Management button clicked');
            const tbody = document.getElementById('fleet-table-body');
            tbody.innerHTML = '<tr><td colspan="3">Loading fleet data...</td></tr>';
            document.getElementById('fleet-section').style.display = 'block';

            fetch('/ai.redlionsalvage.net/api/fleet/get_fleet_vehicles.php?limit=10&page=1')
                .then(response => {
                    console.log('Fetch response status:', response.status);
                    if (!response.ok) {
                        throw new Error('Fetch failed with status ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Fleet data received:', data);
                    if (data.success) {
                        tbody.innerHTML = '';
                        data.data.vehicles.forEach(vehicle => {
                            tbody.innerHTML += `
                                <tr>
                                    <td>${vehicle.vehicle_id || 'N/A'}</td>
                                    <td>${vehicle.type || 'N/A'}</td>
                                    <td>${vehicle.status || 'N/A'}</td>
                                </tr>
                            `;
                        });
                    } else {
                        console.error('API error:', data.error);
                        tbody.innerHTML = '<tr><td colspan="3">Error: ' + data.error + '</td></tr>';
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error.message);
                    tbody.innerHTML = '<tr><td colspan="3">Fetch error: ' + error.message + '</td></tr>';
                });
        });
    </script>
</body>
</html>