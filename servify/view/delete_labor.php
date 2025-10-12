<?php
session_start();
include '../controls/connection.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['job_id'])) {
    header("Location: profile.php");
    exit();
}

$job_id = $_POST['job_id'];
$user_id = $_SESSION['user_id'];

// Delete job from user_jobs
$delete_sql = "DELETE FROM user_jobs WHERE job_id = ? AND user_id = ?";
$stmt = $conn->prepare($delete_sql);
$stmt->bind_param("ii", $job_id, $user_id);

if ($stmt->execute()) {
    header("Location: profile.php");
    exit();
} else {
    echo "Error deleting job.";
}
$stmt->close();
$conn->close();
?>
