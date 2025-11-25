<?php
session_start();
include '../controls/connection.php';

if (!isset($_SESSION['email'], $_SESSION['password'], $_SESSION['role'])) {
    header("Location: signup.php");
    exit();
}

// Fetch locations from DB for laborers
$locations = [];
$sql = "SELECT location_id, location_name, barangay, city, province FROM locations";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $locations[] = $row;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_SESSION['email'];
    $password = $_SESSION['password'];
    $role = $_SESSION['role'];  // get role from session

    $firstname = $_POST['firstname'];
    $middlename = $_POST['middlename'];
    $lastname = $_POST['lastname'];
    $fb_link = $_POST['fb_link'];
    $contact = $_POST['contact'];
    $date_created = date("Y-m-d H:i:s");

    // Handle location differently based on role
    if ($role === "client") {
        $location = $_POST['location_text'];  // free text for client
    } else {
        $location = $_POST['location_select']; // must be from DB for laborer
    }

    $credit_score = 100;
    $is_verified = 0;

    $sql = "INSERT INTO users 
            (email, password, firstname, middlename, lastname, fb_link, location, contact, date_created, role, credit_score, is_verified) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssssssssssii", 
        $email, $password, $firstname, $middlename, $lastname, 
        $fb_link, $location, $contact, $date_created, $role, 
        $credit_score, $is_verified
    );

    if ($stmt->execute()) {
        unset($_SESSION['email'], $_SESSION['password'], $_SESSION['role']); 
        header("Location: login.php");
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}

$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="../styles/signup.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<title>Sign Up</title>
</head>
<body>
<style>
  body {
  margin: 0;
  padding: 0;
  height: 100vh;
  background-size: cover;
  background-position: center;
  background-attachment: fixed;
  position: relative; /* needed so ::before works correctly */
  overflow-x: hidden;
}

/* ✅ Blurred background layer only */
body::before {
  content: "";
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: url('../starita.png') center/cover no-repeat;
  filter: blur(6px);
  z-index: -1; /* keeps it behind everything */
}
</style>

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
  <a href="../index.php">
    <div class="nav-item active">
      <i class="bi bi-house"></i>
      <span>Home</span>
    </div>
  </a>
  <a href="../browse.php">
    <div class="nav-item">
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
        <a href="../view/login.php"><i class="bi bi-person-circle"></i> Signin / Signup</a>
      </div>
    <?php endif; ?>
  </div>
</div>


<div class="signup-container text-center mt-md-5 pt-md-5 mt-4 pt-4">
<h3>Complete Your Profile</h3>
<form action="" method="POST" class="mt-3">

  <div class="mb-3">
      <input class="form-control" name="firstname" placeholder="First Name" required>
  </div>
  <div class="mb-3">
      <input class="form-control" name="middlename" placeholder="Middle Name">
  </div>
  <div class="mb-3">
      <input class="form-control" name="lastname" placeholder="Last Name" required>
  </div>
  <div class="mb-3">
      <input class="form-control" name="fb_link" placeholder="Facebook Link">    
  </div>

  <!-- Location Field -->
  <div class="mb-3">
      <?php if ($_SESSION['role'] === "client"): ?>
          <input type="text" class="form-control" name="location_text" placeholder="Enter your location (any)" required>
      <?php else: ?>
          <select class="form-control" name="location_select" required>
              <option value="">Select Location</option>
              <?php foreach ($locations as $loc): ?>
                  <option value="<?= htmlspecialchars($loc['location_name']) ?>">
                      <?= htmlspecialchars($loc['location_name'] . ", " . $loc['barangay']) ?>
                  </option>
              <?php endforeach; ?>
          </select>
      <?php endif; ?>
  </div>

  <div class="mb-3">
      <input class="form-control" name="contact" placeholder="Contact Number" required>
  </div>

  <button type="submit" class="btn btn-primary w-100">Submit</button>
</form> 
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

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
</script>
</body>
</html>