<?php
require_once '../api/config.php';
if (!isset($_SESSION['employee_id'])) {
    header('Location: index.php');
    exit;
}
$employee_id = $_SESSION['employee_id'];
error_log("timeclock.php: Session employee_id: " . $employee_id);

// Fetch roles di<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require_once "../api/config.php";

// Fetch time logs for the current user
$userId = $_SESSION['user_id'];
$sql = "SELECT tl.log_date, tl.clock_in_time, tl.clock_out_time, 
               b.start_time AS break_start, b.end_time AS break_end
        FROM time_logs tl
        LEFT JOIN breaks b ON tl.id = b.time_log_id
        WHERE tl.employee_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Timeclock</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Timeclock</h1>
    <button id="clockInBtn">Clock In</button>
    <button id="clockOutBtn">Clock Out</button>
    <div id="status"></div>

    <h2>Your Time Logs</h2>
    <table>
        <tr>
            <th>Date</th>
            <th>Clock In</th>
            <th>Clock Out</th>
            <th>Break Start</th>
            <th>Break End</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['log_date']; ?></td>
                <td><?php echo $row['clock_in_time']; ?></td>
                <td><?php echo $row['clock_out_time'] ?? 'Not clocked out'; ?></td>
                <td><?php echo $row['break_start'] ?? 'N/A'; ?></td>
                <td><?php echo $row['break_end'] ?? 'N/A'; ?></td>
            </tr>
        <?php endwhile; ?>
    </table>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $("#clockInBtn").click(function() {
                $.post("../api/timeclock.php", { action: "clock_in" }, function(data) {
                    $("#status").html(data.message);
                    location.reload(); // Refresh to show new log
                }, "json");
            });

            $("#clockOutBtn").click(function() {
                $.post("../api/timeclock.php", { action: "clock_out" }, function(data) {
                    $("#status").html(data.message);
                    location.reload();
                }, "json");
            });
        });
    </script>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>rectly from database
$stmt = $db->prepare("SELECT r.role_name FROM employee_roles er JOIN roles r ON er.role_id = r.id WHERE er.employee_id = ?");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();
$roles = [];
while ($row = $result->fetch_assoc()) {
    $roles[] = $row['role_name'];
}
$stmt->close();
error_log("timeclock.php: Roles fetched: " . json_encode($roles));

// Fetch current time log (if not clocked out)
$stmt = $db->prepare("SELECT id, clock_in, clock_out FROM time_logs WHERE employee_id = ? AND clock_out IS NULL ORDER BY clock_in DESC LIMIT 1");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();
$current_log = $result->fetch_assoc();

