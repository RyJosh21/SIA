<?php
session_start();
include 'db_config.php'; // Ensure this points to your database connection file

// Check if the user is logged in, if not redirect to login page
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

// Fetch inventory items
$items = $pdo->query("SELECT * FROM inventory")->fetchAll(PDO::FETCH_ASSOC);

// Query to get total quantity
$stmt = $pdo->query("SELECT SUM(quantity) AS total_quantity FROM inventory");
$totalQuantity = $stmt->fetchColumn();

// Query to get total sales
$stmt = $pdo->query("SELECT SUM(total) AS total_sales FROM customer_purchase");
$totalSales = $stmt->fetchColumn();

// Query to get available products
$stmt = $pdo->query("SELECT COUNT(*) AS available_products FROM inventory WHERE quantity > 0");
$availableProducts = $stmt->fetchColumn();

// Query sales data for different periods (using created_at for timestamps)
$dailySalesQuery = $pdo->query("SELECT DATE(created_at) AS date, SUM(total) AS total_sales 
                                FROM customer_purchase 
                                WHERE created_at >= CURDATE() - INTERVAL 7 DAY 
                                GROUP BY DATE(created_at)");
$dailySalesData = $dailySalesQuery->fetchAll(PDO::FETCH_ASSOC);

// Weekly Sales (last 4 weeks)
$weeklySalesQuery = $pdo->query("SELECT WEEK(created_at) AS week, SUM(total) AS total_sales 
                                FROM customer_purchase 
                                WHERE created_at >= CURDATE() - INTERVAL 28 DAY 
                                GROUP BY WEEK(created_at)");
$weeklySalesData = $weeklySalesQuery->fetchAll(PDO::FETCH_ASSOC);

// Monthly Sales (last 12 months)
$monthlySalesQuery = $pdo->query("SELECT MONTHNAME(created_at) AS month, SUM(total) AS total_sales 
                                  FROM customer_purchase 
                                  WHERE created_at >= CURDATE() - INTERVAL 1 YEAR 
                                  GROUP BY MONTH(created_at)");
$monthlySalesData = $monthlySalesQuery->fetchAll(PDO::FETCH_ASSOC);

// Yearly Sales (last 5 years)
$yearlySalesQuery = $pdo->query("SELECT YEAR(created_at) AS year, SUM(total) AS total_sales 
                                 FROM customer_purchase 
                                 WHERE created_at >= CURDATE() - INTERVAL 5 YEAR 
                                 GROUP BY YEAR(created_at)");
$yearlySalesData = $yearlySalesQuery->fetchAll(PDO::FETCH_ASSOC);

// Pass the data to JavaScript using json_encode()
$dailySalesJSON = json_encode($dailySalesData);
$weeklySalesJSON = json_encode($weeklySalesData);
$monthlySalesJSON = json_encode($monthlySalesData);
$yearlySalesJSON = json_encode($yearlySalesData);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- Include Chart.js -->
    
    <style>
        body {
            font-family: 'Poppins', Arial, sans-serif;
            background-color: #f2f3f7;
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
            font-family: 'Poppins', sans-serif;
            border-radius: 5px;
            margin: 10px 0;
            transition: background-color 0.3s, color 0.3s;
        }

        .sidebar a:hover {
            background-color: #E23C51;
            padding-left: 25px;
            color: #FAFBFF;
        }
		 /* Hamburger menu styles */
        .hamburger {
            display: none;
            font-size: 30px;
            cursor: pointer;
            color: #031124;
            padding: 10px;
            margin-left: 20px; /* Adjust margin as needed */
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

        /* Circle photo and mini circle photo */
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

        .content {
            margin-left: 300px;
            padding: 20px;
            background-color: #fff;
        }

        /* Dashboard Styles */
        .dashboard-container {
            width: 90%;
            padding: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
        }

        .dashboard {
            font-size: 32px;
            font-family: Poppins;
            font-weight: bold;
            color: #031124;
            margin: 0;
        }

        .date {
            color: #031124;
            font-size: 18px;
            text-align: right;
            margin-left: auto;
        }

        /* Summary Section */
        .summary-container {
            background-color: #F2F3F7;
            border-radius: 10px;
            padding: 20px;
            width: calc(100% - 70px);
            margin: 30px auto;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .summary-title {
            font-size: 30px;
            font-weight: bold;
            color: #031124;
            text-align: center;
            margin-bottom: 20px;
        }

        .summary-items {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .summary-item {
            background-color: #D71445;
            color: #FAFBFF;
            border-radius: 10px;
            padding: 30px;
            width: 30%;
            text-align: center;
            font-size: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .summary-value {
            font-size: 45px;
            font-weight: bold;
        }

        /* Graph Styles */
        .graph-container {
            background-color: #F2F3F7;
            border-radius: 10px;
            padding: 20px;
            width: calc(100% - 70px);
            margin: 30px auto;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .chart-buttons {
            text-align: center;
            margin-bottom: 20px;
        }

        .chart-buttons button {
            background-color: #D71445;
            color: white;
            border: none;
            padding: 10px 20px;
            margin: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .chart-buttons button:hover {
            background-color: #E23C51;
        }

        #salesChart {
            max-width: 100%;
            height: 400px;
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="circle-photo">
        <img src="Assets/ADMIN.png" alt="Profile" style="width: 120px; height: 120px; border-radius: 50%;">
    </div>
    <h2>Staff</h2>
    <a href="index.php">
        <div class="mini-circle-photo">
        <img src="Assets/HOME.png" alt="Home Icon"></div> Home
    </a>
    <a href="inventory.php">
        <div class="mini-circle-photo">
        <img src="Assets/INVENTORY.png" alt="Inventory Icon"></div> Manage Inventory
    </a>
    <a href="pos.php">
        <div class="mini-circle-photo">
        <img src="Assets/POS.png" alt="POS Icon"></div> Point of Sale
    </a>
    <a href="sales.php">
        <div class="mini-circle-photo">
        <img src="Assets/SALES HISTORY.png" alt="Sales History Icon"></div> Sales History
    </a>
    <a href="delete.php">
        <div class="mini-circle-photo">
        <img src="Assets/DELETE.png" alt="Delete Icon"></div> Delete
    </a>
</div>

<!-- Main Content -->
<div class="content">
    <div class="dashboard-container">
        <h2 class="dashboard">Dashboard</h2>
        <p class="date"><?php echo date('F d, Y'); ?></p>
    </div>

    <div class="summary-container">
        <h2 class="summary-title">Summary</h2>
        <div class="summary-items">
            <div class="summary-item">
                <p>Total Quantity</p>
                <p class="summary-value"><?php echo $totalQuantity; ?></p>
            </div>
            <div class="summary-item">
                <p>Total Sales</p>
                <p class="summary-value"><?php echo '₱ ' . number_format($totalSales, 2); ?></p>
            </div>
            <div class="summary-item">
                <p>Available Products</p>
                <p class="summary-value"><?php echo $availableProducts; ?></p>
            </div>
        </div>
    </div>

    <div class="graph-container">
        <div class="chart-buttons">
            <button onclick="showChart('daily')">Daily</button>
            <button onclick="showChart('weekly')">Weekly</button>
            <button onclick="showChart('monthly')">Monthly</button>
            <button onclick="showChart('yearly')">Yearly</button>
        </div>
        <canvas id="salesChart"></canvas>
    </div>
</div>

<script>
    // Chart configuration
    var ctx = document.getElementById('salesChart').getContext('2d');
    var salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [], // Will be populated based on button click
            datasets: [{
                label: 'Total Sales',
                data: [],
                backgroundColor: 'rgba(215, 20, 69, 0.2)',
                borderColor: '#D71445',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true
                    }
                }]
            }
        }
    });

    var salesData = {
        daily: <?php echo $dailySalesJSON; ?>,
        weekly: <?php echo $weeklySalesJSON; ?>,
        monthly: <?php echo $monthlySalesJSON; ?>,
        yearly: <?php echo $yearlySalesJSON; ?>
    };

    function showChart(period) {
        var labels = salesData[period].map(function(item) {
            return item.date || item.week || item.month || item.year;
        });
        var data = salesData[period].map(function(item) {
            return item.total_sales;
        });

        salesChart.data.labels = labels;
        salesChart.data.datasets[0].data = data;
        salesChart.update();
    }

    // Show daily data by default
    showChart('daily');
</script>

</body>
</html>
