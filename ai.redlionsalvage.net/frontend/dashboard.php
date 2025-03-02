<?php
require_once '../api/config.php';
if (!isset($_SESSION['employee_id'])) {
    error_log("dashboard.php: No session employee_id set, redirecting to index.php");
    header('Location: index.php');
    exit;
}
$employee_id = $_SESSION['employee_id'];
error_log("dashboard.php: Session employee_id: " . $employee_id);

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
error_log("dashboard.php: Processed roles: " . json_encode($roles));

// Fetch username for fallback
$stmt = $db->prepare("SELECT username FROM employees WHERE id = ?");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$username = $user['username'] ?? '';
error_log("dashboard.php: Username for employee_id $employee_id: $username");

// Force admin and Aliceoffice buttons based on ID or username
$is_admin = ($employee_id == 1 || $username == 'admin' || in_array('admin', $roles));
$is_aliceoffice = ($employee_id == 9 || $username == 'aliceoffice' || in_array('office', $roles));
if ($is_admin) {
    error_log("dashboard.php: User is admin (ID 1, username 'admin', or 'admin' role detected)");
}
if ($is_aliceoffice) {
    error_log("dashboard.php: User is aliceoffice (ID 9, username 'aliceoffice', or 'office' role detected)");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YardMaster Dashboard</title>
    <link rel="stylesheet" href="/frontend/style.css">
</head>
<body>
    <header>
        <img src="/frontend/logo.png" alt="YardMaster Logo" class="logo">
        <a href="/frontend/dashboard.php" class="home-btn">Home</a>
        <a href="/api/logout.php" class="logout-btn">Logout</a>
    </header>
    <div class="container">
        <h1>Dashboard</h1>
        <div class="button-grid">
            <?php if ($is_admin): ?>
                <!-- Admin sees all buttons -->
                <a href="employee_details.php" class="button">Employee Management</a>
                <a href="reporting.php" class="button">Reporting</a>
                <a href="pending_vehicles.php" class="button">Pending Vehicles</a>
                <a href="timeclock.php" class="button">Timeclock</a>
                <a href="pickup.php" class="button">Vehicle Pickup</a>
                <a href="fleet.php" class="button">Fleet Management</a>
                <a href="pos.php" class="button">Inventory & POS</a>
                <a href="scrap.php" class="button">Scrap Purchases</a>
                <a href="scrap_driver.php" class="button">Scrap Driver</a>
                <a href="yardman.php" class="button">Yardman Operations</a>
            <?php else: ?>
                <!-- Non-admin role-based buttons -->
                <?php if (in_array('baby admin', $roles)): ?>
                    <a href="employee_details.php" class="button">Employee Management</a>
                    <a href="reporting.php" class="button">Reporting</a>
                    <a href="pending_vehicles.php" class="button">Pending Vehicles</a>
                <?php endif; ?>
                <?php if (in_array('employee', $roles) || in_array('yardman', $roles) || in_array('baby admin', $roles) || in_array('office', $roles)): ?>
                    <a href="timeclock.php" class="button">Timeclock</a>
                <?php endif; ?>
                <?php if (in_array('driver', $roles) || in_array('pickup', $roles) || in_array('baby admin', $roles)): ?>
                    <a href="pickup.php" class="button">Vehicle Pickup</a>
                <?php endif; ?>
                <?php if (in_array('fleet', $roles) || in_array('driver', $roles) || in_array('baby admin', $roles)): ?>
                    <a href="fleet.php" class="button">Fleet Management</a>
                <?php endif; ?>
                <?php if ($is_aliceoffice || in_array('office', $roles) || in_array('baby admin', $roles)): ?>
                    <a href="pos.php" class="button">Inventory & POS</a>
                    <a href="scrap.php" class="button">Scrap Purchases</a>
                <?php endif; ?>
                <?php if (in_array('driver', $roles) || in_array('baby admin', $roles)): ?>
                    <a href="scrap_driver.php" class="button">Scrap Driver</a>
                <?php endif; ?>
                <?php if (in_array('yardman', $roles)): ?>
                    <a href="yardman.php" class="button">Yardman Operations</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>