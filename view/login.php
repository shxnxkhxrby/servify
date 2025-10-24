<?php
session_start();
require __DIR__ . '/../vendor/autoload.php'; // PHPMailer autoload

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Handle AJAX requests
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
    header('Content-Type: application/json');

    // 1️⃣ Send OTP
    if ($_POST['action'] === 'send_otp') {
        $email = trim($_POST['email']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
            exit;
        }

        // Generate OTP
        $otp = rand(100000, 999999);
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_otp'] = $otp;

        // Send email via PHPMailer
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'shanekherby2828@gmail.com'; // your gmail
            $mail->Password   = 'zkxm uwci vyry lxhe'; // app password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('shanekherby2828@gmail.com', 'Servify');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Your Servify Password Reset Code';
            $mail->Body    = "<h3>Your OTP is: <b>$otp</b></h3><p>Use this code to reset your Servify password.</p>";

            $mail->send();
            echo json_encode(['success' => true, 'message' => 'OTP sent to your email.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to send email.']);
        }
        exit;
    }

    // 2️⃣ Verify OTP
    if ($_POST['action'] === 'verify_otp') {
        $otp = $_POST['otp'] ?? '';
        if ($otp == $_SESSION['reset_otp']) {
            $_SESSION['otp_verified'] = true;
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Incorrect OTP.']);
        }
        exit;
    }

    // 3️⃣ Reset Password
    if ($_POST['action'] === 'reset_password') {
        if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
            echo json_encode(['success' => false, 'message' => 'OTP not verified.']);
            exit;
        }

        include '../controls/connection.php';
        $new_pass = $_POST['new_password']; // no hash (for testing)
        $email = $_SESSION['reset_email'];

        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $new_pass, $email);

        if ($stmt->execute()) {
            session_unset();
            echo json_encode(['success' => true, 'message' => 'Password reset successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to reset password.']);
        }
        exit;
    }
}

$is_logged_in = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="../styles/login.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <title>Login - Servify</title>
</head>
<body>

<!-- NAVIGATION BAR -->
<header>
  <div class="header-content">
    <div class="brand"><a href="../view/index.php">Servify</a></div>
    <div class="menu-container">
      <nav class="wrapper-2" id="menu">
        <p><a href="../view/browse.php">Services</a></p>
        <p><a href="#">Become a laborer</a></p>
        <p class="divider">|</p>

        <?php if ($is_logged_in): ?>
          <p class="profile-wrapper">
            <span class="profile-icon" onclick="toggleProfileMenu()">
              <img src="../image/man.png" alt="Profile" class="icon">
            </span>
            <div id="profile-menu" class="profile-menu d-none">
              <div class="user-info">
                <a href="../view/profile.php">
                  <span>name</span>
                </a>
                <i class="bi bi-pencil-square"></i>
              </div>
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
  <div class="nav-item active" onclick="goToHome()">
    <i class="bi bi-house"></i>
    <span>Home</span>
  </div>
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
            <img src="../image/man.png" alt="Profile" class="icon">
            <h3 class="user-name">Name...</h3>
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

<!-- LOGIN FORM -->
<div class="login-container text-center mt-5 pt-5">
  <h3>Log in</h3>
  <form action="../controls/login_validation.php" method="POST" class="mt-3">
    <div class="mb-3">
      <input name="email" type="email" class="form-control" placeholder="Email" required>
    </div>
    <div class="mb-3">
      <input name="password" type="password" class="form-control" placeholder="Password" required>
    </div>
    <div class="d-flex justify-content-between mb-3">
      <a href="#" class="small-text" id="forgotPasswordLink">Forgot password?</a>
    </div>
    <button type="submit" class="btn btn-primary w-100">LOGIN</button>
  </form>
  <p class="small-text mt-3">Don't have an account? <a href="../view/signup.php">Sign up</a></p>
</div>

