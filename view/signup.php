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

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg fixed-top bg-white shadow-sm">
  <div class="container">
    <a class="navbar-brand" href="../view/index.php">Servify</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav">
        <li class="nav-item"><a class="nav-link active" href="../view/signup.php">Sign Up</a></li>
        <li class="nav-item"><a class="nav-link">|</a></li>
        <li class="nav-item"><a class="nav-link" href="../view/login.php">Login</a></li>
      </ul>
    </div>
  </div>
</nav>

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

</body>
</html>
