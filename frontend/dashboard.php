<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
require_once '../includes/db_connect.php';

$user_id = $_SESSION['user_id'];
$pdo = getPDOConnection();

// Fetch user details
$stmt = $pdo->prepare("SELECT username, email, role FROM employees WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Role-based access
$is_admin = in_array($user['role'], ['admin', 'baby_admin']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - YardMaster</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
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
                    <?php endif; ?>
                    <?php if ($user['role'] === 'driver'): ?>
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
                    <a href="fleet/dashboard.php" class="btn btn-primary">Manage Fleet</a>
                    <button type="button" class="btn btn-secondary">User Management</button>
                </div>
            </div>
        <?php else: ?>
            <div class="card mt-4">
                <div class="card-body">
                    <p>Your dashboard. Use the navigation to access available features.</p>
                    <?php if ($user['role'] === 'driver'): ?>
                        <a href="fleet/pretrip_form.php" class="btn btn-primary">Submit Pre-Trip Inspection</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>