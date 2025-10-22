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
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="../styles/login.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <title>Login - Servify</title>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg fixed-top bg-white shadow-sm">
  <div class="container">
    <a class="navbar-brand" href="../view/index.php">Servify</a>
    <div class="search-container">
      <form class="d-flex align-items-center" role="search">
        <div class="input-group">
          <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
          <input class="form-control" type="search" placeholder="Search" aria-label="Search">
        </div>
      </form>
    </div>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav">
        <li class="nav-item"><a class="nav-link" href="../view/signup.php">Sign Up</a></li>
        <li class="nav-item"><a class="nav-link disabled">|</a></li>
        <li class="nav-item"><a class="nav-link active" href="../view/login.php">Login</a></li>
      </ul>
    </div>
  </div>
</nav>

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

</body>
</html>
