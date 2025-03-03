<?php
require_once '/api/config.php';  // Absolute path from root
if (!isset($_SESSION['employee_id'])) {
    header('Location: /frontend/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YardMaster Dashboard</title>
    <link rel="stylesheet" href="/frontend/style.css">
</head>
<body>
    <header>
        <img src="/frontend/logo.png" alt="YardMaster Logo" class="logo">
        <a href="/frontend/index.php" class="home-btn">Home</a>
        <a href="/api/logout.php" class="logout-btn">Logout</a>
    </header>
    <div class="dashboard-container">
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['employee_name'] ?? 'User'); ?></h1>
        <!-- Dashboard content here -->
    </div>
</body>
</html>
