<?php
// ProfileTest.php
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

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: LoginTest.php");
    exit();
}

$userId = $_SESSION['user_id'];
$uploadError = "";
$successMessage = "";

// Fetch user information
$userInfo = null;
$stmt = $conn->prepare("SELECT first_name, last_name, email, profile_picture FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $userInfo = $result->fetch_assoc();
} else {
    $uploadError = "User not found.";
}

$stmt->close();

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_picture'])) {
    $targetDir = "Pictures/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true); // Create the directory/folder if it does not exist
    }
    
    $targetFile = $targetDir . basename($_FILES["profile_picture"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    // Check if the file is an image
    $check = getimagesize($_FILES["profile_picture"]["tmp_name"]);
    if ($check === false) {
        $uploadError = "File is not an image.";
        $uploadOk = 0;
    }

    // Check file size (limit to 2MB)
    if ($_FILES["profile_picture"]["size"] > 2000000) {
        $uploadError = "File size should not exceed 2MB.";
        $uploadOk = 0;
    }

    // Allow only certain file formats
    if (!in_array($imageFileType, ["jpg", "jpeg", "png", "gif"])) {
        $uploadError = "Only JPG, JPEG, PNG & GIF files are allowed.";
        $uploadOk = 0;
    }

    // Check if $uploadOk is set to 1
    if ($uploadOk == 1) {
        if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $targetFile)) {
            // Save file path to the database
            $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
            $stmt->bind_param("si", $targetFile, $userId);
            if ($stmt->execute()) {
                $userInfo['profile_picture'] = $targetFile; // Update user info with new profile picture
                $successMessage = "Profile picture uploaded successfully.";
            } else {
                $uploadError = "Failed to save profile picture.";
            }
            $stmt->close();
        } else {
            $uploadError = "Sorry, there was an error uploading your file.";
        }
    }
}

// Handle the remove profile picture action
if (isset($_POST['remove_profile_picture'])) {
    // Set the profile picture to the default path
    $defaultProfilePicturePath = "Pictures/profile.jpg"; // Update with your relative path

    // Update the database to set the profile picture to default
    $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
    $stmt->bind_param("si", $defaultProfilePicturePath, $userId);

    if ($stmt->execute()) {
        $userInfo['profile_picture'] = $defaultProfilePicturePath; // Update user info to reflect the change
        $successMessage = "Profile picture removed successfully.";
    } else {
        $uploadError = "Error updating profile picture: " . $stmt->error;
    }

    $stmt->close();
}

// Handle logout
if (isset($_POST['logout'])) {
    session_destroy(); 
    header("Location: HomepageTest.php");
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .profile-container {
            width: 90%;
            max-width: 600px;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        /* Header and image styling */
        .profile-container h2 {
            margin-top: 0;
            color: #333;
            text-decoration: underline;
        }
        img {
            display: block;
            margin: 0 auto 20px;
            max-width: 120px;
            border-radius: 50%;
            border: 2px solid #ccc;
        }
        /* Error and success message styling */
        .error, .success {
            font-size: 14px;
            margin: 10px 0;
        }
        .error {
            color: #e74c3c;
        }
        .success {
            color: #2ecc71;
        }
        /* Form and button styling */
        form {
            margin: 15px 0;
        }
        input[type="file"] {
            margin-bottom: 10px;
        }
        button {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            color: #fff;
            background-color: #3498db;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .button-container {
            margin-top: 20px;
        }
        #uploadButton {
            display: none; /* Hide the upload button by default */
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <h2>User Profile</h2>

        <!-- Display profile picture -->
        <?php if (isset($userInfo) && $userInfo && isset($userInfo['profile_picture'])): ?>
            <img src="<?php echo htmlspecialchars($userInfo['profile_picture']); ?>" alt="Profile Picture">
        <?php else: ?>
            <p>No profile picture uploaded.</p>
        <?php endif; ?>

        <!-- Display user information -->
        <?php if (isset($userInfo)): ?>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($userInfo['first_name'] . ' ' . $userInfo['last_name']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($userInfo['email']); ?></p>
        <?php else: ?>
            <p>User information not available.</p>
        <?php endif; ?>

        <!-- Success and error messages -->
        <?php if (isset($uploadError)): ?>
            <p class="error"><?php echo $uploadError; ?></p>
        <?php endif; ?>
        <?php if (isset($successMessage)): ?>
            <p class="success"><?php echo $successMessage; ?></p>
        <?php endif; ?>
        
            <!-- Profile picture upload form -->
            <form method="POST" enctype="multipart/form-data">
                <input type="file" name="profile_picture" id="fileInput" required onchange="showUploadButton()">
                <button id="uploadButton" type="submit">Upload Profile Picture</button>
            </form>

            <!-- Show the remove button only if a custom profile picture exists -->
            <?php if (isset($userInfo) && $userInfo['profile_picture'] !== "Pictures/profile.jpg"): ?> <!--Change this if you have a different directory -->
                <form method="POST" style="margin-top: 10px;">
                    <button type="button" onclick="confirmRemovePicture()">Remove Profile Picture</button>
                </form>
            <?php endif; ?>


        <div class="button-container">
            <form method="POST">
                <button type="submit" name="logout">Logout</button>
            </form>
            <form method="POST" action="HomepageTest.php">
                <button type="submit">Back to Homepage</button>
            </form>
        </div>
    </div>

    <script>
        function showUploadButton() {
        const uploadButton = document.getElementById("uploadButton");
        uploadButton.style.display = "block"; // Show the button when a file is selected
    }

    function confirmRemovePicture() {
        const confirmation = confirm("Are you sure you want to remove your profile picture?");
        if (confirmation) {
            // Submit the form to remove the profile picture
            const form = document.createElement("form");
            form.method = "POST";
            form.innerHTML = '<input type="hidden" name="remove_profile_picture" value="1">';
            document.body.appendChild(form);
            form.submit();
        }
    }
    </script>
</body>
</html>
