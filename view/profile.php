<?php
session_start();
include '../controls/connection.php';
include '../controls/hire_functions.php'; // Include hire functions

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$is_logged_in = isset($_SESSION['user_id']);

$sql = "SELECT firstname, middlename, lastname, fb_link, email, location, date_created, contact, 
               COALESCE(is_verified, 0) AS is_verified, profile_picture, role
        FROM users 
        WHERE user_id = ?";
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

// Fetch posted jobs (only relevant for laborers)
$job_sql = "SELECT jobs.job_id, jobs.job_name, user_jobs.job_description, user_jobs.job_image
            FROM jobs
            INNER JOIN user_jobs ON jobs.job_id = user_jobs.job_id
            WHERE user_jobs.user_id = ?";
$job_stmt = $conn->prepare($job_sql);
$job_stmt->bind_param("i", $user_id);
$job_stmt->execute();
$job_result = $job_stmt->get_result();
$job_stmt->close();

// Fetch hire requests for this laborer
$hire_requests = getHiresForUser($conn, $user_id, 'laborer');

// Handle Accept/Decline action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['respond_hire'])) {
    $hire_id = intval($_POST['hire_id']);
    $action = $_POST['action'] === 'accepted' ? 'accepted' : 'declined';
    $response_msg = respondToHire($conn, $hire_id, $action);
    echo "<script>alert('".htmlspecialchars($response_msg)."'); window.location.href='".$_SERVER['PHP_SELF']."';</script>";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Profile</title>
  <link rel="stylesheet" href="../styles/view_profile.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <style>
    body { padding-top: 10px; }
  </style>
</head>

