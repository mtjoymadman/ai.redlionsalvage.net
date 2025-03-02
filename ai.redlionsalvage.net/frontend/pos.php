<?php
require_once '../api/config.php';
if (!isset($_SESSION['employee_id'])) {
    error_log("pos.php: No session employee_id set, redirecting to index.php");
    header('Location: index.php');
    exit;
}
$employee_id = $_SESSION['employee_id'];
error_log("pos.php: Session employee_id: " . $employee_id);

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
error_log("pos.php: Roles fetched: " . json_encode($roles));

if (!in_array('office', $roles) && !in_array('admin', $roles) && !in_array('baby admin', $roles)) {
    error_log("pos.php: User lacks required roles, redirecting to dashboard.php");
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
    <header>
        <img src="/frontend/logo.png" alt="YardMaster Logo" class="logo">
        <a href="/frontend/dashboard.php" class="home-btn">Home</a>
        <a href="/api/logout.php" class="logout-btn">Logout</a>
    </header>
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