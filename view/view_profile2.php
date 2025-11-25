<?php
session_start();
include '../controls/connection.php';
include '../controls/profile_functions.php';
include '../controls/hire_functions.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$job_id  = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;
$is_logged_in = isset($_SESSION['user_id']);
$current_user_id = $is_logged_in ? intval($_SESSION['user_id']) : null;

// Fetch logged-in user's name and profile picture
$current_user_name = '';
$profile_picture = 'uploads/profile_pics/default.jpg';

if ($is_logged_in && $current_user_id) {
    $stmt = $conn->prepare("SELECT firstname, middlename, lastname, profile_picture FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $current_user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $user_row = $res->fetch_assoc();
        $current_user_name = $user_row['firstname'];
        if (!empty($user_row['middlename'])) {
            $current_user_name .= ' ' . $user_row['middlename'];
        }
        $current_user_name .= ' ' . $user_row['lastname'];
        if (!empty($user_row['profile_picture'])) {
            $profile_picture = $user_row['profile_picture'];
        }
    }
    $stmt->close();
}

if ($user_id === 0) { 
    header("Location: 404.php"); 
    exit(); 
}

// Get user profile
$user = getUserProfile($conn, $user_id);
if (!$user) { 
    header("Location: 404.php"); 
    exit(); 
}

// Get services
$services_result = getUserServices($conn, $user_id, $job_id);

// Check if current user is verified
$current_user_verified = $is_logged_in ? isUserVerified($conn, $current_user_id) : false;

// Check if current user can rate/review
$can_rate = false;
if ($is_logged_in && $current_user_verified && $current_user_id !== $user_id) {
    $can_rate = hasHireWithLaborer($conn, $user_id, $current_user_id);
}

function upsertLaborerRating($conn, $laborer_id, $user_id, $rating, $review_text) {
    if (!$conn) return false;
    $check = $conn->prepare("SELECT id FROM laborer_ratings WHERE laborer_id = ? AND user_id = ?");
    if (!$check) return false;
    $check->bind_param("ii", $laborer_id, $user_id);
    $check->execute();
    $res = $check->get_result();
    if ($res && $res->num_rows > 0) {
        $check->close();
        $update = $conn->prepare("UPDATE laborer_ratings SET rating = ?, review = ?, created_at = CURRENT_TIMESTAMP WHERE laborer_id = ? AND user_id = ?");
        if (!$update) return false;
        $update->bind_param("isii", $rating, $review_text, $laborer_id, $user_id);
        $ok = $update->execute();
        $update->close();
        return $ok;
    } else {
        $check->close();
        $insert = $conn->prepare("INSERT INTO laborer_ratings (laborer_id, user_id, rating, review) VALUES (?, ?, ?, ?)");
        if (!$insert) return false;
        $insert->bind_param("iiis", $laborer_id, $user_id, $rating, $review_text);
        $ok = $insert->execute();
        $insert->close();
        return $ok;
    }
}

// Handle rating submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['rating_submit'])) {
    if (!$is_logged_in) {
        $_SESSION['rating_error'] = "You must log in to rate.";
    } elseif (!$current_user_verified) {
        $_SESSION['rating_error'] = "You must verify your account before rating.";
    } elseif ($current_user_id === $user_id) {
        $_SESSION['rating_error'] = "You cannot rate your own profile.";
    } elseif (!$can_rate) {
        $_SESSION['rating_error'] = "You can only rate this laborer after completing a hire transaction.";
    } else {
        $rating = intval($_POST['rating'] ?? 0);
        $review_text = trim($_POST['review'] ?? '');
        if ($rating < 1 || $rating > 5) {
            $_SESSION['rating_error'] = "Invalid rating value.";
        } else {
            if (upsertLaborerRating($conn, $user_id, $current_user_id, $rating, $review_text)) {
                $_SESSION['rating_success'] = "Your rating has been saved.";
            } else {
                $_SESSION['rating_error'] = "Failed to save rating. Please try again.";
            }
        }
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?user_id=" . $user_id . ($job_id ? "&job_id=" . $job_id : ""));
    exit;
}

// Get rating stats
$rating_stats = getRatingStats($conn, $user_id);
$avg_rating = $rating_stats['avg_rating'];
$total_ratings = $rating_stats['total_ratings'];
$user_previous_rating = $is_logged_in ? getUserRating($conn, $user_id, $current_user_id) : 0;

$user_previous_review = '';
if ($is_logged_in) {
    $stmt = $conn->prepare("SELECT review, rating FROM laborer_ratings WHERE laborer_id = ? AND user_id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("ii", $user_id, $current_user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $r = $res->fetch_assoc();
            $user_previous_review = $r['review'] ?? '';
        }
        $stmt->close();
    }
}

// Handle report submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['report_submit'])) {
    if (!$is_logged_in) {
        $_SESSION['report_error'] = "You must log in to submit a report.";
    } else {
        $report_reasons = $_POST['report_reason'] ?? [];
        $additional_details = $_POST['additional_details'] ?? "";
        $attachment_path = null;

        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/uploads/reports/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $filename = basename($_FILES['attachment']['name']);
            $new_name = time() . '_' . $filename;
            $target_file = $upload_dir . $new_name;
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $target_file)) {
                $attachment_path = 'uploads/reports/' . $new_name;
            }
        }

        if (is_array($report_reasons)) {
            $report_reasons = implode(',', $report_reasons);
        }
        $stmt = $conn->prepare("INSERT INTO reports (user_id, reason, additional_details, attachment, status) VALUES (?, ?, ?, ?, 'Pending')");
        $stmt->bind_param("isss", $user_id, $report_reasons, $additional_details, $attachment_path);
        if ($stmt->execute()) {
            $_SESSION['report_success'] = "Report submitted successfully!";
        } else {
            $_SESSION['report_error'] = "Failed to submit report.";
        }
        $stmt->close();
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?user_id=" . $user_id . ($job_id ? "&job_id=" . $job_id : ""));
    exit;
}

