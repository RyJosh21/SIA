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
    <title>Customer Login</title>
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
    </style>
</head>
<body>

<div class="loginpage">
    <div class="rectangle45"></div>
    <div class="rectangle1"></div>

    <div class="welcomeback">Welcome Back!</div>

    <?php if ($error_message): ?>
        <p class="error"><?php echo htmlspecialchars($error_message); ?></p>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <label class="form-label username-label" ></label>
        <input type="text" name="username" class="form-control username" placeholder="Username" required>

        <label class="form-label password-label"></label>
        <input type="password" name="password" class="form-control password" placeholder="Password" required>

        <div class="forgotyourpassword">
            <button type="button" class="btn" onclick="window.location.href='forgot_password.php'">Forgot your password?</button>
        </div>
        
        <button type="submit" class="btn login-btn">Login</button>
    </form>

    <a href="register.php" class="btn register-btn">Register</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
