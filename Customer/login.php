<?php
session_start();
include 'db.php'; // Ensure this points to your database connection file

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Prepare SQL statement
    $sql = "SELECT * FROM customers WHERE username = :username";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user_data && password_verify($password, $user_data['password'])) {
        $_SESSION['username'] = $user_data['username'];
        $_SESSION['user_id'] = $user_data['id'];
        header('Location: customer_dashboard.php');
        exit();
    } else {
        $error_message = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ElectroTrack - Customer</title>
    <link rel="icon" href="Assets/electro.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .loginpage {
            position: absolute;
            background-color: #ffffff;
            height: 100vh;
            width: 100vw;
            padding: 0;
        }

        .rectangle45 {
            background-color: #fafbff;
            height: 100vh;
            width: 44vw;
            position: absolute;
            top: 0;
            left: 0;
        }

        .rectangle1 {
            background-color: #3468c0;
            height: 100vh;
            width: 56vw;
            position: absolute;
            top: 0;
            left: 44vw;
        }

        .welcomeback {
            color:#1a396e;;
            text-align: left;
            font-size: 43px;
            font-weight: bold;
            font-family: Poppins;
            position: absolute;
            left: 6vw;
            top: 30vh;
            width: 35vw;
        }

        .form-label {
            font-size: 18px;
            color: #031124;
            font-family: Poppins;
			color:#1a396e;
            position: absolute;
            left: 6vw;
        }

        .form-control {
            position: absolute;
            left: 6vw;
            width: 35vw;
            padding: 15px; /* Added padding for better spacing */
            font-size: 18px; /* Adjust font size */
        }

        .username-label {
            top: 38vh;
        }

        .password-label {
            top: 50vh;
        }

        .username {
            top: 42vh;
        }

        .password {
            top: 54vh;
        }

        .forgotyourpassword-btn {
            color: #686D76;
            text-align: right;
            font-size: 19px;
            font-style: italic;
            font-family: Poppins;
            position: absolute;
            left: 6vw;
            top: 60vh;
            width: 32.5vw;
        }

        .login-btn {
            background-color: #007bff;
            color: white;
            font-size: 25px;
            width: 35vw;
            height: 50px;
            border-radius: 10px;
            text-align: center;
            position: absolute;
            top: 65vh;
            left: 6vw;
        }

        .login-btn:hover {
            background-color: #0056b3;
        }

        .register-btn {
            background-color: #28a745;
            color: white;
            font-size: 25px;
            width: 35vw;
            height: 50px;
            border-radius: 10px;
            text-align: center;
            position: absolute;
            top: 72vh;
            left: 6vw;
        }

        .register-btn:hover {
            background-color: #218838;
        }

        .error {
            color: red;
            text-align: right;
            position: absolute;
            font-size: 18px;
            font-family: Poppins;
            top: 38vh;
            left: 6vw;
            width: 31.5vw;
        }

        .electrotrack {
            color: #FAFBFF;
            text-align: left;
            font-size: 100px;
            font-weight: 600;
            font-family: Poppins;
            position: absolute;
            left: 50vw;
            top: 20vh;
            width: 35vw;
        }

        .hub {
            color: #FAFBFF;
            text-align: left;
            font-size: 100px;
            font-weight: 600;
            font-family: Poppins;
            position: absolute;
            left: 50vw;
            top: 43vh;
            width: 35vw;
        }

        .quote {
            color: #FAFBFF;
            text-align: left;
            font-size: 27px;
            font-style: italic;
            font-family: Poppins;
            position: absolute;
            left: 65vw;
            top: 68vh;
            width: 35vw;
        }

        .author {
            color: #FAFBFF;
            text-align: right;
            font-size: 25px;
            font-family: Poppins;
            position: absolute;
            left: 59vw;
            top: 73vh;
            width: 35vw;
        }
    </style>
</head>
<body>

<div class="loginpage">
    <div class="rectangle45"></div>
    <div class="rectangle1"></div>

    <div class="electrotrack">ElectroTrack</div>
    <div class="hub">Inventory Hub</div>
    <div class="quote">“Don't wait for opportunity, create it.”</div>
    <div class="author">- George Bernard Shaw</div>

    <div class="welcomeback">Welcome Back!</div>

    <?php if (isset($error_message)): ?>
        <p class="error"><?php echo $error_message; ?></p>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <input type="text" name="username" class="form-control username" placeholder="Username" required>
        <input type="password" name="password" class="form-control password" placeholder="Password" required>

        <!-- Corrected forgot password section -->
        <a href="forgot_password.php" class="forgotyourpassword-btn">Forgot your password?</a>

        <button type="submit" class="btn login-btn">Login</button>
        <a href="register.php" class="btn register-btn">Register</a>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