// Handle hire submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['hire_submit'])) {
    if (!$is_logged_in) {
        $_SESSION['hire_error'] = "You must log in to hire.";
    } elseif (!$current_user_verified) {
        // NEW: Check if user is verified before allowing hire
        $_SESSION['hire_error'] = "You must verify your account before hiring laborers. Please upload verification documents in your profile.";
        header("Location: " . $_SERVER['PHP_SELF'] . "?user_id=" . $user_id . ($job_id ? "&job_id=" . $job_id : ""));
        exit;
    } else {
        $meeting_location = trim($_POST['meeting_location'] ?? '');
        $meeting_lat = floatval($_POST['meeting_lat'] ?? 0);
        $meeting_lng = floatval($_POST['meeting_lng'] ?? 0);
        $message = trim($_POST['hire_message'] ?? '');
        
        if (empty($meeting_location)) {
            $_SESSION['hire_error'] = "Please specify a meeting location.";
        } else {
            $hire_result = sendHireRequest($conn, $current_user_id, $user_id, $message, $meeting_location);
            if ($hire_result) {
                $_SESSION['hire_success'] = "Hire request sent successfully!";
            } else {
                $_SESSION['hire_error'] = "Failed to send hire request.";
            }
        }
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?user_id=" . $user_id . ($job_id ? "&job_id=" . $job_id : ""));
    exit;
}

function renderStars($rating) {
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($rating >= $i) {
            $html .= '<i class="fa-solid fa-star"></i>';
        } elseif ($rating >= ($i - 0.5)) {
            $html .= '<i class="fa-solid fa-star-half-stroke"></i>';
        } else {
            $html .= '<i class="fa-regular fa-star"></i>';
        }
    }
    return $html;
}

