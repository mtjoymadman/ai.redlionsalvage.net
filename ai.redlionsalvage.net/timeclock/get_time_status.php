<?php
require_once '../config.php';
header('Content-Type: application/json');

$stmt = $db->prepare("SELECT clock_in, clock_out FROM time_logs WHERE employee_id = ? AND clock_out IS NULL ORDER BY clock_in DESC LIMIT 1");
$stmt->bind_param("i", $_SESSION['employee_id']);
$stmt->execute();
$result = $stmt->get_result();
$log = $result->fetch_assoc();

echo json_encode(['status' => $log ? "Clocked in since {$log['clock_in']}" : "Clocked out"]);
?>
/api/timeclock/start_break.php:
php
<?php
require_once '../config.php';
$stmt = $db->prepare("INSERT INTO breaks (time_log_id, start_time, duration) SELECT id, NOW(), 0 FROM time_logs WHERE employee_id = ? AND clock_out IS NULL ORDER BY clock_in DESC LIMIT 1");
$stmt->bind_param("i", $_SESSION['employee_id']);
$stmt->execute();
?>
/api/timeclock/end_break.php:
php
<?php
require_once '../config.php';
$stmt = $db->prepare("UPDATE breaks SET duration = TIMESTAMPDIFF(MINUTE, start_time, NOW()) WHERE time_log_id = (SELECT id FROM time_logs WHERE employee_id = ? AND clock_out IS NULL ORDER BY clock_in DESC LIMIT 1) AND duration = 0");
$stmt->bind_param("i", $_SESSION['employee_id']);
$stmt->execute();
?>
/api/timeclock/add_extra_time.php:
php
<?php
require_once '../config.php';
$extra_time = $_POST['extra_time'] ?? 0;
$stmt = $db->prepare("UPDATE time_logs SET extra_time = ? WHERE employee_id = ? AND clock_out IS NULL ORDER BY clock_in DESC LIMIT 1");
$stmt->bind_param("ii", $extra_time, $_SESSION['employee_id']);
$stmt->execute();
?>
/api/timeclock/get_extra_time.php:
php
<?php
require_once '../config.php';
header('Content-Type: application/json');
$stmt = $db->prepare("SELECT DATE(clock_in) AS date, extra_time FROM time_logs WHERE employee_id = ? AND extra_time > 0");
$stmt->bind_param("i", $_SESSION['employee_id']);
$stmt->execute();
$result = $stmt->get_result();
$extra_times = [];
while ($row = $result->fetch_assoc()) {
    $extra_times[] = $row;
}
echo json_encode($extra_times);
?>
/api/timeclock/get_yardman_stats.php:
php
<?php
require_once '../config.php';
header('Content-Type: application/json');
$stmt = $db->prepare("SELECT e.username, DATE(vs.first_scan_at) AS date, COUNT(vs.id) AS vehicles_processed, AVG(TIMESTAMPDIFF(MINUTE, vs.first_scan_at, vs.strip_confirmed_at)) AS avg_time FROM vehicle_stripping vs JOIN employees e ON vs.yardman_id = e.id WHERE vs.strip_confirmed_at IS NOT NULL AND DATE(vs.first_scan_at) = CURDATE() GROUP BY e.username");
$stmt->execute();
$result = $stmt->get_result();
$stats = [];
while ($row = $result->fetch_assoc()) {
    $stats[] = $row;
}
echo json_encode($stats);
?>
/api/timeclock/get_employee_status.php:
php
<?php
require_once '../config.php';
header('Content-Type: application/json');
$result = $db->query("SELECT id, username, full_name, suspended, last_status_change FROM employees ORDER BY username");
$employees = [];
while ($row = $result->fetch_assoc()) {
    $employees[] = $row;
}
echo json_encode($employees);
?>
/api/employee_management/suspend_employee.php:
php
<?php
require_once '../config.php';
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
$employee_id = $data['employee_id'] ?? 0;
$suspend = $data['suspend'] ?? false;

if (in_array('admin', json_decode(file_get_contents("http://{$_SERVER['HTTP_HOST']}/api/get_user_roles.php"), true))) {
    $stmt = $db->prepare("UPDATE employees SET suspended = ?, last_status_change = NOW() WHERE id = ?");
    $stmt->bind_param("ii", $suspend, $employee_id);
    $stmt->execute();
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
}
?>
Vehicle Pickup: /frontend/pickup.php
php
<?php
require_once '../api/config.php';
if (!isset($_SESSION['employee_id']) || !in_array('driver', json_decode(file_get_contents("http://{$_SERVER['HTTP_HOST']}/api/get_user_roles.php"), true))) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Vehicle Pickup</title>
    <link rel="stylesheet" href="/frontend/style.css">
