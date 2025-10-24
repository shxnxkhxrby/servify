<?php
session_start();
require '../vendor/autoload.php'; // PHPMailer autoload

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Handle AJAX actions
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
    header('Content-Type: application/json');

    // 1️⃣ SEND OTP
    if ($_POST['action'] === 'send_otp') {
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $retype = trim($_POST['retype']);
        $role = trim($_POST['role']);

        // Validation
        if ($password !== $retype) {
            echo json_encode(["success" => false, "message" => "Passwords do not match."]);
            exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(["success" => false, "message" => "Invalid email address."]);
            exit;
        }
        if (!preg_match("/^(?=.*[A-Z])(?=.*[!@#$%^&*(),.?\":{}|<>]).{8,}$/", $password)) {
            echo json_encode(["success" => false, "message" => "Password must be at least 8 characters, include one uppercase letter and one special character."]);
            exit;
        }
        if (!in_array($role, ["client", "laborer"])) {
            echo json_encode(["success" => false, "message" => "Please select a valid role."]);
            exit;
        }

        // Generate OTP
        $otp = rand(100000, 999999);
        $_SESSION['signup_email'] = $email;
        $_SESSION['signup_password'] = $password;
        $_SESSION['signup_role'] = $role;
        $_SESSION['signup_otp'] = $otp;

        // Send OTP via PHPMailer
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'shanekherby2828@gmail.com'; // your Gmail
            $mail->Password   = 'zkxm uwci vyry lxhe'; // your Gmail App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('shanekherby2828@gmail.com', 'Servify');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Your Servify Verification Code';
            $mail->Body    = "<h3>Your OTP is: <b>$otp</b></h3><p>Use this code to verify your Servify account.</p>";
            $mail->send();

            echo json_encode(["success" => true, "message" => "OTP sent to your email."]);
        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => "Failed to send OTP."]);
        }
        exit;
    }

    // 2️⃣ VERIFY OTP
    if ($_POST['action'] === 'verify_otp') {
        $otp = $_POST['otp'] ?? '';
        if ($otp == $_SESSION['signup_otp']) {
            // OTP verified — pass details to user_details.php
            $_SESSION['email'] = $_SESSION['signup_email'];
            $_SESSION['password'] = $_SESSION['signup_password'];
            $_SESSION['role'] = $_SESSION['signup_role'];

            // Clear temporary OTP session data
            unset($_SESSION['signup_otp'], $_SESSION['signup_email'], $_SESSION['signup_password'], $_SESSION['signup_role']);

            echo json_encode([
                "success" => true,
                "message" => "Email verified successfully.",
                "redirect" => "user_details.php"
            ]);
        } else {
            echo json_encode(["success" => false, "message" => "Invalid OTP."]);
        }
        exit;
    }
}


// Check login status
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
    
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" type="text/css" href="../styles/signup.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<title>Sign Up - Servify</title>
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


<!-- SIGNUP FORM -->
<div class="signup-container text-center mt-5 pt-5">
  <h3>Create Account</h3>
  <form id="signupForm" class="mt-3">
    <div class="mb-3">
      <input type="email" name="email" class="form-control" placeholder="Email" required>
    </div>

    <div class="mb-3 input-group">
      <input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
      <button class="btn btn-outline-secondary" type="button" id="togglePassword"><i class="fa fa-eye"></i></button>
    </div>

    <div class="mb-3 input-group">
      <input type="password" name="retype" id="retype" class="form-control" placeholder="Retype Password" required>
      <button class="btn btn-outline-secondary" type="button" id="toggleRetype"><i class="fa fa-eye"></i></button>
    </div>

    <div class="mb-3">
      <select name="role" class="form-select" required>
        <option value="">Select Role</option>
        <option value="client">Client</option>
        <option value="laborer">Laborer</option>
      </select>
    </div>

    <button type="submit" class="btn btn-primary w-100">Sign Up</button>
  </form>

  <p class="small-text mt-3">Already have an account? <a href="../view/login.php">Login</a></p>
</div>

<!-- OTP MODAL -->
<div class="modal fade" id="otpModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content p-3">
      <h5>Verify Your Email</h5>
      <p>Enter the 6-digit code sent to your email.</p>
      <input type="text" id="otpInput" class="form-control my-2" placeholder="Enter OTP">
      <button class="btn btn-success w-100" id="verifyOtpBtn">Verify</button>
    </div>
  </div>
</div>

<!-- Toast -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
  <div id="toastMsg" class="toast align-items-center text-bg-primary border-0">
    <div class="d-flex">
      <div class="toast-body"></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const showToast = msg => {
  const toastEl = document.getElementById('toastMsg');
  toastEl.querySelector('.toast-body').textContent = msg;
  new bootstrap.Toast(toastEl).show();
};

// Send OTP
document.getElementById('signupForm').addEventListener('submit', async e => {
  e.preventDefault();
  const form = e.target;
  const data = new URLSearchParams({
    action: 'send_otp',
    email: form.email.value,
    password: form.password.value,
    retype: form.retype.value,
    role: form.role.value
  });

  const res = await fetch('', { method: 'POST', body: data });
  const json = await res.json();
  showToast(json.message);
  if (json.success) new bootstrap.Modal('#otpModal').show();
});

// Verify OTP
document.getElementById('verifyOtpBtn').addEventListener('click', async () => {
  const otp = document.getElementById('otpInput').value.trim();
  const res = await fetch('', {
    method: 'POST',
    body: new URLSearchParams({ action: 'verify_otp', otp })
  });
  const data = await res.json();
  showToast(data.message);
  if (data.success) {
    bootstrap.Modal.getInstance(document.getElementById('otpModal')).hide();
    setTimeout(() => (window.location.href = data.redirect), 1500);
  }
});

// Show/Hide Passwords
const toggle = (btnId, inputId) => {
  document.getElementById(btnId).addEventListener('click', function() {
    const field = document.getElementById(inputId);
    const icon = this.querySelector("i");
    if (field.type === "password") {
      field.type = "text";
      icon.classList.replace("fa-eye", "fa-eye-slash");
    } else {
      field.type = "password";
      icon.classList.replace("fa-eye-slash", "fa-eye");
    }
  });
};

toggle("togglePassword", "password");
toggle("toggleRetype", "retype");
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
</body>
</html>

