<?php
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['employee_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$employee_id = $_SESSION['employee_id'];

// Fetch roles directly from database
$stmt = $db->prepare("SELECT r.role_name FROM employee_roles er JOIN roles r ON er.role_id = r.id WHERE er.employee_id = ?");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();
$roles = [];
while ($row = $result->fetch_assoc()) {
    $roles[] = $row['role_name'];
}
$stmt->close();
error_log("timeclock.php API: Roles fetched for employee_id $employee_id: " . json_encode($roles));

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'clock_in':
        $stmt = $db->prepare("SELECT id FROM time_logs WHERE employee_id = ? AND clock_out IS NULL");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'SQL Error: ' . $db->error]);
            exit;
        }
        $stmt->bind_param("i", $employee_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Already clocked in']);
        } else {
            $stmt = $db->prepare("INSERT INTO time_logs (employee_id, clock_in) VALUES (?, NOW())");
            $stmt->bind_param("i", $employee_id);
            $stmt->execute();
            echo json_encode(['success' => true]);
        }
        break;

    case 'clock_out':
        $stmt = $db->prepare("UPDATE time_logs SET clock_out = NOW() WHERE employee_id = ? AND clock_out IS NULL");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'SQL Error: ' . $db->error]);
            exit;
        }
        $stmt->bind_param("i", $employee_id);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Not clocked in']);
        }
        break;

    case 'start_break':
        $stmt = $db->prepare("SELECT id FROM time_logs WHERE employee_id = ? AND clock_out IS NULL");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'SQL Error: ' . $db->error]);
            exit;
        }
        $stmt->bind_param("i", $employee_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $time_log_id = $row['id'];
            $stmt = $db->prepare("INSERT INTO breaks (time_log_id, start_time, duration) VALUES (?, NOW(), 0)");
            $stmt->bind_param("i", $time_log_id);
            $stmt->execute();
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Not clocked in']);
        }
        break;

    case 'end_break':
        $stmt = $db->prepare("SELECT id FROM time_logs WHERE employee_id = ? AND clock_out IS NULL");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'SQL Error: ' . $db->error]);
            exit;
        }
        $stmt->bind_param("i", $employee_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $time_log_id = $row['id'];
            $stmt = $db->prepare("SELECT id, start_time FROM breaks WHERE time_log_id = ? AND duration = 0 ORDER BY start_time DESC LIMIT 1");
            $stmt->bind_param("i", $time_log_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $break_id = $row['id'];
                $start_time = new DateTime($row['start_time']);
                $end_time = new DateTime();
                $interval = $start_time->diff($end_time);
                $total_seconds = ($interval->h * 3600) + ($interval->i * 60) + $interval->s;
                $duration = round($total_seconds / 60); // Convert to minutes, rounded
                error_log("timeclock.php API: Break $break_id - Start: {$row['start_time']}, End: " . $end_time->format('Y-m-d H:i:s') . ", Duration: $duration minutes");
                $stmt = $db->prepare("UPDATE breaks SET duration = ? WHERE id = ?");
                $stmt->bind_param("ii", $duration, $break_id);
                $stmt->execute();
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No active break found']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Not clocked in']);
        }
        break;

    case 'get_employee_logs':
        if (!in_array('admin', $roles) && !in_array('baby admin', $roles)) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        $target_employee_id = $_GET['employee_id'] ?? 0;
        if (!$target_employee_id) {
            echo json_encode(['success' => false, 'message' => 'Employee ID required']);
            exit;
        }
        $stmt = $db->prepare("SELECT id, clock_in, clock_out FROM time_logs WHERE employee_id = ? ORDER BY clock_in DESC LIMIT 5");
        $stmt->bind_param("i", $target_employee_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $logs = [];
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
        echo json_encode(['success' => true, 'logs' => $logs]);
        break;

    case 'get_breaks':
        if (!in_array('admin', $roles) && !in_array('baby admin', $roles)) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        $time_log_id = $_GET['time_log_id'] ?? 0;
        if (!$time_log_id) {
            echo json_encode(['success' => false, 'message' => 'Time log ID required']);
            exit;
        }
        $stmt = $db->prepare("SELECT id, start_time, duration FROM breaks WHERE time_log_id = ? ORDER BY start_time");
        $stmt->bind_param("i", $time_log_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $breaks = [];
        while ($row = $result->fetch_assoc()) {
            $breaks[] = $row;
        }
        echo json_encode(['success' => true, 'breaks' => $breaks]);
        break;

    case 'correct_time':
        if (!in_array('admin', $roles) && !in_array('baby admin', $roles)) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        $log_id = $_GET['log_id'] ?? 0;
        $input = json_decode(file_get_contents('php://input'), true);
        $clock_in = $input['clock_in'] ?? null;
        $clock_out = $input['clock_out'] ?? null;

        if (!$log_id || !$clock_in) {
            echo json_encode(['success' => false, 'message' => 'Log ID and clock_in are required']);
            exit;
        }

        if (!DateTime::createFromFormat('Y-m-d H:i', $clock_in) || ($clock_out && !DateTime::createFromFormat('Y-m-d H:i', $clock_out))) {
            echo json_encode(['success' => false, 'message' => 'Invalid date/time format']);
            exit;
        }

        $stmt = $db->prepare("UPDATE time_logs SET clock_in = ?, clock_out = ? WHERE id = ?");
        $stmt->bind_param("ssi", $clock_in, $clock_out, $log_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Log not found or no changes made']);
        }
        break;

    case 'correct_break':
        if (!in_array('admin', $roles) && !in_array('baby admin', $roles)) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        $break_id = $_GET['break_id'] ?? 0;
        $input = json_decode(file_get_contents('php://input'), true);
        $start_time = $input['start_time'] ?? null;
        $duration = $input['duration'] ?? null;

        if (!$break_id || !$start_time || $duration <= 0) {
            echo json_encode(['success' => false, 'message' => 'Break ID, start time, and duration are required']);
            exit;
        }

        if (!DateTime::createFromFormat('Y-m-d H:i', $start_time)) {
            echo json_encode(['success' => false, 'message' => 'Invalid start time format']);
            exit;
        }

        $stmt = $db->prepare("UPDATE breaks SET start_time = ?, duration = ? WHERE id = ?");
        $stmt->bind_param("sii", $start_time, $duration, $break_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Break not found or no changes made']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
?>