</head>
<body>
    <div class="container">
        <h1>Vehicle Pickup</h1>
        <form id="pickupForm" class="vehicle-form" method="POST" enctype="multipart/form-data">
            <input type="text" name="stock_number" placeholder="Stock Number" required>
            <input type="text" name="vin" placeholder="VIN" required>
            <input type="text" name="make" placeholder="Make">
            <input type="text" name="model" placeholder="Model">
            <input type="number" name="year" placeholder="Year">
            <input type="text" name="condition" placeholder="Condition">
            <input type="number" name="weight" placeholder="Weight (lbs)">
            <input type="file" name="photo" accept="image/*">
            <select name="pickup_truck_id" required>
                <?php
                $result = $db->query("SELECT id, vin FROM fleet_vehicles");
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='{$row['id']}'>Truck {$row['id']} ({$row['vin']})</option>";
                }
                ?>
            </select>
            <button type="submit" class="button">Submit Pickup</button>
        </form>
        <div id="message" class="error"></div>
    </div>
    <script>
        document.getElementById('pickupForm').addEventListener('submit', function(e) {
            e.preventDefault();
            fetch('/api/vehicle_pickup/submit_pending.php', {
                method: 'POST',
                body: new FormData(this)
            }).then(response => response.json()).then(data => {
                document.getElementById('message').textContent = data.message;
            });
        });
    </script>
</body>
</html>
Vehicle Pickup API: /api/vehicle_pickup/submit_pending.php
php
<?php
require_once '../config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array('driver', json_decode(file_get_contents("http://{$_SERVER['HTTP_HOST']}/api/get_user_roles.php"), true))) {
    $stock_number = $_POST['stock_number'] ?? '';
    $vin = $_POST['vin'] ?? '';
    $make = $_POST['make'] ?? '';
    $model = $_POST['model'] ?? '';
    $year = $_POST['year'] ?? '';
    $condition = $_POST['condition'] ?? '';
    $weight = $_POST['weight'] ?? '';
    $pickup_truck_id = $_POST['pickup_truck_id'] ?? '';
    $photo = $_FILES['photo']['name'] ?? '';
    $photo_path = $photo ? "/assets/uploads/{$vin}_" . time() . "_" . $photo : null;

    if ($photo) {
        move_uploaded_file($_FILES['photo']['tmp_name'], $_SERVER['DOCUMENT_ROOT'] . $photo_path);
    }

    $stmt = $db->prepare("INSERT INTO pending_vehicles (stock_number, vin, make, model, year, condition, weight, photo, submitted_by, pickup_truck_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssissisi", $stock_number, $vin, $make, $model, $year, $condition, $weight, $photo_path, $_SESSION['employee_id'], $pickup_truck_id);
    $stmt->execute();

    // VPIC Lookup (simplified)
    $vpic_data = file_get_contents("https://vpic.nhtsa.dot.gov/api/vehicles/DecodeVin/{$vin}?format=json");
    $vpic = json_decode($vpic_data, true);
    if ($vpic && !$make) {
        $stmt = $db->prepare("UPDATE pending_vehicles SET make = ?, model = ?, year = ? WHERE vin = ?");
        $stmt->bind_param("ssis", $vpic['Results'][5]['Value'], $vpic['Results'][7]['Value'], $vpic['Results'][9]['Value'], $vin);
        $stmt->execute();
    }

    echo json_encode(['success' => true, 'message' => 'Vehicle submitted for approval']);
} else {
    echo json_encode(['success' => false, 'message' => 'Unauthorized or invalid request']);
}
?>
Pending Vehicles: /frontend/pending_vehicles.php
php
<?php
require_once '../api/config.php';
if (!isset($_SESSION['employee_id']) || !in_array('admin', json_decode(file_get_contents("http://{$_SERVER['HTTP_HOST']}/api/get_user_roles.php"), true))) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Pending Vehicles</title>
    <link rel="stylesheet" href="/frontend/style.css">
