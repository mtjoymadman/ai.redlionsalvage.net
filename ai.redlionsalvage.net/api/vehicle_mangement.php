<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', '/home/www/ai.redlionsalvage.net/logs/php_errors.log');
ob_start();

session_start();
include 'config.php'; // Adjusted to use existing config
require_once '../includes/functions.php'; // Assuming this exists or needs creation

if (!$conn) {
    error_log("Database connection failed: " . mysqli_connect_error());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    ob_end_flush();
    exit;
}

// Simplified placeholder for check_role until full implementation
function check_role($roles) {
    $user_roles = json_decode($_SESSION['roles'] ?? '[]', true);
    if (!array_intersect($roles, $user_roles)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
}

check_role(['admin']);

if (isset($_GET['action']) && $_GET['action'] === 'view_file' && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
    $thumbnail = isset($_GET['thumbnail']) && $_GET['thumbnail'] === '1';
    $column = $thumbnail ? 'thumbnail' : 'file_upload';
    $stmt = $conn->prepare("SELECT $column, file_name FROM vehicle_files WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $data = $row[$column];
        $file_name = $row['file_name'] ?? 'unnamed_file';
        if ($data) {
            $mime_type = $thumbnail ? 'image/png' : mime_content_type_from_string($data);
            header("Content-Type: $mime_type");
            header('Content-Disposition: inline; filename="' . htmlspecialchars($file_name) . ($thumbnail ? '_thumb.png' : '') . '"');
            header('Content-Length: ' . strlen($data));
            echo $data;
            $stmt->close();
            $conn->close();
            ob_end_flush();
            exit;
        }
    }
    header("HTTP/1.0 404 Not Found");
    exit("File not found");
}

function mime_content_type_from_string($data) {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    return $finfo->buffer($data);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'upload_file') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => '', 'file_id' => null, 'file_name' => ''];
    if (!empty($_FILES['file_upload']['name'])) {
        $file_type = mime_content_type($_FILES['file_upload']['tmp_name']);
        $file_name = basename($_FILES['file_upload']['name']);
        $file_size = $_FILES['file_upload']['size'];
        if (in_array($file_type, ['application/pdf', 'image/jpeg', 'image/png']) && $file_size <= 16777215) {
            $file_upload = file_get_contents($_FILES['file_upload']['tmp_name']);
            $thumbnail = null;
            if ($file_type === 'application/pdf' && class_exists('Imagick')) {
                try {
                    $imagick = new Imagick();
                    $imagick->readImageBlob($file_upload);
                    $imagick->setIteratorIndex(0);
                    $imagick->thumbnailImage(150, 150, true);
                    $imagick->setImageFormat('png');
                    $thumbnail = $imagick->getImageBlob();
                    $imagick->clear();
                    $imagick->destroy();
                } catch (Exception $e) {
                    error_log("Thumbnail generation failed: " . $e->getMessage());
                }
            } elseif (in_array($file_type, ['image/jpeg', 'image/png'])) {
                try {
                    $imagick = new Imagick();
                    $imagick->readImageBlob($file_upload);
                    $imagick->thumbnailImage(300, 300, true);
                    $imagick->setImageFormat('png');
                    $thumbnail = $imagick->getImageBlob();
                    $imagick->clear();
                    $imagick->destroy();
                } catch (Exception $e) {
                    error_log("Image thumbnail failed: " . $e->getMessage());
                }
            }
            $stmt = $conn->prepare("INSERT INTO vehicle_files (vehicle_id, file_upload, thumbnail, file_name) VALUES (?, ?, ?, ?)");
            $vehicle_id = !empty($_POST['vehicle_id']) && is_numeric($_POST['vehicle_id']) ? (int)$_POST['vehicle_id'] : null;
            $stmt->bind_param("ibss", $vehicle_id, $file_upload, $thumbnail, $file_name);
            $stmt->send_long_data(1, $file_upload);
            if ($thumbnail) $stmt->send_long_data(2, $thumbnail);
            if ($stmt->execute()) {
                $response = ['success' => true, 'message' => 'File uploaded successfully', 'file_id' => $stmt->insert_id, 'file_name' => $file_name];
            } else {
                $response['message'] = "Insert failed: " . $stmt->error;
                error_log("Upload insert failed: " . $stmt->error);
            }
            $stmt->close();
        } else {
            $response['message'] = $file_size > 16777215 ? "File too large" : "Invalid file type";
            error_log("Upload rejected: Type=$file_type, Size=$file_size");
        }
    } else {
        $response['message'] = "No file provided";
        error_log("Upload failed: No file provided");
    }
    ob_clean();
    echo json_encode($response);
    ob_end_flush();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'delete_file') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];
    $conn->begin_transaction();
    try {
        $file_id = (int)$_POST['file_id'];
        $stmt = $conn->prepare("DELETE FROM vehicle_files WHERE id = ?");
        $stmt->bind_param("i", $file_id);
        if ($stmt->execute()) {
            $conn->commit();
            $response = ['success' => true, 'message' => 'File deleted successfully'];
        } else {
            throw new Exception("Delete failed: " . $stmt->error);
        }
        $stmt->close();
    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = $e->getMessage();
        error_log("Delete file failed: " . $e->getMessage());
    }
    ob_clean();
    echo json_encode($response);
    ob_end_flush();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'save_vehicle') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => '', 'vehicle_id' => null];
    $conn->begin_transaction();
    try {
        $id = !empty($_POST['id']) && is_numeric($_POST['id']) ? (int)$_POST['id'] : null;
        $fields = [
            'number' => strtoupper($_POST['number'] ?? ''),
            'vin' => strtoupper($_POST['vin'] ?? ''),
            'title' => strtoupper($_POST['title'] ?? ''),
            'status' => $_POST['status'] ?? null,
            'mv7_date' => parseDateFromForm($_POST['mv7_date']),
            'mv6_date' => parseDateFromForm($_POST['mv6_date']),
            'thirty_day_processed' => parseDateFromForm($_POST['thirty_day_processed']),
            'sold_date' => parseDateFromForm($_POST['sold_date']),
            'strip_date' => parseDateFromForm($_POST['strip_date']),
            'color' => strtoupper($_POST['color'] ?? ''),
            'year' => !empty($_POST['year']) && $_POST['year'] !== '0' ? strtoupper($_POST['year']) : null,
            'make' => strtoupper($_POST['make'] ?? ''),
            'model' => strtoupper($_POST['model'] ?? ''),
            'vehicle_type' => strtoupper($_POST['vehicle_type'] ?? ''),
            'vehicle_body' => strtoupper($_POST['vehicle_body'] ?? ''),
            'updated_by' => 'UNKNOWN'
        ];
        if (isset($_SESSION['employee_id'])) {
            $stmt = $conn->prepare("SELECT username FROM employees WHERE id = ?");
            $stmt->bind_param("i", $_SESSION['employee_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) $fields['updated_by'] = strtoupper($row['username']);
            $stmt->close();
        }

        error_log("Raw POST data: " . json_encode($_POST));
        $file_ids = !empty($_POST['file_ids']) && json_decode($_POST['file_ids'], true) !== null ? json_decode($_POST['file_ids'], true) : [];
        $initial_file_ids = !empty($_POST['initial_file_ids']) && json_decode($_POST['initial_file_ids'], true) !== null ? json_decode($_POST['initial_file_ids'], true) : [];
        error_log("Initial file IDs: " . json_encode($initial_file_ids));
        error_log("Submitted file IDs: " . json_encode($file_ids));

        if (empty($id)) {
            $stmt = $conn->prepare("INSERT INTO vehicles (number, vin, title, status, mv7_date, mv6_date, thirty_day_processed, sold_date, strip_date, color, year, make, model, vehicle_type, vehicle_body, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssssssssss", $fields['number'], $fields['vin'], $fields['title'], $fields['status'], $fields['mv7_date'], $fields['mv6_date'], $fields['thirty_day_processed'], $fields['sold_date'], $fields['strip_date'], $fields['color'], $fields['year'], $fields['make'], $fields['model'], $fields['vehicle_type'], $fields['vehicle_body'], $fields['updated_by']);
            if ($stmt->execute()) {
                $id = $conn->insert_id;
                $response['vehicle_id'] = $id;
                $_SESSION['success_message'] = "Vehicle added successfully!";
            } else {
                throw new Exception("Insert failed: " . $stmt->error);
            }
            $stmt->close();
        } else {
            $stmt = $conn->prepare("SELECT number, vin, title, status, mv7_date, mv6_date, thirty_day_processed, sold_date, strip_date, color, year, make, model, vehicle_type, vehicle_body FROM vehicles WHERE id = ?");
            if (!$stmt) throw new Exception("Prepare failed for SELECT: " . $conn->error);
            $stmt->bind_param("i", $id);
            if (!$stmt->execute()) throw new Exception("Execute failed for SELECT: " . $stmt->error);
            $result = $stmt->get_result();
            $current = $result->fetch_assoc();
            if (!$current) throw new Exception("Vehicle with ID $id not found.");
            $stmt->close();

            $has_field_changes = false;
            foreach ($fields as $key => $value) {
                if ($key === 'updated_by') continue;
                $current_value = $current[$key] ?? null;
                if ($value !== $current_value && !(is_null($value) && is_null($current_value))) {
                    $has_field_changes = true;
                    error_log("Field change detected in $key: submitted='$value', current='$current_value'");
                    break;
                }
            }

            if (!empty($file_ids)) {
                $stmt = $conn->prepare("UPDATE vehicle_files SET vehicle_id = ? WHERE id = ? AND (vehicle_id IS NULL OR vehicle_id = ?)");
                if (!$stmt) throw new Exception("Prepare failed for file update: " . $conn->error);
                foreach ($file_ids as $file_id) {
                    $stmt->bind_param("iii", $id, $file_id, $id);
                    if (!$stmt->execute()) {
                        throw new Exception("File association failed for ID $file_id: " . $stmt->error);
                    }
                }
                $stmt->close();
            }

            $current_file_ids = [];
            $stmt = $conn->prepare("SELECT id FROM vehicle_files WHERE vehicle_id = ?");
            if (!$stmt) throw new Exception("Prepare failed for file SELECT: " . $conn->error);
            $stmt->bind_param("i", $id);
            if (!$stmt->execute()) throw new Exception("Execute failed for file SELECT: " . $stmt->error);
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $current_file_ids[] = (int)$row['id'];
            }
            $stmt->close();
            sort($current_file_ids);
            error_log("Current file IDs after save for vehicle $id: " . json_encode($current_file_ids));

            sort($initial_file_ids);
            $has_file_changes = $initial_file_ids !== $current_file_ids;
            $has_changes = $has_field_changes || $has_file_changes;
            if ($has_changes) {
                error_log("Update triggered: field_changes=$has_field_changes, file_changes=$has_file_changes");
                if ($has_file_changes) {
                    $added = array_diff($current_file_ids, $initial_file_ids);
                    $deleted = array_diff($initial_file_ids, $current_file_ids);
                    error_log("File changes: added=" . json_encode($added) . ", deleted=" . json_encode($deleted));
                }
                $stmt = $conn->prepare("UPDATE vehicles SET number = ?, vin = ?, title = ?, status = ?, mv7_date = ?, mv6_date = ?, thirty_day_processed = ?, sold_date = ?, strip_date = ?, color = ?, year = ?, make = ?, model = ?, vehicle_type = ?, vehicle_body = ?, last_updated_date = NOW(), updated_by = ? WHERE id = ?");
                if (!$stmt) throw new Exception("Prepare failed for UPDATE: " . $conn->error);
                $stmt->bind_param("ssssssssssssssssi", $fields['number'], $fields['vin'], $fields['title'], $fields['status'], $fields['mv7_date'], $fields['mv6_date'], $fields['thirty_day_processed'], $fields['sold_date'], $fields['strip_date'], $fields['color'], $fields['year'], $fields['make'], $fields['model'], $fields['vehicle_type'], $fields['vehicle_body'], $fields['updated_by'], $id);
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Vehicle updated successfully!";
                    $response['vehicle_id'] = $id;
                } else {
                    throw new Exception("Update failed: " . $stmt->error);
                }
                $stmt->close();
            } else {
                $_SESSION['success_message'] = "No change detected. Vehicle not changed!";
                $response['vehicle_id'] = $id;
            }
        }

        $conn->commit();
        $response['success'] = true;
        $response['message'] = $_SESSION['success_message'];
    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = "Error: " . $e->getMessage();
        error_log("Save vehicle failed: " . $e->getMessage());
    }
    ob_clean();
    echo json_encode($response);
    ob_end_flush();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'delete_vehicle') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];
    $conn->begin_transaction();
    try {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM vehicle_files WHERE vehicle_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        $stmt = $conn->prepare("DELETE FROM vehicles WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $conn->commit();
            $_SESSION['success_message'] = "Vehicle deleted successfully";
            $response = ['success' => true, 'message' => 'Vehicle deleted successfully'];
        }
        $stmt->close();
    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = "Error: " . $e->getMessage();
        error_log("Delete vehicle failed: " . $e->getMessage());
    }
    ob_clean();
    echo json_encode($response);
    ob_end_flush();
    exit;
}

