<?php
session_start();
include '../controls/connection.php';
include '../controls/profile_functions.php';
include '../controls/hire_functions.php';

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$job_id  = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;
$is_logged_in = isset($_SESSION['user_id']);
$current_user_id = $is_logged_in ? intval($_SESSION['user_id']) : null;


// Fetch logged-in user's name and profile picture
$current_user_name = '';
$profile_picture = 'uploads/profile_pics/default.jpg'; // Default fallback

if ($is_logged_in && $current_user_id) {
    $stmt = $conn->prepare("SELECT firstname, middlename, lastname, profile_picture FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $current_user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $user_row = $res->fetch_assoc();

        // Build full name
        $current_user_name = $user_row['firstname'];
        if (!empty($user_row['middlename'])) {
            $current_user_name .= ' ' . $user_row['middlename'];
        }
        $current_user_name .= ' ' . $user_row['lastname'];

        // Use profile picture if available
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
  <link rel="stylesheet" type="text/css" href="../styles/view_profile.css?v=<?php echo time(); ?>">
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

<!-- NAVIGATION BAR -->
<header>
  <div class="header-content">
    <div class="brand"><a href="../view/index.php">Servify</a></div>
    <div class="menu-container">
      <nav class="wrapper-2" id="menu">
        <p><a href="../view/browse.php">Services</a></p>
        <!-- <p><a href="#">Become a laborer</a></p> -->
        <p class="divider">|</p>

        <?php if ($is_logged_in): ?>
          <p class="profile-wrapper">
            <span class="profile-icon" onclick="toggleProfileMenu()">
              <img src="../<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile Picture" class="icon">
            </span>
            <div id="profile-menu" class="profile-menu d-none">
              <a href="../view/profile.php" class="user-info-link">
                <div class="user-info">
                  <span><?php echo htmlspecialchars($current_user_name); ?></span>
                  <i class="bi bi-pencil-square"></i>
                </div>
              </a>
              <a href="../view/messages.php"><i class="bi bi-chat-dots"></i> Inbox</a>
              <a href="#"><i class="bi bi-bell"></i> Notifications</a>
              <a href="#"><i class="bi bi-grid"></i> Dashboard</a>
              <a href="../controls/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </div>
          </p>
        <?php else: ?>
          <p class="login"><a href="../view/login.php"><i class="bi bi-box-arrow-in-right"></i> Login / Signup</a></p>
        <?php endif; ?>
      </nav>
    </div>
  </div>
</header>

<!-- Bottom Navigation (Mobile/Tablet Only) -->
<div class="bottom-nav mobile-only">
  <a href="../view/index.php">
    <div class="nav-item active" onclick="goToHome()">
      <i class="bi bi-house"></i>
      <span>Home</span>
    </div>
  </a>
  <a href="../view/browse.php">
    <div class="nav-item" onclick="goToServices()">
      <i class="bi bi-search"></i>
      <span>Services</span>
    </div>
  </a>
  
  <div class="nav-item" onclick="toggleMoreMenu()">
    <i class="bi bi-three-dots"></i>
    <span>More</span>
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
      <!-- Logged-in User Menu -->
      <div class="user-section">
        <div class="profile-info" onclick="toggleProfileMenu()">
          <a href="../view/profile.php" class="profile-link">
            <img src="../<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile Picture" class="icon">
            <h3 class="user-name"><?php echo htmlspecialchars($current_user_name); ?></h3>
          </a>
        </div>
        <i class="bi bi-pencil-square edit-icon"></i>
      </div>

      <div class="section-divider"></div>

      <div class="menu-options">
        <a href="../view/messages.php"><i class="bi bi-chat-dots"></i> Inbox</a>
        <a href="#"><i class="bi bi-bell"></i> Notifications</a>
        <a href="#"><i class="bi bi-grid"></i> Dashboard</a>
        <a href="../controls/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
      </div>

    <?php else: ?>
      <!-- Non-User Menu -->
      <div class="menu-options">
        <a href="../view/become-laborer.php"><i class="bi bi-person-workspace"></i> Become a laborer</a>
        <a href="../view/login.php"><i class="bi bi-person-circle"></i> Signin / Signup</a>
      </div>
    <?php endif; ?>
  </div>
</div>



<!-- CUSTOM PROFILE PAGE -->
<div class="custom-profile-page">
  <main class="grid-container">

    <!-- PROFILE SECTION -->
    <div class="profile-section position-relative">
      <!-- Floating Ellipsis Dropdown -->
      <div class="dropdown position-absolute top-0 end-0 me-2 mt-2">
        <button class="btn ellipsis-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="fa-solid fa-ellipsis-vertical"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <?php if ($is_logged_in): ?>
            <li><button class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#reportModal">Report</button></li>
          <?php else: ?>
            <li><a class="dropdown-item text-danger" href="../view/login.php">Report</a></li>
          <?php endif; ?>
        </ul>
      </div>

      <div class="profile-row">
        <div class="profile-img">
          <img src="../<?php echo htmlspecialchars($user['profile_picture']) ?: 'uploads/profile_pics/default.jpg'; ?>" alt="Profile Picture">
        </div>

        <div class="profile-info">
          <!-- Name + Verification -->
          <div class="name-verification d-flex align-items-center gap-2 mb-2">
            <h2 class="profile-name mb-0"><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['middlename'] . ' ' . $user['lastname']); ?></h2>
             <span class="verification-badge <?php echo $user['is_verified'] ? 'verified' : 'not-verified'; ?>">
               <?php echo $user['is_verified'] ? 'Verified' : ' Not Verified'; ?>
             </span>
          </div>

          <!-- Rating + Location -->
          <p class="rating"><?php echo renderStars($avg_rating); ?> (<?php echo number_format($avg_rating,1); ?>)</p>
          <p class="location">📍 <?php echo htmlspecialchars($user['location']); ?></p>

          <!-- Contact Icons -->
          <div class="social-links">
            <?php echo renderContactIcons($user); ?>
          </div>

          <!-- Action Buttons -->
          <div class="profile-buttons">
            <a href="../view/messages.php?receiver_id=<?php echo $user_id; ?>">
              <button class="message-btn">Message <i class="fa-solid fa-paper-plane"></i></button>
            </a>
            <?php if ($is_logged_in): ?>
              <button class="hire-btn" data-bs-toggle="modal" data-bs-target="#hireModal">Hire <i class="fa-solid fa-circle-check"></i></button>
            <?php else: ?>
              <a href="../view/login.php"><button class="hire-btn">Hire</button></a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>


    <!-- SKILLS SECTION -->
    <div class="skills-section">
      <h3>Services</h3>
      <div class="services-list">
        <?php while ($service = $services_result->fetch_assoc()): ?>
          <div class="service-item mb-3" style="background-color: transparent; border: none;">
            <h5><?php echo htmlspecialchars($service['job_name']); ?></h5>
            <p><?php echo htmlspecialchars($service['job_description']); ?></p>
          </div>
        <?php endwhile; ?>
        <?php if ($services_result->num_rows === 0): ?>
          <p>No services available.</p>
        <?php endif; ?>
      </div>
    </div>


    <!-- ABOUT SECTION -->
    <div class="about-section">
      <nav class="about-nav">
        <a href="#" class="active">About</a>
        <a href="#">Media</a>
        <a href="#">Reviews</a>
      </nav>
      <div class="about-tab-content">

        <!-- About -->
        <div class="tab-panel" id="aboutPanel">
          <p>This is the About content. Tell something about yourself here.</p>
        </div>

        <!-- Media -->
        <div class="tab-panel" id="mediaPanel" style="display:none;">
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
              endif;
            endwhile;

            foreach ($images as $index => $imgPath):
            ?>
              <img src="<?php echo $imgPath; ?>" alt="Service Image"
                   onclick="openLightbox(<?php echo $index; ?>)">
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Lightbox Modal (must be outside tab panels!) -->
        <div class="lightbox-overlay" id="lightbox">
          <div class="lightbox-content">
            <span class="lightbox-close" onclick="closeLightbox()">×</span>

            <div class="lightbox-image-wrapper">
              <span class="lightbox-nav lightbox-prev" onclick="prevImage()">❮</span>
              <img id="lightbox-img" class="lightbox-image" src="" alt="">
              <span class="lightbox-nav lightbox-next" onclick="nextImage()">❯</span>
            </div>

            <div class="lightbox-description" id="lightbox-desc"></div>
          </div>
        </div>

        <!-- Reviews -->
        <div class="tab-panel" id="reviewsPanel" style="display:none;">
          <h5>Rate & Review this Laborer</h5>
          <?php if ($is_logged_in): ?>
            <?php if ($current_user_verified): ?>
              <?php if ($can_rate): ?>
                <form method="POST" id="reviewForm">
                  <input type="hidden" name="rating_submit" value="1">
                  <div class="star-rating mb-2" title="Give rating">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                      <input type="radio" id="star<?php echo $i;?>" name="rating" value="<?php echo $i; ?>"
                        <?php echo ($user_previous_rating === $i) ? 'checked' : ''; ?>>
                      <label for="star<?php echo $i;?>"><i class="fa fa-star"></i></label>
                    <?php endfor; ?>
                  </div>
                  <textarea name="review" placeholder="Write your review..." required><?php echo htmlspecialchars($user_previous_review ?? ''); ?></textarea>
                  <button type="submit"><?php echo $user_previous_rating ? 'Update Review' : 'Submit Review'; ?></button>
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

          <hr>
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
      </div>
    </div>
  </main>
</div>

<!-- Hire Modal -->
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
  </div>
 </div>
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
  </div>
 </div>
</div>



<!-- Tab Switching Script -->
<script>
  document.addEventListener("DOMContentLoaded", function() {
    const tabs = document.querySelectorAll('.about-nav a');
    const panels = document.querySelectorAll('.tab-panel');

    tabs.forEach((tab, index) => {
      tab.addEventListener('click', (e) => {
        e.preventDefault();
        tabs.forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        panels.forEach(p => p.style.display = 'none');
        panels[index].style.display = 'block';
      });
    });

    // Bootstrap Toasts
    const toastElList = [].slice.call(document.querySelectorAll('.toast'));
    toastElList.forEach(function(toastEl) {
      new bootstrap.Toast(toastEl).show();
    });
  });
</script>


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

<script>
  function toggleMenu() {
    const menu = document.getElementById('menu');
    menu.classList.toggle('active');
  }
</script>

<script>
  // Toggle profile dropdown
  function toggleProfileMenu() {
    const menu = document.getElementById('profile-menu');
    menu.classList.toggle('d-none');
  }

  // Toggle fullscreen "More" menu
  function toggleMoreMenu() {
    const menu = document.getElementById('more-menu');
    menu.classList.toggle('d-none');
  }

  // Navigation actions
  function goToHome() {
    window.location.href = '../view/home.php';
  }

  function goToServices() {
    window.location.href = '../view/services.php';
  }
</script>

<script>
  const images = <?php echo json_encode($images); ?>;
  const descriptions = <?php echo json_encode($descriptions); ?>;
  let currentIndex = 0;

  function openLightbox(index) {
    currentIndex = index;
    document.getElementById('lightbox-img').src = images[index];
    document.getElementById('lightbox-desc').textContent = descriptions[index];
    document.getElementById('lightbox').style.display = 'flex';
  }

  function closeLightbox() {
    document.getElementById('lightbox').style.display = 'none';
  }

  function prevImage() {
    currentIndex = (currentIndex - 1 + images.length) % images.length;
    openLightbox(currentIndex);
  }

  function nextImage() {
    currentIndex = (currentIndex + 1) % images.length;
    openLightbox(currentIndex);
  }
</script>

</body>
</html>