</head>
<body>
    <div class="container">
        <h1>Pending Vehicles</h1>
        <table id="pendingTable" class="timeclock-table">
            <tr><th>VIN</th><th>Make</th><th>Model</th><th>Year</th><th>Condition</th><th>Weight</th><th>Submitted By</th><th>Truck</th><th>Action</th></tr>
        </table>
        <div id="message" class="error"></div>
    </div>
    <script>
        function updatePendingTable() {
            fetch('/api/vehicle_pickup/get_pending_vehicles.php').then(response => response.json()).then(data => {
                const table = document.getElementById('pendingTable');
                table.innerHTML = '<tr><th>VIN</th><th>Make</th><th>Model</th><th>Year</th><th>Condition</th><th>Weight</th><th>Submitted By</th><th>Truck</th><th>Action</th></tr>';
                data.forEach(vehicle => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `<td>${vehicle.vin}</td><td>${vehicle.make}</td><td>${vehicle.model}</td><td>${vehicle.year}</td><td>${vehicle.condition}</td><td>${vehicle.weight}</td><td>${vehicle.submitted_by}</td><td>${vehicle.pickup_truck_id}</td><td><button class="button" onclick="processVehicle(${vehicle.id}, 'approve')">Process</button><button class="button" onclick="processVehicle(${vehicle.id}, 'reject')">Reject</button></td>`;
                    table.appendChild(tr);
                });
            });
        }
        function processVehicle(id, action) {
            fetch('/api/vehicle_pickup/process_pending.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, action })
            }).then(response => response.json()).then(data => {
                document.getElementById('message').textContent = data.message;
                updatePendingTable();
            });
        }
        updatePendingTable();
    </script>
</body>
</html>
Pending Vehicles APIs
/api/vehicle_pickup/get_pending_vehicles.php:
php
<?php
require_once '../config.php';
header('Content-Type: application/json');
$result = $db->query("SELECT pv.id, pv.vin, pv.make, pv.model, pv.year, pv.condition, pv.weight, e.username AS submitted_by, pv.pickup_truck_id FROM pending_vehicles pv JOIN employees e ON pv.submitted_by = e.id");
$vehicles = [];
while ($row = $result->fetch_assoc()) {
    $vehicles[] = $row;
}
echo json_encode($vehicles);
?>
/api/vehicle_pickup/process_pending.php:
php
<?php
require_once '../config.php';
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? 0;
$action = $data['action'] ?? '';