<body>
  <nav class="navbar navbar-expand-lg fixed-top bg-white shadow-sm">
    <div class="container">
      <a class="navbar-brand" href="../view/index.php">Servify</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
        <ul class="navbar-nav">
          <?php if ($is_logged_in): ?>
            <li class="nav-item">
              <a class="nav-link" href="../view/index.php"><i class="bi bi-house"></i> Home</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="../view/messages.php"><i class="bi bi-chat-dots"></i> Messages</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="../controls/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
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

  <div class="container mt-2 pt-2">
    <!-- Profile Info -->
    <div class="row align-items-center mb-4 mt-5">
      <div class="col-md-3 text-center">
        <img src="../<?php echo htmlspecialchars($row['profile_picture']) ?: 'uploads/profile_pics/default.jpg'; ?>" alt="Profile Picture" class="img-fluid" style="width: 200px; height: 200px; object-fit: cover;">
      </div>
      <div class="col-md-9">
        <h3 class="d-flex align-items-center gap-2">
          <?php echo htmlspecialchars($row['firstname'] . ' ' . $row['middlename'] . ' ' . $row['lastname']); ?>
          <?php if ($row['is_verified']): ?>
            <span class="badge bg-success">✅ Verified</span>
          <?php else: ?>
            <span class="badge bg-danger">❌ Not Verified</span>
          <?php endif; ?>
        </h3>
        <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($row['location']); ?></p>
        <p><strong>Email: </strong> <?php echo htmlspecialchars($row['email']); ?></p>
        <p><strong>Contact: </strong> <?php echo htmlspecialchars($row['contact']); ?></p>
        <p><strong>Facebook: </strong> <a href="<?php echo htmlspecialchars($row['fb_link']); ?>" target="_blank"><?php echo htmlspecialchars($row['fb_link']);?></a></p>
        <p><strong>Member since: </strong> <?php echo date('F j, Y', strtotime($row['date_created'])); ?></p>
        <button onclick="toggleEditForm()" class="btn btn-primary mt-2">Edit Details</button>
      </div>
    </div>

    <!-- Hire Requests Section -->
    <?php if ($row['role'] === 'laborer'): ?>
      <hr>
      <div class="hire-requests mb-5">
        <h4>Hire Requests</h4>
        <?php if ($hire_requests->num_rows > 0): ?>
          <ul class="list-group">
            <?php while ($hire = $hire_requests->fetch_assoc()): ?>
              <li class="list-group-item d-flex justify-content-between align-items-center flex-column flex-md-row">
                <div>
                  <strong>From: <?php echo htmlspecialchars($hire['employer_firstname'] . ' ' . $hire['employer_middlename'] . ' ' . $hire['employer_lastname']); ?></strong><br>
                  <strong>Message:</strong> <?php echo htmlspecialchars($hire['message']); ?><br>
                  <strong>Location:</strong> <?php echo htmlspecialchars($hire['meeting_location']); ?><br>
                  <strong>Status:</strong> <?php echo ucfirst($hire['status']); ?>
                </div>
                <?php if ($hire['status'] === 'pending'): ?>
                  <div class="mt-2 mt-md-0">
                    <form action="" method="POST" class="d-inline">
                      <input type="hidden" name="hire_id" value="<?php echo $hire['id']; ?>">
                      <input type="hidden" name="action" value="accepted">
                      <button type="submit" name="respond_hire" class="btn btn-success btn-sm">Accept</button>
                    </form>
                    <form action="" method="POST" class="d-inline">
                      <input type="hidden" name="hire_id" value="<?php echo $hire['id']; ?>">
                      <input type="hidden" name="action" value="declined">
                      <button type="submit" name="respond_hire" class="btn btn-danger btn-sm">Decline</button>
                    </form>
                  </div>
                <?php endif; ?>
              </li>
            <?php endwhile; ?>
          </ul>
        <?php else: ?>
          <p>No hire requests at the moment.</p>
        <?php endif; ?>
      </div>
    <?php endif; ?>


    <!-- Edit Form -->
    <div id="editDetailsForm" class="card card-body mb-4" style="display: none;">
      <form action="../controls/user/update_profile.php" method="POST" enctype="multipart/form-data">
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
            <input type="text" name="fb_link" value="<?php echo htmlspecialchars($row['fb_link']); ?>" class="form-control">
          </div>
          <div class="col-md-12 mb-3">
            <label>Location:</label>
            <input type="text" name="location" value="<?php echo htmlspecialchars($row['location']); ?>" class="form-control" required>
          </div>
          <div class="col-md-12 mb-3">
            <label>Profile Picture:</label>
            <input type="file" name="profile_pic" class="form-control">
          </div>
        </div>
        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
        <button type="submit" class="btn btn-success">Save Changes</button>
      </form>
    </div>

    <!-- Show Job List ONLY if user is laborer -->
    <?php if ($row['role'] === 'laborer'): ?>
      <hr>
      <div class="job-container mb-4">
        <h2>My Posted Jobs</h2>
        <?php if ($job_result->num_rows > 0): ?>
          <ul class="list-group">
            <?php while ($job = $job_result->fetch_assoc()): ?>
              <li class="list-group-item d-flex justify-content-between align-items-start flex-column flex-md-row">
                <div>
                  <strong><?php echo htmlspecialchars($job['job_name']); ?></strong>
                  <p class="mb-1"><?php echo htmlspecialchars($job['job_description']); ?></p>
                  <?php if (!empty($job['job_image'])): ?>
                    <img src="http://localhost/servify/uploads/<?php echo htmlspecialchars($job['job_image']); ?>" alt="Job Image" class="img-fluid" style="width: 100px; height: 100px; object-fit: cover;">
                  <?php else: ?>
                    <p>No job image available.</p>
                  <?php endif; ?>
                </div>
                <div>
                  <form action="edit_labor.php" method="POST" class="d-inline">
                    <input type="hidden" name="job_id" value="<?php echo $job['job_id']; ?>">
                    <button type="submit" class="btn btn-warning btn-sm">Edit</button>
                  </form>
                  <form action="delete_labor.php" method="POST" class="d-inline">
                    <input type="hidden" name="job_id" value="<?php echo $job['job_id']; ?>">
                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this job?');">Delete</button>
                  </form>
                </div>
              </li>
            <?php endwhile; ?>
          </ul>
        <?php else: ?>
          <p>No jobs posted yet.</p>
        <?php endif; ?>
      </div>

      <!-- Add Labor -->
      <div class="text-center mb-4">
        <form action="add_labor.php" method="POST">
          <button type="submit" class="btn btn-primary">+ Add Labor</button>
        </form>
      </div>
    <?php endif; ?>

    <hr>

    <!-- Account Verification Section -->
    <div class="verification-container mb-5">
      <h4>Account Verification</h4>
      <div class="alert alert-info">
        <strong>Verification Status:</strong> 
        <?php echo ($row['is_verified'] == 1) ? '✅ Verified' : '❌ Not Verified'; ?>
      </div>

      <?php if ($row['is_verified'] == 0): ?>
        <p>Your account has not been verified yet. Please upload the required documents for verification.</p>
        <form action="../controls/user/upload_verification.php" method="POST" enctype="multipart/form-data">
          <label for="id_proof">Primary ID (Barangay ID if laborer):</label>
          <input type="file" name="id_proof" id="id_proof" class="form-control mb-2" required>
          <label for="supporting_doc">Supporting document (e.g. Birth Certificate, other Government Issued ID, etc.)</label>
          <input type="file" name="supporting_doc" id="supporting_doc" class="form-control mb-2" required>
          <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
          <button type="submit" class="btn btn-success">Upload Documents</button>
        </form>

      <?php endif; ?>
    </div>
  </div>

<script>
  function toggleEditForm() {
    const form = document.getElementById('editDetailsForm');
    form.style.display = (form.style.display === 'none' || form.style.display === '') ? 'block' : 'none';
  }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
