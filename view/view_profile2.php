<?php
session_start();
include '../controls/connection.php';
include '../controls/profile_functions.php';
include '../controls/hire_functions.php';

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$job_id  = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;
$is_logged_in = isset($_SESSION['user_id']);
$current_user_id = $is_logged_in ? intval($_SESSION['user_id']) : null;

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

// Check if current user can rate/review (verified + has completed hire + not self)
$can_rate = false;
if ($is_logged_in && $current_user_verified && $current_user_id !== $user_id) {
    $can_rate = hasHireWithLaborer($conn, $user_id, $current_user_id);
}

/**
 * Upsert helper - update existing rating/review or insert new one
 * Returns true on success, false on failure.
 */
function upsertLaborerRating($conn, $laborer_id, $user_id, $rating, $review_text) {
    // ensure we have a connection
    if (!$conn) return false;

    // check if row exists
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

// Handle rating-only submission (also accepts an optional 'review' textarea)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['rating_submit'])) {
    if (!$is_logged_in) {
        $rating_error = "You must log in to rate.";
    } elseif (!$current_user_verified) {
        $rating_error = "You must verify your account before rating.";
    } elseif ($current_user_id === $user_id) {
        $rating_error = "You cannot rate your own profile.";
    } elseif (!$can_rate) {
        $rating_error = "You can only rate this laborer after completing a hire transaction.";
    } else {
        $rating = intval($_POST['rating'] ?? 0);
        $review_text = trim($_POST['review'] ?? ''); // optional
        if ($rating < 1 || $rating > 5) {
            $rating_error = "Invalid rating value.";
        } else {
            if (upsertLaborerRating($conn, $user_id, $current_user_id, $rating, $review_text)) {
                $rating_success = "Your rating has been saved.";
            } else {
                $rating_error = "Failed to save rating. Please try again.";
            }
        }
    }
}

// Handle rating + review submission (if you use a separate form named submit_review)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit_review'])) {
    if (!$is_logged_in) {
        $review_error = "You must log in to leave a review.";
        $rating_error = $review_error; // mirror toasts if needed
    } elseif (!$current_user_verified) {
        $review_error = "You must verify your account before leaving a review.";
        $rating_error = $review_error;
    } elseif ($current_user_id === $user_id) {
        $review_error = "You cannot review your own profile.";
        $rating_error = $review_error;
    } elseif (!$can_rate) {
        $review_error = "You can only review this laborer after completing a hire transaction.";
        $rating_error = $review_error;
    } else {
        $rating = intval($_POST['rating'] ?? 0);
        $review = trim($_POST['review'] ?? '');
        if ($rating < 1 || $rating > 5) {
            $review_error = "Invalid rating value.";
            $rating_error = $review_error;
        } else {
            if (upsertLaborerRating($conn, $user_id, $current_user_id, $rating, $review)) {
                $review_success = "Your review has been submitted.";
                $rating_success = $review_success; // so your existing toast shows it
            } else {
                $review_error = "Failed to submit review. Please try again.";
                $rating_error = $review_error;
            }
        }
    }
}

// Get rating stats and user’s previous rating
$rating_stats = getRatingStats($conn, $user_id);
$avg_rating = $rating_stats['avg_rating'];
$total_ratings = $rating_stats['total_ratings'];
$user_previous_rating = $is_logged_in ? getUserRating($conn, $user_id, $current_user_id) : 0;

// (Optional) fetch current user's previous review text to prefill the textarea if you want
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
            // if you want to override $user_previous_rating with DB value, uncomment:
            // $user_previous_rating = intval($r['rating']);
        }
        $stmt->close();
    }
}

// Handle report submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['report_submit'])) {
    if (!$is_logged_in) {
        $error_message = "You must log in to submit a report.";
    } else {
        $report_reasons = $_POST['report_reason'] ?? [];
        $additional_details = $_POST['additional_details'] ?? "";
        $success_message = handleReport($conn, $user_id, $report_reasons, $additional_details);
    }
}

// Handle hire submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['hire_submit'])) {
    if (!$is_logged_in) {
        $error_message = "You must log in to hire.";
    } else {
        $meeting_location = trim($_POST['meeting_location'] ?? '');
        $message = trim($_POST['hire_message'] ?? '');
        if (empty($meeting_location)) {
            $error_message = "Please specify a meeting location.";
        } else {
            $success_message = sendHireRequest($conn, $current_user_id, $user_id, $message, $meeting_location);
        }
    }
}

