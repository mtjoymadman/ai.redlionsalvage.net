<?php
require_once '../api/config.php';
if (!isset($_SESSION['employee_id'])) {
    error_log("scrap.php: No session employee_id set, redirecting to index.php");
    header('Location: index.php');
    exit;
}
$employee_id = $_SESSION['employee_id'];
error_log("scrap.php: Session employee_id: " . $employee_id);

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
error_log("scrap.php: Roles fetched: " . json_encode($roles));

if (!in_array('office', $roles) && !in_array('admin', $roles) && !in_array('baby admin', $roles)) {
    error_log("scrap.php: User lacks required roles, redirecting to dashboard.php");
    header('Location: dashboard.php');
    exit;
}

// Fetch recent scrap purchases for display (optional enhancement)
$stmt = $db->prepare("SELECT material, weight, price_per_lb, purchase_date FROM scrap_purchases WHERE employee_id = ? ORDER BY purchase_date DESC LIMIT 5");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();
$purchases = [];
while ($row = $result->fetch_assoc()) {
    $purchases[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YardMaster Scrap Purchases</title>
    <link rel="stylesheet" href="/frontend/style.css">
</head>
<body>
    <header>
        <img src="/frontend/logo.png" alt="YardMaster Logo" class="logo">
        <a href="/frontend/dashboard.php" class="home-btn">Home</a>
        <a href="/api/logout.php" class="logout-btn">Logout</a>
    </header>
    <div class="container">
        <h1>Scrap Purchases</h1>
        <form id="scrapForm" class="vehicle-form">
            <div class="form-group">
                <label for="material">Material:</label>
                <input type="text" id="material" name="material" placeholder="e.g., Steel" required>
            </div>
            <div class="form-group">
                <label for="weight">Weight (lbs):</label>
                <input type="number" id="weight" name="weight" min="1" step="0.1" required>
            </div>
            <div class="form-group">
                <label for="price">Price per lb ($):</label>
                <input type="number" id="price" name="price" min="0" step="0.01" required>
            </div>
            <div class="form-group">
                <button type="submit" class="button">Record Purchase</button>
            </div>
        </form>
        <div id="message" class="message"></div>

        <?php if (!empty($purchases)): ?>
            <h2>Recent Scrap Purchases</h2>
            <div class="table-wrapper">
                <table class="timeclock-table">
                    <thead>
                        <tr>
                            <th>Material</th>
                            <th>Weight (lbs)</th>
                            <th>Price per lb ($)</th>
                            <th>Purchase Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($purchases as $purchase): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($purchase['material']); ?></td>
                                <td><?php echo $purchase['weight']; ?></td>
                                <td><?php echo $purchase['price_per_lb']; ?></td>
                                <td><?php echo (new DateTime($purchase['purchase_date'], new DateTimeZone('America/New_York')))->format('Y-m-d H:i:s'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <script>
        document.getElementById('scrapForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const purchase = {
                material: formData.get('material'),
                weight: parseFloat(formData.get('weight')),
                price: parseFloat(formData.get('price'))
            };
            fetch('/api/scrap/record_purchase.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(purchase)
            }).then(response => response.json()).then(data => {
                const messageDiv = document.getElementById('message');
                messageDiv.textContent = data.message;
                if (data.success) {
                    document.getElementById('scrapForm').reset();
                    location.reload(); // Refresh to show new purchase
                }
            }).catch(error => {
                document.getElementById('message').textContent = 'Error: ' + error;
            });
        });
    </script>
</body>
</html>