if (in_array('admin', json_decode(file_get_contents("http://{$_SERVER['HTTP_HOST']}/api/get_user_roles.php"), true))) {
    if ($action === 'approve') {
        $stmt = $db->prepare("INSERT INTO vehicles (stock_number, vin, make, model, year) SELECT stock_number, vin, make, model, year FROM pending_vehicles WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt = $db->prepare("DELETE FROM pending_vehicles WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        // Simplified NMVTIS submission placeholder
        echo json_encode(['success' => true, 'message' => 'Vehicle approved and added to inventory']);
    } else {
        $stmt = $db->prepare("DELETE FROM pending_vehicles WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo json_encode(['success' => true, 'message' => 'Vehicle rejected']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
}
?>
Inventory & POS: /frontend/pos.php
php
<?php
require_once '../api/config.php';
if (!isset($_SESSION['employee_id']) || !in_array('office', json_decode(file_get_contents("http://{$_SERVER['HTTP_HOST']}/api/get_user_roles.php"), true))) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Inventory & POS</title>
    <link rel="stylesheet" href="/frontend/style.css">
</head>
<body>
    <div class="container">
        <h1>Inventory & POS</h1>
        <input type="text" id="search" placeholder="Search Parts">
        <div id="search-results"></div>
        <div id="cart">
            <h2>Cart</h2>
            <table id="cart-table" class="timeclock-table">
                <tr><th>Part</th><th>Quantity</th><th>Price</th><th>Action</th></tr>
            </table>
            <button id="checkout" class="button">Checkout</button>
        </div>
        <div id="message" class="error"></div>
    </div>
    <script>
        let cart = [];
        document.getElementById('search').addEventListener('input', function() {
            fetch('/api/inventory/get_parts.php?query=' + this.value).then(response => response.json()).then(data => {
                const results = document.getElementById('search-results');
                results.innerHTML = '';
                data.forEach(part => {
                    const div = document.createElement('div');
                    div.innerHTML = `${part.name} - $${part.price} <button onclick="addToCart(${part.id}, '${part.name}', ${part.price})">Add</button>`;
                    results.appendChild(div);
                });
            });
        });
        function addToCart(id, name, price) {
            const existing = cart.find(item => item.id === id);
            if (existing) {
                existing.quantity++;
            } else {
                cart.push({ id, name, price, quantity: 1 });
            }
            updateCart();
        }
        function updateCart() {
            const table = document.getElementById('cart-table');
            table.innerHTML = '<tr><th>Part</th><th>Quantity</th><th>Price</th><th>Action</th></tr>';
            cart.forEach(item => {
                const tr = document.createElement('tr');
                tr.innerHTML = `<td>${item.name}</td><td>${item.quantity}</td><td>$${item.price * item.quantity}</td><td><button onclick="removeFromCart(${item.id})">Remove</button></td>`;
                table.appendChild(tr);
            });
        }
        function removeFromCart(id) {
            cart = cart.filter(item => item.id !== id);
            updateCart();
        }
        document.getElementById('checkout').addEventListener('click', function() {
            fetch('/api/pos/record_sale.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ items: cart })
            }).then(response => response.json()).then(data => {
                document.getElementById('message').textContent = data.message;
                if (data.success) cart = [];
                updateCart();
            });
        });
    </script>
</body>
</html>
POS APIs
/api/inventory/get_parts.php:
php
<?php
require_once '../config.php';
header('Content-Type: application/json');
$query = "%" . ($_GET['query'] ?? '') . "%";
$stmt = $db->prepare("SELECT p.id, p.name, p.price FROM parts p JOIN vehicle_parts vp ON p.id = vp.part_id WHERE p.name LIKE ? AND vp.quantity > 0");
$stmt->bind_param("s", $query);
$stmt->execute();
$result = $stmt->get_result();
$parts = [];
while ($row = $result->fetch_assoc()) {
    $parts[] = $row;
}
echo json_encode($parts);
?>
/api/pos/record_sale.php:
php
<?php
require_once '../config.php';
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
$items = $data['items'] ?? [];

if (in_array('office', json_decode(file_get_contents("http://{$_SERVER['HTTP_HOST']}/api/get_user_roles.php"), true))) {
    $total = 0;
    foreach ($items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    $stmt = $db->prepare("INSERT INTO sales (employee_id, total_amount) VALUES (?, ?)");
    $stmt->bind_param("id", $_SESSION['employee_id'], $total);
    $stmt->execute();
    $sale_id = $db->insert_id;

    foreach ($items as $item) {
        $stmt = $db->prepare("INSERT INTO sale_items (sale_id, part_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiid", $sale_id, $item['id'], $item['quantity'], $item['price']);
        $stmt->execute();
        $stmt = $db->prepare("INSERT INTO parts (id, name, price) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), price = VALUES(price)");
        $stmt->bind_param("isd", $item['id'], $item['name'], $item['price']);
        $stmt->execute();
    }

    // Simplified printer/drawer logic
    file_get_contents("http://{$_SERVER['HTTP_HOST']}/api/pos/print_receipt.php?sale_id={$sale_id}");
    file_get_contents("http://{$_SERVER['HTTP_HOST']}/api/pos/open_drawer.php");

    echo json_encode(['success' => true, 'message' => 'Sale recorded']);
} else {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
}
?>
/api/pos/print_receipt.php (Placeholder):
php
<?php
require_once '../config.php';
// Placeholder for Epson TM-T20III printing logic
$sale_id = $_GET['sale_id'] ?? 0;
echo "Receipt printed for Sale ID: $sale_id"; // Replace with actual ESC/POS commands
?>
/api/pos/open_drawer.php (Placeholder):
php
<?php
require_once '../config.php';
// Placeholder for cash drawer logic (e.g., ESC/POS command via printer)
echo "Cash drawer opened";
?>
Fleet Tracking: /frontend/fleet.php
php
<?php
require_once '../api/config.php';
if (!isset($_SESSION['employee_id']) || !in_array('fleet', json_decode(file_get_contents("http://{$_SERVER['HTTP_HOST']}/api/get_user_roles.php"), true))) {
    header('Location: dashboard.php');
    exit;
}
$roles = json_decode(file_get_contents("http://{$_SERVER['HTTP_HOST']}/api/get_user_roles.php"), true);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fleet Management</title>
    <link rel="stylesheet" href="/frontend/style.css">
</head>
<body>
    <div class="container">
        <h1>Fleet Management</h1>
        <table id="fleetTable" class="timeclock-table">
            <tr><th>ID</th><th>Make</th><th>Model</th><th>Year</th><th>License Plate</th><th>VIN</th><th>Assigned Driver</th><th>Scales Installed</th><th>Current Status</th><th>Mileage (miles)</th><th>Maintenance Due</th><th>Documents Expiring</th><th>Actions</th></tr>
        </table>
        <form id="addVehicleForm" class="vehicle-form">
            <input type="text" name="make" placeholder="Make" required>
            <input type="text" name="model" placeholder="Model" required>
            <input type="number" name="year" placeholder="Year" required>
            <input type="text" name="license_plate" placeholder="License Plate">
            <input type="text" name="vin" placeholder="VIN" required>
            <input type="checkbox" id="has_scales" name="has_scales"> Has Scales Installed
            <input type="number" name="mileage" placeholder="Current Mileage (miles)" required>
            <button type="submit" class="button">Add Vehicle</button>
        </form>
        <div id="outboundScrap">
            <h2>Outbound Scrap</h2>
            <form id="outboundScrapForm" class="vehicle-form">
                <select id="truck_id" name="truck_id" required></select>
                <select id="metal_type" name="metal_type" required>
                    <?php
                    $result = $db->query("SELECT metal_type FROM scrap_metal_types");
                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='{$row['metal_type']}'>{$row['metal_type']}</option>";
                    }
                    ?>
                </select>
                <input type="number" id="weight" name="weight" readonly>
                <button type="submit" class="button">Record Outbound</button>
            </form>
            <table id="outboundScrapHistory" class="timeclock-table"></table>
        </div>
        <div id="truckWeightDisplay" class="timeclock-table"></div>
        <div id="receiptUploadForm" class="vehicle-form modal" style="display:none;">
            <select id="receipt_truck_id" name="truck_id" required></select>
            <input type="file" id="receipt_file" name="receipt_file" accept="image/*,application/pdf" required>
            <select id="service_type" name="service_type" required>
                <option value="Parts">Parts</option>
                <option value="Service">Service</option>
                <option value="Parts & Service">Parts & Service</option>
                <option value="Supplies">Supplies</option>
            </select>
            <input type="date" id="service_date" name="service_date" required>
            <input type="checkbox" id="needs_reimbursement" name="needs_reimbursement"> Needs Reimbursement
            <textarea id="receipt_notes" name="notes" placeholder="Service Notes"></textarea>
            <div id="manualAmountDiv" style="display:none;">
                <input type="number" id="manual_amount" name="manual_amount" step="0.01" placeholder="Enter Amount ($)" required>
                <button type="button" id="submitManualAmount" class="button">Submit Manual Amount</button>
            </div>
            <button type="submit" id="uploadReceiptButton" class="button">Upload Receipt</button>
        </div>
        <?php if (in_array('office', $roles) || in_array('admin', $roles) || in_array('baby admin', $roles)): ?>
            <div id="documentUploadForm" class="vehicle-form modal" style="display:none;">
                <select id="doc_truck_id" name="truck_id" required></select>
                <input type="file" id="document_file" name="document_file" accept="image/*,application/pdf" required>
                <select id="document_type" name="document_type" required>
                    <option value="Registration">Registration</option>
                    <option value="Insurance">Insurance</option>
                    <option value="Title">Title</option>
                    <option value="Other">Other</option>
                </select>
                <input type="date" id="expiration_date" name="expiration_date" required>
                <textarea id="doc_notes" name="notes" placeholder="Document Notes"></textarea>
                <button type="submit" class="button">Submit for Approval</button>
            </div>
        <?php endif; ?>
        <?php if (in_array('admin', $roles) || in_array('baby admin', $roles)): ?>
            <div id="approvalQueue" class="timeclock-table"></div>
        <?php endif; ?>
        <div id="driverHistoryModal" class="container modal" style="display:none;"></div>
        <div id="message" class="error"></div>
    </div>
    <script>
        function updateFleetTable() {
            fetch('/api/fleet/get_fleet_vehicles.php').then(response => response.json()).then(data => {
                const table = document.getElementById('fleetTable');
                table.innerHTML = '<tr><th>ID</th><th>Make</th><th>Model</th><th>Year</th><th>License Plate</th><th>VIN</th><th>Assigned Driver</th><th>Scales Installed</th><th>Current Status</th><th>Mileage (miles)</th><th>Maintenance Due</th><th>Documents Expiring</th><th>Actions</th></tr>';
                data.forEach(vehicle => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `<td>${vehicle.id}</td><td>${vehicle.make}</td><td>${vehicle.model}</td><td>${vehicle.year}</td><td>${vehicle.license_plate}</td><td>${vehicle.vin}</td><td>${vehicle.assigned_driver || ''}</td><td>${vehicle.has_scales ? '✔' : ''}</td><td>${vehicle.current_status}</td><td><input type="number" value="${vehicle.mileage}" onchange="updateMileage(${vehicle.id}, this.value)"></td><td>${vehicle.maintenance_due || ''}</td><td class="${vehicle.documents_expiring > 0 ? 'alert-red' : ''}">${vehicle.documents_expiring || 0}</td><td><button onclick="showModal('assignDriverForm', ${vehicle.id})">Assign Driver</button><button onclick="viewHistory(${vehicle.id})">View History</button><?php if (in_array('admin', $roles) || in_array('baby admin', $roles)): ?><button onclick="showModal('maintenanceForm', ${vehicle.id})">Schedule Maintenance</button><?php endif; ?><button onclick="showModal('receiptUploadForm', ${vehicle.id})">Upload Receipt</button><?php if (in_array('office', $roles) || in_array('admin', $roles) || in_array('baby admin', $roles)): ?><button onclick="showModal('documentUploadForm', ${vehicle.id})">Upload Document</button><?php endif; ?></td>`;
                    table.appendChild(tr);
                });
            });
        }
        function updateMileage(id, mileage) {
            fetch('/api/fleet/update_mileage.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, mileage })
            });
        }
        function showModal(formId, truckId) {
            const modal = document.getElementById(formId);
            modal.style.display = 'block';
            modal.querySelector('[name="truck_id"]').value = truckId;
        }
        function viewHistory(truckId) {
            fetch(`/api/fleet/get_driver_history.php?truck_id=${truckId}`).then(response => response.json()).then(data => {
                const modal = document.getElementById('driverHistoryModal');
                modal.innerHTML = '<h2>Driver History</h2><table><tr><th>Date Assigned</th><th>Driver</th><th>Date Unassigned</th></tr>' + data.map(row => `<tr><td>${row.date_assigned}</td><td>${row.username}</td><td>${row.unassigned_at || ''}</td></tr>`).join('') + '</table>';
                modal.style.display = 'block';
            });
        }
        document.getElementById('addVehicleForm').addEventListener('submit', function(e) {
            e.preventDefault();
            fetch('/api/fleet/add_vehicle.php', {
                method: 'POST',
                body: new FormData(this)
            }).then(response => response.json()).then(data => {
                document.getElementById('message').textContent = data.message;
                updateFleetTable();
            });
        });
        document.getElementById('outboundScrapForm').addEventListener('submit', function(e) {
            e.preventDefault();
            fetch('/api/scrap/record_outbound.php', {
                method: 'POST',
                body: new FormData(this)
            }).then(response => response.json()).then(data => {
                document.getElementById('message').textContent = data.message;
                updateScrapHistory();
            });
        });
        function updateScrapHistory() {
            fetch('/api/scrap/get_outbound_history.php').then(response => response.json()).then(data => {
                const table = document.getElementById('outboundScrapHistory');
                table.innerHTML = '<tr><th>Truck ID</th><th>Metal Type</th><th>Weight (lbs)</th><th>Date</th></tr>';
                data.forEach(row => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `<td>${row.truck_id}</td><td>${row.metal_type}</td><td>${row.weight}</td><td>${row.transaction_date}</td>`;
                    table.appendChild(tr);
                });
            });
        }
        function updateTruckWeights() {
            fetch('/api/fleet/get_truck_weights.php').then(response => response.json()).then(data => {
                const table = document.getElementById('truckWeightDisplay');
                table.innerHTML = '<tr><th>Truck ID</th><th>Current Weight (lbs)</th><th>Last Weighed</th><th>Status</th></tr>';
                data.forEach(row => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `<td>${row.id}</td><td>${row.current_weight}</td><td>${row.last_weighed_at}</td><td>${row.status}</td>`;
                    table.appendChild(tr);
                });
                const truckSelects = ['truck_id', 'receipt_truck_id', 'doc_truck_id'];
                truckSelects.forEach(select => {
                    const sel = document.getElementById(select);
                    sel.innerHTML = '<option value="">Select Truck</option>' + data.map(row => `<option value="${row.id}">Truck ${row.id} (${row.vin})</option>`).join('');
                });
            });
        }
        document.getElementById('receiptUploadForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('/api/fleet/upload_receipt.php', {
                method: 'POST',
                body: formData
            }).then(response => response.json()).then(data => {
                if (data.success) {
                    document.getElementById('message').textContent = data.message;
                    this.style.display = 'none';
                    updateApprovalQueue();
                } else if (data.message === 'OCR failed') {
                    document.getElementById('manualAmountDiv').style.display = 'block';
                    document.getElementById('uploadReceiptButton').style.display = 'none';
                } else {
                    document.getElementById('message').textContent = data.message;
                }
            });
        });
        document.getElementById('submitManualAmount').addEventListener('click', function() {
            const formData = new FormData(document.getElementById('receiptUploadForm'));
            fetch('/api/fleet/upload_receipt.php', {
                method: 'POST',
                body: formData
            }).then(response => response.json()).then(data => {
                document.getElementById('message').textContent = data.message;
                document.getElementById('receiptUploadForm').style.display = 'none';
                updateApprovalQueue();
            });
        });
        document.getElementById('documentUploadForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            fetch('/api/fleet/upload_document.php', {
                method: 'POST',
                body: new FormData(this)
            }).then(response => response.json()).then(data => {
                document.getElementById('message').textContent = data.message;
                this.style.display = 'none';
                updateApprovalQueue();
                updateFleetTable();
            });
        });
        function updateApprovalQueue() {
            fetch('/api/fleet/get_pending_items.php').then(response => response.json()).then(data => {
                const table = document.getElementById('approvalQueue');
                table.innerHTML = '<tr><th>Truck ID</th><th>VIN</th><th>Type</th><th>Uploaded By</th><th>Uploaded At</th><th>File</th><th>Needs Reimbursement</th><th>Action</th></tr>';
                data.forEach(item => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `<td>${item.truck_id}</td><td>${item.vin}</td><td>${item.type}</td><td>${item.uploaded_by}</td><td>${item.uploaded_at}</td><td><a href="${item.file_path}" target="_blank">View</a></td><td>${item.needs_reimbursement || 'No'}</td><td><button onclick="approveItem('${item.type}', ${item.id})">Approve</button><button onclick="rejectItem('${item.type}', ${item.id})">Reject</button></td>`;
                    table.appendChild(tr);
                });
            });
        }
        function approveItem(type, id) {
            fetch('/api/fleet/approve_item.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ type, id })
            }).then(() => { updateApprovalQueue(); updateFleetTable(); });
        }
        function rejectItem(type, id) {
            fetch('/api/fleet/reject_item.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ type, id })
            }).then(() => updateApprovalQueue());
        }
        setInterval(updateFleetTable, 30000);
        setInterval(updateTruckWeights, 5000);
        updateFleetTable();
        updateTruckWeights();
        <?php if (in_array('admin', $roles) || in_array('baby admin', $roles)): ?>
            setInterval(updateApprovalQueue, 30000);
            updateApprovalQueue();
        <?php endif; ?>
    </script>
