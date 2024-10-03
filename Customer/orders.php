<?php
session_start();
include 'db.php';  // Ensure this points to your database connection file

// Ensure the user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

$username = $_SESSION['username'];
$userId = $_SESSION['user_id']; // Assuming you have user_id stored in session

// Update order quantity based on the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_order'])) {
        $orderId = $_POST['order_id'];

        // Check if the action is an update
        if ($_POST['update_order'] === 'update') {
            // Get the new quantity from the input
            $newQuantity = intval($_POST['new_quantity']); // Convert to integer

            // Ensure new quantity is valid
            if ($newQuantity < 1) {
                $errorMessage = "Quantity must be at least 1.";
            } else {
                // Update the order in the database
                $stmt = $conn->prepare("UPDATE orders SET quantity = ? WHERE id = ? AND user_id = ?");
                if ($stmt->execute([$newQuantity, $orderId, $userId])) {
                    $successMessage = "Order updated successfully.";
                } else {
                    $errorMessage = "Failed to update order.";
                }
            }
        }
    }

    // Check if the action is a removal
    if (isset($_POST['remove_order'])) {
        $orderId = $_POST['order_id'];

        // Delete the order from the database
        $stmt = $conn->prepare("DELETE FROM orders WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$orderId, $userId])) {
            $successMessage = "Order removed successfully.";
        } else {
            $errorMessage = "Failed to remove order.";
        }
    }
}

