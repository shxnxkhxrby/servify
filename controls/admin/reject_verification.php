<?php
include '../connection.php';

if (isset($_GET['request_id'])) {
    $request_id = $_GET['request_id'];

    // Update verification request status to rejected
    $update_status = "UPDATE verification_requests SET status = 'rejected' WHERE request_id = ?";
    $stmt = $conn->prepare($update_status);
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $stmt->close();

    $conn->close();
    header("Location: http://localhost/servify/view/barangay_staff_dashboard.php");
}
?>
