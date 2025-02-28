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