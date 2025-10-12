<?php
// Include the database connection file
include '../controls/connection.php';

// Start session to check if the user is logged in
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user ID from session
$user_id = $_SESSION['user_id'];
$is_logged_in = isset($_SESSION['user_id']);

// Query to fetch user profile information
$sql = "SELECT firstname, middlename, lastname, fb_link, email, location, date_created, contact, 
               COALESCE(is_verified, 0) AS is_verified, profile_picture 
        FROM users 
        WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Fetch user details
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
} else {
    echo "User details not found.";
    exit();
}
$stmt->close();

// Query to fetch jobs posted by the user
$job_sql = "SELECT jobs.job_id, jobs.job_name, user_jobs.job_description
            FROM jobs
            INNER JOIN user_jobs ON jobs.job_id = user_jobs.job_id
            WHERE user_jobs.user_id = ?";
$job_stmt = $conn->prepare($job_sql);
$job_stmt->bind_param("i", $user_id);
$job_stmt->execute();
$job_result = $job_stmt->get_result();
$job_stmt->close();

// Close the database connection
$conn->close();
?>
