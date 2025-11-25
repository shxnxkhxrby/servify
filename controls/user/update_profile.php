<?php
session_start();
include '../connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve user data
    $firstname = $_POST['firstname'];
    $middlename = $_POST['middlename'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    $contact = $_POST['contact'];
    $fb_link = $_POST['fb_link'];
    $location = $_POST['location'];

    // Handle profile picture upload
    $profile_pic = $_FILES['profile_pic'];
    $profile_pic_path = null;

    // If a profile picture is uploaded, handle the file upload
    if ($profile_pic && $profile_pic['error'] == 0) {
        // Define the absolute path to the target directory
        $target_dir = $_SERVER['DOCUMENT_ROOT'] . "/servify/uploads/profile_pics/";
        $target_file = $target_dir . basename($profile_pic['name']);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Validate file type (allow only JPG, JPEG, PNG)
        $allowed_types = ['jpg', 'jpeg', 'png'];
        if (in_array($imageFileType, $allowed_types)) {
            // Try moving the uploaded file to the target directory
            if (move_uploaded_file($profile_pic['tmp_name'], $target_file)) {
                $profile_pic_path = "uploads/profile_pics/" . basename($profile_pic['name']);
            } else {
                echo "Sorry, there was an error uploading your file.";
            }
        } else {
            echo "Only JPG, JPEG, and PNG files are allowed.";
        }
    } else {
        // If no new profile picture is uploaded, keep the existing one
        $sql = "SELECT profile_picture FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $profile_pic_path = $row['profile_picture'];  // Keep current profile picture
    }

    // Update user details and profile picture in the database
    $update_sql = "UPDATE users SET firstname = ?, middlename = ?, lastname = ?, email = ?, contact = ?, fb_link = ?, location = ?, profile_picture = ? WHERE user_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ssssssssi", $firstname, $middlename, $lastname, $email, $contact, $fb_link, $location, $profile_pic_path, $user_id);
    
    if ($stmt->execute()) {
        header("Location: http://localhost/servify/view/profile.php?update=success");
    } else {
        echo "Error updating profile: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