</body>
</html>
Fleet APIs
/api/fleet/get_fleet_vehicles.php:
php
<?php
require_once '../config.php';
header('Content-Type: application/json');
$result = $db->query("SELECT fv.*, e.username AS assigned_driver, IF(fv.last_weighed_at > NOW() - INTERVAL 1 HOUR, 'In Use', IF(fv.maintenance_due <= NOW(), 'Maintenance', 'Idle')) AS current_status, (SELECT COUNT(*) FROM fleet_vehicle_documents fvd WHERE fvd.truck_id = fv.id AND fvd.status = 'approved' AND fvd.expiration_date <= NOW() + INTERVAL 30 DAY) AS documents_expiring FROM fleet_vehicles fv LEFT JOIN fleet_vehicle_assignments fva ON fv.id = fva.vehicle_id AND fva.unassigned_at IS NULL LEFT JOIN employees e ON fva.driver_id = e.id");
$vehicles = [];
while ($row = $result->fetch_assoc()) {
    $vehicles[] = $row;
}
echo json_encode($vehicles);
?>
/api/fleet/add_vehicle.php:
php
<?php
require_once '../config.php';
header('Content-Type: application/json');
$make = $_POST['make'] ?? '';
$model = $_POST['model'] ?? '';
$year = $_POST['year'] ?? '';
$license_plate = $_POST['license_plate'] ?? '';
$vin = $_POST['vin'] ?? '';
$has_scales = isset($_POST['has_scales']) ? 1 : 0;
$mileage = $_POST['mileage'] ?? 0;

