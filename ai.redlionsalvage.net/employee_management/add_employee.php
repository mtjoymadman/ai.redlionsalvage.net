<?php
session_start();

header('Content-Type: application/json');
ob_start();

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', '/home/www/ai.redlionsalvage.net/logs/php_errors.log');

error_log("[add_employee.php] Starting execution");

$conn = new mysqli('localhost', 'salvageyard_ai', '7361dead', 'salvageyard__ai');
if ($conn->connect_error) {
    error_log("[add_employee.php] Database connection failed: " . $conn->connect_error);
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $conn->connect_error]);
    ob_end_flush();
    exit;
}
error_log("[add_employee.php] Database connection established");

if (!isset($_SESSION['employee_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("[add_employee.php] Invalid request: Session ID=" . ($_SESSION['employee_id'] ?? 'unset') . ", Method=" . $_SERVER['REQUEST_METHOD']);
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    ob_end_flush();
    exit;
}

$employee_id = $_SESSION['employee_id'];
error_log("[add_employee.php] Verifying admin role for employee_id: $employee_id");
$stmt = $conn->prepare("SELECT role_id FROM employee_roles WHERE employee_id = ? AND role_id = 1");
if (!$stmt) {
    error_log("[add_employee.php] Role check prepare failed: " . $conn->error);
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Role check prepare failed: ' . $conn->error]);
    ob_end_flush();
    exit;
}
$stmt->bind_param("i", $employee_id);
if (!$stmt->execute()) {
    error_log("[add_employee.php] Role check execute failed: " . $stmt->error);
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Role check execute failed: ' . $stmt->error]);
    ob_end_flush();
    exit;
}
$result = $stmt->get_result();
if (!$result->fetch_assoc()) {
    error_log("[add_employee.php] Unauthorized access attempt by employee_id: $employee_id");
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    ob_end_flush();
    exit;
}
error_log("[add_employee.php] Admin role verified");

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || empty($data['username'])) {
    error_log("[add_employee.php] Invalid or missing data: " . print_r($data, true));
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Missing username']);
    ob_end_flush();
    exit;
}
error_log("[add_employee.php] Data received: " . json_encode($data));

$employee_id = $data['employee_id'] ?? null;
$username = $data['username'];
$password = !empty($data['password']) ? password_hash($data['password'], PASSWORD_DEFAULT) : null;
$roles = $data['roles'] ?? [];
$suspended = $data['suspended'] ? 1 : 0;

if ($employee_id) {
    error_log("[add_employee.php] Updating employee ID=$employee_id");
    $stmt = $conn->prepare("UPDATE employees SET username = ?, password = ?, suspended = ? WHERE id = ?");
    if (!$stmt) {
        error_log("[add_employee.php] Update prepare failed: " . $conn->error);
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Update prepare failed: ' . $conn->error]);
        ob_end_flush();
        exit;
    }
    $stmt->bind_param("ssii", $username, $password, $suspended, $employee_id);
    if (!$stmt->execute()) {
        error_log("[add_employee.php] Update execute failed: " . $stmt->error);
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Update execute failed: ' . $stmt->error]);
        ob_end_flush();
        exit;
    }
    error_log("[add_employee.php] Employee updated, clearing roles for ID=$employee_id");
    $stmt = $conn->prepare("DELETE FROM employee_roles WHERE employee_id = ?");
    if (!$stmt) {
        error_log("[add_employee.php] Role delete prepare failed: " . $conn->error);
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Role delete prepare failed: ' . $conn->error]);
        ob_end_flush();
        exit;
    }
    $stmt->bind_param("i", $employee_id);
    if (!$stmt->execute()) {
        error_log("[add_employee.php] Role delete execute failed: " . $stmt->error);
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Role delete execute failed: ' . $stmt->error]);
        ob_end_flush();
        exit;
    }
} else {
    error_log("[add_employee.php] Adding new employee: Username=$username");
    $stmt = $conn->prepare("INSERT INTO employees (username, password, suspended) VALUES (?, ?, ?)");
    if (!$stmt) {
        error_log("[add_employee.php] Insert prepare failed: " . $conn->error);
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Insert prepare failed: ' . $conn->error]);
        ob_end_flush();
        exit;
    }
    $stmt->bind_param("ssi", $username, $password, $suspended);
    if (!$stmt->execute()) {
        error_log("[add_employee.php] Insert execute failed: " . $stmt->error);
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Insert execute failed: ' . $stmt->error]);
        ob_end_flush();
        exit;
    }
    $employee_id = $conn->insert_id;
    error_log("[add_employee.php] New employee inserted with ID=$employee_id");
}

if (!empty($roles)) {
    error_log("[add_employee.php] Assigning roles for employee ID=$employee_id");
    foreach ($roles as $role) {
        error_log("[add_employee.php] Checking role: $role");
        $stmt = $conn->prepare("SELECT id FROM roles WHERE role_name = ?");
        if (!$stmt) {
            error_log("[add_employee.php] Role select prepare failed: " . $conn->error);
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'Role select prepare failed: ' . $conn->error]);
            ob_end_flush();
            exit;
        }
        $stmt->bind_param("s", $role);
        if (!$stmt->execute()) {
            error_log("[add_employee.php] Role select execute failed: " . $stmt->error);
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'Role select execute failed: ' . $stmt->error]);
            ob_end_flush();
            exit;
        }
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $role_id = $row['id'];
            error_log("[add_employee.php] Found existing role: $role with ID=$role_id");
        } else {
            error_log("[add_employee.php] Role $role not found, creating new");
            $stmt = $conn->prepare("INSERT INTO roles (role_name) VALUES (?)");
            if (!$stmt) {
                error_log("[add_employee.php] Role insert prepare failed: " . $conn->error);
                ob_clean();
                echo json_encode(['success' => false, 'error' => 'Role insert prepare failed: ' . $conn->error]);
                ob_end_flush();
                exit;
            }
            $stmt->bind_param("s", $role);
            if (!$stmt->execute()) {
                error_log("[add_employee.php] Role insert execute failed: " . $stmt->error);
                ob_clean();
                echo json_encode(['success' => false, 'error' => 'Role insert execute failed: ' . $stmt->error]);
                ob_end_flush();
                exit;
            }
            $role_id = $conn->insert_id;
            error_log("[add_employee.php] Created new role: $role with ID=$role_id");
        }
        $stmt = $conn->prepare("INSERT INTO employee_roles (employee_id, role_id) VALUES (?, ?)");
        if (!$stmt) {
            error_log("[add_employee.php] Role assignment prepare failed: " . $conn->error);
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'Role assignment prepare failed: ' . $conn->error]);
            ob_end_flush();
            exit;
        }
        $stmt->bind_param("ii", $employee_id, $role_id);
        if (!$stmt->execute()) {
            error_log("[add_employee.php] Role assignment execute failed: " . $stmt->error);
        } else {
            error_log("[add_employee.php] Assigned role $role (ID=$role_id) to employee ID=$employee_id");
        }
    }
}

ob_clean();
echo json_encode(['success' => true]);
error_log("[add_employee.php] Execution completed successfully");
$conn->close();
ob_end_flush();
?>