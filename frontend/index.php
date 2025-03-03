<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// require_once '/api/config.php';
// if (isset($_SESSION['employee_id'])) {
//     header('Location: /frontend/dashboard.php');
//     exit;
// }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YardMaster Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <img src="logo.png" alt="YardMaster Logo" class="logo">
        <a href="/index.xhtml" class="home-btn">Home</a>
    </header>
    <div class="login-container">
        <h1>Login</h1>
        <form id="loginForm" class="vehicle-form" method="POST" action="../api/login.php">
            <div class="form-group">
                <input type="text" name="username" placeholder="Username" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="Password (optional in dev mode)">
            </div>
            <button type="submit" class="button">Login</button>
        </form>
        <div id="message" class="error"></div>
    </div>
    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            fetch('../api/login.php', {
                method: 'POST',
                body: new FormData(this)
            }).then(response => response.json()).then(data => {
                if (data.success) {
                    window.location.href = 'dashboard.php';
                } else {
                    document.getElementById('message').textContent = data.message || 'Login failed';
                }
            }).catch(error => {
                document.getElementById('message').textContent = 'Error: ' + error.message;
            });
        });
    </script>
</body>
</html>
