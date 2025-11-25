<?php
session_start();
require '../vendor/autoload.php';
require '../controls/connection.php';

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
        $captcha_response = $_POST['captcha_response'] ?? '';

        // ✅ Verify reCAPTCHA
        $secret_key = "6Ldx7wYsAAAAALecWgQJ8tdEzbny4r8YEJz2sALC"; // reCAPTCHA secret key
        $verify_url = "https://www.google.com/recaptcha/api/siteverify";
        $response = file_get_contents($verify_url . "?secret=" . $secret_key . "&response=" . $captcha_response);
        $response_data = json_decode($response);

        if (!$response_data->success) {
            echo json_encode(["success" => false, "message" => "Please complete the CAPTCHA verification."]);
            exit;
        }

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

        // ✅ Check if email already exists in database
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo json_encode(["success" => false, "message" => "This email is already registered. Please login instead."]);
            $stmt->close();
            exit;
        }
        $stmt->close();

        // ✅ Check OTP cooldown (prevent spam)
        if (isset($_SESSION['otp_last_sent'])) {
            $time_diff = time() - $_SESSION['otp_last_sent'];
            if ($time_diff < 60) { // 60 seconds cooldown
                $remaining = 60 - $time_diff;
                echo json_encode(["success" => false, "message" => "Please wait {$remaining} seconds before requesting another OTP."]);
                exit;
            }
        }

        // ✅ Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Generate OTP
        $otp = rand(100000, 999999);
        $_SESSION['signup_email'] = $email;
        $_SESSION['signup_password'] = $hashed_password; // Store hashed password
        $_SESSION['signup_role'] = $role;
        $_SESSION['signup_otp'] = $otp;
        $_SESSION['otp_last_sent'] = time();
        $_SESSION['otp_expires'] = time() + 600; // OTP expires in 10 minutes

        // ✅ Send OTP via PHPMailer (Your existing SMTP settings)
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'shanekherby2828@gmail.com';
            $mail->Password   = 'gmum gtma drra ffhd';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('shanekherby2828@gmail.com', 'Servify');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Your Servify Verification Code';
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2 style='color: #4CAF50;'>Email Verification</h2>
                    <p>Thank you for signing up with Servify!</p>
                    <p>Your verification code is:</p>
                    <div style='background: #f4f4f4; padding: 15px; text-align: center; font-size: 24px; font-weight: bold; letter-spacing: 5px;'>
                        $otp
                    </div>
                    <p style='color: #666; font-size: 12px;'>This code will expire in 10 minutes.</p>
                    <p style='color: #666; font-size: 12px;'>If you didn't request this code, please ignore this email.</p>
                </div>
            ";
            
            $mail->send();

            echo json_encode(["success" => true, "message" => "OTP sent to your email. Check your inbox."]);
        } catch (Exception $e) {
            error_log("Mailer Error: " . $mail->ErrorInfo); // Log error
            echo json_encode(["success" => false, "message" => "Failed to send OTP. Please try again later."]);
        }
        exit;
    }

    // 2️⃣ VERIFY OTP
    if ($_POST['action'] === 'verify_otp') {
        $otp = $_POST['otp'] ?? '';
        
        // Check if OTP expired
        if (isset($_SESSION['otp_expires']) && time() > $_SESSION['otp_expires']) {
            echo json_encode(["success" => false, "message" => "OTP has expired. Please request a new one."]);
            exit;
        }

        if ($otp == $_SESSION['signup_otp']) {
            // OTP verified — pass details to user_details.php
            $_SESSION['email'] = $_SESSION['signup_email'];
            $_SESSION['password'] = $_SESSION['signup_password']; // Already hashed
            $_SESSION['role'] = $_SESSION['signup_role'];

            // Clear temporary OTP session data
            unset($_SESSION['signup_otp'], $_SESSION['signup_email'], $_SESSION['signup_password'], $_SESSION['signup_role'], $_SESSION['otp_last_sent'], $_SESSION['otp_expires']);

            echo json_encode([
                "success" => true,
                "message" => "Email verified successfully.",
                "redirect" => "user_details.php"
            ]);
        } else {
            echo json_encode(["success" => false, "message" => "Invalid OTP. Please check and try again."]);
        }
        exit;
    }

    // 3️⃣ RESEND OTP
    if ($_POST['action'] === 'resend_otp') {
        if (!isset($_SESSION['signup_email'])) {
            echo json_encode(["success" => false, "message" => "Session expired. Please start over."]);
            exit;
        }

        // Check cooldown
        if (isset($_SESSION['otp_last_sent'])) {
            $time_diff = time() - $_SESSION['otp_last_sent'];
            if ($time_diff < 60) {
                $remaining = 60 - $time_diff;
                echo json_encode(["success" => false, "message" => "Please wait {$remaining} seconds before resending."]);
                exit;
            }
        }

        // Generate new OTP
        $otp = rand(100000, 999999);
        $_SESSION['signup_otp'] = $otp;
        $_SESSION['otp_last_sent'] = time();
        $_SESSION['otp_expires'] = time() + 600;

        // Send OTP
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'shanekherby2828@gmail.com';
            $mail->Password   = 'gmum gtma drra ffhd';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('shanekherby2828@gmail.com', 'Servify');
            $mail->addAddress($_SESSION['signup_email']);
            $mail->isHTML(true);
            $mail->Subject = 'Your New Servify Verification Code';
            $mail->Body    = "<h3>Your new OTP is: <b>$otp</b></h3><p>This code will expire in 10 minutes.</p>";
            
            $mail->send();
            echo json_encode(["success" => true, "message" => "New OTP sent successfully."]);
        } catch (Exception $e) {
            echo json_encode(["success" => false, "message" => "Failed to resend OTP."]);
        }
        exit;
    }
}

