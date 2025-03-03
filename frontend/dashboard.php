<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporti
ng(E_ALL);
// require_once '/api/config.php';
// session_start();
// if (!isset($_SESSION['employee_id'])) {
//     header('Location: /frontend/index.php');
//     exit;
// }
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
        <h1>Welcome to Dashboard</h1>
        <!-- Static content for testing -->
    </div>
</body>
</html>
