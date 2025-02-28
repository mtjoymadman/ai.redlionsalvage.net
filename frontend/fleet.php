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