function getLaborerReviewsResult($conn, $laborer_id) {
    $stmt = $conn->prepare("SELECT r.rating, r.review, r.created_at, u.firstname, u.lastname
                            FROM laborer_ratings r
                            JOIN users u ON r.user_id = u.user_id
                            WHERE r.laborer_id = ?
                            ORDER BY r.created_at DESC");
    if (!$stmt) return false;
    $stmt->bind_param("i", $laborer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    return $result;
}

$reviews_result = getLaborerReviewsResult($conn, $user_id);
?>
<?php include '../nav.php' ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?> - Servify</title>
  <link rel="stylesheet" href="../styles/profile.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
  
<style>
:root{--primary-color:#007bff;--success-color:#28a745;--danger-color:#dc3545;--warning-color:#ffc107;--light-bg:#f8f9fa;--card-shadow:0 2px 12px rgba(0,0,0,0.08);}
body{padding-top:80px;background-color:var(--light-bg);}
@media(max-width:768px){body{padding-top:70px;}}
.custom-profile-page{max-width:1200px;margin:0 auto;padding:20px;}
.profile-card{background:white;border-radius:16px;padding:30px;margin-bottom:30px;box-shadow:var(--card-shadow);position:relative;}
.profile-header-wrapper{display:flex;gap:25px;align-items:start;flex-wrap:wrap;}
.profile-img-wrapper{position:relative;}
.profile-img-wrapper img{width:120px;height:120px;border-radius:50%;object-fit:cover;border:4px solid var(--primary-color);box-shadow:0 4px 12px rgba(0,123,255,0.2);cursor:pointer;transition:all 0.3s;}
.profile-img-wrapper img:hover{transform:scale(1.05);box-shadow:0 6px 16px rgba(0,123,255,0.4);}
.profile-details{flex:1;min-width:250px;}
.profile-name-section{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:12px;}
.profile-name-section h2{margin:0;font-size:28px;font-weight:700;color:#212529;}
.verification-badge{padding:6px 14px;border-radius:20px;font-size:13px;font-weight:600;display:inline-flex;align-items:center;gap:5px;}
.verification-badge.verified{background:#d4edda;color:#155724;}
.verification-badge.not-verified{background:#f8d7da;color:#721c24;}
.profile-meta{color:#6c757d;font-size:15px;line-height:1.8;margin-bottom:15px;}
.profile-meta i{width:20px;color:var(--primary-color);margin-right:5px;}
.rating-display{color:gold;font-size:18px;margin-bottom:10px;}
.rating-display i{color:gold;}
.contact-icons{display:flex;gap:15px;margin:15px 0;}
.contact-icons a{color:#6c757d;font-size:24px;transition:all 0.3s;}
.contact-icons a:hover{color:var(--primary-color);transform:translateY(-2px);}
.action-buttons{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;box-sizing:border-box;flex-wrap:nowrap;}
.action-buttons a,.action-buttons button{flex:1;display:flex;align-items:center;justify-content:center;text-decoration:none;width:100%;box-sizing:border-box;padding:0;}
.btn-message,.btn-hire{padding:12px 24px;border-radius:10px;font-weight:600;display:flex;align-items:center;justify-content:center;gap:8px;transition:all 0.3s ease;border:2px solid transparent;cursor:pointer;text-decoration:none;height:48px;animation:buttonPop 0.3s ease;width:100%;box-sizing:border-box;flex:1;}
.btn-message i,.btn-hire i{width:20px;text-align:center;}
.btn-message{background:white;color:var(--primary-color);border-color:var(--primary-color);}
.btn-message:hover{background:var(--primary-color);color:white;transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,123,255,0.3);}
.btn-hire{background:white;color:var(--success-color);border-color:var(--success-color);}
.btn-hire:hover{background:var(--success-color);color:white;transform:translateY(-2px);box-shadow:0 4px 12px rgba(40,167,69,0.3);}
@keyframes buttonPop{0%{transform:scale(0.95);opacity:0.7;}100%{transform:scale(1);opacity:1;}}
/* Clean, subtle dropdown icon styling */
.dropdown-toggle-custom{position:absolute;right:30px;background:transparent;border:none;font-size:22px;color:#dc3545;cursor:pointer;padding:10px;border-radius:8px;transition:all 0.3s ease;box-shadow:none;}
.dropdown-toggle-custom {display: inline-flex;align-items: center;gap: 6px;border: none;background: none;cursor: pointer;transition: all 0.2s ease; }
.dropdown-toggle-custom:hover {color: #dc3545;background: #ffffff;transform: scale(1.05)}
/* Tabs */
.custom-tabs{background:white;border-radius:16px;padding:8px;margin-bottom:25px;box-shadow:var(--card-shadow);display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:8px;}
.tab-button{padding:14px 20px;border:none;background:transparent;color:#6c757d;font-weight:600;font-size:15px;border-radius:10px;cursor:pointer;transition:all 0.3s ease;white-space:nowrap;display:flex;align-items:center;justify-content:center;gap:8px;}
.tab-button:hover{background:#f8f9fa;color:var(--primary-color);}
.tab-button.active{background:var(--primary-color);color:white;box-shadow:0 4px 12px rgba(0,123,255,0.3);}
.tab-content-wrapper{background:white;border-radius:16px;padding:30px;box-shadow:var(--card-shadow);min-height:400px;}
.tab-pane{display:none;animation:fadeIn 0.3s ease;}
.tab-pane.active{display:block;}
@keyframes fadeIn{from{opacity:0;transform:translateY(10px);}to{opacity:1;transform:translateY(0);}}

/* Cards and gallery */
.service-card{background:#f8f9fa;border-radius:12px;padding:20px;margin-bottom:15px;border:1px solid #e9ecef;transition:all 0.3s ease;}
.service-card:hover{box-shadow:0 4px 12px rgba(0,0,0,0.1);transform:translateY(-2px);}
.service-card h5{color:#212529;font-weight:700;margin-bottom:10px;}
.service-card p{color:#6c757d;line-height:1.6;margin:0;}
.media-gallery{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:15px;margin-top:20px;}
.media-gallery img{width:100%;height:200px;object-fit:cover;border-radius:12px;cursor:pointer;transition:all 0.3s;border:2px solid transparent;}
.media-gallery img:hover{transform:scale(1.05);border-color:var(--primary-color);box-shadow:0 4px 12px rgba(0,123,255,0.3);}

/* Lightbox */
.lightbox-overlay{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.95);z-index:10000;justify-content:center;align-items:center;}
.lightbox-content{position:relative;max-width:90%;max-height:90%;display:flex;flex-direction:column;align-items:center;}
.lightbox-close{position:absolute;top:-40px;right:0;color:white;font-size:40px;cursor:pointer;z-index:10001;background:rgba(0,0,0,0.5);width:50px;height:50px;border-radius:50%;display:flex;align-items:center;justify-content:center;transition:all 0.3s;}
.lightbox-close:hover{background:rgba(255,255,255,0.2);transform:rotate(90deg);}
.lightbox-image-wrapper{position:relative;display:flex;align-items:center;justify-content:center;max-width:100%;}
.lightbox-image{max-width:90vw;max-height:80vh;object-fit:contain;border-radius:8px;}
.lightbox-nav{position:absolute;top:50%;transform:translateY(-50%);background:rgba(0,0,0,0.7);color:white;font-size:30px;padding:20px;cursor:pointer;border-radius:50%;width:60px;height:60px;display:flex;align-items:center;justify-content:center;transition:all 0.3s;user-select:none;}
.lightbox-nav:hover{background:rgba(255,255,255,0.3);transform:translateY(-50%) scale(1.1);}
.lightbox-prev{left:-80px;}
.lightbox-next{right:-80px;}
.lightbox-description{color:white;margin-top:20px;padding:15px 30px;background:rgba(0,0,0,0.7);border-radius:8px;max-width:600px;text-align:center;}

/* Review */
.review-form{background:#f8f9fa;padding:25px;border-radius:12px;margin-bottom:25px;}
.star-rating{direction:rtl;display:inline-flex;align-items:center;margin-bottom:15px;}
.star-rating input{display:none;}
.star-rating label{font-size:2rem;color:#ddd;cursor:pointer;transition:color 0.2s;padding:0 5px;}
.star-rating input:checked~label,.star-rating label:hover,.star-rating label:hover~label{color:gold;}
.review-form textarea{width:100%;padding:15px;border:1px solid #dee2e6;border-radius:8px;resize:vertical;min-height:100px;margin-bottom:15px;}
.review-form button{background:var(--primary-color);color:white;border:none;padding:12px 30px;border-radius:8px;font-weight:600;cursor:pointer;transition:all 0.3s;}
.review-form button:hover{background:#0056b3;transform:translateY(-2px);}
.review-card{background:#f8f9fa;border-radius:12px;padding:20px;margin-bottom:15px;border-left:4px solid var(--primary-color);}
.review-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;}
.review-author{font-weight:700;color:#212529;}
.review-date{color:#6c757d;font-size:14px;}
.review-stars{color:gold;margin-bottom:10px;}
.review-text{color:#495057;line-height:1.6;}
.empty-state{text-align:center;padding:60px 20px;color:#6c757d;}
.empty-state i{font-size:64px;margin-bottom:20px;opacity:0.3;}

/* Map & Location */
#map{height:350px;border-radius:12px;margin:15px 0;border:2px solid #dee2e6;}
.location-input-group{display:flex;gap:10px;margin-bottom:15px;align-items:stretch;}
#refreshLocationBtn{background:var(--primary-color);color:white;border:none;width:40px;height:40px;border-radius:6px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.3s;}
#refreshLocationBtn:hover:not(:disabled){background:#0056b3;}
#refreshLocationBtn:disabled{opacity:0.6;cursor:not-allowed;}
#refreshLocationBtn.spinning i{animation:spin 1s linear infinite;}
@keyframes spin{from{transform:rotate(0deg);}to{transform:rotate(360deg);}}

/* Modal Close */
.modal-header .btn-close{transition:transform 0.3s ease,background-color 0.3s ease;display:flex;align-items:center;justify-content:center;color:#000;}
.modal-header .btn-close:hover{transform:rotate(90deg) scale(1.1);background-color:#dc3545;color:#fff;opacity:1;}

/* Loading + Toast */
.loading-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);display:flex;justify-content:center;align-items:center;z-index:9999;}
.spinner-container{text-align:center;background:white;padding:40px;border-radius:15px;box-shadow:0 10px 40px rgba(0,0,0,0.3);}
.toast-notification{position:fixed;top:100px;right:20px;background:white;padding:20px 24px;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.15);z-index:9999;display:none;min-width:300px;animation:slideIn 0.3s ease;}
.toast-notification.show{display:flex;align-items:center;gap:12px;}
.toast-notification.success{border-left:4px solid var(--success-color);}
.toast-notification.error{border-left:4px solid var(--danger-color);}
.toast-notification i{font-size:24px;}
.toast-notification.success i{color:var(--success-color);}
.toast-notification.error i{color:var(--danger-color);}
@keyframes slideIn{from{transform:translateX(400px);opacity:0;}to{transform:translateX(0);opacity:1;}}

/* Responsive */
@media(max-width:768px){
  .custom-profile-page{padding:15px;}
  .profile-card{padding:20px;}
  .profile-header-wrapper{flex-direction:column;align-items:center;text-align:center;}
  .profile-name-section{justify-content:center;flex-direction:column;}
  .profile-name-section h2{font-size:24px;}
  .profile-img-wrapper img{width:100px;height:100px;}
  .contact-icons{justify-content:center;}
  .action-buttons{justify-content:center;width:100%;flex-wrap:wrap;}
  .btn-message,.btn-hire{flex:1 1 auto;min-width:140px;max-width:none;padding:10px 16px;font-size:14px;}
  .custom-tabs{grid-template-columns:repeat(3,1fr);gap:5px;padding:5px;}
  .tab-button{padding:10px 8px;font-size:13px;flex-direction:column;gap:4px;}
  .tab-button i{font-size:18px;}
  .tab-button span{font-size:11px;}
  .tab-content-wrapper{padding:20px 15px;}
  .media-gallery{grid-template-columns:repeat(2,1fr);gap:10px;}
  .media-gallery img{height:150px;}
  .service-card{padding:15px;}
  .review-card{padding:15px;}
  .toast-notification{top:80px;right:10px;left:10px;min-width:auto;}
  .dropdown-toggle-custom{top:15px;right:15px;font-size:20px;}
  .lightbox-nav{width:50px;height:50px;font-size:24px;padding:10px;}
  .lightbox-prev{left:10px;}
  .lightbox-next{right:10px;}
  .lightbox-image{max-width:95vw;max-height:70vh;}
  .lightbox-description{max-width:90%;padding:10px 15px;font-size:14px;}
  #refreshLocationBtn{min-width:48px;padding:10px;}
  .location-input-group{gap:8px;}
}
@media(max-width:480px){
  .custom-tabs{grid-template-columns:repeat(3,1fr);}
  .action-buttons{gap:10px;}
  .btn-message,.btn-hire{font-size:13px;padding:10px 12px;min-width:120px;}
  .profile-meta{font-size:14px;}
  .rating-display{font-size:16px;}
  .media-gallery{grid-template-columns:1fr;}
}
.btn-hire.unverified {
  position: relative;
}

.btn-hire.unverified::after {
  content: '⚠';
  position: absolute;
  top: -8px;
  right: -8px;
  background: #ffc107;
  color: #000;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: bold;
  box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}
</style>

</head>

<body>

<!-- Toast Notifications -->
<?php if (isset($_SESSION['hire_success'])): ?>
<div class="toast-notification success show" id="toast">
  <i class="bi bi-check-circle-fill"></i>
  <div>
    <strong>Success!</strong>
    <p class="mb-0"><?php echo htmlspecialchars($_SESSION['hire_success']); unset($_SESSION['hire_success']); ?></p>
  </div>
</div>
<?php endif; ?>

<?php if (isset($_SESSION['hire_error'])): ?>
<div class="toast-notification error show" id="toast">
  <i class="bi bi-x-circle-fill"></i>
  <div>
    <strong>Error!</strong>
    <p class="mb-0"><?php echo htmlspecialchars($_SESSION['hire_error']); unset($_SESSION['hire_error']); ?></p>
  </div>
</div>
<?php endif; ?>

<?php if (isset($_SESSION['rating_success'])): ?>
<div class="toast-notification success show" id="toast">
  <i class="bi bi-check-circle-fill"></i>
  <div>
    <strong>Success!</strong>
    <p class="mb-0"><?php echo htmlspecialchars($_SESSION['rating_success']); unset($_SESSION['rating_success']); ?></p>
  </div>
</div>
<?php endif; ?>

<?php if (isset($_SESSION['rating_error'])): ?>
<div class="toast-notification error show" id="toast">
  <i class="bi bi-x-circle-fill"></i>
  <div>
    <strong>Error!</strong>
    <p class="mb-0"><?php echo htmlspecialchars($_SESSION['rating_error']); unset($_SESSION['rating_error']); ?></p>
  </div>
</div>
<?php endif; ?>

<?php if (isset($_SESSION['report_success'])): ?>
<div class="toast-notification success show" id="toast">
  <i class="bi bi-check-circle-fill"></i>
  <div>
    <strong>Success!</strong>
    <p class="mb-0"><?php echo htmlspecialchars($_SESSION['report_success']); unset($_SESSION['report_success']); ?></p>
  </div>
</div>
<?php endif; ?>

<?php if (isset($_SESSION['report_error'])): ?>
<div class="toast-notification error show" id="toast">
  <i class="bi bi-x-circle-fill"></i>
  <div>
    <strong>Error!</strong>
    <p class="mb-0"><?php echo htmlspecialchars($_SESSION['report_error']); unset($_SESSION['report_error']); ?></p>
  </div>
</div>
<?php endif; ?>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="loading-overlay d-none">
  <div class="spinner-container">
    <div class="spinner-border text-primary" style="width: 4rem; height: 4rem;"></div>
    <p style="margin-top: 20px; font-size: 18px;">Sending hire request...</p>
  </div>
</div>

<!-- Fullscreen More Menu -->
<div id="more-menu" class="fullscreen-menu d-none">
  <div class="menu-panel">
    <div class="menu-header">
      <h1 class="menu-title">SERVIFY</h1>
      <span class="close-btn" onclick="toggleMoreMenu()">✕</span>
    </div>
    <?php if ($is_logged_in): ?>
      <div class="user-section">
        <div class="profile-info">
          <a href="../view/profile.php" class="profile-link">
            <img src="../<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile Picture" class="icon">
            <h3 class="user-name"><?php echo htmlspecialchars($current_user_name); ?></h3>
          </a>
        </div>
      </div>
      <div class="section-divider"></div>
      <div class="menu-options">
        <a href="../view/messages.php"><i class="bi bi-chat-dots"></i> Inbox</a>
        <a href="../controls/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
      </div>
    <?php else: ?>
      <div class="menu-options">
        <a href="../view/become-laborer.php"><i class="bi bi-person-workspace"></i> Become a laborer</a>
        <a href="../view/login.php"><i class="bi bi-person-circle"></i> Signin / Signup</a>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- PROFILE PAGE -->
<div class="custom-profile-page">
  <!-- Profile Card -->
  <div class="profile-card">
    <!-- Report Dropdown -->
    <div class="dropdown position-absolute" style="top: 20px; right: 20px;">
      <button class="dropdown-toggle-custom" type="button" data-bs-toggle="dropdown">
        <i class="bi-flag-fill"></i>
      </button>
      <ul class="dropdown-menu dropdown-menu-end">
        <?php if ($is_logged_in): ?>
          <li><button class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#reportModal">
            <i class="bi-exclamation-triangle" style="margin-right: 10px;"></i>  Report User
          </button></li>
        <?php else: ?>
          <li><a class="dropdown-item text-danger" href="../view/login.php">
            <i class="bi-exclamation-triangle" style="margin-right: 10px;"></i>  Report User
          </a></li>
        <?php endif; ?>
      </ul>
    </div>

    <div class="profile-header-wrapper">
      <div class="profile-img-wrapper">
        <img src="../<?php echo htmlspecialchars($user['profile_picture']) ?: 'uploads/profile_pics/default.jpg'; ?>" 
             alt="Profile Picture"
             onclick="openProfileLightbox()">
      </div>
      
      <div class="profile-details">
        <div class="profile-name-section">
          <h2><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['middlename'] . ' ' . $user['lastname']); ?></h2>
          <span class="verification-badge <?php echo $user['is_verified'] ? 'verified' : 'not-verified'; ?>">
            <i class="bi bi-<?php echo $user['is_verified'] ? 'check-circle-fill' : 'x-circle-fill'; ?>"></i>
            <?php echo $user['is_verified'] ? 'Verified' : 'Not Verified'; ?>
          </span>
        </div>
        
        <div class="rating-display">
          <?php echo renderStars($avg_rating); ?>
          <span style="color: #6c757d; font-size: 16px; margin-left: 8px;">
            (<?php echo number_format($avg_rating, 1); ?> • <?php echo $total_ratings; ?> reviews)
          </span>
        </div>

        <div class="profile-meta">
          <p><i class="bi bi-geo-alt-fill"></i> <?php echo htmlspecialchars($user['location']); ?></p>
          <?php if (!empty($user['email'])): ?>
            <p><i class="bi bi-envelope-fill"></i> <?php echo htmlspecialchars($user['email']); ?></p>
          <?php endif; ?>
          <?php if (!empty($user['contact'])): ?>
            <p><i class="bi bi-telephone-fill"></i> <?php echo htmlspecialchars($user['contact']); ?></p>
          <?php endif; ?>
        </div>

        <div class="contact-icons">
          <?php if (!empty($user['fb_link'])): ?>
            <a href="<?php echo htmlspecialchars($user['fb_link']); ?>" target="_blank" title="Facebook">
              <i class="fab fa-facebook"></i>
            </a>
          <?php endif; ?>
          <?php if (!empty($user['email'])): ?>
            <a href="mailto:<?php echo htmlspecialchars($user['email']); ?>" title="Email">
              <i class="fas fa-envelope"></i>
            </a>
          <?php endif; ?>
          <?php if (!empty($user['contact'])): ?>
            <a href="tel:<?php echo htmlspecialchars($user['contact']); ?>" title="Phone">
              <i class="fas fa-phone"></i>
            </a>
          <?php endif; ?>
        </div>

        <div class="action-buttons">
          <a href="../view/messages.php?receiver_id=<?php echo $user_id; ?>" style="text-decoration: none;">
            <button class="btn-message">
              <i class="bi bi-chat-dots"></i> Message
            </button>
          </a>
          <?php if ($is_logged_in): ?>
            <?php if ($current_user_verified): ?>
              <button class="btn-hire" data-bs-toggle="modal" data-bs-target="#hireModal">
                <i class="bi bi-check-circle"></i> Hire
              </button>
            <?php else: ?>
              <button class="btn-hire unverified" onclick="showVerificationWarning()">
                <i class="bi bi-shield-exclamation"></i> Hire
              </button>
            <?php endif; ?>
          <?php else: ?>
            <a href="../view/login.php" style="text-decoration: none;">
              <button class="btn-hire">
                <i class="bi bi-check-circle"></i> Hire
              </button>
            </a>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </div>

  <!-- Tab Navigation -->
  <div class="custom-tabs">
    <button class="tab-button active" onclick="switchTab('services')">
      <i class="bi bi-briefcase"></i>
      <span>Services</span>
    </button>
    <button class="tab-button" onclick="switchTab('media')">
      <i class="bi bi-images"></i>
      <span>Media</span>
    </button>
    <button class="tab-button" onclick="switchTab('reviews')">
      <i class="bi bi-star"></i>
      <span>Reviews</span>
    </button>
  </div>

  <!-- Tab Content -->
  <div class="tab-content-wrapper">
    <!-- Services Tab -->
    <div id="services-tab" class="tab-pane active">
      <h4 class="mb-4"><i class="bi bi-briefcase-fill"></i> Services Offered</h4>
      
      <?php 
      $services_result->data_seek(0);
      if ($services_result->num_rows > 0): 
      ?>
        <?php while ($service = $services_result->fetch_assoc()): ?>
          <div class="service-card">
            <h5><?php echo htmlspecialchars($service['job_name']); ?></h5>
            <p><?php echo htmlspecialchars($service['job_description']); ?></p>
            <?php if (!empty($service['job_image'])): ?>
              <img src="../uploads/<?php echo htmlspecialchars($service['job_image']); ?>" 
                   alt="Service Image" 
                   style="max-width: 200px; border-radius: 8px; margin-top: 10px;">
            <?php endif; ?>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="empty-state">
          <i class="bi bi-briefcase"></i>
          <h4>No services listed</h4>
          <p>This laborer hasn't added any services yet.</p>
        </div>
      <?php endif; ?>
    </div>

    <!-- Media Tab -->
    <div id="media-tab" class="tab-pane">
      <h4 class="mb-4"><i class="bi bi-images"></i> Portfolio & Media</h4>
      
      <div class="media-gallery">
        <?php
        $images = [];
        $descriptions = [];
        $services_result->data_seek(0);
        while ($service = $services_result->fetch_assoc()):
          if (!empty($service['job_image'])):
            $img = "../uploads/" . htmlspecialchars($service['job_image']);
            $desc = htmlspecialchars($service['job_description'] ?? 'No description');
            $images[] = $img;
            $descriptions[] = $desc;
        ?>
            <img src="<?php echo $img; ?>" alt="Portfolio Image" onclick="openLightbox(<?php echo count($images) - 1; ?>)">
        <?php 
          endif;
        endwhile;
        
        if (empty($images)): 
        ?>
          <div class="empty-state" style="grid-column: 1 / -1;">
            <i class="bi bi-images"></i>
            <h4>No media available</h4>
            <p>This laborer hasn't uploaded any portfolio images yet.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Reviews Tab -->
    <div id="reviews-tab" class="tab-pane">
      <h4 class="mb-4"><i class="bi bi-star-fill"></i> Ratings & Reviews</h4>
      
      <!-- Rating Form -->
      <?php if ($is_logged_in): ?>
        <?php if ($current_user_verified): ?>
          <?php if ($can_rate): ?>
            <div class="review-form">
              <h5>Rate & Review this Laborer</h5>
              <form method="POST">
                <input type="hidden" name="rating_submit" value="1">
                <div class="star-rating">
                  <?php for ($i = 5; $i >= 1; $i--): ?>
                    <input type="radio" id="star<?php echo $i;?>" name="rating" value="<?php echo $i; ?>"
                      <?php echo ($user_previous_rating === $i) ? 'checked' : ''; ?> required>
                    <label for="star<?php echo $i;?>"><i class="fa fa-star"></i></label>
                  <?php endfor; ?>
                </div>
                <textarea name="review" placeholder="Share your experience with this laborer..." required><?php echo htmlspecialchars($user_previous_review); ?></textarea>
                <button type="submit">
                  <i class="bi bi-send"></i> <?php echo $user_previous_rating ? 'Update Review' : 'Submit Review'; ?>
                </button>
              </form>
            </div>
          <?php else: ?>
            <div class="alert alert-warning">
              <i class="bi bi-info-circle"></i>
              You can only review this laborer after completing a hire transaction.
            </div>
          <?php endif; ?>
        <?php else: ?>
          <div class="alert alert-warning">
            <i class="bi bi-shield-exclamation"></i>
            You must verify your account to leave reviews.
            <a href="../view/profile.php" class="btn btn-sm btn-warning ms-2">Verify Account</a>
          </div>
        <?php endif; ?>
      <?php else: ?>
        <div class="alert alert-info">
          <i class="bi bi-info-circle"></i>
          <a href="../view/login.php">Log in</a> to leave a review.
        </div>
      <?php endif; ?>

      <!-- Reviews List -->
      <h5 class="mt-4 mb-3">All Reviews (<?php echo $total_ratings; ?>)</h5>
      
      <?php if ($reviews_result && $reviews_result->num_rows > 0): ?>
        <?php while ($rev = $reviews_result->fetch_assoc()): ?>
          <div class="review-card">
            <div class="review-header">
              <div>
                <span class="review-author">
                  <?php echo htmlspecialchars($rev['firstname'] . " " . $rev['lastname']); ?>
                </span>
              </div>
              <span class="review-date">
                <i class="bi bi-clock"></i>
                <?php echo date('M j, Y', strtotime($rev['created_at'])); ?>
              </span>
            </div>
            <div class="review-stars">
              <?php echo renderStars($rev['rating']); ?>
            </div>
            <?php if (!empty($rev['review'])): ?>
              <p class="review-text"><?php echo nl2br(htmlspecialchars($rev['review'])); ?></p>
            <?php endif; ?>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="empty-state">
          <i class="bi bi-star"></i>
          <h4>No reviews yet</h4>
          <p>Be the first to review this laborer!</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Lightbox Modal -->
<div class="lightbox-overlay" id="lightbox">
  <div class="lightbox-content">
    <span class="lightbox-close" onclick="closeLightbox()">×</span>

    <div class="lightbox-image-wrapper">
      <span class="lightbox-nav lightbox-prev" onclick="prevImage()" id="prevBtn">❮</span>
      <img id="lightbox-img" class="lightbox-image" src="" alt="">
      <span class="lightbox-nav lightbox-next" onclick="nextImage()" id="nextBtn">❯</span>
    </div>

    <div class="lightbox-description" id="lightbox-desc"></div>
  </div>
</div>

<!-- Profile Picture Lightbox -->
<div class="lightbox-overlay" id="profileLightbox">
  <div class="lightbox-content">
    <span class="lightbox-close" onclick="closeProfileLightbox()">×</span>
    <div class="lightbox-image-wrapper">
      <img id="profile-lightbox-img" class="lightbox-image" 
           src="../<?php echo htmlspecialchars($user['profile_picture']) ?: 'uploads/profile_pics/default.jpg'; ?>" 
           alt="Profile Picture">
    </div>
    <div class="lightbox-description">
      <?php echo htmlspecialchars($user['firstname'] . ' ' . $user['middlename'] . ' ' . $user['lastname']); ?>
    </div>
  </div>
</div>

<!-- Hire Modal -->
<div class="modal fade" id="hireModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-check-circle"></i> Hire Laborer</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form method="POST" id="hireForm">
          <input type="hidden" name="hire_submit" value="1">
          <input type="hidden" name="meeting_lat" id="meeting_lat">
          <input type="hidden" name="meeting_lng" id="meeting_lng">
          
          <div class="mb-3">
            <label for="meeting_location" class="form-label"><strong>Meeting Location</strong></label>
            <div class="location-input-group">
              <input type="text" class="form-control" name="meeting_location" id="meeting_location" 
                     placeholder="Click on map or use current location" readonly required>
              <button type="button" id="refreshLocationBtn" onclick="refreshLocation()">
                <i class="bi bi-arrow-clockwise"></i>
              </button>
            </div>
            <div id="map"></div>
            <small class="text-muted">Click on the map or drag the marker to set your meeting location.</small>
          </div>

          <div class="mb-3">
            <label for="hire_message" class="form-label"><strong>Message</strong></label>
            <textarea class="form-control" name="hire_message" id="hire_message" 
                      placeholder="Discuss rates, job details, location specifics, etc." rows="4" required></textarea>
          </div>
          
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-success" id="submitHireBtn">
              <i class="bi bi-send"></i> Send Hire Request
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Report Modal -->
<div class="modal fade" id="reportModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-flag"></i> Report User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="report_submit" value="1">

          <div class="mb-3">
            <label class="form-label"><strong>Reason for Report</strong></label>
            <select class="form-select" name="report_reason[]" required>
              <option value="">-- Select a reason --</option>
              <option value="false_information">False Information</option>
              <option value="nudity">Inappropriate Content</option>
              <option value="harassment">Harassment</option>
              <option value="spam">Spam</option>
              <option value="hate_speech">Hate Speech</option>
              <option value="scam">Scam/Fraud</option>
              <option value="other">Other</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label"><strong>Additional Details</strong></label>
            <textarea class="form-control" name="additional_details" rows="4" 
                      placeholder="Provide more information about your report..."></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label"><strong>Attachment (Optional)</strong></label>
            <input type="file" class="form-control" name="attachment" accept="image/*,.pdf">
            <small class="text-muted">Upload evidence if available (images or PDF).</small>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-danger">
              <i class="bi bi-flag"></i> Submit Report
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
// Lightbox functionality
const images = <?php echo json_encode($images); ?>;
const descriptions = <?php echo json_encode($descriptions); ?>;
let currentIndex = 0;

function openLightbox(index) {
  currentIndex = index;
  document.getElementById('lightbox-img').src = images[index];
  document.getElementById('lightbox-desc').textContent = descriptions[index];
  document.getElementById('lightbox').style.display = 'flex';
  document.body.style.overflow = 'hidden';
  
  if (images.length <= 1) {
    document.getElementById('prevBtn').style.display = 'none';
    document.getElementById('nextBtn').style.display = 'none';
  } else {
    document.getElementById('prevBtn').style.display = 'flex';
    document.getElementById('nextBtn').style.display = 'flex';
  }
}

function closeLightbox() {
  document.getElementById('lightbox').style.display = 'none';
  document.body.style.overflow = 'auto';
}

function prevImage() {
  if (images.length <= 1) return;
  currentIndex = (currentIndex - 1 + images.length) % images.length;
  openLightbox(currentIndex);
}

function nextImage() {
  if (images.length <= 1) return;
  currentIndex = (currentIndex + 1) % images.length;
  openLightbox(currentIndex);
}

function openProfileLightbox() {
  document.getElementById('profileLightbox').style.display = 'flex';
  document.body.style.overflow = 'hidden';
}

function closeProfileLightbox() {
  document.getElementById('profileLightbox').style.display = 'none';
  document.body.style.overflow = 'auto';
}

document.addEventListener('keydown', function(e) {
  const lightbox = document.getElementById('lightbox');
  const profileLightbox = document.getElementById('profileLightbox');
  
  if (e.key === 'Escape') {
    if (lightbox.style.display === 'flex') {
      closeLightbox();
    }
    if (profileLightbox.style.display === 'flex') {
      closeProfileLightbox();
    }
  } else if (e.key === 'ArrowLeft' && lightbox.style.display === 'flex') {
    prevImage();
  } else if (e.key === 'ArrowRight' && lightbox.style.display === 'flex') {
    nextImage();
  }
});

document.getElementById('lightbox').addEventListener('click', function(e) {
  if (e.target === this) {
    closeLightbox();
  }
});

document.getElementById('profileLightbox').addEventListener('click', function(e) {
  if (e.target === this) {
    closeProfileLightbox();
  }
});

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
    setTimeout(function() {
      toast.style.animation = 'slideIn 0.3s ease reverse';
      setTimeout(function() {
        toast.remove();
      }, 300);
    }, 3000);
  }
});

let map;
let marker;
let mapInitialized = false;

document.getElementById('hireModal').addEventListener('shown.bs.modal', function () {
  if (!mapInitialized) {
    map = L.map('map').setView([14.5995, 120.9842], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap contributors',
      maxZoom: 19
    }).addTo(map);

    marker = L.marker([14.5995, 120.9842], { 
      draggable: true,
      autoPan: true
    }).addTo(map);

    setTimeout(() => {
      map.invalidateSize();
    }, 100);

    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
        position => {
          const lat = position.coords.latitude;
          const lng = position.coords.longitude;
          map.setView([lat, lng], 15);
          marker.setLatLng([lat, lng]);
          updateLocationInput(lat, lng);
        }
      );
    }

    marker.on('dragend', function(e) {
      const latlng = e.target.getLatLng();
      updateLocationInput(latlng.lat, latlng.lng);
    });

    map.on('click', function(e) {
      marker.setLatLng(e.latlng);
      updateLocationInput(e.latlng.lat, e.latlng.lng);
    });

    mapInitialized = true;
  } else {
    setTimeout(() => {
      map.invalidateSize();
    }, 100);
  }
});

function refreshLocation() {
  const btn = document.getElementById('refreshLocationBtn');
  
  if (!navigator.geolocation) {
    alert('Geolocation is not supported by your browser');
    return;
  }
  
  btn.classList.add('spinning');
  btn.disabled = true;
  
  navigator.geolocation.getCurrentPosition(
    position => {
      const lat = position.coords.latitude;
      const lng = position.coords.longitude;
      
      map.setView([lat, lng], 15);
      marker.setLatLng([lat, lng]);
      updateLocationInput(lat, lng);
      
      btn.classList.remove('spinning');
      btn.disabled = false;
    },
    error => {
      btn.classList.remove('spinning');
      btn.disabled = false;
      alert('Unable to get your location. Please allow location access.');
    },
    {
      enableHighAccuracy: true,
      timeout: 10000,
      maximumAge: 0
    }
  );
}

function updateLocationInput(lat, lng) {
  const roundedLat = lat.toFixed(6);
  const roundedLng = lng.toFixed(6);
  
  document.getElementById('meeting_lat').value = roundedLat;
  document.getElementById('meeting_lng').value = roundedLng;
  
  fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
    .then(response => response.json())
    .then(data => {
      if (data.display_name) {
        document.getElementById('meeting_location').value = data.display_name;
      } else {
        document.getElementById('meeting_location').value = `Lat: ${roundedLat}, Lng: ${roundedLng}`;
      }
    })
    .catch(error => {
      document.getElementById('meeting_location').value = `Lat: ${roundedLat}, Lng: ${roundedLng}`;
    });
}

document.getElementById('hireForm').addEventListener('submit', function(e) {
  document.getElementById('loadingOverlay').classList.remove('d-none');
  document.getElementById('submitHireBtn').disabled = true;
});

function showVerificationWarning() {
  // Create toast element
  const toast = document.createElement('div');
  toast.className = 'toast-notification error show';
  toast.innerHTML = `
    <i class="bi bi-shield-exclamation"></i>
    <div>
      <strong>Verification Required!</strong>
      <p class="mb-0">You must verify your account before hiring laborers. 
      <a href="../view/profile.php" style="color: #dc3545; text-decoration: underline; font-weight: bold;">
        Verify Now
      </a></p>
    </div>
  `;
  
  document.body.appendChild(toast);
  
  // Auto remove after 5 seconds
  setTimeout(() => {
    toast.style.animation = 'slideIn 0.3s ease reverse';
    setTimeout(() => {
      toast.remove();
    }, 300);
  }, 5000);
}
</script>

</body>
</html>