function renderStars($rating) {
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($rating >= $i) {
            $html .= '<i class="fa-solid fa-star" style="color:gold;font-size:1.5rem;"></i> ';
        } elseif ($rating >= ($i - 0.5)) {
            $html .= '<i class="fa-solid fa-star-half-stroke" style="color:gold;font-size:1.5rem;"></i> ';
        } else {
            $html .= '<i class="fa-regular fa-star" style="color:gold;font-size:1.5rem;"></i> ';
        }
    }
    return $html;
}

function renderContactIcons($user) {
    $html = '';
    if (!empty($user['fb_link'])) {
        $html .= '<a href="' . htmlspecialchars($user['fb_link']) . '" target="_blank" class="me-2"><i class="fab fa-facebook fa-lg"></i></a>';
    }
    if (!empty($user['email'])) {
        $html .= '<a href="mailto:' . htmlspecialchars($user['email']) . '" class="me-2"><i class="fas fa-envelope fa-lg"></i></a>';
    }
    if (!empty($user['contact'])) {
        $html .= '<a href="tel:' . htmlspecialchars($user['contact']) . '" class="me-2"><i class="fas fa-phone fa-lg"></i></a>';
    }
    return $html;
}

// Fetch all reviews for this laborer (returns mysqli_result so your HTML can use ->num_rows and ->fetch_assoc())
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