// Check login status
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" type="text/css" href="../styles/signup.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<title>Sign Up - Servify</title>
<style>
body {
  margin: 0;
  padding: 0;
  height: 100vh;
  background-size: cover;
  background-position: center;
  background-attachment: fixed;
  position: relative;
  overflow-x: hidden;
}

body::before {
  content: "";
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: url('../starita.png') center/cover no-repeat;
  filter: blur(6px);
  z-index: -1;
}

/* Loading Spinner */
.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.7);
  display: none;
  justify-content: center;
  align-items: center;
  z-index: 9999;
}

.loading-overlay.active {
  display: flex;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 5px solid #f3f3f3;
  border-top: 5px solid #4CAF50;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.loading-text {
  color: white;
  margin-top: 20px;
  font-size: 16px;
}

/* Countdown Timer */
.countdown-timer {
  color: #666;
  font-size: 14px;
  margin-top: 10px;
}

.resend-btn {
  color: #4CAF50;
  cursor: pointer;
  text-decoration: underline;
}

.resend-btn.disabled {
  color: #ccc;
  cursor: not-allowed;
  text-decoration: none;
}
</style>
</head>
<body>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
  <div class="text-center">
    <div class="spinner"></div>
    <div class="loading-text">Sending verification code...</div>
  </div>
</div>

<!-- NAVIGATION BAR -->
<header>
  <div class="header-content">
    <div class="brand"><a href="../index.php">Servify</a></div>
    <div class="menu-container">
      <nav class="wrapper-2" id="menu">
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
              <a href="../controls/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </div>
          </p>
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
  <a href="../view/browse.php">
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
      <div class="user-section">
        <div class="profile-info">
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
        <a href="../controls/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
      </div>
    <?php else: ?>
      <div class="menu-options">
        <a href="../view/login.php"><i class="bi bi-person-circle"></i> Signin / Signup</a>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- SIGNUP FORM -->
<div class="signup-container text-center mt-md-5 pt-md-5 mt-4 pt-4">
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

    <!-- reCAPTCHA -->
    <div class="mb-3 d-flex justify-content-center">
      <div class="g-recaptcha" data-sitekey="6Ldx7wYsAAAAAEMS27dkWzd-WQ6_4uN3Z4mKgote"></div>
    </div>

    <button type="submit" class="btn btn-primary w-100" id="signupBtn">Sign Up</button>
  </form>

  <p class="small-text mt-3">Already have an account? <a href="../view/login.php">Login</a></p>
