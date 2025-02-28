<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
require_once '../includes/db_connect.php';

$user_id = $_SESSION['user_id'];
$pdo = getPDOConnection();

// Fetch user details
$stmt = $pdo->prepare("SELECT username, email, role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if user is admin
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
    <script>
        console.log('Script loaded in head'); // Immediate check
        function fleetClickHandler(event) {
            event.preventDefault();
            console.log('Fleet button clicked inline');
            document.getElementById('fleet-section').style.display = 'block';
            document.getElementById('fleet-table-body').innerHTML = '<tr><td colspan="3">Test data loaded</td></tr>';
        }
    </script>
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
                            <a class="nav-link" href="#">Fleet Management</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Admin Settings</a>
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
                    <button type="button" id="fleet-btn" class="btn btn-primary" onclick="fleetClickHandler(event)">Fleet Management</button>
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
    <script>
        console.log('Body script loaded');
        document.getElementById('fleet-btn').addEventListener('click', function(event) {
            event.preventDefault();
            console.log('Fleet button clicked via listener');
            document.getElementById('fleet-section').style.display = 'block';
            document.getElementById('fleet-table-body').innerHTML = '<tr><td colspan="3">Test data loaded via listener</td></tr>';
        });
    </script>
</body>
</html>