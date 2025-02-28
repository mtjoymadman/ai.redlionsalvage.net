<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

// Database configuration
require_once '../../includes/db_connect.php';

try {
    // Create database connection
    $pdo = getPDOConnection();

    // Get query parameters
    $status = isset($_GET['status']) ? trim($_GET['status']) : null;
    $type = isset($_GET['type']) ? trim($_GET['type']) : null;
    $sort_by = isset($_GET['sort_by']) ? trim($_GET['sort_by']) : 'vehicle_id';
    $sort_order = isset($_GET['sort_order']) && strtoupper($_GET['sort_order']) === 'DESC' ? 'DESC' : 'ASC';
    $page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) && $_GET['limit'] > 0 && $_GET['limit'] <= 100 ? (int)$_GET['limit'] : 10;

    // Validate sort_by against known columns to prevent injection
    $allowed_sorts = ['vehicle_id', 'type', 'status'];
    $sort_by = in_array($sort_by, $allowed_sorts) ? $sort_by : 'vehicle_id';

    // Calculate offset for pagination
    $offset = ($page - 1) * $limit;

    // Build dynamic query
    $query = "SELECT * FROM fleet_vehicles WHERE 1=1";
    $params = [];

    if ($status) {
        $query .= " AND status = :status";
        $params[':status'] = $status;
    }
    if ($type) {
        $query .= " AND type = :type";
        $params[':type'] = $type;
    }

    $query .= " ORDER BY $sort_by $sort_order LIMIT :limit OFFSET :offset";
    $params[':limit'] = $limit;
    $params[':offset'] = $offset;

    // Prepare and execute the main query
    $stmt = $pdo->prepare($query);
    foreach ($params as $key => $value) {
        $paramType = ($key === ':limit' || $key === ':offset') ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($key, $value, $paramType);
    }
    $stmt->execute();
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) as total FROM fleet_vehicles WHERE 1=1";
    $countParams = [];
    if ($status) {
        $countQuery .= " AND status = :status";
        $countParams[':status'] = $status;
    }
    if ($type) {
        $countQuery .= " AND type = :type";
        $countParams[':type'] = $type;
    }
    $countStmt = $pdo->prepare($countQuery);
    foreach ($countParams as $key => $value) {
        $countStmt->bindValue($key, $value, PDO::PARAM_STR);
    }
    $countStmt->execute();
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($total / $limit);

    // Return success response with pagination metadata
    echo json_encode([
        'success' => true,
        'data' => [
            'vehicles' => $vehicles,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_vehicles' => $total,
                'items_per_page' => $limit
            ]
        ]
    ]);

} catch (PDOException $e) {
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>