</div>

<!-- OTP MODAL -->
<div class="modal fade" id="otpModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content p-3">
      <h5>Verify Your Email</h5>
      <p>Enter the 6-digit code sent to <strong id="emailDisplay"></strong></p>
      <input type="text" id="otpInput" class="form-control my-2" placeholder="Enter OTP" maxlength="6">
      <button class="btn btn-success w-100 mb-2" id="verifyOtpBtn">Verify</button>
      <div class="text-center countdown-timer">
        <span id="countdownText">Didn't receive code? <span class="resend-btn disabled" id="resendBtn">Wait <span id="countdown">60</span>s</span></span>
      </div>
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
let countdownInterval;

const showToast = msg => {
  const toastEl = document.getElementById('toastMsg');
  toastEl.querySelector('.toast-body').textContent = msg;
  new bootstrap.Toast(toastEl).show();
};

const showLoading = () => {
  document.getElementById('loadingOverlay').classList.add('active');
};

const hideLoading = () => {
  document.getElementById('loadingOverlay').classList.remove('active');
};

// Countdown Timer
const startCountdown = (seconds) => {
  let timeLeft = seconds;
  const countdownEl = document.getElementById('countdown');
  const resendBtn = document.getElementById('resendBtn');
  
  resendBtn.classList.add('disabled');
  
  countdownInterval = setInterval(() => {
    timeLeft--;
    countdownEl.textContent = timeLeft;
    
    if (timeLeft <= 0) {
      clearInterval(countdownInterval);
      resendBtn.innerHTML = 'Resend Code';
      resendBtn.classList.remove('disabled');
      resendBtn.onclick = resendOTP;
    }
  }, 1000);
};

// Send OTP
document.getElementById('signupForm').addEventListener('submit', async e => {
  e.preventDefault();
  const form = e.target;
  const captchaResponse = grecaptcha.getResponse();

  if (!captchaResponse) {
    showToast('Please complete the CAPTCHA verification.');
    return;
  }

  showLoading();
  
  const data = new URLSearchParams({
    action: 'send_otp',
    email: form.email.value,
    password: form.password.value,
    retype: form.retype.value,
    role: form.role.value,
    captcha_response: captchaResponse
  });

  try {
    const res = await fetch('', { method: 'POST', body: data });
    const json = await res.json();
    hideLoading();
    showToast(json.message);
    
    if (json.success) {
      document.getElementById('emailDisplay').textContent = form.email.value;
      new bootstrap.Modal('#otpModal').show();
      startCountdown(60);
      grecaptcha.reset(); // Reset CAPTCHA
    } else {
      grecaptcha.reset();
    }
  } catch (error) {
    hideLoading();
    showToast('An error occurred. Please try again.');
    grecaptcha.reset();
  }
});

// Verify OTP
document.getElementById('verifyOtpBtn').addEventListener('click', async () => {
  const otp = document.getElementById('otpInput').value.trim();
  
  if (otp.length !== 6) {
    showToast('Please enter a valid 6-digit OTP.');
    return;
  }

  showLoading();
  
  const res = await fetch('', {
    method: 'POST',
    body: new URLSearchParams({ action: 'verify_otp', otp })
  });
  
  const data = await res.json();
  hideLoading();
  showToast(data.message);
  
  if (data.success) {
    clearInterval(countdownInterval);
    bootstrap.Modal.getInstance(document.getElementById('otpModal')).hide();
    setTimeout(() => (window.location.href = data.redirect), 1500);
  }
});

// Resend OTP
const resendOTP = async () => {
  showLoading();
  
  const res = await fetch('', {
    method: 'POST',
    body: new URLSearchParams({ action: 'resend_otp' })
  });
  
  const data = await res.json();
  hideLoading();
  showToast(data.message);
  
  if (data.success) {
    startCountdown(60);
  }
};

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

function toggleProfileMenu() {
  const menu = document.getElementById('profile-menu');
  menu.classList.toggle('d-none');
}

function toggleMoreMenu() {
  const menu = document.getElementById('more-menu');
  menu.classList.toggle('d-none');
}
</script>
</body>
</html>