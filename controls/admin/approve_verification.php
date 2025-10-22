<?php
include '../connection.php';

if (isset($_GET['request_id'])) {
    $request_id = $_GET['request_id'];

    // Get user_id from verification request
    $user_query = "SELECT user_id FROM verification_requests WHERE request_id = ?";
    $stmt = $conn->prepare($user_query);
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $stmt->bind_result($user_id);
    $stmt->fetch();
    $stmt->close();

    // Update user as verified
    $verify_query = "UPDATE users SET is_verified = 1 WHERE user_id = ?";
    $stmt = $conn->prepare($verify_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    // Update verification request status
    $update_status = "UPDATE verification_requests SET status = 'approved' WHERE request_id = ?";
    $stmt = $conn->prepare($update_status);
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $stmt->close();

    $conn->close();
    header("Location: http://localhost/servify/view/barangay_staff_dashboard.php");
}
?>
