<?php
session_start();
include 'db.php'; // Ensure this points to your database connection file

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? ''); // New name field
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Insert into customers table
        $stmt = $conn->prepare("INSERT INTO customers (name, username, email, password) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$name, $username, $email, $hashed_password])) {
            header('Location: login.php');
            exit();
        } else {
            $error_message = "Error: Could not register user.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register as Customer</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            margin-top: 50px;
        }
        .error {
            color: red;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Register as Customer</h2>
    <?php if ($error_message): ?>
        <p class="text-danger"><?php echo htmlspecialchars($error_message); ?></p>
    <?php endif; ?>
    <form method="POST">
        <input type="text" name="name" placeholder="Full Name" required class="form-control mb-3">
        <input type="text" name="username" placeholder="Username" required class="form-control mb-3">
        <input type="email" name="email" placeholder="Email" required class="form-control mb-3">
        <input type="password" name="password" placeholder="Password" required class="form-control mb-3">
        <button type="submit" class="btn btn-primary">Register</button>
        <a href="login.php" class="btn btn-link">Login</a>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