function formatDateForForm($date) {
    if (!$date) return '';
    $dt = new DateTime($date, new DateTimeZone('UTC'));
    return $dt->format('m-d-Y');
}

function parseDateFromForm($date) {
    if (empty($date)) return null;
    $date = trim($date);
    if (preg_match('/^\d{8}$/', $date)) {
        $dt = DateTime::createFromFormat('mdY', $date, new DateTimeZone('UTC'));
    } else {
        $dt = DateTime::createFromFormat('m-d-Y', $date, new DateTimeZone('UTC'));
    }
    return $dt ? $dt->format('Y-m-d') : null;
}

function formatDateTimeForTable($date) {
    if (!$date) return '';
    $dt = new DateTime($date, new DateTimeZone('UTC'));
    $dt->setTimezone(new DateTimeZone('America/New_York'));
    return $dt->format('M j, Y g:i A');
}

$status_options = [];
$enum_query = $conn->query("SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'vehicles' AND COLUMN_NAME = 'status'");
if ($enum_query && $row = $enum_query->fetch_assoc()) {
    $enum_str = $row['COLUMN_TYPE'];
    preg_match_all("/'([^']*)'/", $enum_str, $matches);
    $status_options = array_filter($matches[1], function($value) { return $value !== ''; });
}

$sort_column = isset($_GET['sort']) && !empty($_GET['sort']) ? $_GET['sort'] : 'last_updated_date_desc';
$sort_field = null;
$sort_direction = null;
if ($sort_column) {
    $sort_direction = strpos($sort_column, '_desc') !== false ? 'DESC' : 'ASC';
    $sort_field = str_replace(['_asc', '_desc'], '', $sort_column);
}
$allowed_fields = [
    'number' => 'number',
    'vin' => 'vin',
    'status' => 'status',
    'year' => 'year',
    'make' => 'make',
    'model' => 'model',
    'last_updated_date' => 'COALESCE(last_updated_date, date_created)'
];
$order_by_field = ($sort_field && isset($allowed_fields[$sort_field])) ? $allowed_fields[$sort_field] : 'COALESCE(last_updated_date, date_created)';
$order_by = "$order_by_field $sort_direction";

