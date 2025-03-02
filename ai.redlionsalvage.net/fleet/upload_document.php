<?php
require_once '../config.php';
header('Content-Type: application/json');
$roles = json_decode(file_get_contents("http://{$_SERVER['HTTP_HOST']}/api/get_user_roles.php"), true);

if (in_array('office', $roles) || in_array('admin', $roles) || in_array('baby admin', $roles)) {
    $truck_id = $_