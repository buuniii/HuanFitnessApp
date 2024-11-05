<?php
// DeleteInfo.php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "user_auth";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables for messages and users (line 17-30 chatgpt part :Skull:)
$successMessage = "";
$errorMessage = "";
$users = [];

// Fetch all users from the database
$sql = "SELECT id, first_name, last_name FROM users";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row; // Store each user in an array
    }
}

// Check if a specific user is to be deleted
if (isset($_POST['delete_user'])) {
    $userId = $_POST['user_id'];
    $deleteSql = "DELETE FROM users WHERE id = ?";
    
     //Execute delete statement
    if ($stmt = $conn->prepare($deleteSql)) {
        $stmt->bind_param("i", $userId);
        
        if ($stmt->execute()) {
            $successMessage = "User deleted successfully!";
            
            // Reset ID sequence
            $resetIdSql = "
                SET @count = 0;
                UPDATE users SET id = @count:= @count + 1;
                ALTER TABLE users AUTO_INCREMENT = 1;
            ";
            
            if ($conn->multi_query($resetIdSql)) {
                do {
                    // Skip results if there are multiple results from the queries
                    if ($result = $conn->store_result()) {
                        $result->free();
                    }
                } while ($conn->more_results() && $conn->next_result());
            } else {
                $errorMessage = "Error resetting ID sequence: " . $conn->error;
            }
            
        } else {
            $errorMessage = "Error deleting user: " . $stmt->error;
        }
        
        $stmt->close();
    } else {
        $errorMessage = "Error preparing statement: " . $conn->error;
    }
}


// Function to delete all users and reset the id
if (isset($_POST['delete_all'])) {
    // Check if there are any users before attempting to delete
    $checkSql = "SELECT COUNT(*) FROM users";
    $result = $conn->query($checkSql);
    $row = $result->fetch_row();
    
    if ($row[0] > 0) {
        // Proceed to delete if records exist
        $truncateSql = "TRUNCATE TABLE users";
        if ($conn->query($truncateSql) === TRUE) {
            $message = "All users deleted successfully and ID reset.";
        } else {
            $errorMessage = "Error deleting users: " . $conn->error;
        }
    } else {
        $errorMessage = "No users to delete.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <style>
        .success {
            color: green;
            font-size: 14px;
        }
        .error {
            color: red;
            font-size: 14px;
        }
    </style>
</head>
<body>

<?php if ($successMessage): ?>
    <div class="success"><?php echo $successMessage; ?></div>
<?php elseif ($errorMessage): ?>
    <div class="error"><?php echo $errorMessage; ?></div>
<?php endif; ?>

<h1><u>Admin Page test v0.1</u></h1>
<form method="POST" action="">
    <label for="user_id">Select a user to delete:</label><br>
    <select name="user_id" required>
        <option value="">--Select User--</option>
        <?php foreach ($users as $user): ?>
            <option value="<?php echo $user['id']; ?>">
                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>
    <button type="submit" name="delete_user">Delete User</button>
</form>
<br>

<!-- Button to go back to the registration form -->
<form action="FormTest.php" method="GET">
    <button type="submit">Back to Registration</button>
</form>

<br>

<!-- Button to delete all users -->
<form method="POST" action="">
    <!-- Display message or error just above the button -->
    <?php if (!empty($message)) echo "<p class='message'>$message</p>"; ?>
    <?php if (!empty($errorMessage)) echo "<p class='error'>$errorMessage</p>"; ?>
    
    <button type="submit" name="delete_all">Delete All Users</button>
</form>
</body>
</html>