$stmt = $db->prepare("INSERT INTO fleet_vehicles (make, model, year, license_plate, vin, has_scales, mileage) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssisssi", $make, $model, $year, $license_plate, $vin, $has_scales, $mileage);
$stmt->execute();

echo json_encode(['success' => true, 'message' => 'Vehicle added successfully']);
?>
/api/fleet/update_mileage.php:
php
<?php
require_once '../config.php';
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? 0;
$mileage = $data['mileage'] ?? 0;

$stmt = $db->prepare("UPDATE fleet_vehicles SET mileage = ? WHERE id = ?");
$stmt->bind_param("ii", $mileage, $id);
$stmt->execute();

echo json_encode(['success' => true]);
?>
/api/fleet/get_driver_history.php:
php
<?php
require_once '../config.php';
header('Content-Type: application/json');
$truck_id = $_GET['truck_id'] ?? 0;
$stmt = $db->prepare("SELECT fva.date_assigned, e.username, fva.unassigned_at FROM fleet_vehicle_assignments fva JOIN employees e ON fva.driver_id = e.id WHERE fva.vehicle_id = ? ORDER BY fva.date_assigned DESC");
$stmt->bind_param("i", $truck_id);
$stmt->execute();
$result = $stmt->get_result();
$history = [];
while ($row = $result->fetch_assoc()) {
    $history[] = $row;
}
echo json_encode($history);
?>
/api/fleet/upload_receipt.php:
php
<?php
require_once '../config.php';
header('Content-Type: application/json');
$truck_id = $_POST['truck_id'] ?? '';
$service_type = $_POST['service_type'] ?? '';
$service_date = $_POST['service_date'] ?? '';
$needs_reimbursement = isset($_POST['needs_reimbursement']) ? 1 : 0;
$notes = $_POST['notes'] ?? '';
$receipt_file = $_FILES['receipt_file']['name'] ?? '';
$receipt_path = $receipt_file ? "/assets/receipts/{$truck_id}_" . time() . "_" . $receipt_file : null;

