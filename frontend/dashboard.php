<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

echo "PHP executed successfully<br>";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Test</title>
    <script>
        console.log('Head script loaded');
    </script>
</head>
<body>
    <button type="button" id="fleet-btn" onclick="console.log('Fleet clicked inline')">Fleet Management</button>
    <script>
        console.log('Body script loaded');
        document.getElementById('fleet-btn').addEventListener('click', function(event) {
            event.preventDefault();
            console.log('Fleet clicked via listener');
        });
    </script>
</body>
</html>