// Fetch the user's orders including the created_at timestamp
$stmt = $conn->prepare("SELECT o.id, o.quantity, i.item_name, i.price, o.created_at FROM orders o JOIN inventory i ON o.product_id = i.id WHERE o.user_id = ?");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate the total bill
$totalBill = 0;
foreach ($orders as $order) {
    $totalBill += $order['quantity'] * $order['price'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">

    <style>
        body {
            font-family: 'Poppins', Arial, sans-serif;
            background-color: #f2f3f7;
            margin: 0;
            padding: 0;
        }

        .sidebar {
            height: 100%;
            width: 300px;
            position: fixed;
            z-index: 1;
            top: 0;
            left: 0;
            background-color: #1a396e;
            padding-top: 20px;
            transition: width 0.3s ease;
            overflow-x: hidden;
            padding: 20px 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .sidebar h2 {
            color: #fff;
            font-size: 32px;
            text-align: center;
            margin-bottom: 40px;
        }

        .sidebar img {
            border-radius: 50%;
            width: 80px;
            height: 80px;
            margin-bottom: 20px;
        }

        .sidebar a {
            padding: 16px 20px;
            text-decoration: none;
            color: #FAFBFF;
            display: block;
            margin: 10px 0;
            font-size: 20px;
            transition: background-color 0.3s;
            width: 100%;
            text-align: center;
        }

        .sidebar a:hover {
            background-color: #E23C51;
        }

        .content {
            margin-left: 300px;
            padding: 30px;
            background-color: #fff;
            transition: margin-left 0.3s ease;
            min-height: 100vh;
        }

        .order {
            background-color: #E2E3E7;
            border-radius: 10px;
            padding: 15px;
            margin: 10px 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-secondary {
            margin-left: 5px;
        }

        h1 {
            color: #555;
            font-size: 40px;
            font-weight: bold;
        }

        .total-bill {
            font-size: 20px;
            font-weight: bold;
            margin-top: 20px;
        }

        .logout-button {
            margin-top: auto;
            padding: 20px 0;
            text-align: center;
        }

        .order-time {
            text-align: right;
            font-size: 14px;
            color: #555;
        }

        .current-time {
            font-size: 16px;
            color: #555;
            text-align: right;
        }

    </style>
</head>
<body>

<div class="sidebar">
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h2>
    <img src="Assets/ADMIN.png" alt="Profile Picture" class="profile-pic" style="width: 100px; height: 100px; border-radius: 50%; margin: 0 auto; display: block;">
    
    <a href="customer_dashboard.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'customer_dashboard.php') ? 'active' : ''; ?>">
        <i class="fas fa-home"></i> Home
    </a>
    <a href="orders.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'orders.php') ? 'active' : ''; ?>">
        <i class="fas fa-shopping-cart"></i> My Orders
    </a>
    
    <div class="logout-button">
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log Out</a>
    </div>
</div>

<div class="content">
    <h1>My Orders
    <div class="current-time" id="dateTime"></div>
    </h1>

    <?php if (isset($successMessage)): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($successMessage); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($errorMessage)): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($errorMessage); ?>
        </div>
    <?php endif; ?>

    <?php if (count($orders) > 0): ?>
        <div class="list-group">
            <?php foreach ($orders as $order): ?>
                <div class="order list-group-item">
                    <div>
                        <strong>Product:</strong> <?php echo htmlspecialchars($order['item_name']); ?><br>
                        <strong>Price:</strong> ₱<?php echo htmlspecialchars(number_format($order['price'], 2)); ?><br>
                        <strong>Quantity:</strong>
                        <div style="display: flex; align-items: center;">
                            <button class="btn btn-secondary btn-sm" onclick="decreaseQuantity(<?php echo $order['id']; ?>)">-</button>
                            <input type="number" id="quantity-<?php echo $order['id']; ?>" value="<?php echo htmlspecialchars($order['quantity']); ?>" min="1" style="width: 60px; margin: 0 10px;" readonly>
                            <button class="btn btn-secondary btn-sm" onclick="increaseQuantity(<?php echo $order['id']; ?>)">+</button>
                        </div>
                    </div>
                    <div>
                        <div class="order-time">
                            <strong>Order Date:</strong> <?php echo htmlspecialchars(date('F j, Y, g:i A', strtotime($order['created_at']))); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="total-bill">
            Total Bill: ₱<?php echo htmlspecialchars(number_format($totalBill, 2)); ?>
        </div>
        <form action="customer_checkout.php" method="post" style="margin-top: 20px;">
            <input type="hidden" name="total_bill" value="<?php echo htmlspecialchars($totalBill); ?>">
            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($userId); ?>">
            <button type="submit" class="btn btn-primary">Proceed to Checkout</button>
        </form>
    <?php else: ?>
        <p>No orders found.</p>
    <?php endif; ?>
</div>

<script>
    function increaseQuantity(orderId) {
        const quantityInput = document.getElementById('quantity-' + orderId);
        let quantity = parseInt(quantityInput.value);
        quantityInput.value = quantity + 1;
        updateOrderQuantity(orderId, quantity + 1);
    }

    function decreaseQuantity(orderId) {
        const quantityInput = document.getElementById('quantity-' + orderId);
        let quantity = parseInt(quantityInput.value);
        
        if (quantity > 1) {
            quantityInput.value = quantity - 1;
            updateOrderQuantity(orderId, quantity - 1);
        } else if (quantity === 1) {
            // Remove the order when quantity reaches zero
            removeOrder(orderId);
        }
    }

    function updateOrderQuantity(orderId, newQuantity) {
        const formData = new FormData();
        formData.append('order_id', orderId);
        formData.append('new_quantity', newQuantity);
        formData.append('update_order', 'update');

        fetch('orders.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            location.reload();
        })
        .catch(error => console.error('Error:', error));
    }

    function removeOrder(orderId) {
        const formData = new FormData();
        formData.append('order_id', orderId);
        formData.append('remove_order', 'remove');

        fetch('orders.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            location.reload();
        })
        .catch(error => console.error('Error:', error));
    }

    // Function to display current date and time
    function updateDateTime() {
        const now = new Date();
        document.getElementById('dateTime').innerHTML = now.toLocaleString();
    }

    // Update time every second
    setInterval(updateDateTime, 1000);
    updateDateTime();
</script>

</body>
</html>
