<?php
function getPDOConnection() {
    $dsn = 'mysql:host=lmysql.us.cloudlogin.co;dbname=salvageyard_ai;charset=utf8';
    $username = 'salvageyard_ai';
    $password = 'y7361dead';
    try {
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}
?>