if ($receipt_path) {
    move_uploaded_file($_FILES['receipt_file']['tmp_name'], $_SERVER['DOCUMENT_ROOT'] . $receipt_path);
    $amount = null;
    $ocr_result = json_decode(shell_exec("python3 /api/fleet/ocr_receipt.py " . escapeshellarg($_SERVER['DOCUMENT_ROOT'] . $receipt_path)), true);
    $amount = $ocr_result['amount'] ?? null;

    if ($amount === null && !isset($_POST['manual_amount'])) {
        echo json_encode(['success' => false, 'message' => 'OCR failed']);
        exit;
    } elseif (isset($_POST['manual_amount'])) {
        $amount = floatval($_POST['manual_amount']);
    }

    $stmt = $db->prepare("INSERT INTO fleet_maintenance_receipts (truck_id, receipt_path, service_type, service_date, amount, needs_reimbursement, notes, uploaded_by, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("isssdisi", $truck_id, $receipt_path, $service_type, $service_date, $amount, $needs_reimbursement, $notes, $_SESSION['employee_id']);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => "Receipt submitted for Truck {$truck_id}, cost $$amount " . (isset($_POST['manual_amount']) ? 'added manually' : 'extracted') . "—awaiting approval"]);
} else {
    echo json_encode(['success' => false, 'message' => 'File upload failed']);
}
?>
/api/fleet/ocr_receipt.py:
python
import pytesseract
from PIL import Image
import sys
import json
import re

image_path = sys.argv[1]
text = pytesseract.image_to_string(Image.open(image_path))
amount = re.search(r'\$?\d+(\.\d{2})?', text)
print(json.dumps({'amount': float(amount.group(0).replace('$', '')) if amount else None}))
/api/fleet/upload_document.php:
php
<?php
require_once '../config.php';
header('Content-Type: application/json');
$roles = json_decode(file_get_contents("http://{$_SERVER['HTTP_HOST']}/api/get_user_roles.php"), true);

if (in_array('office', $roles) || in_array('admin', $roles) || in_array('baby admin', $roles)) {
    $truck_id = $_