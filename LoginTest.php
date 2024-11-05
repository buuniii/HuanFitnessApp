<?php
// LoginTest.php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "user_auth";

$loginError = "";
$loginSuccess = "";
$passError ="";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Login form handling
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Prepare and execute query
    $stmt = $conn->prepare("SELECT id, first_name, last_name, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($userId, $firstName, $lastName, $storedPassword);
        $stmt->fetch();

        // Check if the password matches
        if ($password === $storedPassword) {
            // Set session variables
            $_SESSION['user_id'] = $userId;
            $_SESSION['first_name'] = $firstName;
            $_SESSION['last_name'] = $lastName;

             // Set login success message
            $_SESSION['login_successful'] = true;

            // Redirect to homepage
            header("Location: HomePageTest.php");
            exit();
        } else {
            $loginError = "Invalid email or password.";
        }
    } else {
        $loginError = "Email does not exist. Please check your email or register.";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login Form</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .login-container {
            background-color: #fff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        h1 {
            color: #333;
            margin-bottom: 20px;
            font-size: 24px;
        }

        .input-field {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid #ddd;
            font-size: 16px;
            box-sizing: border-box;
        }

        .input-field:focus {
            outline: none;
            border-color: #00aaff;
            box-shadow: 0 0 5px rgba(0, 170, 255, 0.2);
        }

        .toggle-password {
            cursor: pointer;
            font-size: 12px;
            color: #00aaff;
            display: inline-block;
            margin-top: 5px;
        }

        .login-btn {
            width: 100%;
            padding: 10px;
            background-color: #00aaff;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 15px;
            transition: background-color 0.3s;
        }

        .login-btn:hover {
            background-color: #008fcc;
        }

        .error {
            color: red;
            font-size: 12px;
            margin-top: 10px;
        }

        .register-link {
            display: block;
            margin-top: 20px;
            color: #00aaff;
            text-decoration: none;
            font-size: 14px;
        }

        .register-link:hover {
            text-decoration: underline;
        }

        .extra-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }
        .extra-buttons button {
            background-color: #f4f4f9;
            color: #333;
            padding: 8px 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        .extra-buttons button:hover {
            background-color: #ddd;
        }
    </style>

    <script>
        function togglePasswordVisibility() {
            var passwordField = document.getElementById("password");
            passwordField.type = passwordField.type === "password" ? "text" : "password";
        }
    </script>
</head>
<body>
    <div class="login-container">
        <h1>Login to Your Account</h1>

        <form method="POST" action="">
            <input type="email" name="email" class="input-field" placeholder="Email" required>
            <input type="password" id="password" name="password" class="input-field" placeholder="Password" required>
            <div class="password-toggle">
                <input type="checkbox" onclick="togglePasswordVisibility('password')"> Show Password
            </div>
            <span class="error"><?php echo $passError; ?></span>
            <button type="submit" name="login" class="login-btn">Login</button>
            <span class="error"><?php echo $loginError; ?></span>
        </form>

        <a href="FormTest.php" class="register-link">Don’t have an account? Register here</a>

        <div class ="extra-buttons" >
            <form method="GET" action="HomePageTest.php" style="display:inline;">
            <button type="submit">Back to Homepage</button>
        </div>
    </div>
</body>
</html>
