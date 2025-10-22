<?php
include '../controls/connection.php';

// Fetch profile details
function getUserProfile($conn, $user_id) {
    $sql = "SELECT firstname, middlename, lastname, fb_link, email, location, date_created, 
                   contact, is_verified, profile_picture 
            FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user;
}

// Fetch services offered by the user
function getUserServices($conn, $user_id, $job_id = 0) {
    if ($job_id === 0) {
        $sql = "SELECT jobs.job_name, user_jobs.job_description, user_jobs.job_image 
                FROM jobs
                INNER JOIN user_jobs ON jobs.job_id = user_jobs.job_id
                WHERE user_jobs.user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
    } else {
        $sql = "SELECT jobs.job_name, user_jobs.job_description, user_jobs.job_image 
                FROM jobs
                INNER JOIN user_jobs ON jobs.job_id = user_jobs.job_id
                WHERE jobs.job_id = ? AND user_jobs.user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $job_id, $user_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    return $result;
}

// Check current user's verification
function isUserVerified($conn, $user_id) {
    $sql = "SELECT is_verified FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return ($res && $res['is_verified'] == 1);
}

// Check if the current user has an accepted hire with the laborer
function hasHireWithLaborer($conn, $laborer_id, $user_id) {
    $sql = "SELECT id 
            FROM hires 
            WHERE laborer_id = ? AND employer_id = ? AND status = 'accepted'
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false; // safety check
    $stmt->bind_param("ii", $laborer_id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();
    return ($res && $res->num_rows > 0);
}


// Handle rating submission
function handleRating($conn, $laborer_id, $user_id, $rating) {
    $check_sql = "SELECT id FROM laborer_ratings WHERE laborer_id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $laborer_id, $user_id);
    $check_stmt->execute();
    $check_res = $check_stmt->get_result();
    $check_stmt->close();

    if ($check_res->num_rows > 0) {
        $sql = "UPDATE laborer_ratings SET rating = ?, created_at = NOW() 
                WHERE laborer_id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $rating, $laborer_id, $user_id);
        $success = $stmt->execute();
        $stmt->close();
        return $success ? "Your rating has been updated." : "Failed to update rating.";
    } else {
        $sql = "INSERT INTO laborer_ratings (laborer_id, user_id, rating) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $laborer_id, $user_id, $rating);
        $success = $stmt->execute();
        $stmt->close();
        return $success ? "Thank you for rating." : "Failed to submit rating.";
    }
}

// Get average rating and total ratings
function getRatingStats($conn, $laborer_id) {
    $sql = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_ratings 
            FROM laborer_ratings WHERE laborer_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $laborer_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return [
        'avg_rating' => $row['avg_rating'] !== null ? round($row['avg_rating'], 1) : 0,
        'total_ratings' => intval($row['total_ratings'])
    ];
}

// Get user’s previous rating
function getUserRating($conn, $laborer_id, $user_id) {
    $sql = "SELECT rating FROM laborer_ratings WHERE laborer_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $laborer_id, $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ? intval($row['rating']) : 0;
}

// Handle report submission
function handleReport($conn, $user_id, $report_reasons, $additional_details) {
    if (empty($report_reasons)) return "No report reasons selected.";
    $status = 'pending';
    $sql = "INSERT INTO reports (user_id, reason, additional_details, status, report_date) 
            VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    foreach ($report_reasons as $reason) {
        $stmt->bind_param("isss", $user_id, $reason, $additional_details, $status);
        if (!$stmt->execute()) {
            $stmt->close();
            return "There was an issue submitting your report. Please try again.";
        }
    }
    $stmt->close();
    return "Your report has been submitted successfully and is awaiting admin review.";
}
?>
