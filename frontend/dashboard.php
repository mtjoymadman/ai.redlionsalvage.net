<?php
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

if (!$user) {
    die("User not found in database for ID: $user_id");
}

// Define role-based access
$is_admin = ($user['role'] === 'admin' || $user['role'] === 'baby_admin');
$is_driver = ($user['role'] === 'driver');

echo "<!-- Debug: User role is '{$user['role']}' -->";
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
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">YardMaster</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">Dashboard</a>
                    </li>
                    <?php if ($is_admin): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="fleet/dashboard.php">Fleet Management</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Admin Settings</a>
                        </li>
                    <?php endif; ?>
                    <?php if ($is_driver): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="fleet/pretrip_form.php">Pre-Trip Inspection</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

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
                    <a href="fleet/dashboard.php" class="btn btn-primary" id="fleet-btn">Fleet Management</a>
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
        <?php elseif ($is_driver): ?>
            <div class="card mt-4">
                <div class="card-body">
                    <p>Your driver dashboard. Submit your pre-trip inspection below.</p>
                    <a href="fleet/pretrip_form.php" class="btn btn-primary">Submit Pre-Trip Inspection</a>
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
    <script>
        console.log('Dashboard page loaded');
        <?php if ($is_admin): ?>
        document.getElementById('fleet-btn').addEventListener('click', function(event) {
            event.preventDefault();
            console.log('Fleet Management button clicked');
            const tbody = document.getElementById('fleet-table-body');
            tbody.innerHTML = '<tr><td colspan="3">Loading fleet data...</td></tr>';
            document.getElementById('fleet-section').style.display = 'block';

            fetch('../api/fleet/get_fleet_vehicles.php?limit=10&page=1')
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
        <?php endif; ?>
    </script>
</body>
</html>