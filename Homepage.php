<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "user_auth";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$userInfo = null;
$successMessage = "";
$welcomeMessage = "";

// Check if registration or login success session variable is set
if (isset($_SESSION['registration_successful']) && $_SESSION['registration_successful']) {
    $successMessage = "Registration successful!";
    $_SESSION['new_registration'] = true; // Mark as new registration
    unset($_SESSION['registration_successful']); // Clear the session variable
}

if (isset($_SESSION['login_successful']) && $_SESSION['login_successful']) {
    $successMessage = "Login successful!";
    unset($_SESSION['login_successful']);
}

// Check if user is logged in by checking session
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Fetch user information from the database
    $stmt = $conn->prepare("SELECT first_name, last_name, email, profile_picture FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $userInfo = $result->fetch_assoc();
        $_SESSION['first_name'] = $userInfo['first_name'];
        // Determine the appropriate welcome message
        if (isset($_SESSION['new_registration'])) {
            $welcomeMessage = "Welcome, " . htmlspecialchars($userInfo['first_name']) . "!";
            unset($_SESSION['new_registration']);  // Clear the new registration flag
        } else {
            $welcomeMessage = "Welcome back, " . htmlspecialchars($userInfo['first_name']) . "!";
        }
    } else {
        echo "<p style='color: red; text-align: center;'>User not found.</p>";
    }

    $stmt->close();
} else {
    echo "<p style='text-align: center;'>Please log in to view your information.</p>";
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Homepage</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }

        .header {
            background-color: #333;
            color: #fff;
            padding: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            margin: 0;
        }

        .profile {
            display: flex;
            align-items: center;
            cursor: pointer;
        }

        .profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%; /* Make it circular */
            margin-right: 10px; /* Space between image and name */
        }

        .profile-name {
            color: #fff;
            transition: opacity 0.3s ease;
        }

        .content {
            padding: 20px;
        }

        .success {
            color: #2ecc71;
            font-size: 18px;
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #2ecc71;
            border-radius: 5px;
            background-color: #e9f9e9;
        }
    </style>
    <script>
        function dismissSuccessMessage() {
            const successMessageDiv = document.getElementById("success-message");
            successMessageDiv.style.display = "none";
        }
    </script>
</head>
<body>

<?php if ($successMessage): ?>
    <div class="success" id="success-message">
        <?php echo htmlspecialchars($successMessage); ?>
        <button onclick="dismissSuccessMessage()">OK</button>
    </div>
<?php endif; ?>

<div class="header">
    <h1>Welcome to the Homepage</h1>
    <?php if ($userInfo): ?>
        <div class="profile">
            <a href="ProfileTest.php" style="display: flex; align-items: center; text-decoration: none; color: white;">
                <img src="<?php echo htmlspecialchars($userInfo['profile_picture']); ?>" alt="Profile Picture">
                <span class="profile-name"><?php echo htmlspecialchars($userInfo['first_name'] . ' ' . $userInfo['last_name']); ?></span>
            </a>
        </div>
    <?php else: ?>
        <p><a href="LoginTest.php" style="color: white;">Login</a> or <a href="FormTest.php" style="color: white;">register</a> to view your profile.</p>
    <?php endif; ?>
</div>

<div class="content">
    <h2>Homepage Content</h2>
    <?php if ($userInfo): ?>
        <p><?php echo htmlspecialchars($welcomeMessage); ?></p> <!-- Welcome message based on login or registration -->
        <p>Enjoy your personalized homepage, <?php echo htmlspecialchars($userInfo['first_name']); ?>.</p>
    <?php else: ?>
        <p>Welcome to your homepage! Please <a href="FormTest.php">register</a> or <a href="LoginTest.php">log in</a> to access personalized features.</p>
    <?php endif; ?>
</div>
</body>
</html>
