<?php
session_start();
include '../controls/connection.php';
include '../controls/hire_functions.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$is_logged_in = isset($_SESSION['user_id']);

// Fetch user details
$sql = "SELECT firstname, middlename, lastname, fb_link, email, location, date_created, contact, 
               COALESCE(is_verified, 0) AS is_verified, profile_picture, role
        FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
} else {
    echo "User details not found.";
    exit();
}
$stmt->close();

// Count existing jobs for this user
$count_sql = "SELECT COUNT(*) as job_count FROM user_jobs WHERE user_id = ?";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$job_count = $count_result->fetch_assoc()['job_count'];
$count_stmt->close();

// Fetch posted jobs
$job_sql = "SELECT jobs.job_id, jobs.job_name, user_jobs.job_description, user_jobs.job_image
            FROM jobs
            INNER JOIN user_jobs ON jobs.job_id = user_jobs.job_id
            WHERE user_jobs.user_id = ?";
$job_stmt = $conn->prepare($job_sql);
$job_stmt->bind_param("i", $user_id);
$job_stmt->execute();
$job_result = $job_stmt->get_result();
$job_stmt->close();

// Fetch ONLY pending hire requests
$hire_requests_sql = "SELECT h.*, 
                      u1.firstname AS employer_firstname, 
                      u1.middlename AS employer_middlename, 
                      u1.lastname AS employer_lastname,
                      h.created_at, h.status
                      FROM hires h
                      JOIN users u1 ON h.employer_id = u1.user_id
                      WHERE h.laborer_id = ? AND h.status = 'pending'
                      ORDER BY h.created_at DESC";
$hire_requests_stmt = $conn->prepare($hire_requests_sql);
$hire_requests_stmt->bind_param("i", $user_id);
$hire_requests_stmt->execute();
$hire_requests = $hire_requests_stmt->get_result();
$hire_requests_stmt->close();

// Fetch accepted transactions
$accepted_sql = "SELECT h.*, 
                  u1.firstname AS employer_firstname, 
                  u1.middlename AS employer_middlename, 
                  u1.lastname AS employer_lastname,
                  h.created_at, h.status
                  FROM hires h
                  JOIN users u1 ON h.employer_id = u1.user_id
                  WHERE h.laborer_id = ? AND h.status = 'accepted'
                  ORDER BY h.created_at DESC";
$accepted_stmt = $conn->prepare($accepted_sql);
$accepted_stmt->bind_param("i", $user_id);
$accepted_stmt->execute();
$accepted_result = $accepted_stmt->get_result();
$accepted_stmt->close();

// Fetch declined transactions
$declined_sql = "SELECT h.*, 
                  u1.firstname AS employer_firstname, 
                  u1.middlename AS employer_middlename, 
                  u1.lastname AS employer_lastname,
                  h.created_at, h.status
                  FROM hires h
                  JOIN users u1 ON h.employer_id = u1.user_id
                  WHERE h.laborer_id = ? AND h.status = 'declined'
                  ORDER BY h.created_at DESC";
