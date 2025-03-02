<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set content type to JSON
header('Content-Type: application/json');

// Simulate a successful response with dummy data
$response = [
    'success' => true,
    'data' => [
        'vehicles' => [
            ['vehicle_id' => 'V001', 'type' => 'Truck', 'status' => 'Active'],
            ['vehicle_id' => 'V002', 'type' => 'Van', 'status' => 'Inactive'],
            ['vehicle_id' => 'V003', 'type' => 'Car', 'status' => 'Active']
        ]
    ]
];

// Output the JSON response
echo json_encode($response);
?>