// prepare reviews_result for your HTML
$reviews_result = getLaborerReviewsResult($conn, $user_id);
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" type="text/css" href="../styles/view_profile.css">
  <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <title>Profile</title>

  <style>
    .star-rating { direction: rtl; display: inline-flex; align-items: center; }
    .star-rating input { display: none; }
    .star-rating label { font-size: 1.8rem; color: #ccc; cursor: pointer; transition: color 0.15s; padding: 0 4px; }
    .star-rating input:checked ~ label,
    .star-rating label:hover,
    .star-rating label:hover ~ label { color: gold; }
    .rating-summary { margin-left: 12px; font-size: 0.95rem; color: #333; display: inline-block; vertical-align: middle; }
    .toast-container { position: fixed; bottom: 1rem; right: 1rem; z-index: 2000; }

    /* Improved contact icons style */
    .contact-icons a {
        color: #555;
        font-size: 1.2rem;
        margin-right: 10px;
        transition: color 0.2s;
    }
    .contact-icons a:hover {
        color: #0d6efd;
        text-decoration: none;
    }
    .contact-icons {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .contact-icons .btn {
        padding: 0.25rem 0.6rem;
        font-size: 0.85rem;
    }
  </style>
</head>
<body>

  <!-- NAVBAR -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm fixed-top">
    <div class="container">
      <!-- Logo -->
      <a class="navbar-brand fw-bold text-white" href="index.php">Servify</a>

      <!-- Toggler -->
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
        aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <!-- Navbar Links -->
      <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
        <ul class="navbar-nav">
          <?php if ($is_logged_in): ?>
            <!-- Messages Icon -->
            <li class="nav-item position-relative">
              <a class="nav-link" href="../view/messages.php">
                <i class="bi bi-chat-dots"></i> Messages
                <span id="unread-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none">
                  
                </span>
              </a>
            </li>

            <!-- Profile -->
            <li class="nav-item">
              <a class="nav-link" href="../view/profile.php">
                <i class="bi bi-person-circle"></i> Profile
              </a>
            </li>

            <!-- Logout -->
            <li class="nav-item">
              <a class="nav-link" href="../controls/logout.php">
                <i class="bi bi-box-arrow-right"></i> Logout
              </a>
            </li>
          <?php else: ?>
            <li class="nav-item"><a class="nav-link" href="../view/signup.php">Sign Up</a></li>
            <li class="nav-item"><a class="nav-link">|</a></li>
            <li class="nav-item"><a class="nav-link" href="../view/login.php">Login</a></li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>

<div class="container mt-5 pt-4">

  <!-- PROFILE -->
  <div class="profile-section d-flex flex-wrap justify-content-between align-items-start">
      <div class="profile-pic-container me-3 mb-3 mb-md-0">
          <img src="../<?php echo htmlspecialchars($user['profile_picture']) ?: 'uploads/profile_pics/default.jpg'; ?>" 
               alt="Profile Picture" 
               class="profile-pic" 
               style="width:140px;height:140px;object-fit:cover;border-radius:10px;">
      </div>

      <div class="flex-grow-1">
          <!-- Name & Rating -->
          <div class="name-rating d-flex flex-wrap align-items-center justify-content-between mb-2">
              <div>
                <h3 class="mb-1"><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['middlename'] . ' ' . $user['lastname']); ?></h3>
                <div class="profile-detail">
                    <?php echo $user['is_verified'] ? '<span class="verified">✅ Verified</span>' : '<span class="not-verified">❌ Not Verified</span>'; ?>
                </div>
              </div>
              <div class="text-end">
                  <div>
                      <span class="rating-summary"><?php echo number_format($avg_rating,1); ?></span>
                      <?php echo renderStars($avg_rating); ?>
                  </div>
                  <div class="text-muted" style="font-size:0.9rem;"><?php echo $total_ratings; ?> rating<?php echo $total_ratings !== 1 ? 's' : ''; ?></div>
              </div>
          </div>

          <!-- Location & Phone -->
          <p class="mb-1"><i class="fas fa-map-marker-alt me-2" style="color:#0d6efd;"></i><?php echo htmlspecialchars($user['location']); ?></p>
          <p class="mb-2"><i class="fas fa-phone-alt me-2" style="color:#0d6efd;"></i><?php echo htmlspecialchars($user['contact']); ?></p>

          <!-- Contact Icons -->
          <div class="contact-icons mb-3">
              <?php echo renderContactIcons($user); ?>
          </div>

          <!-- Message, Hire & Report Buttons -->
          <div class="d-flex flex-wrap gap-2 mb-4">
              <!-- Message Button -->
              <a href="../view/messages.php?receiver_id=<?php echo $user_id; ?>" title="Send Message">
                  <button class="btn btn-info">Message</button>
              </a>

              <!-- Hire Button -->
              <?php if ($is_logged_in): ?>
                  <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#hireModal">Hire</button>
              <?php else: ?>
                  <a href="../view/login.php" class="btn btn-success">Hire</a>
              <?php endif; ?>

              <!-- Report Button -->
              <?php if ($is_logged_in): ?>
                  <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#reportModal">Report</button>
              <?php else: ?>
                  <a href="../view/login.php" class="btn btn-danger">Report</a>
              <?php endif; ?>
          </div>


      </div>
  </div>

  <div class="modal fade" id="hireModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title">Hire Laborer</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
      <form method="POST" id="hireForm">
        <input type="hidden" name="hire_submit" value="1">
        <div class="mb-3">
          <label for="meeting_location" class="form-label">Meeting Location</label>
          <input type="text" class="form-control" name="meeting_location" id="meeting_location" placeholder="Enter location">
        </div>
        <div class="mb-3">
          <label for="hire_message" class="form-label">Message (optional)</label>
          <textarea class="form-control" name="hire_message" id="hire_message" placeholder="Message to laborer"></textarea>
        </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      <button type="submit" class="btn btn-success">Send Hire Request</button>
      </form>
    </div>
  </div></div>
</div>


  <hr>
<!-- RATING & REVIEW SECTION -->
<div class="mb-4">
    <h5>Rate & Review this Laborer</h5>
    <?php if ($is_logged_in): ?>
        <?php if ($current_user_verified): ?>
            <?php if ($can_rate): ?>
                <form method="POST">
                    <input type="hidden" name="rating_submit" value="1">
                    <div class="star-rating mb-2" title="Give rating">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" id="star<?php echo $i;?>" name="rating" value="<?php echo $i; ?>"
                                <?php echo ($user_previous_rating === $i) ? 'checked' : ''; ?>>
                            <label for="star<?php echo $i;?>"><i class="fa fa-star"></i></label>
                        <?php endfor; ?>
                    </div>
                    <div class="mb-2">
                        <textarea class="form-control" name="review" placeholder="Write your review..."><?php echo htmlspecialchars($user_previous_review ?? ''); ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <?php echo $user_previous_rating ? 'Update Review' : 'Submit Review'; ?>
                    </button>
                </form>
            <?php else: ?>
                <p class="text-warning">You can only review this laborer after completing a hire transaction.</p>
            <?php endif; ?>
        <?php else: ?>
            <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#verifyModal">
                You must verify your account to rate/review
            </button>
        <?php endif; ?>
    <?php else: ?>
        <p><a href="../view/login.php">Log in</a> to leave a review.</p>
    <?php endif; ?>
</div>

<!-- REVIEWS LIST -->
<div class="mb-4">
    <h5>Rating & Reviews</h5>
    <?php if ($reviews_result && $reviews_result->num_rows > 0): ?>
        <?php while ($rev = $reviews_result->fetch_assoc()): ?>
            <div class="border rounded p-2 mb-2">
                <strong><?php echo htmlspecialchars($rev['firstname'] . " " . $rev['lastname']); ?></strong>
                <small class="text-muted">(<?php echo $rev['created_at']; ?>)</small>
                <div><?php echo renderStars($rev['rating']); ?></div>
                <?php if (!empty($rev['review'])): ?>
                    <p><?php echo nl2br(htmlspecialchars($rev['review'])); ?></p>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p class="text-muted">No reviews yet.</p>
    <?php endif; ?>
</div>



  <hr>

  <!-- SERVICES -->
  <div class="row">
    <div class="col-md-12">
      <h6 class="mt-3">Services</h6>
      <div class="services-list">
        <?php while ($service = $services_result->fetch_assoc()): ?>
          <div class="service-item mb-3 p-3 border rounded">
              <h5><?php echo htmlspecialchars($service['job_name']); ?></h5>
              <p><?php echo htmlspecialchars($service['job_description']); ?></p>
              <?php if (!empty($service['job_image'])): ?>
                  <img src="../uploads/<?php echo htmlspecialchars($service['job_image']); ?>" alt="Service Image" class="service-image" style="width:100%;max-width:300px;max-height:300px;height:auto;border-radius:10px;margin-top:10px;">
              <?php else: ?>
                  <p class="text-muted">This laborer has not yet added a photo for this job.</p>
              <?php endif; ?>
          </div>
        <?php endwhile; ?>
        <?php if ($services_result->num_rows === 0): ?>
          <p>No services available.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Toast Container -->
<div class="toast-container">
  <?php if (!empty($rating_success)): ?>
    <div class="toast align-items-center text-bg-success border-0" role="alert" data-bs-delay="3000" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body"><?php echo htmlspecialchars($rating_success); ?></div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    </div>
  <?php endif; ?>
  <?php if (!empty($rating_error)): ?>
    <div class="toast align-items-center text-bg-warning border-0" role="alert" data-bs-delay="3000" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body"><?php echo htmlspecialchars($rating_error); ?></div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    </div>
  <?php endif; ?>
  <?php if (!empty($success_message)): ?>
    <div class="toast align-items-center text-bg-success border-0" role="alert" data-bs-delay="3000" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body"><?php echo htmlspecialchars($success_message); ?></div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    </div>
  <?php endif; ?>
  <?php if (!empty($error_message)): ?>
    <div class="toast align-items-center text-bg-danger border-0" role="alert" data-bs-delay="3000" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body"><?php echo htmlspecialchars($error_message); ?></div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- Verify Modal -->
<div class="modal fade" id="verifyModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title">Account Verification Required</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">Your account is not verified yet. Do you want to verify now?</div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
      <a href="../view/profile.php" class="btn btn-primary">Yes, Verify</a>
    </div>
  </div></div>
</div>

<!-- Report Modal -->
<div class="modal fade" id="reportModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title">Report User</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
      <form method="POST" id="reportForm">
        <input type="hidden" name="report_submit" value="1">
        <select class="form-select" name="report_reason[]">
          <option value="false_information">False Information</option>
          <option value="nudity">Nudity</option>
          <option value="harassment">Harassment</option>
          <option value="spam">Spam</option>
          <option value="hate_speech">Hate Speech</option>
          <option value="scam">Scam</option>
          <option value="other">Other</option>
        </select>
        <textarea class="form-control mt-2" name="additional_details" placeholder="Additional details (optional)"></textarea>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      <button type="submit" class="btn btn-danger">Submit Report</button>
      </form>
    </div>
  </div></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
<script>
  document.addEventListener("DOMContentLoaded", function() {
    const toastElList = [].slice.call(document.querySelectorAll('.toast'));
    toastElList.forEach(function(toastEl) {
      new bootstrap.Toast(toastEl).show();
    });
  });
</script>
</body>
</html> 