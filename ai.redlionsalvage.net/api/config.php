<?php
session_start();
date_default_timezone_set('America/New_York'); // Set PHP to Eastern New York timezone
define('DB_HOST', 'mysql.us.cloudlogin.co');
define('DB_USER', 'salvageyard_ai');
define('DB_PASS', '7361dead');
define('DB_NAME', 'salvageyard_ai');
define('DEVELOPMENT_MODE', true);

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Set MySQL session timezone to EST (UTC-5); use offset since named zones aren't loaded
$db->query("SET time_zone = '-05:00';");
error_log("config.php: MySQL timezone set to -05:00 (EST)");
?>