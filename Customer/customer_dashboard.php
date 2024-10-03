<?php
session_start();  // Start the session

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "inventory_system";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Capture filter values from the form
$category = isset($_GET['category']) ? $_GET['category'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : '';

// Query to get distinct categories from the 'inventory' table
$categoryQuery = "SELECT DISTINCT category FROM inventory";
$categoryResult = $conn->query($categoryQuery);

// Check if the query was successful for fetching categories
if (!$categoryResult) {
    die("Error executing category query: " . $conn->error);
}

// Construct SQL query with filters
$query = "SELECT * FROM inventory WHERE 1=1"; 

if (!empty($category)) {
    $query .= " AND category = '$category'";
}

// Add sorting based on user selection
switch ($sort) {
    case 'a-z':
        $query .= " ORDER BY item_name ASC"; 
        break;
    case 'z-a':
        $query .= " ORDER BY item_name DESC"; 
        break;
    case 'new-old':
        $query .= " ORDER BY date_added DESC"; 
        break;
    case 'old-new':
        $query .= " ORDER BY date_added ASC"; 
        break;
    default:
        // No sorting applied
        break;
}

// Execute the main query for displaying items
$result = $conn->query($query);

// Check if the main query was successful
if (!$result) {
    die("Error executing main query: " . $conn->error);
}

// Check for add order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_order'])) {
    $productId = (int)$_POST['product_id'];
    $userId = $_SESSION['user_id'];

    // Check if an order already exists for this user and product
    $stmtCheck = $conn->prepare("SELECT quantity FROM orders WHERE user_id = ? AND product_id = ?");
    $stmtCheck->bind_param("ii", $userId, $productId);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();

    if ($resultCheck->num_rows > 0) {
        // Order exists, update the quantity
        $row = $resultCheck->fetch_assoc();
        $newQuantity = $row['quantity'] + 1; // Increment quantity

        $stmtUpdate = $conn->prepare("UPDATE orders SET quantity = ? WHERE user_id = ? AND product_id = ?");
        $stmtUpdate->bind_param("iii", $newQuantity, $userId, $productId);
        if ($stmtUpdate->execute()) {
            header('Location: orders.php'); // Redirect to orders page after updating
            exit();
        } else {
            echo "Error updating order: " . $stmtUpdate->error;
        }
    } else {
        // Insert a new order
        $stmtInsert = $conn->prepare("INSERT INTO orders (user_id, product_id, quantity) VALUES (?, ?, 1)");
        $stmtInsert->bind_param("ii", $userId, $productId);
        if ($stmtInsert->execute()) {
            header('Location: orders.php'); // Redirect to orders page after adding
            exit();
        } else {
            echo "Error adding order: " . $stmtInsert->error;
        }
    }

    $stmtCheck->close();
}

// Close the database connection at the end of the script
$conn->close(); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard</title>
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

        .logout-button {
            margin-top: auto;
            padding: 20px 0;
            text-align: center;
        }

        .content {
            margin-left: 300px;
            padding: 30px;
            background-color: #fff;
            min-height: 100vh;
        }

        form {
            background-color: #ffffff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        label {
            margin-right: 10px;
            font-weight: 600;
            color: #1a396e;
        }

        select {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
            outline: none;
            transition: border-color 0.3s;
        }

        select:focus {
            border-color: #1a396e;
        }

        button {
            padding: 8px 15px;
            background-color: #1a396e;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #E23C51;
        }

        .item-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            grid-gap: 20px;
        }

        .item {
            background-color: #ffffff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }

        .item:hover {
            transform: scale(1.02);
        }

        .item h3 {
            color: #031124;
            font-size: 24px;
            margin: 0 0 10px 0;
        }

        .item p {
            margin: 5px 0;
            color: #555;
        }

        h1 {
            color: #555;
            font-size: 40px;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .current-time {
            font-size: 16px;
            color: #555;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%; /* Sidebar takes full width on smaller screens */
            }

            .content {
                margin-left: 0; /* No margin when sidebar is full-width */
            }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h2>
    <img src="Assets/ADMIN.png" alt="Profile Picture" class="profile-pic" style="width: 100px; height: 100px; border-radius: 50%; margin: 0 auto; display: block;">
    
    <!-- Active Link Class Logic -->
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
    <h1>Available Products
        <span class="current-time"><?php echo date('l, F j, Y'); ?></span>
    </h1>

    <div>
        <label for="category">Filter by Category:</label>
        <select id="category" onchange="filterProducts()">
            <option value="">All</option>
            <?php while ($categoryRow = $categoryResult->fetch_assoc()): ?>
                <option value="<?php echo htmlspecialchars($categoryRow['category']); ?>" <?php if ($category === $categoryRow['category']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($categoryRow['category']); ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label for="sort">Sort By:</label>
        <select id="sort" onchange="sortProducts()">
            <option value="">None</option>
            <option value="a-z" <?php if ($sort === 'a-z') echo 'selected'; ?>>A-Z</option>
            <option value="z-a" <?php if ($sort === 'z-a') echo 'selected'; ?>>Z-A</option>
            <option value="new-old" <?php if ($sort === 'new-old') echo 'selected'; ?>>Newest First</option>
            <option value="old-new" <?php if ($sort === 'old-new') echo 'selected'; ?>>Oldest First</option>
        </select>
    </div>

    <div class="item-grid">
        <?php while ($item = $result->fetch_assoc()): ?>
            <div class="item">
                <h3><?php echo htmlspecialchars($item['item_name']); ?></h3>
                <p>Price: <?php echo htmlspecialchars($item['price']); ?></p>
                <p>Category: <?php echo htmlspecialchars($item['category']); ?></p>
                <p>Date Added: <?php echo htmlspecialchars($item['date_added']); ?></p>
                <p>Available Quantity: <?php echo htmlspecialchars($item['quantity']); ?></p> <!-- Added quantity display -->
                <form method="POST" action="customer_dashboard.php">
                    <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($item['id']); ?>">
                    <button type="submit" name="add_order">Add to Orders</button>
                </form>
            </div>
        <?php endwhile; ?>
    </div>

</div>

<script>
function filterProducts() {
    const category = document.getElementById('category').value;
    const sort = document.getElementById('sort').value;
    const url = new URL(window.location.href);
    
    // Update the URL parameters
    url.searchParams.set('category', category);
    url.searchParams.set('sort', sort);
    
    // Navigate to the updated URL
    window.location.href = url.toString();
}

function sortProducts() {
    const category = document.getElementById('category').value;
    const sort = document.getElementById('sort').value;
    const url = new URL(window.location.href);
    
    // Update the URL parameters
    url.searchParams.set('category', category);
    url.searchParams.set('sort', sort);
    
    // Navigate to the updated URL
    window.location.href = url.toString();
}
</script>

</body>
</html>