// Fetch breaks for current log (if any)
$breaks = [];
$total_break_minutes = 0;
if ($current_log) {
    $stmt = $db->prepare("SELECT id, start_time, duration FROM breaks WHERE time_log_id = ? ORDER BY start_time");
    $stmt->bind_param("i", $current_log['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $breaks[] = $row;
        $total_break_minutes += $row['duration'];
    }
}

// Calculate worked time for current log
$worked_time = null;
if ($current_log && !$current_log['clock_out']) {
    $start = new DateTime($current_log['clock_in'], new DateTimeZone('America/New_York'));
    $end = new DateTime('now', new DateTimeZone('America/New_York'));
    $interval = $start->diff($end);
    $worked_minutes = ($interval->h * 60) + $interval->i - $total_break_minutes;
    $worked_time = sprintf('%d hours %d minutes', floor($worked_minutes / 60), $worked_minutes % 60);
    error_log("timeclock.php: Worked time - Start: " . $start->format('Y-m-d H:i:s') . ", End: " . $end->format('Y-m-d H:i:s') . ", Minutes: $worked_minutes");
}

// Fetch recent time logs
$stmt = $db->prepare("SELECT id, clock_in, clock_out FROM time_logs WHERE employee_id = ? ORDER BY clock_in DESC LIMIT 5");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();
$time_logs = [];
while ($row = $result->fetch_assoc()) {
    $time_logs[] = $row;
}

// Fetch all employees for admin view
$all_employees = [];
$is_admin = (in_array('admin', $roles) || in_array('baby admin', $roles));
error_log("timeclock.php: Is admin or baby admin: " . ($is_admin ? 'Yes' : 'No'));
if ($is_admin) {
    $stmt = $db->prepare("SELECT id, username, full_name FROM employees WHERE id != ? ORDER BY username");
    $stmt->bind_param("i", $employee_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $all_employees[] = $row;
        }
        error_log("timeclock.php: Employees fetched: " . json_encode($all_employees));
    } else {
        error_log("timeclock.php: Employee query failed: " . $stmt->error);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YardMaster Timeclock</title>
    <link rel="stylesheet" href="/frontend/style.css?version=<?php echo time(); ?>">
</head>
<body>
    <header>
        <img src="/frontend/logo.png" alt="YardMaster Logo" class="logo">
        <a href="/frontend/dashboard.php" class="home-btn">Home</a>
        <a href="/api/logout.php" class="logout-btn">Logout</a>
    </header>
    <div class="container">
        <h1>Timeclock</h1>

        <div class="timeclock-buttons">
            <?php if (!$current_log): ?>
                <button id="clockInBtn" class="button">Clock In</button>
            <?php else: ?>
                <button id="clockOutBtn" class="button">Clock Out</button>
                <?php if (empty($breaks) || $breaks[count($breaks) - 1]['duration'] > 0): ?>
                    <button id="startBreakBtn" class="button">Start Break</button>
                <?php else: ?>
                    <button id="endBreakBtn" class="button">End Break</button>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <?php if ($current_log): ?>
            <div class="worked-time">
                Clocked in at: <?php echo (new DateTime($current_log['clock_in'], new DateTimeZone('America/New_York')))->format('Y-m-d H:i:s'); ?>
            </div>
            <div class="worked-time">
                Total worked time (excluding breaks): <?php echo $worked_time; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($breaks)): ?>
            <h2>Breaks for Current Shift</h2>
            <div class="table-wrapper">
                <table class="timeclock-table">
                    <thead>
                        <tr>
                            <th>Start Time</th>
                            <th>Duration (minutes)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($breaks as $break): ?>
                            <tr>
                                <td><?php echo (new DateTime($break['start_time'], new DateTimeZone('America/New_York')))->format('Y-m-d H:i:s'); ?></td>
                                <td><?php echo $break['duration'] > 0 ? $break['duration'] : 'In Progress'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <h2>Recent Time Logs</h2>
        <div class="table-wrapper">
            <table class="timeclock-table">
                <thead>
                    <tr>
                        <th>Clock In</th>
                        <th>Clock Out</th>
                        <th>Duration</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($time_logs as $log): ?>
                        <tr>
                            <td><?php echo (new DateTime($log['clock_in'], new DateTimeZone('America/New_York')))->format('Y-m-d H:i:s'); ?></td>
                            <td><?php echo $log['clock_out'] ? (new DateTime($log['clock_out'], new DateTimeZone('America/New_York')))->format('Y-m-d H:i:s') : 'Active'; ?></td>
                            <td>
                                <?php
                                if ($log['clock_out']) {
                                    $start = new DateTime($log['clock_in'], new DateTimeZone('America/New_York'));
                                    $end = new DateTime($log['clock_out'], new DateTimeZone('America/New_York'));
                                    $interval = $start->diff($end);
                                    echo $interval->format('%h hours %i minutes');
                                } else {
                                    echo 'In Progress';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php if ($is_admin): ?>
            <h2>Manage Employee Time Logs</h2>
            <div class="form-group">
                <select id="employeeSelect">
                    <option value="">Select Employee</option>
                    <?php foreach ($all_employees as $emp): ?>
                        <option value="<?php echo $emp['id']; ?>"><?php echo htmlspecialchars($emp['username']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div id="employeeTimeLogs" class="table-wrapper" style="display: none;">
                <table class="timeclock-table">
                    <thead>
                        <tr>
                            <th>Clock In</th>
                            <th>Clock Out</th>
                            <th>Duration</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="employeeTimeLogsBody"></tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <script>
        console.log("Script loaded successfully");

        document.getElementById('clockInBtn')?.addEventListener('click', function() {
            console.log("Clock In clicked");
            fetch('/api/timeclock.php?action=clock_in', { method: 'POST', headers: { 'Content-Type': 'application/json' } })
                .then(response => response.json()).then(data => {
                    if (data.success) location.reload();
                    else alert('Error: ' + data.message);
                }).catch(error => console.error('Clock In fetch error: ' + error));
        });

        document.getElementById('clockOutBtn')?.addEventListener('click', function() {
            console.log("Clock Out clicked");
            fetch('/api/timeclock.php?action=clock_out', { method: 'POST', headers: { 'Content-Type': 'application/json' } })
                .then(response => response.json()).then(data => {
                    if (data.success) location.reload();
                    else alert('Error: ' + data.message);
                }).catch(error => console.error('Clock Out fetch error: ' + error));
        });

        document.getElementById('startBreakBtn')?.addEventListener('click', function() {
            console.log("Start Break clicked");
            fetch('/api/timeclock.php?action=start_break', { method: 'POST', headers: { 'Content-Type': 'application/json' } })
                .then(response => response.json()).then(data => {
                    if (data.success) location.reload();
                    else alert('Error: ' + data.message);
                }).catch(error => console.error('Start Break fetch error: ' + error));
        });

        document.getElementById('endBreakBtn')?.addEventListener('click', function() {
            console.log("End Break clicked");
            fetch('/api/timeclock.php?action=end_break', { method: 'POST', headers: { 'Content-Type': 'application/json' } })
                .then(response => response.json()).then(data => {
                    if (data.success) location.reload();
                    else alert('Error: ' + data.message);
                }).catch(error => console.error('End Break fetch error: ' + error));
        });

        <?php if ($is_admin): ?>
        console.log("Admin section initializing");
        const employeeSelect = document.getElementById('employeeSelect');
        if (employeeSelect) {
            console.log("EmployeeSelect found, attaching listener");
            employeeSelect.addEventListener('change', function() {
                const employeeId = this.value;
                console.log("Employee selected: " + employeeId);
                if (employeeId) {
                    fetch('/api/timeclock.php?action=get_employee_logs&employee_id=' + employeeId, {
                        method: 'GET',
                        credentials: 'same-origin'
                    })
                        .then(response => {
                            console.log("Fetch response status: " + response.status);
                            if (!response.ok) throw new Error('Network response was not ok: ' + response.statusText);
                            return response.json();
                        })
                        .then(data => {
                            console.log("Fetch data received: ", data);
                            const tbody = document.getElementById('employeeTimeLogsBody');
                            tbody.innerHTML = '';
                            if (data.success && data.logs && data.logs.length > 0) {
                                data.logs.forEach(log => {
                                    const tr = document.createElement('tr');
                                    tr.innerHTML = `
                                        <td>${new Date(log.clock_in).toLocaleString('en-US', { timeZone: 'America/New_York' })}</td>
                                        <td>${log.clock_out ? new Date(log.clock_out).toLocaleString('en-US', { timeZone: 'America/New_York' }) : 'Active'}</td>
                                        <td>${log.clock_out ? Math.floor((new Date(log.clock_out) - new Date(log.clock_in)) / 60000) + ' minutes' : 'In Progress'}</td>
                                        <td><button class="edit-btn" onclick="correctTime(${log.id}, '${log.clock_in}', '${log.clock_out}')">Correct</button></td>
                                    `;
                                    tbody.appendChild(tr);

                                    fetch('/api/timeclock.php?action=get_breaks&time_log_id=' + log.id, {
                                        method: 'GET',
                                        credentials: 'same-origin'
                                    })
                                        .then(response => {
                                            console.log("Break fetch status: " + response.status);
                                            if (!response.ok) throw new Error('Break fetch failed: ' + response.statusText);
                                            return response.json();
                                        })
                                        .then(breakData => {
                                            console.log("Break data for log " + log.id + ": ", breakData);
                                            if (breakData.success && breakData.breaks && breakData.breaks.length > 0) {
                                                const breakTable = document.createElement('table');
                                                breakTable.className = 'timeclock-table';
                                                breakTable.innerHTML = `
                                                    <thead><tr><th>Break Start</th><th>Duration (min)</th><th>Action</th></tr></thead>
                                                    <tbody></tbody>
                                                `;
                                                breakData.breaks.forEach(brk => {
                                                    const breakTr = document.createElement('tr');
                                                    breakTr.innerHTML = `
                                                        <td>${new Date(brk.start_time).toLocaleString('en-US', { timeZone: 'America/New_York' })}</td>
                                                        <td>${brk.duration > 0 ? brk.duration : 'In Progress'}</td>
                                                        <td><button class="edit-btn" onclick="correctBreak(${brk.id}, '${brk.start_time}', ${brk.duration})">Correct Break</button></td>
                                                    `;
                                                    breakTable.querySelector('tbody').appendChild(breakTr);
                                                });
                                                tr.insertAdjacentElement('afterend', breakTable);
                                            }
                                        }).catch(error => console.error('Break fetch error: ' + error));
                                });
                                document.getElementById('employeeTimeLogs').style.display = 'block';
                            } else {
                                console.log("No logs or fetch failed: ", data);
                                document.getElementById('employeeTimeLogs').style.display = 'none';
                                alert('No time logs found or fetch failed: ' + (data.message || 'Unknown error'));
                            }
                        }).catch(error => {
                            console.error('Fetch error: ' + error);
                            alert('Failed to load time logs: ' + error.message);
                        });
                } else {
                    document.getElementById('employeeTimeLogs').style.display = 'none';
                }
            });
        } else {
            console.error("employeeSelect element not found");
            alert("Employee dropdown not found - please reload the page");
        }

        function correctTime(logId, clockIn, clockOut) {
            console.log("Correct Time clicked for log " + logId);
            const clockInDate = new Date(clockIn);
            const clockOutDate = clockOut ? new Date(clockOut) : null;
            const correctionForm = `
                <form id="correctionForm-${logId}" class="vehicle-form">
                    <div class="form-group">
                        <label>Clock In Date:</label>
                        <input type="date" class="date-input" name="clock_in_date" value="${clockInDate.toISOString().split('T')[0]}" required>
                    </div>
                    <div class="form-group">
                        <label>Clock In Time:</label>
                        <input type="time" class="time-input" name="clock_in_time" value="${clockInDate.toTimeString().slice(0, 5)}" required>
                    </div>
                    <div class="form-group">
                        <label>Clock Out Date:</label>
                        <input type="date" class="date-input" name="clock_out_date" value="${clockOutDate ? clockOutDate.toISOString().split('T')[0] : ''}">
                    </div>
                    <div class="form-group">
                        <label>Clock Out Time:</label>
                        <input type="time" class="time-input" name="clock_out_time" value="${clockOutDate ? clockOutDate.toTimeString().slice(0, 5) : ''}">
                    </div>
                    <div class="form-group">
                        <button type="submit" class="button">Save</button>
                        <button type="button" class="button" onclick="this.closest('form').remove(); document.getElementById('employeeSelect').dispatchEvent(new Event('change'))">Cancel</button>
                    </div>
                </form>
            `;
            const tr = event.target.closest('tr');
            tr.innerHTML = `<td colspan="4">${correctionForm}</td>`;

            document.getElementById(`correctionForm-${logId}`).addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const clockInNew = `${formData.get('clock_in_date')} ${formData.get('clock_in_time')}`;
                const clockOutNew = formData.get('clock_out_date') && formData.get('clock_out_time') ? 
                    `${formData.get('clock_out_date')} ${formData.get('clock_out_time')}` : null;

                fetch('/api/timeclock.php?action=correct_time&log_id=' + logId, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ clock_in: clockInNew, clock_out: clockOutNew })
                }).then(response => response.json()).then(data => {
                    if (data.success) {
                        document.getElementById('employeeSelect').dispatchEvent(new Event('change'));
                    } else {
                        alert('Error: ' + data.message);
                    }
                }).catch(error => console.error('Correct Time fetch error: ' + error));
            });
        }

        function correctBreak(breakId, startTime, duration) {
            console.log("Correct Break clicked for break " + breakId);
            const startDate = new Date(startTime);
            const correctionForm = `
                <form id="breakCorrectionForm-${breakId}" class="vehicle-form">
                    <div class="form-group">
                        <label>Break Start Date:</label>
                        <input type="date" class="date-input" name="start_date" value="${startDate.toISOString().split('T')[0]}" required>
                    </div>
                    <div class="form-group">
                        <label>Break Start Time:</label>
                        <input type="time" class="time-input" name="start_time" value="${startDate.toTimeString().slice(0, 5)}" required>
                    </div>
                    <div class="form-group">
                        <label>Duration (minutes):</label>
                        <input type="number" class="number-input" name="duration" value="${duration > 0 ? duration : ''}" min="1" required>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="button">Save</button>
                        <button type="button" class="button" onclick="this.closest('form').parentElement.remove(); document.getElementById('employeeSelect').dispatchEvent(new Event('change'))">Cancel</button>
                    </div>
                </form>
            `;
            const tr = event.target.closest('tr');
            tr.innerHTML = `<td colspan="3">${correctionForm}</td>`;

            document.getElementById(`breakCorrectionForm-${breakId}`).addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const startTimeNew = `${formData.get('start_date')} ${formData.get('start_time')}`;
                const durationNew = parseInt(formData.get('duration'));

                fetch('/api/timeclock.php?action=correct_break&break_id=' + breakId, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ start_time: startTimeNew, duration: durationNew })
                }).then(response => response.json()).then(data => {
                    if (data.success) {
                        document.getElementById('employeeSelect').dispatchEvent(new Event('change'));
                    } else {
                        alert('Error: ' + data.message);
                    }
                }).catch(error => console.error('Correct Break fetch error: ' + error));
            });
        }
        <?php endif; ?>
    </script>
</body>
</html>