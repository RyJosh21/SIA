<?php
session_start();
include 'db.php';  // Ensure this points to your database connection file

// Include PHPMailer
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Ensure the user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

$username = $_SESSION['username'];  // Get the username from session
$userId = $_SESSION['user_id'];

// Initialize totalBill and purchase flags
$totalBill = 0;
$purchaseComplete = false;
$confirmPurchase = false;

// Fetch orders for this user to calculate total bill and product details
$stmt = $conn->prepare("SELECT o.product_id, o.quantity, i.item_name, i.price FROM orders o JOIN inventory i ON o.product_id = i.id WHERE o.user_id = ?");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare receipt details
$receiptDetails = [];

foreach ($orders as $order) {
    $productId = $order['product_id'];
    $quantity = $order['quantity'];
    $productName = $order['item_name'];  // Assuming your inventory has item_name
    $price = $order['price'];

    // Calculate total bill
    if ($price) {
        $lineTotal = $price * $quantity;
        $totalBill += $lineTotal;

        // Add details to the receipt
        $receiptDetails[] = [
            'name' => $productName,
            'quantity' => $quantity,
            'price' => $price,
            'lineTotal' => $lineTotal
        ];
    }
}

// Fetch the user's saved email if available
$stmt = $conn->prepare("SELECT email FROM customers WHERE id = ?");
$stmt->execute([$userId]);
$savedEmail = $stmt->fetchColumn();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    // Use saved email from the database directly if no email is provided
    $email = !empty($_POST['email']) ? $_POST['email'] : $savedEmail;

    // Check if the email exists and is valid for invoice sending
    if (!$email) {
        echo "<p class='alert alert-danger'>No email found for the transaction. Please provide a valid email address.</p>";
    } else {
        if (isset($_POST['confirm'])) {
            // Process payment
            try {
                $conn->beginTransaction();

                foreach ($orders as $order) {
                    $productId = $order['product_id'];
                    $quantity = $order['quantity'];

                    // Insert into customer_purchase
                    $stmt = $conn->prepare("INSERT INTO customer_purchase (user_id, product_id, quantity, total, name, email) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$userId, $productId, $quantity, $totalBill, $name, $email]);

                    // Check current quantity
                    $stmt = $conn->prepare("SELECT quantity FROM inventory WHERE id = ?");
                    $stmt->execute([$productId]);
                    $currentQuantity = $stmt->fetchColumn();

                    // Reduce the quantity in the inventory
                    if ($currentQuantity >= $quantity) {
                        $stmt = $conn->prepare("UPDATE inventory SET quantity = quantity - ? WHERE id = ?");
                        $stmt->execute([$quantity, $productId]);
                    } else {
                        throw new Exception("Not enough quantity for Product ID: $productId. Available: $currentQuantity, Requested: $quantity.");
                    }
                }

                // Clear the user's orders after purchase
                $stmt = $conn->prepare("DELETE FROM orders WHERE user_id = ?");
                $stmt->execute([$userId]);

                $conn->commit();
                $purchaseComplete = true;

                // Send the transaction invoice to the provided or saved email
                $mail = new PHPMailer(true);
                $invoiceSent = false; // Initialize invoiceSent flag

                try {
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com'; // Gmail SMTP server
                    $mail->SMTPAuth = true;
                    $mail->Username = 'uvomtrjoshua@gmail.com'; // Your Gmail email
                    $mail->Password = 'koib skke gnxz dywq'; // Your generated App Password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    // Recipients
                    $mail->setFrom('uvomtrjoshua@gmail.com', 'ElectroTrack'); // Updated shop name
                    $mail->addAddress($email, $name);

                    // Prepare email content
                    $body = '<h1>Thank you for your purchase at ElectroTrack!</h1>';
                    $body .= '<p>Your total bill is: <strong>' . htmlspecialchars($totalBill) . '</strong></p>';
                    $body .= '<h2>Order Details:</h2>';
                    $body .= '<table border="1" cellpadding="10" style="border-collapse:collapse; width:100%;">';
                    $body .= '<tr><th>Product Name</th><th>Quantity</th><th>Price</th><th>Line Total</th></tr>';
                    
                    // Add each item to the email
                    foreach ($receiptDetails as $item) {
                        $body .= '<tr>';
                        $body .= '<td>' . htmlspecialchars($item['name']) . '</td>';
                        $body .= '<td>' . htmlspecialchars($item['quantity']) . '</td>';
                        $body .= '<td>' . htmlspecialchars($item['price']) . '</td>';
                        $body .= '<td>' . htmlspecialchars($item['lineTotal']) . '</td>';
                        $body .= '</tr>';
                    }
                    
                    $body .= '</table>';
                    
                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Purchase Invoice from ElectroTrack'; // Updated subject line
                    $mail->Body    = $body; // Use the built HTML body
                    $mail->AltBody = strip_tags($body); // Plain text version

                    $mail->send();
                    $invoiceSent = true; // Set flag to true if email is sent successfully

                } catch (Exception $e) {
                    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                }

                // Display success message for email sending
                if ($invoiceSent) {
                    echo "<p class='alert alert-success'>Invoice sent successfully to: " . htmlspecialchars($email) . "</p>";
                } else {
                    echo "<p class='alert alert-danger'>Failed to send invoice. Please check your email settings.</p>";
                }

            } catch (Exception $e) {
                $conn->rollBack();
                echo "<p class='alert alert-danger'>Failed to complete the purchase: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        } else {
            // Show confirmation before payment
            $confirmPurchase = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - ElectroTrack</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', Arial, sans-serif;
            background-color: #f2f3f7;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 800px;
            margin: 40px auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #1a396e;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .btn-primary {
            background-color: #3468c0;
            color: #fff;
        }
        .btn-secondary {
            background-color: #28a745;
            color: #fff;
        }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 16px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
        }
        .alert-info {
            background-color: #cce5ff;
            color: #004085;
        }
    </style>
    <script>
       
    function printReceipt() {
        let receiptContent = `
            <h1 style="text-align:center;">Receipt from ElectroTrack</h1>
            <p style="text-align:center;">Thank you for your purchase, ${<?php echo json_encode($username); ?>}!</p>
            <p style="text-align:center;">Your total bill is: <strong>${<?php echo json_encode($totalBill); ?>}</strong></p>
            <h2 style="border-bottom: 2px solid #1a396e;">Order Details:</h2>
            <table border="1" cellpadding="10" style="border-collapse:collapse; width:100%; margin: auto;">
                <tr>
                    <th style="text-align:left;">Product Name</th>
                    <th style="text-align:left;">Quantity</th>
                    <th style="text-align:left;">Price</th>
                    <th style="text-align:left;">Line Total</th>
                </tr>
                <?php foreach ($receiptDetails as $item): ?>
                    <tr>
                        <td style="text-align:left;"><?php echo htmlspecialchars($item['name']); ?></td>
                        <td style="text-align:left;"><?php echo htmlspecialchars($item['quantity']); ?></td>
                        <td style="text-align:left;"><?php echo htmlspecialchars($item['price']); ?></td>
                        <td style="text-align:left;"><?php echo htmlspecialchars($item['lineTotal']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <h3 style="border-top: 2px solid #1a396e; margin-top: 20px; text-align:center;">Thank you for choosing ElectroTrack!</h3>
            <p style="text-align:center;">We appreciate your business and hope you enjoy your purchase.</p>
        `;
        let newWindow = window.open('', '', 'height=600,width=800');
        newWindow.document.write(receiptContent);
        newWindow.document.close();
        newWindow.print();
    }
</script>
</head>
<body>
    <div class="container">
        <h2>Checkout</h2>
        
        <?php if ($purchaseComplete): ?>
            <p class='alert alert-success'>Thank you for your purchase, <?php echo htmlspecialchars($username); ?>! Your purchase was completed successfully!</p>
            <button onclick="printReceipt()" class="btn btn-secondary">Print Receipt</button>
            <a href="customer_dashboard.php" class="btn btn-primary" style="margin-left: 10px;">Shop Again</a> <!-- Added Shop Again button -->
        <?php elseif ($confirmPurchase): ?>
            <p class='alert alert-warning'>Please confirm your purchase details:</p>
            <form method="POST">
                <div class="form-group">
                    <label for="name">Name:</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($username); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($savedEmail); ?>" required>
                </div>
                <button type="submit" name="confirm" class="btn btn-primary">Confirm Purchase</button>
            </form>
        <?php else: ?>
            <p class='alert alert-info'>You have the following items in your cart:</p>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Quantity</th>
                        <th>Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($receiptDetails as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                            <td><?php echo htmlspecialchars($item['price']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p><strong>Total Bill: </strong><?php echo htmlspecialchars($totalBill); ?></p>
            <form method="POST">
                <button type="submit" name="confirm" class="btn btn-primary">Proceed to Payment</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
