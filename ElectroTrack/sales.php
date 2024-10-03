<?php
session_start(); // Start the session
include 'db_config.php'; // Include database configuration

// Check if the user is logged in, if not redirect to login page
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

// Fetch sales data
$sales = [];
try {
    $stmt = $pdo->query('SELECT * FROM customer_purchase'); // Adjust the table name if necessary
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $salesError = 'Error fetching sales data: ' . htmlspecialchars($e->getMessage());
}

// Handle AJAX request
if (isset($_GET['ajax']) && $_GET['ajax'] === 'true') {
    header('Content-Type: application/json');
    echo json_encode($sales);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales History</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', Arial, sans-serif;
            background-color: #f2f3f7;
            color: #333;
            margin: 0;
            padding: 0;
        }

        /* Sidebar Styles */
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
        }

        .logout-link {
            position: absolute;
            bottom: 20px;
            left: 0;
            width: 100%;
            text-align: center;
            padding: 12px 0;
            background-color: #1a396e;
            color: #FAFBFF;
            font-size: 18px;
            border-radius: 5px;
            transition: background-color 0.3s, color 0.3s;
            text-decoration: none;
        }

        .logout-link:hover {
            background-color: #E23C51;
            color: #FAFBFF;
            padding-left: 0;
        }

        .sidebar h2 {
            color: #fff;
            font-size: 24px;
            text-align: center;
            margin-bottom: 20px;
        }

        .sidebar a {
            padding: 12px 20px;
            text-decoration: none;
            display: flex;
            align-items: center;
            color: #FAFBFF;
            font-size: 18px;
            border-radius: 5px;
            margin: 10px 0;
            transition: background-color 0.3s, color 0.3s, padding-left 0.3s;
        }

        .sidebar a:hover {
            background-color: #E23C51;
            padding-left: 25px;
            color: #FAFBFF;
        }

        .content {
            margin-left: 300px;
            padding: 30px;
            background-color: #fff;
            transition: margin-left 0.3s ease;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            margin-top: 50px;
        }

        .header h1 {
            color: #031124;
            font-size: 65px;
            font-weight: bold;
            margin: 0;
        }

        .table {
            margin-top: 20px;
            background-color: #FAFBFF;
        }

        .table th {
            background-color: #EEE347;
            color: #031124;
            text-align: center;
        }

        .table td {
            text-align: center;
        }

        .hamburger {
            display: none;
            font-size: 30px;
            cursor: pointer;
            color: #031124;
            padding: 10px;
            margin-left: 20px;
        }

        @media (max-width: 750px) {
            .sidebar {
                width: 0;
                padding: 0;
                transition: 0.3s;
            }

            .content {
                margin-left: 0;
                transition: 0.3s;
            }

            .hamburger {
                display: block;
            }
        }

        .circle-photo {
            width: 120px;
            height: 120px;
            margin: 0 auto 20px auto;
        }

        .circle-photo img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
        }

        .mini-circle-photo {
            width: 30px;
            height: 30px;
            margin-right: 10px;
            display: inline-block;
        }

        .mini-circle-photo img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
        }
    </style>
</head>
<body>

<div class="hamburger" onclick="toggleSidebar()">&#9776;</div>

<div class="sidebar" id="sidebar">
    <div class="circle-photo">
        <img src="Assets/ADMIN.png" alt="Profile">
    </div>
    <h2>Admin</h2>
    <a href="index.php">
        <div class="mini-circle-photo">
            <img src="Assets/HOME.png" alt="Home Icon">
        </div> Home
    </a>
    <a href="inventory.php">
        <div class="mini-circle-photo">
            <img src="Assets/INVENTORY.png" alt="Inventory Icon">
        </div> Manage Inventory
    </a>
    <a href="pos.php">
        <div class="mini-circle-photo">
            <img src="Assets/POS.png" alt="POS Icon">
        </div> Point of Sale
    </a>
    <a href="sales.php">
        <div class="mini-circle-photo">
            <img src="Assets/SALES HISTORY.png" alt="Sales History Icon">
        </div> Sales History
    </a>
    <a href="delete.php">
        <div class="mini-circle-photo">
            <img src="Assets/DELETE.png" alt="Delete Icon">
        </div> Delete Item
    </a>
    <a href="logout.php" class="logout-link">
        <div class="mini-circle-photo">
            <img src="Assets/LOGOUT.png" alt="Logout Icon">
        </div> Logout
    </a>
</div>

<div class="content">
    <div class="header">
        <h1>Sales History</h1>
    </div>

    <?php if (isset($salesError)): ?>
        <div class="alert alert-danger"><?php echo $salesError; ?></div>
    <?php endif; ?>

    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>User ID</th>
                <th>Product ID</th>
                <th>Quantity</th>
                <th>Total</th>
                <th>Sale Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sales as $sale): ?>
                <tr>
                    <td><?php echo htmlspecialchars($sale['id'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($sale['user_id'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($sale['product_id'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($sale['quantity'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($sale['total'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($sale['created_at'] ?? 'N/A'); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
    function fetchSales() {
        const tbody = document.querySelector('.table tbody');
        tbody.innerHTML = '<tr><td colspan="6">Loading...</td></tr>'; // Display loading message
        fetch('sales.php?ajax=true&rand=' + Math.random())
            .then(response => response.json())
            .then(data => {
                tbody.innerHTML = ''; // Clear loading message
                data.forEach(sale => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${sale.id ?? 'N/A'}</td>
                        <td>${sale.user_id ?? 'N/A'}</td>
                        <td>${sale.product_id ?? 'N/A'}</td>
                        <td>${sale.quantity ?? 'N/A'}</td>
                        <td>${sale.total ?? 'N/A'}</td>
                        <td>${sale.created_at ?? 'N/A'}</td>
                    `;
                    tbody.appendChild(row);
                });
            })
            .catch(error => {
                tbody.innerHTML = '<tr><td colspan="6">Error fetching sales data.</td></tr>';
                console.error('Error fetching sales data:', error);
            });
    }

    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        if (sidebar.style.width === '300px') {
            sidebar.style.width = '0';
        } else {
            sidebar.style.width = '300px';
        }
        
        const content = document.querySelector('.content');
        content.style.marginLeft = sidebar.style.width === '300px' ? '300px' : '0';
    }

    window.onload = fetchSales; // Fetch sales data when the page loads
</script>
</body>
</html>
