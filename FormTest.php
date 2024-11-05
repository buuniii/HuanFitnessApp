<?php
// FormTest.php

session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "user_auth";

$passError = "";
$confirmPassError = ""; 
$generalError = "";
$nameError = "";
$successMessage = "";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Registration form handling
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate names
    if (!preg_match("/^[a-zA-Z\s]+$/", $first_name) || !preg_match("/^[a-zA-Z\s]+$/", $last_name)) {
        $nameError = "First and last names must contain only letters.";
    } elseif (strlen($password) < 8) {
        $passError = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirm_password) {
        $passError = "Passwords do not match!";
    } else {
        // Check if email already exists
        $checkEmailStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $checkEmailStmt->bind_param("s", $email);
        $checkEmailStmt->execute();
        $checkEmailStmt->store_result();

        if ($checkEmailStmt->num_rows > 0) {
            $generalError = "An account with this email already exists.";
        } else {
            // Prepare and bind for insertion with default profile picture
            $defaultProfilePicture = 'Pictures\\profile.jpg'; // Set the path to the default profile picture (change this if you have a different directory)
            $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, profile_picture) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $first_name, $last_name, $email, $password, $defaultProfilePicture);

            if ($stmt->execute()) {
                // Retrieve the newly created user's ID
                $user_id = $stmt->insert_id;

                // Store user ID in session to automatically log them in
                $_SESSION['user_id'] = $user_id;

                $_SESSION['registration_successful'] = true;
                $_SESSION['new_registration'] = true;

                // Redirect to homepage with success message
                header("Location: HomePageTest.php?success=1");
                exit();
            } else {
                $generalError = "Error during registration. Please try again.";
            }

            $stmt->close();
        }

        $checkEmailStmt->close();
    }

    $conn->close();
}
?>




<!DOCTYPE html>
<html>
<head>
    <title>Register Form</title>
    <style>
        /* Body and Form Styles */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .form-container {
            width: 100%;
            max-width: 400px;
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        h1 {
            text-align: center;
            color: #333;
        }
        .form-container form {
            display: flex;
            flex-direction: column;
        }

        /* Input Fields */
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
            box-sizing: border-box;
            font-size: 14px;
        }
        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: #333;
            outline: none;
        }

        /* Error and Success Messages */
        .error {
            color: #e74c3c;
            font-size: 12px;
            margin-top: 5px;
            margin-bottom: 8px;
        }
        .success {
            color: #2ecc71;
            font-size: 14px;
            text-align: center;
            margin-top: 10px;
        }

        /* Show Password Toggle */
        .password-toggle {
            display: flex;
            align-items: center;
            font-size: 12px;
            color: #555;
        }
        .password-toggle input[type="checkbox"] {
            margin-right: 5px;
        }

        /* Buttons */
        button[type="submit"] {
            background-color: #00aaff;
            color: #fff;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 12px 0;
        }
        button[type="submit"]:hover {
            background-color: #008fcc;
        }

        /* Centered Additional Links */
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

        .register-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #00aaff;
            text-decoration: none;
            font-size: 14px;
        }

        .register-link:hover {
            text-decoration: underline;
        }
    </style>

    <script>
        function togglePasswordVisibility(id) {
            var passwordField = document.getElementById(id);
            passwordField.type = (passwordField.type === "password") ? "text" : "password";
        }
    </script>
</head>
<body>
    <div class="form-container">
        <h1><u>Registration Form</u></h1>
        <form method="POST" action="">
            <input type="text" name="first_name" value="<?php echo isset($first_name) ? htmlspecialchars($first_name) : ''; ?>" placeholder="First Name" required>
            <span class="error"><?php echo $nameError; ?></span>

            <input type="text" name="last_name" value="<?php echo isset($last_name) ? htmlspecialchars($last_name) : ''; ?>" placeholder="Last Name" required>
            <span class="error"><?php echo $nameError; ?></span>

            <input type="email" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" placeholder="Email" required>

            <input type="password" id="password" name="password" placeholder="Password" required>
            <div class="password-toggle">
                <input type="checkbox" onclick="togglePasswordVisibility('password')"> Show Password
            </div>
            <span class="error"><?php echo $passError; ?></span>

            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
            <div class="password-toggle">
                <input type="checkbox" onclick="togglePasswordVisibility('confirm_password')"> Show Password
            </div>
            <span class="error"><?php echo $confirmPassError; ?></span>

            <button type="submit" name="register">Register</button>
            <span class="error"><?php echo $generalError; ?></span>
            <span class="success"><?php echo $successMessage; ?></span>
        </form>

        <a href="LoginTest.php" class="register-link">Already have an account? Login here</a>

        <div class="extra-buttons">
            <form method="GET" action="DeleteInfo.php" style="display:inline;">
                <button type="submit">Manage User Info</button>
            </form>
            <form method="GET" action="HomePageTest.php" style="display:inline;">
                <button type="submit">Back to Homepage</button>
            </form>
        </div>
    </div>
</body>
</html>
