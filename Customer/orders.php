
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
                // If quantity is less than 1, remove the order instead of updating
                $stmt = $conn->prepare("DELETE FROM orders WHERE id = ? AND user_id = ?");
                $stmt->execute([$orderId, $userId]);
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
    <link rel="icon" href="Assets/electro.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">

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

        .order-item {
            transition: transform 0.3s ease;
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
            text-align: center; /* Center align total bill */
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

    
        .date {
            color: #031124;
            font-size: 18px;
            text-align: right;
            margin-left: auto;
        }

        .continue-shopping {
            display: block;
            margin: 20px auto;
            text-align: left;
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
        <div class="date" id="dateTime"></div>
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
                <div class="order-item list-group-item" id="order-item-<?php echo $order['id']; ?>">
                    <div class="order">
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
                        <div class="order-time"><?php echo htmlspecialchars($order['created_at']); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
		 <div class="continue-shopping">
            <a href="customer_dashboard.php" class="btn btn-secondary">Add parts</a>
        </div>

        <div class="total-bill" style="text-align: right; margin-top: 20px;">
            <strong>Total Bill:</strong> ₱<?php echo htmlspecialchars(number_format($totalBill, 2)); ?>
			
        </div>
        <div style="text-align: right; margin-top: 20px;">
            <form action="customer_checkout.php" method="post">
                <input type="hidden" name="total_bill" value="<?php echo htmlspecialchars($totalBill); ?>">
                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($userId); ?>">
                <button type="submit" class="btn btn-primary">Proceed to Checkout</button>
            </form>
        </div>

    <?php else: ?>
        <p>No orders found.</p>
    <?php endif; ?>
</div>

<script>
    let touchstartX = 0;
    let touchendX = 0;

    function checkSwipe(orderId) {
        if (touchendX < touchstartX) {
            // Swipe left
            removeOrder(orderId);
        }
    }

    const orderItems = document.querySelectorAll('.order-item');

    orderItems.forEach(item => {
        item.addEventListener('touchstart', e => {
            touchstartX = e.changedTouches[0].screenX;
        });

        item.addEventListener('touchend', e => {
            touchendX = e.changedTouches[0].screenX;
            checkSwipe(item.id.split('-')[2]); // Get order ID from the item ID
        });
    });

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
            // Remove the order item from the DOM
            const orderItem = document.getElementById('order-item-' + orderId);
            if (orderItem) {
                orderItem.remove();
            }
            location.reload(); // Refresh the page to update the order list
        })
        .catch(error => console.error('Error:', error));
    }

    function increaseQuantity(orderId) {
        const quantityInput = document.getElementById('quantity-' + orderId);
        quantityInput.value = parseInt(quantityInput.value) + 1;

        // Optionally update the order in the database
        updateOrder(orderId, quantityInput.value);
    }

    function decreaseQuantity(orderId) {
        const quantityInput = document.getElementById('quantity-' + orderId);
        if (quantityInput.value > 1) {
            quantityInput.value = parseInt(quantityInput.value) - 1;

            // Optionally update the order in the database
            updateOrder(orderId, quantityInput.value);
        } else {
            // If quantity is zero, remove the order
            removeOrder(orderId);
        }
    }

    function updateOrder(orderId, newQuantity) {
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
            console.log(data);
        })
        .catch(error => console.error('Error:', error));
    }
	function updateDateTime() {
        const now = new Date();
        const dateOptions = { year: 'numeric', month: 'long', day: 'numeric' };
        const timeString = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        const dateString = now.toLocaleDateString('en-US', dateOptions);
        document.getElementById('dateTime').textContent = `${dateString} ${timeString}`;
    }
    
    // Update date and time every second
    setInterval(updateDateTime, 1000);
    updateDateTime(); // Initial call to display immediately
</script>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>