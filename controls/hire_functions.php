<?php
// hire_functions.php
include 'connection.php';

/**
 * Send a hire request from employer to laborer.
 * @param mysqli $conn
 * @param int $employer_id
 * @param int $laborer_id
 * @param string $message
 * @param string $meeting_location
 * @return string Success or error message
 */
function sendHireRequest($conn, $employer_id, $laborer_id, $message, $meeting_location) {
    // Check if there is already a pending hire for this pair
    $stmt_check = $conn->prepare("SELECT id FROM hires WHERE employer_id=? AND laborer_id=? AND status='pending'");
    $stmt_check->bind_param("ii", $employer_id, $laborer_id);
    $stmt_check->execute();
    $stmt_check->store_result();
    if ($stmt_check->num_rows > 0) {
        $stmt_check->close();
        return "You already have a pending hire request for this laborer.";
    }
    $stmt_check->close();

    // Insert hire request
    $stmt = $conn->prepare("INSERT INTO hires (employer_id, laborer_id, message, meeting_location) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $employer_id, $laborer_id, $message, $meeting_location);

    if ($stmt->execute()) {
        $stmt->close();
        return "Hire request sent successfully!";
    } else {
        $stmt->close();
        return "Failed to send hire request. Please try again.";
    }
}

/**
 * Laborer accepts or declines a hire request
 * @param mysqli $conn
 * @param int $hire_id
 * @param string $action 'accepted' or 'declined'
 * @return string
 */
function respondToHire($conn, $hire_id, $action) {
    $allowed = ['accepted', 'declined'];
    if (!in_array($action, $allowed)) return "Invalid action.";

    $stmt = $conn->prepare("UPDATE hires SET status=? WHERE id=?");
    $stmt->bind_param("si", $action, $hire_id);
    if ($stmt->execute()) {
        $stmt->close();
        return "Hire request " . $action . " successfully.";
    } else {
        $stmt->close();
        return "Failed to update hire status.";
    }
}

/**
 * Fetch hires for a user (employer or laborer) with employer or laborer name
 * @param mysqli $conn
 * @param int $user_id
 * @param string $role 'laborer' or 'employer'
 * @return mysqli_result
 */
function getHiresForUser($conn, $user_id, $role = 'laborer') {
    if ($role === 'laborer') {
        // Fetch hires for laborer and include employer name
        $sql = "SELECT h.*, 
                       u.firstname AS employer_firstname, 
                       u.middlename AS employer_middlename, 
                       u.lastname AS employer_lastname
                FROM hires h
                JOIN users u ON h.employer_id = u.user_id
                WHERE h.laborer_id = ?
                ORDER BY h.created_at DESC";
    } else {
        // Fetch hires for employer and include laborer name
        $sql = "SELECT h.*, 
                       u.firstname AS laborer_firstname, 
                       u.middlename AS laborer_middlename, 
                       u.lastname AS laborer_lastname
                FROM hires h
                JOIN users u ON h.laborer_id = u.user_id
                WHERE h.employer_id = ?
                ORDER BY h.created_at DESC";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result();
}
?>
