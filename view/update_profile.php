<?php
session_start();
include '../../controls/connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Process the uploaded profile picture
if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === 0) {
    $target_dir = "../../uploads/profile_pics/";
    $file_name = "user_" . $user_id . "_" . basename($_FILES["profile_pic"]["name"]);
    $target_file = $target_dir . $file_name;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Validate the file (check if it's an image)
    $check = getimagesize($_FILES["profile_pic"]["tmp_name"]);
    if ($check !== false) {
        // Move the uploaded file to the target directory
        if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file)) {
            // Update the profile picture path in the database
            $sql = "UPDATE users SET profile_picture = ? WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $target_file, $user_id);
            $stmt->execute();
            $stmt->close();

            echo "Profile picture updated successfully!";
        } else {
            echo "Error uploading the file.";
        }
    } else {
        echo "The file is not an image.";
    }
} else {
    echo "No file uploaded or error occurred.";
}

$conn->close();
?>