$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
unset($_SESSION['success_message']);

$result = $conn->query("SELECT id, number, vin, title, status, color, year, make, model, vehicle_type, vehicle_body, date_created, last_updated_date, updated_by FROM vehicles ORDER BY $order_by");
$vehicles = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$db_error = !$result ? "Error loading vehicles: " . $conn->error : (empty($vehicles) ? "No vehicles found in the database." : null);

$vehicle_files = [];
foreach ($vehicles as &$vehicle) {
    if ($vehicle['year'] === '0' || $vehicle['year'] === 0) {
        $vehicle['year'] = '';
    }
    $stmt = $conn->prepare("SELECT id, file_name FROM vehicle_files WHERE vehicle_id = ?");
    $stmt->bind_param("i", $vehicle['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $initial_file_ids = [];
    while ($file = $result->fetch_assoc()) {
        $vehicle_files[$vehicle['id']][] = $file;
        $initial_file_ids[] = (int)$file['id'];
    }
    $stmt->close();
    $vehicle['initial_file_ids'] = json_encode($initial_file_ids);
}
unset($vehicle);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Vehicle Database</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <a href="../frontend/dashboard.html" class="home-btn">Home</a>
        <img src="../assets/images/logo_small.png" alt="Red Lion Salvage" class="logo">
        <a href="../api/logout.php" class="logout-btn">Logout</a>
    </header>
    <main>
        <h1>Vehicle Database</h1>
        <?php if ($success_message): ?>
            <div class="message-container"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if ($db_error): ?>
            <div class="error-container"><?php echo htmlspecialchars($db_error); ?></div>
        <?php endif; ?>

        <div class="search-bar">
            <input type="text" id="searchInput" placeholder="Search vehicles...">
            <button id="clearSearchBtn">Clear</button>
            <button id="addEntryBtn">Add Entry</button>
        </div>

        <div class="table-wrapper">
            <table class="vehicle-table" id="vehicleTable">
                <thead>
                    <tr>
                        <th data-sort="files">Files <span class="sort-indicator"></span></th>
                        <th data-sort="number">RLS Number <span class="sort-indicator"></span></th>
                        <th data-sort="vin">VIN <span class="sort-indicator"></span></th>
                        <th data-sort="status">Status <span class="sort-indicator"></span></th>
                        <th data-sort="year">Year <span class="sort-indicator"></span></th>
                        <th data-sort="make">Make <span class="sort-indicator"></span></th>
                        <th data-sort="model">Model <span class="sort-indicator"></span></th>
                        <th data-sort="last_updated_date">Date Updated <span class="sort-indicator">▼</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vehicles as $vehicle): ?>
                        <?php
                        $latest_date = $vehicle['last_updated_date'] ? ($vehicle['last_updated_date'] > $vehicle['date_created'] ? $vehicle['last_updated_date'] : $vehicle['date_created']) : $vehicle['date_created'];
                        $vehicle_data = json_encode([
                            'id' => $vehicle['id'] ?? '',
                            'number' => $vehicle['number'] ?? '',
                            'vin' => $vehicle['vin'] ?? '',
                            'title' => $vehicle['title'] ?? '',
                            'status' => $vehicle['status'] ?? '',
                            'color' => $vehicle['color'] ?? '',
                            'year' => $vehicle['year'] ?? '',
                            'make' => $vehicle['make'] ?? '',
                            'model' => $vehicle['model'] ?? '',
                            'vehicle_type' => $vehicle['vehicle_type'] ?? '',
                            'vehicle_body' => $vehicle['vehicle_body'] ?? '',
                            'date_created' => $vehicle['date_created'] ?? '',
                            'last_updated_date' => $latest_date ?? '',
                            'updated_by' => $vehicle['updated_by'] ?? '',
                            'files' => $vehicle_files[$vehicle['id']] ?? [],
                            'initial_file_ids' => $vehicle['initial_file_ids'] ?? '[]'
                        ]);
                        ?>
                        <tr onclick="loadVehicleForEdit('<?php echo htmlspecialchars($vehicle_data); ?>')">
                            <td><button class="files-btn" onclick="showFilesModal('<?php echo htmlspecialchars($vehicle['id']); ?>', '<?php echo htmlspecialchars(json_encode($vehicle_files[$vehicle['id']] ?? [])); ?>'); event.stopPropagation();">Files</button></td>
                            <td><?php echo htmlspecialchars($vehicle['number'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($vehicle['vin'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($vehicle['status'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($vehicle['year'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($vehicle['make'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($vehicle['model'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars(formatDateTimeForTable($latest_date)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <button id="showAllBtn">Show All</button>

        <div id="vehicleFormContainer" class="modal">
            <div class="modal-content">
                <h2>Vehicle Details</h2>
                <form id="vehicleForm" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="idInput">
                    <input type="hidden" name="original_vin" id="originalVinInput">
                    <input type="hidden" name="action" id="actionInput" value="save">
                    <input type="hidden" name="file_ids" id="fileIdsInput" value="[]">
                    <input type="hidden" name="initial_file_ids" id="initialFileIdsInput" value="[]">
                    <label>RLS Number: <input type="text" name="number" id="numberInput"></label>
                    <div class="vin-container">
                        <div class="vin-wrapper"><label for="vinInput">VIN:</label><input type="text" name="vin" id="vinInput"></div>
                        <button type="button" id="fetchVinData">Fetch</button>
                    </div>
                    <label>Title: <input type="text" name="title" id="titleInput"></label>
                    <label>Status: <select name="status" id="statusInput"><option value="">Select Status</option><?php foreach ($status_options as $option): ?><option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option><?php endforeach; ?></select></label>
                    <div class="date-container">
                        <div class="mv7-wrapper"><label for="mv7DateInput">MV7 Date:</label><input type="text" name="mv7_date" id="mv7DateInput" placeholder="MM-DD-YYYY" onblur="formatDateInput(this)"></div>
                        <button type="button" id="setMv7CurrentDate">Today</button>
                        <div class="mv6-wrapper"><label for="mv6DateInput">MV6 Date:</label><input type="text" name="mv6_date" id="mv6DateInput" placeholder="MM-DD-YYYY" onblur="formatDateInput(this)"></div>
                        <button type="button" id="setMv6CurrentDate">Today</button>
                    </div>
                    <div class="date-container-centered">
                        <div class="thirty-day-wrapper"><label for="thirtyDayProcessedInput">30 Day Processed Date:</label><input type="text" name="thirty_day_processed" id="thirtyDayProcessedInput" placeholder="MM-DD-YYYY" onblur="formatDateInput(this)"></div>
                        <div class="sold-date-wrapper"><label for="soldDateInput">Sold Date:</label><input type="text" name="sold_date" id="soldDateInput" placeholder="MM-DD-YYYY" onblur="formatDateInput(this)"></div>
                        <div class="strip-date-wrapper"><label for="stripDateInput">Strip Date:</label><input type="text" name="strip_date" id="stripDateInput" placeholder="MM-DD-YYYY" onblur="formatDateInput(this)"></div>
                    </div>
                    <label>Color: <input type="text" name="color" id="colorInput"></label>
                    <label>Year: <input type="text" name="year" id="yearInput"></label>
                    <label>Make: <input type="text" name="make" id="makeInput"></label>
                    <label>Model: <input type="text" name="model" id="modelInput"></label>
                    <label>Vehicle Type: <input type="text" name="vehicle_type" id="vehicleTypeInput"></label>
                    <label>Body Class: <input type="text" name="vehicle_body" id="vehicleBodyInput"></label>
                    <div id="photoUploadContainer">
                        <button type="button" class="upload-btn" id="photoUploadBtn">Upload Photo</button>
                        <input type="file" name="file_upload" id="photoUploadInput" accept="image/jpeg,image/png">
                        <div id="photoUploadProgress"></div>
                        <div id="photoUploadStatus"></div>
                        <div id="photoThumbnailsContainer"></div>
                    </div>
                    <div id="fileUploadContainer">
                        <button type="button" class="upload-btn" id="pdfUploadBtn">Upload PDF</button>
                        <input type="file" name="file_upload" id="fileUploadInput" accept="application/pdf">
                        <div id="pdfUploadProgress"></div>
                        <div id="pdfUploadStatus"></div>
                        <div id="pdfThumbnailsContainer"></div>
                    </div>
                    <div class="button-container">
                        <button type="button" id="cancelForm">Cancel</button>
                        <button type="button" id="saveVehicleBtn">Save</button>
                    </div>
                    <div class="delete-container">
                        <button type="button" id="deleteButton">Delete</button>
                    </div>
                    <div class="updated-by" id="updatedByDisplay"></div>
                </form>
            </div>
        </div>

        <div id="filesModal" class="files-modal">
            <div class="files-modal-content">
                <h2>Vehicle Files</h2>
                <div id="filesPhotoUploadContainer">
                    <button type="button" class="upload-btn" id="filesPhotoUploadBtn">Upload Photo</button>
                    <input type="file" name="file_upload" id="filesPhotoUploadInput" accept="image/jpeg,image/png">
                    <div id="filesPhotoUploadProgress"></div>
                    <div id="filesPhotoUploadStatus"></div>
                    <div id="filesPhotoThumbnailsContainer"></div>
                </div>
                <div id="filesPdfUploadContainer">
                    <button type="button" class="upload-btn" id="filesPdfUploadBtn">Upload PDF</button>
                    <input type="file" name="file_upload" id="filesPdfUploadInput" accept="application/pdf">
                    <div id="filesPdfUploadProgress"></div>
                    <div id="filesPdfUploadStatus"></div>
                    <div id="filesPdfThumbnailsContainer"></div>
                </div>
                <div class="button-container">
                    <button type="button" id="closeFilesModal">Close</button>
                </div>
            </div>
        </div>
    </main>

    <script>
        console.log('Script loaded');
        const formContainer = document.getElementById('vehicleFormContainer');
        const filesModal = document.getElementById('filesModal');
        const body = document.body;
        const form = document.getElementById('vehicleForm');
        let uploadedFileIds = [];
        let currentVehicleId = null;
        const isAdmin = true;
        const entriesPerPage = 10;

        function showFormModal() {
            console.log('Showing form modal');
            formContainer.classList.add('active');
            body.classList.add('form-modal-open');
        }

        function hideFormModal() {
            console.log('Hiding form modal');
            formContainer.classList.remove('active');
            body.classList.remove('form-modal-open');
            uploadedFileIds = JSON.parse(document.getElementById('fileIdsInput').value || '[]');
        }

        function showFilesModal(vehicleId, filesJson) {
            console.log('Showing files modal for vehicle:', vehicleId);
            currentVehicleId = vehicleId;
            filesModal.classList.add('active');
            body.classList.add('files-modal-open');
            const files = JSON.parse(filesJson);
            uploadedFileIds = files.map(file => file.id);
            const photoThumbnailsContainer = document.getElementById('filesPhotoThumbnailsContainer');
            const pdfThumbnailsContainer = document.getElementById('filesPdfThumbnailsContainer');
            photoThumbnailsContainer.innerHTML = '';
            pdfThumbnailsContainer.innerHTML = '';
            document.getElementById('filesPhotoUploadProgress').innerHTML = '';
            document.getElementById('filesPdfUploadProgress').innerHTML = '';
            document.getElementById('filesPhotoUploadStatus').textContent = '';
            document.getElementById('filesPdfUploadStatus').textContent = '';
            files.forEach(file => {
                const link = document.createElement('a');
                link.href = `?action=view_file&id=${file.id}`;
                link.target = '_blank';
                link.className = file.file_name.toLowerCase().endsWith('.pdf') ? 'pdfThumbnailLink' : 'photoThumbnailLink';
                const img = document.createElement('img');
                img.src = `?action=view_file&id=${file.id}&thumbnail=1`;
                img.className = file.file_name.toLowerCase().endsWith('.pdf') ? 'pdfThumbnail' : 'photoThumbnail';
                img.onerror = () => {
                    console.error(`Thumbnail failed for ID: ${file.id}`);
                    img.src = file.file_name.toLowerCase().endsWith('.pdf') ? '/images/pdf_placeholder.png' : `?action=view_file&id=${file.id}`;
                };
                link.appendChild(img);
                if (file.file_name.toLowerCase().endsWith('.pdf')) {
                    const nameSpan = document.createElement('span');
                    nameSpan.className = 'thumbnail-name';
                    nameSpan.textContent = file.file_name;
                    link.appendChild(nameSpan);
                }
                if (isAdmin) {
                    const deleteBtn = document.createElement('button');
                    deleteBtn.className = 'delete-btn';
                    deleteBtn.textContent = 'X';
                    deleteBtn.onclick = (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        if (confirm(`Delete "${file.file_name}"?`)) {
                            deleteFile(file.id, link, file.file_name.toLowerCase().endsWith('.pdf') ? 'filesPdfUploadStatus' : 'filesPhotoUploadStatus', file.file_name.toLowerCase().endsWith('.pdf') ? pdfThumbnailsContainer : photoThumbnailsContainer);
                        }
                    };
                    link.appendChild(deleteBtn);
                }
                if (file.file_name.toLowerCase().endsWith('.pdf')) {
                    pdfThumbnailsContainer.appendChild(link);
                } else {
                    photoThumbnailsContainer.appendChild(link);
                }
            });
        }

        function hideFilesModal() {
            console.log('Hiding files modal');
            filesModal.classList.remove('active');
            body.classList.remove('files-modal-open');
            currentVehicleId = null;
            uploadedFileIds = [];
        }

        document.getElementById('closeFilesModal').addEventListener('click', hideFilesModal);

        function formatDateForForm(date) {
            if (!date) return '';
            const dt = new Date(date);
            const month = (dt.getUTCMonth() + 1).toString().padStart(2, '0');
            const day = dt.getUTCDate().toString().padStart(2, '0');
            const year = dt.getUTCFullYear();
            return `${month}-${day}-${year}`;
        }

        function loadVehicleForEdit(vehicleJson) {
            const vehicle = JSON.parse(vehicleJson);
            console.log('Loading vehicle for edit:', vehicle);
            document.getElementById('idInput').value = vehicle.id || '';
            document.getElementById('numberInput').value = vehicle.number || '';
            document.getElementById('vinInput').value = vehicle.vin || '';
            document.getElementById('titleInput').value = vehicle.title || '';
            document.getElementById('statusInput').value = vehicle.status || '';
            document.getElementById('mv7DateInput').value = formatDateForForm(vehicle.mv7_date) || '';
            document.getElementById('mv6DateInput').value = formatDateForForm(vehicle.mv6_date) || '';
            document.getElementById('thirtyDayProcessedInput').value = formatDateForForm(vehicle.thirty_day_processed) || '';
            document.getElementById('soldDateInput').value = formatDateForForm(vehicle.sold_date) || '';
            document.getElementById('stripDateInput').value = formatDateForForm(vehicle.strip_date) || '';
            document.getElementById('colorInput').value = vehicle.color || '';
            document.getElementById('yearInput').value = vehicle.year === '0' ? '' : vehicle.year || '';
            document.getElementById('makeInput').value = vehicle.make || '';
            document.getElementById('modelInput').value = vehicle.model || '';
            document.getElementById('vehicleTypeInput').value = vehicle.vehicle_type || '';
            document.getElementById('vehicleBodyInput').value = vehicle.vehicle_body || '';
            document.getElementById('originalVinInput').value = vehicle.vin || '';
            document.getElementById('actionInput').value = 'save';

            const created = vehicle.date_created ? new Date(vehicle.date_created) : null;
            const updated = vehicle.last_updated_date ? new Date(vehicle.last_updated_date) : null;
            const latestDate = updated && created ? (updated > created ? updated : created) : (updated || created);
            document.getElementById('updatedByDisplay').textContent = vehicle.updated_by && latestDate ? `Updated by: ${vehicle.updated_by.toUpperCase()} ${latestDate.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}` : '';

            const photoThumbnailsContainer = document.getElementById('photoThumbnailsContainer');
            const pdfThumbnailsContainer = document.getElementById('pdfThumbnailsContainer');
            photoThumbnailsContainer.innerHTML = '';
            pdfThumbnailsContainer.innerHTML = '';
            const files = vehicle.files || [];
            uploadedFileIds = files.map(file => file.id);
            document.getElementById('initialFileIdsInput').value = vehicle.initial_file_ids || '[]';
            const pendingFileIds = JSON.parse(document.getElementById('fileIdsInput').value || '[]');
            uploadedFileIds = [...new Set([...uploadedFileIds, ...pendingFileIds])];
            document.getElementById('fileIdsInput').value = JSON.stringify(uploadedFileIds);
            files.forEach(file => {
                const link = document.createElement('a');
                link.href = `?action=view_file&id=${file.id}`;
                link.target = '_blank';
                link.className = file.file_name.toLowerCase().endsWith('.pdf') ? 'pdfThumbnailLink' : 'photoThumbnailLink';
                const img = document.createElement('img');
                img.src = `?action=view_file&id=${file.id}&thumbnail=1`;
                img.className = file.file_name.toLowerCase().endsWith('.pdf') ? 'pdfThumbnail' : 'photoThumbnail';
                img.onerror = () => {
                    console.error(`Thumbnail failed for ID: ${file.id}`);
                    img.src = file.file_name.toLowerCase().endsWith('.pdf') ? '/images/pdf_placeholder.png' : `?action=view_file&id=${file.id}`;
                };
                link.appendChild(img);
                if (file.file_name.toLowerCase().endsWith('.pdf')) {
                    const nameSpan = document.createElement('span');
                    nameSpan.className = 'thumbnail-name';
                    nameSpan.textContent = file.file_name;
                    link.appendChild(nameSpan);
                }
                if (isAdmin) {
                    const deleteBtn = document.createElement('button');
                    deleteBtn.className = 'delete-btn';
                    deleteBtn.textContent = 'X';
                    deleteBtn.onclick = (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        if (confirm(`Delete "${file.file_name}"?`)) {
                            deleteFile(file.id, link, file.file_name.toLowerCase().endsWith('.pdf') ? 'pdfUploadStatus' : 'photoUploadStatus', file.file_name.toLowerCase().endsWith('.pdf') ? pdfThumbnailsContainer : photoThumbnailsContainer);
                        }
                    };
                    link.appendChild(deleteBtn);
                }
                if (file.file_name.toLowerCase().endsWith('.pdf')) {
                    pdfThumbnailsContainer.appendChild(link);
                } else {
                    photoThumbnailsContainer.appendChild(link);
                }
            });

            showFormModal();
        }

        document.getElementById('addEntryBtn').addEventListener('click', () => {
            console.log('Add Entry clicked');
            hideFormModal();
            showFormModal();
        });

        document.getElementById('cancelForm').addEventListener('click', hideFormModal);

        document.getElementById('fetchVinData').addEventListener('click', async () => {
            const vin = document.getElementById('vinInput').value.toUpperCase();
            try {
                const response = await fetch(`https://vpic.nhtsa.dot.gov/api/vehicles/DecodeVinValues/${vin}?format=json`);
                const data = await response.json();
                if (data.Results && data.Results[0]) {
                    const vehicle = data.Results[0];
                    document.getElementById('yearInput').value = vehicle.ModelYear || '';
                    document.getElementById('makeInput').value = vehicle.Make || '';
                    document.getElementById('modelInput').value = vehicle.Model || '';
                    document.getElementById('vehicleTypeInput').value = vehicle.VehicleType || '';
                    document.getElementById('vehicleBodyInput').value = vehicle.BodyClass || '';
                }
            } catch (error) {
                console.error('VIN Fetch Error:', error);
            }
        });

        document.getElementById('setMv7CurrentDate').addEventListener('click', () => {
            const today = new Date();
            document.getElementById('mv7DateInput').value = `${(today.getMonth() + 1).toString().padStart(2, '0')}-${today.getDate().toString().padStart(2, '0')}-${today.getFullYear()}`;
        });

        document.getElementById('setMv6CurrentDate').addEventListener('click', () => {
            const today = new Date();
            document.getElementById('mv6DateInput').value = `${(today.getMonth() + 1).toString().padStart(2, '0')}-${today.getDate().toString().padStart(2, '0')}-${today.getFullYear()}`;
        });

        document.getElementById('deleteButton').addEventListener('click', () => {
            const id = document.getElementById('idInput').value;
            if (id && confirm('Are you sure you want to delete this vehicle?')) {
                const formData = new FormData();
                formData.append('id', id);
                fetch('?action=delete_vehicle', { method: 'POST', body: formData })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            hideFormModal();
                            location.reload();
                        } else {
                            document.getElementById('pdfUploadStatus').textContent = data.message;
                        }
                    })
                    .catch(() => document.getElementById('pdfUploadStatus').textContent = 'Delete failed');
            }
        });

        document.getElementById('searchInput').addEventListener('input', (e) => {
            console.log('Search input:', e.target.value);
            const searchTerm = e.target.value.toUpperCase();
            const rows = Array.from(document.querySelectorAll('#vehicleTable tbody tr'));
            rows.forEach(row => {
                const text = Array.from(row.cells).map(cell => cell.textContent.toUpperCase()).join(' ');
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
            applyPagination();
        });

        document.getElementById('clearSearchBtn').addEventListener('click', () => {
            console.log('Clear search clicked');
            document.getElementById('searchInput').value = '';
            resetToDefaultSort();
        });

        function formatDateInput(input) {
            let value = input.value.replace(/[^0-9]/g, '');
            if (value.length === 8) {
                const month = value.slice(0, 2);
                const day = value.slice(2, 4);
                const year = value.slice(4, 8);
                input.value = `${month}-${day}-${year}`;
            }
        }

        function sortTable(columnIndex) {
            const table = document.getElementById('vehicleTable');
            const headers = table.querySelectorAll('th');
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.getElementsByTagName('tr'));
            const column = headers[columnIndex].getAttribute('data-sort');
            const isDateColumn = column === 'last_updated_date';
            const isNumericColumn = column === 'year';

            const currentSort = sessionStorage.getItem('sortColumn') === column ? sessionStorage.getItem('sortDirection') : (isDateColumn ? 'desc' : 'asc');
            const direction = currentSort === 'asc' ? 'desc' : 'asc';

            headers.forEach(header => header.querySelector('.sort-indicator').textContent = '');

            rows.sort((a, b) => {
                let aValue = a.cells[columnIndex].textContent.trim();
                let bValue = b.cells[columnIndex].textContent.trim();

                if (isDateColumn) {
                    aValue = new Date(aValue).getTime() || 0;
                    bValue = new Date(bValue).getTime() || 0;
                    return direction === 'desc' ? bValue - aValue : aValue - bValue;
                } else if (isNumericColumn) {
                    aValue = aValue === '' ? -Infinity : parseInt(aValue, 10);
                    bValue = bValue === '' ? -Infinity : parseInt(bValue, 10);
                    return direction === 'asc' ? aValue - bValue : bValue - aValue;
                } else {
                    return direction === 'asc' ? aValue.localeCompare(bValue) : bValue.localeCompare(aValue);
                }
            });

            while (tbody.firstChild) {
                tbody.removeChild(tbody.firstChild);
            }
            rows.forEach(row => tbody.appendChild(row));

            const indicator = headers[columnIndex].querySelector('.sort-indicator');
            indicator.textContent = direction === 'asc' ? '▲' : '▼';

            sessionStorage.setItem('sortColumn', column);
            sessionStorage.setItem('sortDirection', direction);

            const url = new URL(window.location);
            url.searchParams.set('sort', `${column}_${direction}`);
            window.history.pushState({}, '', url);

            applyPagination();
        }

        function resetToDefaultSort() {
            const headers = document.querySelectorAll('#vehicleTable th');
            const defaultColumnIndex = 7;
            sessionStorage.setItem('sortColumn', 'last_updated_date');
            sessionStorage.setItem('sortDirection', 'desc');
            const rows = Array.from(document.querySelectorAll('#vehicleTable tbody').getElementsByTagName('tr'));
            
            rows.sort((a, b) => {
                const aValue = new Date(a.cells[defaultColumnIndex].textContent.trim()).getTime() || 0;
                const bValue = new Date(b.cells[defaultColumnIndex].textContent.trim()).getTime() || 0;
                return bValue - aValue;
            });

            const tbody = document.querySelector('#vehicleTable tbody');
            while (tbody.firstChild) {
                tbody.removeChild(tbody.firstChild);
            }
            rows.forEach(row => {
                row.style.display = '';
                tbody.appendChild(row);
            });

            headers.forEach(header => header.querySelector('.sort-indicator').textContent = '');
            headers[defaultColumnIndex].querySelector('.sort-indicator').textContent = '▼';

            const url = new URL(window.location);
            url.searchParams.set('sort', 'last_updated_date_desc');
            window.history.pushState({}, '', url);

            applyPagination();
        }

        function applyPagination() {
            const rows = Array.from(document.querySelectorAll('#vehicleTable tbody tr'));
            const visibleRows = rows.filter(row => row.style.display !== 'none');
            visibleRows.forEach((row, index) => {
                row.style.display = index < entriesPerPage ? '' : 'none';
            });
        }

        function showAllEntries() {
            const rows = Array.from(document.querySelectorAll('#vehicleTable tbody tr'));
            rows.forEach(row => row.style.display = '');
            document.getElementById('showAllBtn').style.display = 'none';
        }

        document.querySelectorAll('#vehicleTable th').forEach((header, index) => {
            header.addEventListener('click', () => sortTable(index));
        });

        document.getElementById('showAllBtn').addEventListener('click', () => {
            showAllEntries();
        });

        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const sortParam = urlParams.get('sort');
            if (!sortParam || sortParam === 'last_updated_date_desc') {
                resetToDefaultSort();
            } else {
                const [column, direction] = sortParam.split('_');
                const headers = document.querySelectorAll('#vehicleTable th');
                const columnIndex = Array.from(headers).findIndex(h => h.getAttribute('data-sort') === column);
                if (columnIndex !== -1) {
                    sessionStorage.setItem('sortColumn', column);
                    sessionStorage.setItem('sortDirection', direction);
                    sortTable(columnIndex);
                } else {
                    resetToDefaultSort();
                }
            }
        });

        function handleFileUpload(inputId, buttonId, progressId, statusId, thumbnailsContainerId, thumbnailClass, isPhoto, vehicleIdField = null) {
            const input = document.getElementById(inputId);
            const button = document.getElementById(buttonId);

            button.addEventListener('click', () => input.click());

            input.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (!file) return;

                const formData = new FormData();
                formData.append('file_upload', file);
                if (vehicleIdField) {
                    const vehicleId = vehicleIdField === 'currentVehicleId' ? currentVehicleId : document.getElementById(vehicleIdField).value;
                    if (vehicleId) formData.append('vehicle_id', vehicleId);
                }

                const progressContainer = document.getElementById(progressId);
                const fillId = `${progressId}Fill-${Date.now()}`;
                const textId = `${progressId}Text-${Date.now()}`;
                progressContainer.innerHTML = `<div class="progress-container"><p>${file.name}</p><div class="progress-bar"><div class="progress-fill" id="${fillId}"></div></div><p class="progress-text" id="${textId}">0%</p></div>`;
                document.getElementById(statusId).textContent = 'Uploading...';

                const xhr = new XMLHttpRequest();
                xhr.open('POST', '?action=upload_file', true);
                xhr.upload.onprogress = (event) => {
                    if (event.lengthComputable) {
                        const percent = Math.round((event.loaded / event.total) * 100);
                        document.getElementById(fillId).style.width = `${percent}%`;
                        document.getElementById(textId).textContent = `${percent}%`;
                    }
                };
                xhr.onload = () => {
                    if (xhr.status === 200) {
                        const response = JSON.parse(xhr.responseText);
                        document.getElementById(statusId).textContent = response.message;
                        if (response.success) {
                            uploadedFileIds.push(response.file_id);
                            document.getElementById('fileIdsInput').value = JSON.stringify(uploadedFileIds);
                            const thumbnailsContainer = document.getElementById(thumbnailsContainerId);
                            const link = document.createElement('a');
                            link.href = `?action=view_file&id=${response.file_id}`;
                            link.target = '_blank';
                            link.className = isPhoto ? 'photoThumbnailLink' : 'pdfThumbnailLink';
                            const img = document.createElement('img');
                            img.src = `?action=view_file&id=${response.file_id}&thumbnail=1`;
                            img.className = isPhoto ? 'photoThumbnail' : 'pdfThumbnail';
                            img.onerror = () => {
                                console.error(`Thumbnail failed for ID: ${response.file_id}`);
                                img.src = isPhoto ? `?action=view_file&id=${response.file_id}` : '/images/pdf_placeholder.png';
                            };
                            link.appendChild(img);
                            if (!isPhoto) {
                                const nameSpan = document.createElement('span');
                                nameSpan.className = 'thumbnail-name';
                                nameSpan.textContent = response.file_name;
                                link.appendChild(nameSpan);
                            }
                            if (isAdmin) {
                                const deleteBtn = document.createElement('button');
                                deleteBtn.className = 'delete-btn';
                                deleteBtn.textContent = 'X';
                                deleteBtn.onclick = (e) => {
                                    e.preventDefault();
                                    e.stopPropagation();
                                    if (confirm(`Delete "${response.file_name}"?`)) {
                                        deleteFile(response.file_id, link, statusId, thumbnailsContainer);
                                    }
                                };
                                link.appendChild(deleteBtn);
                            }
                            thumbnailsContainer.appendChild(link);
                        }
                    } else {
                        document.getElementById(statusId).textContent = 'Upload failed: Server error';
                        console.error("Upload failed with status: " + xhr.status + " - " + xhr.statusText);
                    }
                    input.value = '';
                };
                xhr.onerror = () => {
                    document.getElementById(statusId).textContent = 'Upload failed: Network error';
                    console.error("Upload network error");
                };
                xhr.send(formData);
            });
        }

        function deleteFile(fileId, thumbnailElement, statusId, thumbnailsContainer) {
            const formData = new FormData();
            formData.append('file_id', fileId);
            fetch('?action=delete_file', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    document.getElementById(statusId).textContent = data.message;
                    if (data.success) {
                        uploadedFileIds = uploadedFileIds.filter(id => id !== fileId);
                        document.getElementById('fileIdsInput').value = JSON.stringify(uploadedFileIds);
                        thumbnailElement.remove();
                    }
                })
                .catch(() => document.getElementById(statusId).textContent = 'Delete failed');
        }

        handleFileUpload('photoUploadInput', 'photoUploadBtn', 'photoUploadProgress', 'photoUploadStatus', 'photoThumbnailsContainer', 'photoThumbnail', true, 'idInput');
        handleFileUpload('fileUploadInput', 'pdfUploadBtn', 'pdfUploadProgress', 'pdfUploadStatus', 'pdfThumbnailsContainer', 'pdfThumbnail', false, 'idInput');
        handleFileUpload('filesPhotoUploadInput', 'filesPhotoUploadBtn', 'filesPhotoUploadProgress', 'filesPhotoUploadStatus', 'filesPhotoThumbnailsContainer', 'photoThumbnail', true, 'currentVehicleId');
        handleFileUpload('filesPdfUploadInput', 'filesPdfUploadBtn', 'filesPdfUploadProgress', 'filesPdfUploadStatus', 'filesPdfThumbnailsContainer', 'pdfThumbnail', false, 'currentVehicleId');

        document.getElementById('saveVehicleBtn').addEventListener('click', (e) => {
            e.preventDefault();
            const formData = new FormData(form);
            formData.set('file_ids', document.getElementById('fileIdsInput').value);
            formData.set('initial_file_ids', document.getElementById('initialFileIdsInput').value);
            fetch('?action=save_vehicle', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    document.getElementById('pdfUploadStatus').textContent = data.message;
                    if (data.success) {
                        hideFormModal();
                        location.reload();
                    }
                })
                .catch(error => {
                    console.error('Save failed:', error);
                    document.getElementById('pdfUploadStatus').textContent = 'Save failed';
                });
        });
    </script>
</body>
</html>
<?php ob_end_flush(); ?>