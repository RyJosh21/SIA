<?php
session_start();  // Start the session

// Database connection
$host = 'localhost';
$dbname = 'inventory_system';
$db_username = 'root';  // Change to your database username
$db_password = '';  // Change to your database password
$conn = new PDO("mysql:host=$host;dbname=$dbname", $db_username, $db_password);

// Login logic
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check if the user exists
    $sql = "SELECT * FROM users WHERE username = :username";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user_data && password_verify($password, $user_data['password'])) {
        // Password is correct, start a session
        $_SESSION['username'] = $user_data['username'];
        $_SESSION['role'] = $user_data['role'];

        // Redirect based on role
        if ($user_data['role'] == 'staff') {
            header('Location: Staff\staff_dashboard.php');
        } else {
            header('Location: index.php');
        }
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
    <title>Login - Inventory System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Custom CSS applied to ensure full-screen coverage */
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

        .inventory {
            color: #fafbff;
            text-align: left;
            font-size: 70px;
            font-family: Poppins;
            position: absolute;
            left: 56vw;
            top: 50vh;
            transform: translateY(-50%);
            width: 35vw;
        }

        .welcomeback {
            color: #031124;
            text-align: left;
            font-size: 40px;
            font-family: Poppins;
            position: absolute;
            left: 6vw;
            top: 30vh;
            width: 35vw;
        }

        .username, .password {
            color: #031124;
            text-align: left;
            font-size: 25px;
            font-family: Poppins;
            position: absolute;
            width: 35vw;
        }

        .username {
            top: 40vh;
            left: 6vw;
        }

        .password {
            top: 50vh;
            left: 6vw;
        }

        .forgotyourpassword {
            color: rgba(0, 0, 0, 0.5);
            text-align: right;
            font-size: 20px;
            font-family: Poppins;
            position: absolute;
            left: 6vw;
            top: 60vh;
            width: 35vw;
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
            text-align: center;
            position: absolute;
            top: 25vh;
            left: 6vw;
            width: 35vw;
        }

        /* ElectroTrack Inventory Hub and Quote */
        .electrotrack {
            color: #fafbff;
            text-align: left;
            font-size: 70px;
            font-family: Poppins;
            position: absolute;
            left: 56vw;
            top: 35vh;
            width: 35vw;
        }

        .hub {
            color: #fafbff;
            text-align: left;
            font-size: 70px;
            font-family: Poppins;
            position: absolute;
            left: 56vw;
            top: 45vh;
            width: 35vw;
        }

        .quote {
            color: #fafbff;
            text-align: left;
            font-size: 27px;
            font-family: Poppins;
            position: absolute;
            left: 56vw;
            top: 60vh;
            width: 35vw;
        }

        .author {
            color: #fafbff;
            text-align: right;
            font-size: 25px;
            font-family: Poppins;
            position: absolute;
            left: 56vw;
            top: 63vh;
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
		<div class="forgotyourpassword">
            <button type="button" class="btn" onclick="window.location.href='forgot_password.php'">Forgot your password?</button>
        </div>
        <button type="submit" class="btn login-btn">Login</button>
        <a href="register.php" class="btn register-btn">Register</a>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>