<!-- FORGOT PASSWORD MODALS -->
<div class="modal fade" id="forgotModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content p-3">
      <h5>Forgot Password</h5>
      <input type="email" id="forgotEmail" class="form-control my-2" placeholder="Enter your email">
      <button class="btn btn-primary w-100" id="sendOtpBtn">Send OTP</button>
    </div>
  </div>
</div>

<div class="modal fade" id="otpModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content p-3">
      <h5>Enter Verification Code</h5>
      <input type="text" id="otpInput" class="form-control my-2" placeholder="Enter OTP">
      <button class="btn btn-success w-100" id="verifyOtpBtn">Verify</button>
    </div>
  </div>
</div>

<div class="modal fade" id="resetModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content p-3">
      <h5>Reset Password</h5>

      <!-- New Password -->
      <div class="input-group my-2">
        <input type="password" id="newPass" class="form-control" placeholder="New Password">
        <button class="btn btn-outline-secondary" type="button" id="toggleNewPass"><i class="bi bi-eye"></i></button>
      </div>

      <!-- Retype Password -->
      <div class="input-group my-2">
        <input type="password" id="retypePass" class="form-control" placeholder="Retype New Password">
        <button class="btn btn-outline-secondary" type="button" id="toggleRetypePass"><i class="bi bi-eye"></i></button>
      </div>

      <button class="btn btn-success w-100 mt-2" id="resetPassBtn">Reset Password</button>
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

// Modal actions
document.getElementById('forgotPasswordLink').addEventListener('click', () => {
  new bootstrap.Modal('#forgotModal').show();
});

document.getElementById('sendOtpBtn').addEventListener('click', async () => {
  const email = document.getElementById('forgotEmail').value.trim();
  if (!email) return showToast('Enter your email.');
  const res = await fetch('', {
    method: 'POST',
    body: new URLSearchParams({ action: 'send_otp', email })
  });
  const data = await res.json();
  showToast(data.message);
  if (data.success) {
    bootstrap.Modal.getInstance(document.getElementById('forgotModal')).hide();
    new bootstrap.Modal('#otpModal').show();
  }
});

document.getElementById('verifyOtpBtn').addEventListener('click', async () => {
  const otp = document.getElementById('otpInput').value.trim();
  const res = await fetch('', {
    method: 'POST',
    body: new URLSearchParams({ action: 'verify_otp', otp })
  });
  const data = await res.json();
  showToast(data.message || (data.success ? 'OTP Verified!' : 'Error'));
  if (data.success) {
    bootstrap.Modal.getInstance(document.getElementById('otpModal')).hide();
    new bootstrap.Modal('#resetModal').show();
  }
});

document.getElementById('resetPassBtn').addEventListener('click', async () => {
  const newPass = document.getElementById('newPass').value.trim();
  const retypePass = document.getElementById('retypePass').value.trim();

  if (newPass.length < 8) return showToast('Password must be at least 8 characters.');
  if (newPass !== retypePass) return showToast('Passwords do not match.');

  const res = await fetch('', {
    method: 'POST',
    body: new URLSearchParams({ action: 'reset_password', new_password: newPass })
  });
  const data = await res.json();
  showToast(data.message);
  if (data.success) bootstrap.Modal.getInstance(document.getElementById('resetModal')).hide();
});

// Show/Hide Password toggles
document.getElementById('toggleNewPass').addEventListener('click', () => {
  const input = document.getElementById('newPass');
  const icon = document.querySelector('#toggleNewPass i');
  input.type = input.type === 'password' ? 'text' : 'password';
  icon.classList.toggle('bi-eye');
  icon.classList.toggle('bi-eye-slash');
});

document.getElementById('toggleRetypePass').addEventListener('click', () => {
  const input = document.getElementById('retypePass');
  const icon = document.querySelector('#toggleRetypePass i');
  input.type = input.type === 'password' ? 'text' : 'password';
  icon.classList.toggle('bi-eye');
  icon.classList.toggle('bi-eye-slash');
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

</body>
</html>