$declined_stmt = $conn->prepare($declined_sql);
$declined_stmt->bind_param("i", $user_id);
$declined_stmt->execute();
$declined_result = $declined_stmt->get_result();
$declined_stmt->close();

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile_picture'])) {
    if (isset($_POST['cropped_image']) && !empty($_POST['cropped_image'])) {
        $cropped_image = $_POST['cropped_image'];
        
        // Remove data:image/jpeg;base64, or similar prefix
        $image_parts = explode(";base64,", $cropped_image);
        $image_base64 = base64_decode($image_parts[1]);
        
        // Create unique filename
        $upload_dir = '../uploads/profile_pics/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $filename = 'profile_' . $user_id . '_' . time() . '.jpg';
        $filepath = $upload_dir . $filename;
        
        // Save the image
        if (file_put_contents($filepath, $image_base64)) {
            // Delete old profile picture if exists
            if (!empty($row['profile_picture']) && file_exists('../' . $row['profile_picture'])) {
                unlink('../' . $row['profile_picture']);
            }
            
            // Update database
            $update_sql = "UPDATE users SET profile_picture = ? WHERE user_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $db_path = 'uploads/profile_pics/' . $filename;
            $update_stmt->bind_param("si", $db_path, $user_id);
            
            if ($update_stmt->execute()) {
                $_SESSION['notification'] = ['type' => 'success', 'message' => 'Profile picture updated successfully!'];
            } else {
                $_SESSION['notification'] = ['type' => 'error', 'message' => 'Failed to update profile picture in database.'];
            }
            $update_stmt->close();
        } else {
            $_SESSION['notification'] = ['type' => 'error', 'message' => 'Failed to save profile picture.'];
        }
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle Accept/Decline action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['respond_hire'])) {
    $hire_id = intval($_POST['hire_id']);
    $action = $_POST['action'] === 'accepted' ? 'accepted' : 'declined';
    $response_msg = respondToHire($conn, $hire_id, $action);
    sendHireResponseEmail($conn, $hire_id, $action);
    $_SESSION['notification'] = ['type' => 'success', 'message' => $response_msg];
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle Add Labor with job limit check
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_labor'])) {
    // Check job count limit
    if ($job_count >= 10) {
        $_SESSION['notification'] = ['type' => 'error', 'message' => 'Maximum job limit reached! You can only post up to 10 jobs.'];
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    
    // Check if image is uploaded
    if (!isset($_FILES['job_image']) || $_FILES['job_image']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['notification'] = ['type' => 'error', 'message' => 'Job image is required!'];
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    
    $job_id = intval($_POST['job_id']);
    $job_description = trim($_POST['job_description']);
    $user_id = $_SESSION['user_id'];
    
    // Handle image upload
    $upload_dir = '../uploads/';
    $file_name = time() . '_' . basename($_FILES['job_image']['name']);
    $target_file = $upload_dir . $file_name;
    
    if (move_uploaded_file($_FILES['job_image']['tmp_name'], $target_file)) {
        $job_image = $file_name;
    } else {
        $_SESSION['notification'] = ['type' => 'error', 'message' => 'Failed to upload image. Please try again.'];
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    
    // Insert into user_jobs table
    $insert_sql = "INSERT INTO user_jobs (user_id, job_id, job_description, job_image) VALUES (?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("iiss", $user_id, $job_id, $job_description, $job_image);
    
    if ($insert_stmt->execute()) {
        $_SESSION['notification'] = ['type' => 'success', 'message' => 'Labor added successfully!'];
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $_SESSION['notification'] = ['type' => 'error', 'message' => 'Error adding labor. Please try again.'];
    }
    $insert_stmt->close();
}

function sendHireResponseEmail($conn, $hire_id, $action) {
    $sql = "SELECT h.employer_id, h.laborer_id, h.meeting_location, h.message,
                u1.firstname AS employer_firstname, u1.lastname AS employer_lastname, u1.email AS employer_email,
                u2.firstname AS laborer_firstname, u2.lastname AS laborer_lastname
            FROM hires h
            JOIN users u1 ON h.employer_id = u1.user_id
            JOIN users u2 ON h.laborer_id = u2.user_id
            WHERE h.id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return;
    $stmt->bind_param("i", $hire_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $employer_name = trim($row['employer_firstname'] . ' ' . $row['employer_lastname']);
        $laborer_name = trim($row['laborer_firstname'] . ' ' . $row['laborer_lastname']);
        $subject = ($action === 'accepted') ? "Your hire request has been accepted!" : "Your hire request has been declined.";
        $status_color = ($action === 'accepted') ? '#28a745' : '#dc3545';
        $body = "<div style='font-family: Arial, sans-serif; background-color:#f8f9fa; padding:20px; border-radius:8px;'>
            <h2 style='color:#007bff;'>Servify Notification</h2>
            <p>Dear <strong>{$employer_name}</strong>,</p>
            <p>Your hire request for <strong>{$laborer_name}</strong> has been 
            <strong style='color:{$status_color};'>".ucfirst($action)."</strong>.</p>
            <p style='margin-top:15px;'>Thank you for using <strong>Servify</strong>.</p>
        </div>";
        sendEmail($row['employer_email'], $subject, $body);
    }
}

function sendEmail($to, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'shanekherby2828@gmail.com';
        $mail->Password = 'gmum gtma drra ffhd';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom('shanekherby2828@gmail.com', 'Servify Notifications');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->send();
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
    }
}

// Fetch available jobs for dropdown
$jobs_dropdown_sql = "SELECT job_id, job_name FROM jobs ORDER BY job_name";
$jobs_dropdown_result = $conn->query($jobs_dropdown_sql);
?>
<?php include '../nav.php' ?>
<?php
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Profile - Servify</title>
  <link rel="stylesheet" href="../styles/profile.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
<style>
:root { --primary-color: #007bff; --success-color: #28a745; --danger-color: #dc3545; --warning-color: #ffc107; --light-bg: #f8f9fa; --card-shadow: 0 2px 12px rgba(0,0,0,0.08); }
body { padding-top: 80px; background-color: var(--light-bg); }
@media (max-width: 768px) { body { padding-top: 70px; } }
.custom-profile-page { max-width: 1400px; margin: 0 auto; padding: 20px; }
.profile-card { background: white; border-radius: 16px; padding: 30px; margin-bottom: 30px; box-shadow: var(--card-shadow); }
.profile-header-wrapper { display: flex; gap: 25px; align-items: flex-start; margin-bottom: 20px; }
.profile-img-wrapper { position: relative; flex-shrink: 0; cursor: pointer; transition: transform 0.3s ease; }
.profile-img-wrapper:hover { transform: scale(1.05); }
.profile-img-wrapper img { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid var(--primary-color); box-shadow: 0 4px 12px rgba(0,123,255,0.2); transition: all 0.3s ease; }
.profile-img-wrapper:hover img { filter: brightness(0.8); }
.profile-img-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border-radius: 50%; background: rgba(0, 123, 255, 0.8); display: flex; flex-direction: column; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s ease; color: white; font-size: 12px; font-weight: 600; }
.profile-img-wrapper:hover .profile-img-overlay { opacity: 1; }
.profile-img-overlay i { font-size: 24px; margin-bottom: 5px; }
.profile-details { flex: 1; min-width: 250px; display: flex; flex-direction: column; }
.profile-name-section { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 12px; min-height: 40px; }
.profile-name-section h2 { margin: 0; font-size: 28px; font-weight: 700; color: #212529; line-height: 1.2; }
.verification-badge { padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; }
.verification-badge.verified { background: #d4edda; color: #155724; }
.verification-badge.not-verified { background: #f8d7da; color: #721c24; }
.profile-meta { color: #6c757d; font-size: 15px; line-height: 1.8; }
.profile-meta p { margin: 6px 0; display: flex; align-items: center; min-height: 27px; }
.profile-meta i { width: 20px; color: var(--primary-color); margin-right: 8px; flex-shrink: 0; }
.profile-contact-info { display: flex; flex-direction: column; gap: 0; align-items: flex-start; justify-content: flex-start; min-width: 280px; margin-top: 50px; }
.profile-contact-info p { margin: 3px 0; display: flex; align-items: center; gap: 8px; font-size: 15px; color: #6c757d; line-height: 1.8; min-height: 27px; }
.profile-contact-info i { color: var(--primary-color); font-size: 16px; width: 20px; flex-shrink: 0; }
.profile-contact-info span { word-break: break-word; text-align: right; }
.profile-edit-button-wrapper { width: 100%; }
.profile-card .btn-add-labor { background: linear-gradient(135deg, var(--primary-color) 0%, #0056b3 100%); color: white; border: none; padding: 14px 28px; border-radius: 10px; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 4px 12px rgba(0,123,255,0.3); transition: all 0.3s ease; cursor: pointer; width: 100%; font-size: 16px; }
.profile-card .btn-add-labor:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,123,255,0.4); color: white; }
.custom-tabs { background: white; border-radius: 16px; padding: 8px; margin-bottom: 25px; box-shadow: var(--card-shadow); display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; }
.tab-button { padding: 14px 12px; border: none; background: transparent; color: #6c757d; font-weight: 600; font-size: 15px; border-radius: 10px; cursor: pointer; transition: all 0.3s ease; white-space: nowrap; display: inline-flex; flex-direction: row; align-items: center; justify-content: center; gap: 6px; }
.tab-button:hover { background: #f8f9fa; color: var(--primary-color); }
.tab-button.active { background: var(--primary-color); color: white; box-shadow: 0 4px 12px rgba(0,123,255,0.3); }
.tab-button i { font-size: 16px; flex-shrink: 0; }
.tab-button span { flex-shrink: 0; }
@media (max-width: 1024px) { .custom-tabs { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 768px) { .custom-tabs { grid-template-columns: repeat(2, 1fr); gap: 6px; padding: 6px; } .tab-button { font-size: 13px; padding: 12px 8px; gap: 4px; } .tab-button i { font-size: 14px; } }
@media (max-width: 480px) { .custom-tabs { grid-template-columns: 1fr; } .tab-button { font-size: 14px; padding: 14px 12px; gap: 6px; } }
.tab-content-wrapper { background: white; border-radius: 16px; padding: 30px; box-shadow: var(--card-shadow); min-height: 400px; }
.tab-pane { display: none; animation: fadeIn 0.3s ease; }
.tab-pane.active { display: block; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.job-card { background: #f8f9fa; border-radius: 12px; padding: 20px; margin-bottom: 15px; border: 1px solid #e9ecef; transition: all 0.3s ease; }
.job-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); transform: translateY(-2px); }
.job-card-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px; }
.job-card-title { font-size: 18px; font-weight: 700; color: #212529; margin: 0; }
.job-card-description { color: #6c757d; margin: 10px 0; line-height: 1.6; }
.job-image { width: 100%; max-width: 200px; height: 150px; object-fit: cover; border-radius: 8px; margin-top: 12px; }
.hire-card { background: #f8f9fa; border-radius: 12px; padding: 24px; margin-bottom: 15px; border: 1px solid #e9ecef; transition: all 0.3s ease; }
.hire-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); transform: translateY(-2px); }
.hire-card-pending { border-left: 4px solid var(--warning-color); }
.hire-card-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 16px; }
.hire-card-title { font-size: 18px; font-weight: 700; margin: 0; color: #212529; }
.hire-card-info { background: #ffffff; padding: 15px; border-radius: 8px; margin: 12px 0; border: 1px solid #e9ecef; }
.hire-card-info p { margin: 8px 0; color: #495057; }
.hire-card-info strong { color: #212529; }
.hire-card-actions { display: flex; gap: 10px; margin-top: 16px; }
.hire-card-actions .btn { flex: 1; font-weight: 600; }
.transaction-card { border-left: 4px solid; padding: 20px; margin-bottom: 15px; background: #f8f9fa; border-radius: 8px; }
.transaction-card.accepted { border-left-color: var(--success-color); }
.transaction-card.declined { border-left-color: var(--danger-color); }
.transaction-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 10px; }
.transaction-status { padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: 600; }
.transaction-status.accepted { background: #d4edda; color: #155724; }
.transaction-status.declined { background: #f8d7da; color: #721c24; }
.empty-state { text-align: center; padding: 60px 20px; color: #6c757d; }
.empty-state i { font-size: 64px; margin-bottom: 20px; opacity: 0.3; }
.empty-state h4 { margin-bottom: 10px; color: #495057; }
.jobs-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; gap: 15px; flex-wrap: wrap; }
.jobs-header h4 { margin: 0; font-size: 24px; font-weight: 700; color: #212529; display: flex; align-items: center; gap: 10px; }
.jobs-header h4 i { color: var(--primary-color); }
.jobs-header-left { display: flex; align-items: center; gap: 12px; }
.jobs-header-right { display: flex; align-items: center; gap: 10px; }
.btn-add-labor-compact { width: 42px; height: 42px; border-radius: 50%; background: linear-gradient(135deg, var(--primary-color) 0%, #0056b3 100%); color: white; border: none; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,123,255,0.3); transition: all 0.3s ease; cursor: pointer; font-size: 20px; flex-shrink: 0; }
.btn-add-labor-compact:hover { transform: translateY(-2px) scale(1.05); box-shadow: 0 6px 16px rgba(0,123,255,0.4); }
.btn-add-labor-compact:disabled { opacity: 0.6; cursor: not-allowed; background: #6c757d; }
.btn-add-labor-compact:disabled:hover { transform: none; }
.help-button { width: 28px; height: 28px; border-radius: 50%; background: #f8f9fa; color: #6c757d; border: 2px solid #e9ecef; display: inline-flex; align-items: center; justify-content: center; cursor: help; font-size: 14px; font-weight: 700; transition: all 0.3s ease; position: relative; flex-shrink: 0; }
.help-button:hover { background: var(--primary-color); color: white; border-color: var(--primary-color); transform: scale(1.1); }
.help-tooltip { position: absolute; bottom: 100%; right: 0; margin-bottom: 10px; padding: 15px; background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); width: 320px; opacity: 0; visibility: hidden; transition: all 0.3s ease; z-index: 1000; border: 2px solid var(--primary-color); }
.help-button:hover .help-tooltip { opacity: 1; visibility: visible; transform: translateY(-5px); }
.help-tooltip::after { content: ''; position: absolute; top: 100%; right: 20px; border: 8px solid transparent; border-top-color: var(--primary-color); }
.help-tooltip h6 { margin: 0 0 12px 0; color: var(--primary-color); font-weight: 700; font-size: 15px; display: flex; align-items: center; gap: 8px; }
.help-tooltip ul { margin: 0; padding-left: 20px; list-style: none; }
.help-tooltip li { margin: 8px 0; color: #495057; font-size: 13px; line-height: 1.5; position: relative; padding-left: 8px; }
.help-tooltip li::before { content: '•'; position: absolute; left: -8px; color: var(--primary-color); font-weight: bold; }
@media (max-width: 992px) { .profile-header-wrapper { flex-wrap: wrap; } .profile-contact-info { width: 100%; align-items: flex-start; justify-content: flex-start; padding: 15px 0 0 0; margin-top: 0; border-top: 1px solid #e9ecef; } .profile-contact-info span { text-align: left; } }
@media (max-width: 768px) { .custom-profile-page { padding: 15px; } .profile-card { padding: 20px; } .profile-header-wrapper { flex-direction: column; align-items: center; text-align: center; } .profile-name-section { justify-content: center; flex-direction: column; } .profile-name-section h2 { font-size: 24px; } .profile-img-wrapper img { width: 130px; height: 130px; } .profile-details { width: 100%; } .profile-meta { text-align: center; } .profile-meta p { justify-content: center; } .profile-contact-info { align-items: center; width: 100%; margin-top: 0; } .profile-contact-info p { justify-content: center; } .profile-contact-info span { text-align: center; } .profile-card .btn-add-labor { width: 100%; justify-content: center; } .tab-content-wrapper { padding: 20px 15px; } .job-card, .hire-card { padding: 16px; } .jobs-header { flex-direction: column; align-items: stretch; } .jobs-header h4 { font-size: 20px; justify-content: center; } .help-tooltip { width: 280px; right: 50%; transform: translateX(50%); } .help-button:hover .help-tooltip { transform: translateX(50%) translateY(-5px); } .help-tooltip::after { right: 50%; transform: translateX(50%); } }
@media (max-width: 480px) { .profile-name-section h2 { font-size: 20px; } .profile-contact-info p { font-size: 14px; } .profile-meta { font-size: 14px; } .profile-card .btn-add-labor { font-size: 14px; padding: 12px; } .help-tooltip { width: 260px; } }
.btn-add-labor { background: linear-gradient(135deg, var(--primary-color) 0%, #0056b3 100%); color: white; border: none; padding: 14px 28px; border-radius: 10px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(0,123,255,0.3); transition: all 0.3s ease; cursor: pointer; }
.btn-add-labor:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,123,255,0.4); color: white; }
.contact-info-card { background: white; border: 1px solid #e9ecef; color: #495057; padding: 24px; border-radius: 12px; margin-bottom: 20px; box-shadow: var(--card-shadow); }
.contact-info-card h4 { margin-bottom: 16px; font-weight: 700; color: #212529; }
.contact-info-card p { margin: 8px 0; display: flex; align-items: center; gap: 10px; }
.contact-info-card i { color: var(--primary-color); width: 20px; }
.contact-info-card a { color: var(--primary-color); text-decoration: none; }
.contact-info-card a:hover { text-decoration: underline; }
.crop-container { max-width: 100%; max-height: 400px; margin: 20px auto; overflow: hidden; position: relative; }
.crop-container img { max-width: 100%; }
.crop-controls { display: flex; gap: 10px; justify-content: center; margin-top: 15px; flex-wrap: wrap; }
.toast-notification { position: fixed; top: 100px; right: 20px; background: white; padding: 20px 24px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); z-index: 9999; display: none; min-width: 300px; animation: slideIn 0.3s ease; }
.toast-notification.show { display: flex; align-items: center; gap: 12px; }
.toast-notification.success { border-left: 4px solid var(--success-color); }
.toast-notification.error { border-left: 4px solid var(--danger-color); }
.toast-notification i { font-size: 24px; }
.toast-notification.success i { color: var(--success-color); }
.toast-notification.error i { color: var(--danger-color); }
@keyframes slideIn { from { transform: translateX(400px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
@media (max-width: 768px) { .toast-notification { top: 80px; right: 10px; left: 10px; min-width: auto; } }
.job-count-badge { background: #e9ecef; color: #495057; padding: 6px 14px; border-radius: 20px; font-size: 14px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
</style>
</head>

<body>

<!-- Toast Notification -->
<?php if (isset($_SESSION['notification'])): ?>
<div class="toast-notification <?php echo $_SESSION['notification']['type']; ?>" id="toast">
  <i class="bi bi-<?php echo $_SESSION['notification']['type'] === 'success' ? 'check-circle-fill' : 'x-circle-fill'; ?>"></i>
  <div>
    <strong><?php echo $_SESSION['notification']['type'] === 'success' ? 'Success!' : 'Error!'; ?></strong>
    <p class="mb-0"><?php echo htmlspecialchars($_SESSION['notification']['message']); ?></p>
  </div>
</div>
<?php unset($_SESSION['notification']); endif; ?>

<!-- PROFILE PAGE -->
<div class="custom-profile-page">
 <!-- Profile Card -->
<div class="profile-card">
  <div class="profile-header-wrapper">
    <div class="profile-img-wrapper" onclick="document.getElementById('profile_pic_input').click()">
      <img src="../<?php echo htmlspecialchars($row['profile_picture']) ?: 'uploads/profile_pics/default.jpg'; ?>" alt="Profile Picture" id="currentProfilePic">
      <div class="profile-img-overlay">
        <i class="bi bi-camera-fill"></i>
        <span>Change Photo</span>
      </div>
    </div>
    
    <div class="profile-details">
      <div class="profile-name-section">
        <h2><?php echo htmlspecialchars($row['firstname'] . ' ' . $row['middlename'] . ' ' . $row['lastname']); ?></h2>
        <span class="verification-badge <?php echo $row['is_verified'] ? 'verified' : 'not-verified'; ?>">
          <i class="bi bi-<?php echo $row['is_verified'] ? 'check-circle-fill' : 'x-circle-fill'; ?>"></i>
          <?php echo $row['is_verified'] ? 'Verified' : 'Not Verified'; ?>
        </span>
      </div>
      
      <div class="profile-meta">
        <p><i class="bi bi-geo-alt-fill"></i> <?php echo htmlspecialchars($row['location']); ?></p>
        <p><i class="bi bi-calendar3"></i> Member since <?php echo date('F j, Y', strtotime($row['date_created'])); ?></p>
        <p><i class="bi bi-briefcase-fill"></i> <?php echo ucfirst(htmlspecialchars($row['role'])); ?></p>
      </div>
    </div>

    <!-- Contact Info on Right Side (for both laborer and client) -->
    <div class="profile-contact-info">
      <p><i class="bi bi-envelope-fill"></i> <span><?php echo htmlspecialchars($row['email']); ?></span></p>
      <p><i class="bi bi-telephone-fill"></i> <span><?php echo htmlspecialchars($row['contact']); ?></span></p>
      <?php if (!empty($row['fb_link'])): ?>
      <p><i class="bi bi-facebook"></i> <span><a href="<?php echo htmlspecialchars($row['fb_link']); ?>" target="_blank">View Profile</a></span></p>
      <?php endif; ?>
    </div>
  </div>

  <div class="profile-edit-button-wrapper">
    <button type="button" class="btn-add-labor" data-bs-toggle="modal" data-bs-target="#editDetailsModal">
      <i class="bi bi-pencil-square"></i> Edit Profile
    </button>
  </div>
</div>

  <!-- Tab Navigation -->
  <div class="custom-tabs">
    <?php if ($row['role'] === 'laborer'): ?>
    <button class="tab-button active" onclick="switchTab('jobs')">
      <i class="bi bi-briefcase"></i>
      <span>Posted Jobs</span>
    </button>
    <?php endif; ?>
    
    <button class="tab-button <?php echo ($row['role'] === 'client') ? 'active' : ''; ?>" onclick="switchTab('requests')">
      <i class="bi bi-bell"></i>
      <span>Hire Requests</span>
    </button>
    <button class="tab-button" onclick="switchTab('accepted')">
      <i class="bi bi-check-circle"></i>
      <span>Accepted</span>
    </button>
    <button class="tab-button" onclick="switchTab('declined')">
      <i class="bi bi-x-circle"></i>
      <span>Declined</span>
    </button>
    <button class="tab-button" onclick="switchTab('verification')">
      <i class="bi bi-shield-check"></i>
      <span>Verification</span>
    </button>
  </div>

  <!-- Tab Content -->
  <div class="tab-content-wrapper">
    <!-- Posted Jobs Tab (LABORER ONLY) -->
    <?php if ($row['role'] === 'laborer'): ?>
    <div id="jobs-tab" class="tab-pane active">
      <div class="jobs-header">
        <div class="jobs-header-left">
          <h4>
            <i class="bi bi-briefcase-fill"></i> My Posted Jobs
          </h4>
          <span class="job-count-badge">
            <i class="bi bi-list-check"></i>
            <?php echo $job_count; ?>/10
          </span>
          <button type="button" 
                  class="btn-add-labor-compact" 
                  data-bs-toggle="modal" 
                  data-bs-target="#addLaborModal" 
                  <?php echo ($job_count >= 10) ? 'disabled title="Maximum job limit reached (10/10)"' : 'title="Add new labor"'; ?>>
            <i class="bi bi-plus"></i>
          </button>
        </div>
        <div class="jobs-header-right">
          <div class="help-button">
            ?
            <div class="help-tooltip">
              <h6><i class="bi bi-lightbulb-fill"></i> Profile Guide</h6>
              <ul>
                <li><strong>Add Jobs:</strong> Click the <i class="bi bi-plus-circle"></i> button to post your services (max 10)</li>
                <li><strong>Edit Profile:</strong> Click "Edit Profile" to update your information</li>
                <li><strong>Change Photo:</strong> Click your profile picture to upload a new one</li>
                <li><strong>Hire Requests:</strong> Check the "Hire Requests" tab to see new job opportunities</li>
                <li><strong>Verification:</strong> Upload required documents in the "Verification" tab to gain trust</li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <?php if ($job_result->num_rows > 0): ?>
        <?php while ($job = $job_result->fetch_assoc()): ?>
          <div class="job-card">
            <div class="job-card-header">
              <h5 class="job-card-title"><?php echo htmlspecialchars($job['job_name']); ?></h5>
              <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                  <i class="bi bi-three-dots-vertical"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li>
                    <button type="button" class="dropdown-item text-warning" onclick="openEditJob(<?php echo $job['job_id']; ?>)">
                      <i class="bi bi-pencil"></i> Edit
                    </button>
                  </li>
                  <li>
                    <button type="submit" form="deleteJobForm<?php echo $job['job_id']; ?>" class="dropdown-item text-danger">
                      <i class="bi bi-trash"></i> Delete
                    </button>
                    <form id="deleteJobForm<?php echo $job['job_id']; ?>" action="delete_labor.php" method="POST" class="d-none">
                      <input type="hidden" name="job_id" value="<?php echo $job['job_id']; ?>">
                    </form>
                  </li>
                </ul>
              </div>
            </div>
            <p class="job-card-description"><?php echo htmlspecialchars($job['job_description']); ?></p>
            <?php if (!empty($job['job_image'])): ?>
              <img src="http://localhost/servify/uploads/<?php echo htmlspecialchars($job['job_image']); ?>" alt="Job Image" class="job-image">
            <?php endif; ?>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="empty-state">
          <i class="bi bi-briefcase"></i>
          <h4>No jobs posted yet</h4>
          <p>Start by adding your first labor service!</p>
        </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Hire Requests Tab -->
    <div id="requests-tab" class="tab-pane <?php echo ($row['role'] === 'client') ? 'active' : ''; ?>">
      <h4 class="mb-4"><i class="bi bi-bell-fill"></i> Pending Hire Requests</h4>
      
      <?php if ($hire_requests->num_rows > 0): ?>
        <?php while ($hire = $hire_requests->fetch_assoc()): ?>
          <div class="hire-card hire-card-pending">
            <div class="hire-card-header">
              <h5 class="hire-card-title">
                <i class="bi bi-person-circle"></i>
                <?php echo htmlspecialchars($hire['employer_firstname'] . ' ' . $hire['employer_middlename'] . ' ' . $hire['employer_lastname']); ?>
              </h5>
            </div>
            
            <div class="hire-card-info">
              <p><strong><i class="bi bi-chat-left-text"></i> Message:</strong><br><?php echo htmlspecialchars($hire['message']); ?></p>
              <p><strong><i class="bi bi-geo-alt"></i> Meeting Location:</strong><br><?php echo htmlspecialchars($hire['meeting_location']); ?></p>
              <p><strong><i class="bi bi-calendar3"></i> Requested:</strong> <?php echo date('F j, Y g:i A', strtotime($hire['created_at'])); ?></p>
            </div>

            <div class="hire-card-actions">
              <form action="" method="POST" class="w-100">
                <input type="hidden" name="hire_id" value="<?php echo $hire['id']; ?>">
                <input type="hidden" name="action" value="accepted">
                <button type="submit" name="respond_hire" class="btn btn-success">
                  <i class="bi bi-check-circle"></i> Accept
                </button>
              </form>
              <form action="" method="POST" class="w-100">
                <input type="hidden" name="hire_id" value="<?php echo $hire['id']; ?>">
                <input type="hidden" name="action" value="declined">
                <button type="submit" name="respond_hire" class="btn btn-danger">
                  <i class="bi bi-x-circle"></i> Decline
                </button>
              </form>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="empty-state">
          <i class="bi bi-bell"></i>
          <h4>No pending hire requests</h4>
          <p>You'll see new hire requests here when employers reach out.</p>
        </div>
      <?php endif; ?>
    </div>

    <!-- Accepted Transactions Tab -->
    <div id="accepted-tab" class="tab-pane">
      <h4 class="mb-4"><i class="bi bi-check-circle-fill"></i> Accepted Transactions</h4>
      
      <?php if ($accepted_result->num_rows > 0): ?>
        <?php while ($transaction = $accepted_result->fetch_assoc()): ?>
          <div class="transaction-card accepted">
            <div class="transaction-header">
              <div>
                <h5 class="mb-1">
                  <i class="bi bi-person-circle"></i>
                  <?php echo htmlspecialchars($transaction['employer_firstname'] . ' ' . $transaction['employer_middlename'] . ' ' . $transaction['employer_lastname']); ?>
                </h5>
                <small class="text-muted">
                  <i class="bi bi-calendar3"></i> 
                  <?php echo date('F j, Y g:i A', strtotime($transaction['created_at'])); ?>
                </small>
              </div>
              <span class="transaction-status accepted">
                <i class="bi bi-check-circle-fill"></i>
                Accepted
              </span>
            </div>
            
            <div class="mt-3">
              <p class="mb-2"><strong><i class="bi bi-chat-left-text"></i> Message:</strong></p>
              <p class="text-muted"><?php echo htmlspecialchars($transaction['message']); ?></p>
              
              <p class="mb-1 mt-3"><strong><i class="bi bi-geo-alt"></i> Meeting Location:</strong></p>
              <p class="text-muted"><?php echo htmlspecialchars($transaction['meeting_location']); ?></p>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="empty-state">
          <i class="bi bi-check-circle"></i>
          <h4>No accepted transactions</h4>
          <p>Accepted hire requests will appear here.</p>
        </div>
      <?php endif; ?>
    </div>

    <!-- Declined Transactions Tab -->
    <div id="declined-tab" class="tab-pane">
      <h4 class="mb-4"><i class="bi bi-x-circle-fill"></i> Declined Transactions</h4>
      
      <?php if ($declined_result->num_rows > 0): ?>
        <?php while ($transaction = $declined_result->fetch_assoc()): ?>
          <div class="transaction-card declined">
            <div class="transaction-header">
              <div>
                <h5 class="mb-1">
                  <i class="bi bi-person-circle"></i>
                  <?php echo htmlspecialchars($transaction['employer_firstname'] . ' ' . $transaction['employer_middlename'] . ' ' . $transaction['employer_lastname']); ?>
                </h5>
                <small class="text-muted">
                  <i class="bi bi-calendar3"></i> 
                  <?php echo date('F j, Y g:i A', strtotime($transaction['created_at'])); ?>
                </small>
              </div>
              <span class="transaction-status declined">
                <i class="bi bi-x-circle-fill"></i>
                Declined
              </span>
            </div>
            
            <div class="mt-3">
              <p class="mb-2"><strong><i class="bi bi-chat-left-text"></i> Message:</strong></p>
              <p class="text-muted"><?php echo htmlspecialchars($transaction['message']); ?></p>
              
              <p class="mb-1 mt-3"><strong><i class="bi bi-geo-alt"></i> Meeting Location:</strong></p>
              <p class="text-muted"><?php echo htmlspecialchars($transaction['meeting_location']); ?></p>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="empty-state">
          <i class="bi bi-x-circle"></i>
          <h4>No declined transactions</h4>
          <p>Declined hire requests will appear here.</p>
        </div>
      <?php endif; ?>
    </div>

    <!-- Verification Tab -->
    <div id="verification-tab" class="tab-pane">
      <h4 class="mb-4"><i class="bi bi-shield-check"></i> Account Verification</h4>
      
      <div class="alert alert-<?php echo $row['is_verified'] ? 'success' : 'warning'; ?> d-flex align-items-center">
        <i class="bi bi-<?php echo $row['is_verified'] ? 'check-circle-fill' : 'exclamation-triangle-fill'; ?> fs-4 me-3"></i>
        <div>
          <strong>Verification Status:</strong> 
          <?php echo ($row['is_verified'] == 1) ? 'Your account is verified' : 'Your account is not yet verified'; ?>
        </div>
      </div>

      <?php if ($row['is_verified'] == 0): ?>
        <div class="mt-4">
          <h5 class="mb-3">Upload Verification Documents</h5>
          <p class="text-muted mb-4">Please upload the required documents to verify your account and gain more trust from <?php echo ($row['role'] === 'laborer') ? 'employers' : 'laborers'; ?>.</p>
          
          <form action="../controls/user/upload_verification.php" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
              <label for="id_proof" class="form-label">
                <strong>Primary ID</strong> <?php echo ($row['role'] === 'laborer') ? '(Barangay ID if laborer)' : '(Valid Government ID)'; ?>
              </label>
              <input type="file" name="id_proof" id="id_proof" class="form-control" required>
            </div>
                
            <div class="mb-4">
              <label for="supporting_doc" class="form-label">
                <strong>Supporting Document</strong> (Birth Certificate, Government ID, etc.)
              </label>
              <input type="file" name="supporting_doc" id="supporting_doc" class="form-control" required>
            </div>
            
            <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
            <button type="submit" class="btn btn-success">
              <i class="bi bi-upload"></i> Upload Documents
            </button>
          </form>
        </div>
      <?php else: ?>
        <div class="text-center py-5">
          <i class="bi bi-check-circle-fill text-success" style="font-size: 64px;"></i>
          <h4 class="mt-3">All Set!</h4>
          <p class="text-muted">Your account has been verified. You can now enjoy full access to all features.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>




<!-- Add Labor Modal -->
<div class="modal fade" id="addLaborModal" tabindex="-1" aria-labelledby="addLaborLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addLaborLabel"><i class="bi bi-plus-circle"></i> Add New Labor</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="" method="POST" enctype="multipart/form-data">
        <div class="modal-body">
          <div class="mb-3">
            <label for="job_id" class="form-label"><strong>Select Job Category:</strong></label>
            <select name="job_id" id="job_id" class="form-select" required>
              <option value="">-- Choose a job --</option>
              <?php 
              if ($jobs_dropdown_result && $jobs_dropdown_result->num_rows > 0):
                while ($job_option = $jobs_dropdown_result->fetch_assoc()): 
              ?>
                <option value="<?php echo $job_option['job_id']; ?>">
                  <?php echo htmlspecialchars($job_option['job_name']); ?>
                </option>
              <?php 
                endwhile;
              endif;
              ?>
            </select>
          </div>

          <div class="mb-3">
            <label for="job_description" class="form-label"><strong>Job Description:</strong></label>
            <textarea name="job_description" id="job_description" class="form-control" rows="5" placeholder="Describe your service, experience, rates, etc." required></textarea>
            <small class="text-muted">Provide details about your service to attract more clients.</small>
          </div>

          <div class="mb-3">
            <label for="job_image" class="form-label"><strong>Job Image (Required):</strong></label>
            <input type="file" name="job_image" id="job_image" class="form-control" accept="image/*" required>
            <small class="text-muted">Upload an image showcasing your work or service.</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="add_labor" class="btn btn-success">
            <i class="bi bi-check-circle"></i> Add Labor
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Details Modal -->
<div class="modal fade" id="editDetailsModal" tabindex="-1" aria-labelledby="editDetailsLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editDetailsLabel"><i class="bi bi-pencil-square"></i> Edit Profile Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="../controls/user/update_profile.php" method="POST">
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label>First Name:</label>
              <input type="text" name="firstname" value="<?php echo htmlspecialchars($row['firstname']); ?>" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
              <label>Middle Name:</label>
              <input type="text" name="middlename" value="<?php echo htmlspecialchars($row['middlename']); ?>" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
              <label>Last Name:</label>
              <input type="text" name="lastname" value="<?php echo htmlspecialchars($row['lastname']); ?>" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
              <label>Email:</label>
              <input type="email" name="email" value="<?php echo htmlspecialchars($row['email']); ?>" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
              <label>Contact:</label>
              <input type="text" name="contact" value="<?php echo htmlspecialchars($row['contact']); ?>" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
              <label>Facebook Link:</label>
              <input type="url" name="fb_link" id="fb_link" value="<?php echo htmlspecialchars($row['fb_link']); ?>" class="form-control" pattern="https?://(www\.)?(facebook|fb)\.com/.*" title="Please enter a valid Facebook URL (e.g., https://facebook.com/yourprofile)">
              <small class="text-muted">Must be a valid Facebook URL</small>
            </div>
            <div class="col-md-12 mb-3">
              <label>Location:</label>
              <input type="text" name="location" value="<?php echo htmlspecialchars($row['location']); ?>" class="form-control" required>
            </div>
          </div>
          <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">
            <i class="bi bi-check-circle"></i> Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Image Crop Modal -->
<div class="modal fade" id="cropModal" tabindex="-1" aria-labelledby="cropModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="cropModalLabel"><i class="bi bi-crop"></i> Crop Profile Picture</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="cancelCrop()"></button>
      </div>
      <div class="modal-body">
        <div class="crop-container">
          <img id="cropImage" style="max-width: 100%;">
        </div>
        <div class="crop-controls">
          <button type="button" class="btn btn-sm btn-outline-secondary" onclick="cropper.zoom(0.1)">
            <i class="bi bi-zoom-in"></i> Zoom In
          </button>
          <button type="button" class="btn btn-sm btn-outline-secondary" onclick="cropper.zoom(-0.1)">
            <i class="bi bi-zoom-out"></i> Zoom Out
          </button>
          <button type="button" class="btn btn-sm btn-outline-secondary" onclick="cropper.rotate(90)">
            <i class="bi bi-arrow-clockwise"></i> Rotate
          </button>
          <button type="button" class="btn btn-sm btn-outline-secondary" onclick="cropper.reset()">
            <i class="bi bi-arrow-counterclockwise"></i> Reset
          </button>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="cancelCrop()">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="cropAndSave()">
          <i class="bi bi-check-circle"></i> Crop & Save
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Edit Job Modal -->
<div class="modal fade" id="editJobModal" tabindex="-1" aria-labelledby="editJobLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editJobLabel"><i class="bi bi-pencil-square"></i> Edit Labor</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="editJobContent">
        <div class="text-center py-5 text-muted">Loading...</div>
      </div>
    </div>
  </div>
</div>

<!-- Hidden file input for profile picture -->
<input type="file" id="profile_pic_input" style="display: none;" accept="image/*">

<script>
function switchTab(tabName) {
  document.querySelectorAll('.tab-pane').forEach(pane => {
    pane.classList.remove('active');
  });
  
  document.querySelectorAll('.tab-button').forEach(btn => {
    btn.classList.remove('active');
  });
  
  document.getElementById(tabName + '-tab').classList.add('active');
  
  event.target.closest('.tab-button').classList.add('active');
}

let cropper = null;
let cropModalInstance = null;

// Profile picture upload and crop
document.getElementById('profile_pic_input').addEventListener('change', function(e) {
  const file = e.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = function(event) {
      const cropImage = document.getElementById('cropImage');
      cropImage.src = event.target.result;
      
      cropModalInstance = new bootstrap.Modal(document.getElementById('cropModal'), {
        backdrop: 'static',
        keyboard: false
      });
      cropModalInstance.show();
      
      document.getElementById('cropModal').addEventListener('shown.bs.modal', function initializeCropper() {
        if (cropper) {
          cropper.destroy();
        }
        cropper = new Cropper(cropImage, {
          aspectRatio: 1,
          viewMode: 2,
          minCropBoxWidth: 100,
          minCropBoxHeight: 100,
          autoCropArea: 1,
          responsive: true,
          guides: true,
          center: true,
          highlight: true,
          cropBoxResizable: true,
          toggleDragModeOnDblclick: false,
        });
        document.getElementById('cropModal').removeEventListener('shown.bs.modal', initializeCropper);
      });
    };
    reader.readAsDataURL(file);
  }
});

function cancelCrop() {
  if (cropper) {
    cropper.destroy();
    cropper = null;
  }
  
  document.getElementById('profile_pic_input').value = '';
  document.getElementById('cropImage').src = '';
  
  if (cropModalInstance) {
    cropModalInstance.hide();
  }
  
  setTimeout(() => {
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => backdrop.remove());
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
  }, 300);
}

function cropAndSave() {
  if (cropper) {
    const canvas = cropper.getCroppedCanvas({
      width: 400,
      height: 400,
      imageSmoothingEnabled: true,
      imageSmoothingQuality: 'high',
    });
    
    canvas.toBlob(function(blob) {
      const reader = new FileReader();
      reader.onloadend = function() {
        const croppedImageData = reader.result;
        
        // Update profile picture preview immediately
        document.getElementById('currentProfilePic').src = croppedImageData;
        
        // Send to server via AJAX
        const formData = new FormData();
        formData.append('cropped_image', croppedImageData);
        formData.append('update_profile_picture', '1');
        
        fetch('', {
          method: 'POST',
          body: formData
        })
        .then(response => response.text())
        .then(data => {
          // Reload page to show updated profile picture
          location.reload();
        })
        .catch(error => {
          console.error('Error uploading profile picture:', error);
          alert('Failed to upload profile picture. Please try again.');
        });
        
        // Clean up cropper
        if (cropper) {
          cropper.destroy();
          cropper = null;
        }
        
        if (cropModalInstance) {
          cropModalInstance.hide();
        }
        
        setTimeout(function() {
          const backdrops = document.querySelectorAll('.modal-backdrop');
          backdrops.forEach(backdrop => backdrop.remove());
          document.body.classList.remove('modal-open');
          document.body.style.overflow = '';
          document.body.style.paddingRight = '';
        }, 300);
      };
      reader.readAsDataURL(blob);
    }, 'image/jpeg', 0.9);
  }
}

document.getElementById('cropModal').addEventListener('hidden.bs.modal', function() {
  if (cropper) {
    cropper.destroy();
    cropper = null;
  }
  document.getElementById('profile_pic_input').value = '';
  document.getElementById('cropImage').src = '';
  
  setTimeout(() => {
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => backdrop.remove());
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
  }, 100);
});

document.querySelector('#editDetailsModal form').addEventListener('submit', function(e) {
  const email = document.querySelector('input[name="email"]').value;
  const fbLink = document.getElementById('fb_link').value;
  
  if (email && !email.toLowerCase().endsWith('@gmail.com')) {
    e.preventDefault();
    alert('Please enter a valid Gmail address (must end with @gmail.com)');
    return false;
  }
  
  if (fbLink && !fbLink.match(/^https?:\/\/(www\.)?(facebook|fb)\.com\/.+/)) {
    e.preventDefault();
    alert('Please enter a valid Facebook URL (e.g., https://facebook.com/yourprofile)');
    return false;
  }
});

function openEditJob(jobId) {
  const modalBody = document.getElementById('editJobContent');
  modalBody.innerHTML = '<div class="text-center py-5 text-muted">Loading...</div>';

  fetch('edit_labor_content.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'job_id=' + jobId
  })
  .then(response => response.text())
  .then(html => {
    modalBody.innerHTML = html;
    const modal = new bootstrap.Modal(document.getElementById('editJobModal'));
    modal.show();
  })
  .catch(err => {
    modalBody.innerHTML = '<div class="text-danger text-center py-5">Error loading edit form.</div>';
    console.error(err);
  });
}

function toggleProfileMenu() {
  const menu = document.getElementById('profile-menu');
  menu.classList.toggle('d-none');
}

function toggleMoreMenu() {
  const menu = document.getElementById('more-menu');
  menu.classList.toggle('d-none');
}

window.addEventListener('DOMContentLoaded', function() {
  const toast = document.getElementById('toast');
  if (toast) {
    toast.classList.add('show');
    setTimeout(function() {
      toast.style.animation = 'slideIn 0.3s ease reverse';
      setTimeout(function() {
        toast.remove();
      }, 300);
    }, 3000);
  }
});

document.addEventListener('click', function(event) {
  const profileMenu = document.getElementById('profile-menu');
  const profileIcon = document.querySelector('.profile-icon');
  
  if (profileMenu && !profileMenu.classList.contains('d-none')) {
    if (!profileIcon.contains(event.target) && !profileMenu.contains(event.target)) {
      profileMenu.classList.add('d-none');
    }
  }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
</body>
</html>