<?php
session_start();
require __DIR__ . '/../vendor/autoload.php';
require '../controls/connection.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Handle AJAX requests
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
    header('Content-Type: application/json');

    // 1️⃣ LOGIN VALIDATION WITH PROGRESSIVE SECURITY
    if ($_POST['action'] === 'login') {
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
            exit;
        }

        // Check for account lockout
        $lockout_key = 'login_attempts_' . md5($email);
        
        // Initialize lockout tracking if not exists
        if (!isset($_SESSION[$lockout_key])) {
            $_SESSION[$lockout_key] = [
                'count' => 0,
                'total_lockouts' => 0
            ];
        }

        if (isset($_SESSION[$lockout_key]['locked_until'])) {
            $lockout_time = $_SESSION[$lockout_key]['locked_until'];
            if (time() < $lockout_time) {
                $remaining = ceil(($lockout_time - time()) / 60);
                echo json_encode([
                    'success' => false, 
                    'message' => "Account temporarily locked. Try again in {$remaining} minute(s).",
                    'locked' => true,
                    'lockout_duration' => $remaining,
                    'total_lockouts' => $_SESSION[$lockout_key]['total_lockouts']
                ]);
                exit;
            } else {
                // Reset after lockout period
                $_SESSION[$lockout_key]['count'] = 0;
                unset($_SESSION[$lockout_key]['locked_until']);
            }
        }

        // Fetch user from database
        $stmt = $conn->prepare("SELECT user_id, email, password, role, firstname, middlename, lastname, profile_picture FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Email not found.']);
            $stmt->close();
            exit;
        }

        $user = $result->fetch_assoc();
        $stmt->close();

        // Verify password using password_verify()
        if (password_verify($password, $user['password'])) {
            // Successful login - clear failed attempts and regenerate session
            unset($_SESSION[$lockout_key]);
            session_regenerate_id(true);

            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['firstname'] = $user['firstname'];
            $_SESSION['middlename'] = $user['middlename'];
            $_SESSION['lastname'] = $user['lastname'];
            $_SESSION['profile_picture'] = $user['profile_picture'];

            echo json_encode([
                'success' => true, 
                'message' => 'Login successful!',
                'redirect' => '../index.php'
            ]);
        } else {
            // Failed login - track attempts with progressive security
            $_SESSION[$lockout_key]['count']++;
            
            $remaining_attempts = 5 - $_SESSION[$lockout_key]['count'];

            // Check if account should be locked
            if ($_SESSION[$lockout_key]['count'] >= 5) {
                // Increment total lockouts
                $_SESSION[$lockout_key]['total_lockouts']++;
                
                // Determine lockout duration based on lockout history
                $lockout_duration = 15; // Default 15 minutes
                if ($_SESSION[$lockout_key]['total_lockouts'] > 2) {
                    $lockout_duration = 30; // 30 minutes after 3rd lockout
                }
                
                $_SESSION[$lockout_key]['locked_until'] = time() + ($lockout_duration * 60);
                
                // Check if should require email verification
                $require_verification = $_SESSION[$lockout_key]['total_lockouts'] > 2;
                
                echo json_encode([
                    'success' => false, 
                    'message' => "Too many failed attempts. Account locked for {$lockout_duration} minutes.",
                    'locked' => true,
                    'lockout_duration' => $lockout_duration,
                    'total_lockouts' => $_SESSION[$lockout_key]['total_lockouts'],
                    'require_verification' => $require_verification,
                    'email' => $email
                ]);
            } else {
                // Progressive warnings based on remaining attempts
                $warning_level = $remaining_attempts <= 1 ? 'high' : 
                                ($remaining_attempts <= 2 ? 'medium' : 'low');
                
                $show_captcha = $remaining_attempts <= 2;
                
                echo json_encode([
                    'success' => false, 
                    'message' => "Incorrect password. {$remaining_attempts} attempt(s) remaining.",
                    'attempts_left' => $remaining_attempts,
                    'warning_level' => $warning_level,
                    'show_captcha' => $show_captcha
                ]);
            }
        }
        exit;
    }

    // 2️⃣ Send OTP for Password Reset
    if ($_POST['action'] === 'send_otp') {
        $email = trim($_POST['email']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
            exit;
        }

        // Check if email exists in database
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Email not found in our system.']);
            $stmt->close();
            exit;
        }
        $stmt->close();

        // Check OTP cooldown
        if (isset($_SESSION['otp_reset_last_sent'])) {
            $time_diff = time() - $_SESSION['otp_reset_last_sent'];
            if ($time_diff < 60) {
                $remaining = 60 - $time_diff;
                echo json_encode(['success' => false, 'message' => "Please wait {$remaining} seconds before requesting another OTP."]);
                exit;
            }
        }

        // Generate OTP
        $otp = rand(100000, 999999);
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_otp'] = $otp;
        $_SESSION['otp_reset_last_sent'] = time();
        $_SESSION['otp_reset_expires'] = time() + 600; // 10 minutes

        // Send email via PHPMailer
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
            $mail->Subject = 'Your Servify Password Reset Code';
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2 style='color: #4CAF50;'>Password Reset Request</h2>
                    <p>You requested to reset your Servify password.</p>
                    <p>Your verification code is:</p>
                    <div style='background: #f4f4f4; padding: 15px; text-align: center; font-size: 24px; font-weight: bold; letter-spacing: 5px;'>
                        $otp
                    </div>
                    <p style='color: #666; font-size: 12px;'>This code will expire in 10 minutes.</p>
                    <p style='color: #666; font-size: 12px;'>If you didn't request this, please ignore this email.</p>
                </div>
            ";

            $mail->send();
            echo json_encode(['success' => true, 'message' => 'OTP sent to your email.']);
        } catch (Exception $e) {
            error_log("Mailer Error: " . $mail->ErrorInfo);
            echo json_encode(['success' => false, 'message' => 'Failed to send email. Please try again.']);
        }
        exit;
    }

    // 3️⃣ Verify OTP
    if ($_POST['action'] === 'verify_otp') {
        $otp = $_POST['otp'] ?? '';
        
        // Check if OTP expired
        if (isset($_SESSION['otp_reset_expires']) && time() > $_SESSION['otp_reset_expires']) {
            echo json_encode(['success' => false, 'message' => 'OTP has expired. Please request a new one.']);
            exit;
        }

        if ($otp == $_SESSION['reset_otp']) {
            $_SESSION['otp_verified'] = true;
            echo json_encode(['success' => true, 'message' => 'OTP verified successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Incorrect OTP. Please try again.']);
        }
        exit;
    }

    // 4️⃣ Reset Password
    if ($_POST['action'] === 'reset_password') {
        if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
            echo json_encode(['success' => false, 'message' => 'OTP not verified.']);
            exit;
        }

        $new_pass = $_POST['new_password'];
        $retype_pass = $_POST['retype_password'];

        // Validate password
        if ($new_pass !== $retype_pass) {
            echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
            exit;
        }

        if (!preg_match("/^(?=.*[A-Z])(?=.*[!@#$%^&*(),.?\":{}|<>]).{8,}$/", $new_pass)) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters, include one uppercase letter and one special character.']);
            exit;
        }

        // Hash the new password
        $hashed_password = password_hash($new_pass, PASSWORD_DEFAULT);
        $email = $_SESSION['reset_email'];

        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashed_password, $email);

        if ($stmt->execute()) {
            // Clear all reset-related sessions
            unset($_SESSION['reset_email'], $_SESSION['reset_otp'], $_SESSION['otp_verified'], 
                  $_SESSION['otp_reset_last_sent'], $_SESSION['otp_reset_expires']);
            
            echo json_encode(['success' => true, 'message' => 'Password reset successfully! You can now login.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to reset password. Please try again.']);
        }
        $stmt->close();
        exit;
    }

    // 5️⃣ Resend OTP
    if ($_POST['action'] === 'resend_otp') {
        if (!isset($_SESSION['reset_email'])) {
            echo json_encode(['success' => false, 'message' => 'Session expired. Please start over.']);
            exit;
        }

        // Check cooldown
        if (isset($_SESSION['otp_reset_last_sent'])) {
            $time_diff = time() - $_SESSION['otp_reset_last_sent'];
            if ($time_diff < 60) {
                $remaining = 60 - $time_diff;
                echo json_encode(['success' => false, 'message' => "Please wait {$remaining} seconds before resending."]);
                exit;
            }
        }

        // Generate new OTP
        $otp = rand(100000, 999999);
        $_SESSION['reset_otp'] = $otp;
        $_SESSION['otp_reset_last_sent'] = time();
        $_SESSION['otp_reset_expires'] = time() + 600;

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
            $mail->addAddress($_SESSION['reset_email']);
            $mail->isHTML(true);
            $mail->Subject = 'Your New Servify Password Reset Code';
            $mail->Body    = "<h3>Your new OTP is: <b>$otp</b></h3><p>This code will expire in 10 minutes.</p>";
            
            $mail->send();
            echo json_encode(['success' => true, 'message' => 'New OTP sent successfully.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to resend OTP.']);
        }
        exit;
    }

    // 6️⃣ REQUEST ACCOUNT UNLOCK VIA EMAIL
    if ($_POST['action'] === 'request_unlock') {
        $email = trim($_POST['email']);
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
            exit;
        }

        // Check if email exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Email not found.']);
            $stmt->close();
            exit;
        }
        $stmt->close();

        // Check cooldown for unlock requests
        if (isset($_SESSION['unlock_request_last_sent'])) {
            $time_diff = time() - $_SESSION['unlock_request_last_sent'];
            if ($time_diff < 120) { // 2 minutes cooldown
                $remaining = 120 - $time_diff;
                echo json_encode(['success' => false, 'message' => "Please wait {$remaining} seconds before requesting another unlock code."]);
                exit;
            }
        }

        // Generate unlock OTP
        $unlock_otp = rand(100000, 999999);
        $_SESSION['unlock_email'] = $email;
        $_SESSION['unlock_otp'] = $unlock_otp;
        $_SESSION['unlock_request_last_sent'] = time();
        $_SESSION['unlock_expires'] = time() + 600; // 10 minutes

        // Send unlock email
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
            $mail->Subject = '🔓 Unlock Your Servify Account';
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2 style='color: #4CAF50;'>🔓 Account Unlock Request</h2>
                    <p>You requested to unlock your Servify account.</p>
                    <p>Your unlock verification code is:</p>
                    <div style='background: #f4f4f4; padding: 15px; text-align: center; font-size: 24px; font-weight: bold; letter-spacing: 5px;'>
                        $unlock_otp
                    </div>
                    <p style='color: #666; font-size: 12px;'>This code will expire in 10 minutes.</p>
                    <p style='color: #666; font-size: 12px;'>If you didn't request this, please secure your account immediately.</p>
                </div>
            ";

            $mail->send();
            echo json_encode(['success' => true, 'message' => 'Unlock code sent to your email.']);
        } catch (Exception $e) {
            error_log("Mailer Error: " . $mail->ErrorInfo);
            echo json_encode(['success' => false, 'message' => 'Failed to send unlock email. Please try again.']);
        }
        exit;
    }

    // 7️⃣ VERIFY UNLOCK OTP
    if ($_POST['action'] === 'verify_unlock') {
        $otp = $_POST['otp'] ?? '';
        
        // Check if unlock session exists
        if (!isset($_SESSION['unlock_otp'])) {
            echo json_encode(['success' => false, 'message' => 'No unlock request found. Please request a new unlock code.']);
            exit;
        }

        // Check if OTP expired
        if (isset($_SESSION['unlock_expires']) && time() > $_SESSION['unlock_expires']) {
            echo json_encode(['success' => false, 'message' => 'Unlock code has expired. Please request a new one.']);
            exit;
        }

        // Verify OTP
        if ($otp == $_SESSION['unlock_otp']) {
            $email = $_SESSION['unlock_email'];
            $lockout_key = 'login_attempts_' . md5($email);
            
            // Clear lockout and reset attempts
            if (isset($_SESSION[$lockout_key])) {
                $_SESSION[$lockout_key]['count'] = 0;
                unset($_SESSION[$lockout_key]['locked_until']);
                // Note: We keep total_lockouts for tracking purposes
            }
            
            // Clear unlock session data
            unset($_SESSION['unlock_otp'], $_SESSION['unlock_email'], $_SESSION['unlock_expires'], $_SESSION['unlock_request_last_sent']);
            
            echo json_encode(['success' => true, 'message' => 'Account unlocked successfully! You can now login.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid unlock code. Please try again.']);
        }
        exit;
    }
}

$is_logged_in = isset($_SESSION['user_id']);
?>
<?php include '../nav.php' ?>
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

.warning-text {
  color: #dc3545;
  font-size: 13px;
  margin-top: 5px;
}

.lockout-warning {
  background: #fff3cd;
  border: 1px solid #ffc107;
  padding: 10px;
  border-radius: 5px;
  color: #856404;
  margin-bottom: 15px;
  font-size: 14px;
}
</style>
</head>
<body>

<div class="loading-overlay" id="loadingOverlay">
  <div class="text-center">
    <div class="spinner"></div>
    <div class="loading-text">Processing...</div>
  </div>
</div>


<div class="login-container text-center mt-md-5 pt-md-5 mt-4 pt-4">
  <h3>Log in</h3>
  
  <div id="lockoutWarning" class="lockout-warning d-none"></div>
  
  <form id="loginForm" class="mt-3">
    <div class="mb-3">
      <input name="email" id="loginEmail" type="email" class="form-control" placeholder="Email" required>
    </div>
    <div class="mb-3 input-group">
      <input name="password" id="loginPassword" type="password" class="form-control" placeholder="Password" required>
      <button class="btn btn-outline-secondary" type="button" id="toggleLoginPassword"><i class="bi bi-eye"></i></button>
    </div>
    <div id="attemptsWarning" class="warning-text d-none"></div>
    <div class="d-flex justify-content-between mb-3">
      <a href="#" class="small-text" id="forgotPasswordLink">Forgot password?</a>
    </div>
    <button type="submit" class="btn btn-primary w-100" id="loginBtn">LOGIN</button>
  </form>
  <p class="small-text mt-3">Don't have an account? <a href="../view/signup.php">Sign up</a></p>
</div>

<div class="modal fade" id="forgotModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content p-3">
      <h5>Forgot Password</h5>
      <p class="small">Enter your email to receive a verification code</p>
      <input type="email" id="forgotEmail" class="form-control my-2" placeholder="Enter your email">
      <button class="btn btn-primary w-100" id="sendOtpBtn">Send OTP</button>
    </div>
  </div>
</div>

<div class="modal fade" id="otpModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content p-3">
      <h5>Enter Verification Code</h5>
      <p class="small">Enter the 6-digit code sent to <strong id="emailDisplay"></strong></p>
      <input type="text" id="otpInput" class="form-control my-2" placeholder="Enter OTP" maxlength="6">
      <button class="btn btn-success w-100 mb-2" id="verifyOtpBtn">Verify</button>
      <div class="text-center countdown-timer">
        <span id="countdownText">Didn't receive code? <span class="resend-btn disabled" id="resendBtn">Wait <span id="countdown">60</span>s</span></span>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="resetModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content p-3">
      <h5>Reset Password</h5>

      <div class="input-group my-2">
        <input type="password" id="newPass" class="form-control" placeholder="New Password">
        <button class="btn btn-outline-secondary" type="button" id="toggleNewPass"><i class="bi bi-eye"></i></button>
      </div>

      <div class="input-group my-2">
        <input type="password" id="retypePass" class="form-control" placeholder="Retype New Password">
        <button class="btn btn-outline-secondary" type="button" id="toggleRetypePass"><i class="bi bi-eye"></i></button>
      </div>

      <p class="small text-muted">Password must be at least 8 characters with one uppercase letter and one special character.</p>

      <button class="btn btn-success w-100 mt-2" id="resetPassBtn">Reset Password</button>
    </div>
  </div>
</div>

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

const showLoading = () => document.getElementById('loadingOverlay').classList.add('active');
const hideLoading = () => document.getElementById('loadingOverlay').classList.remove('active');

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

// Enhanced login handler with progressive security
document.getElementById('loginForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  
  const email = document.getElementById('loginEmail').value.trim();
  const password = document.getElementById('loginPassword').value.trim();
  
  showLoading();
  
  try {
    const res = await fetch('', {
      method: 'POST',
      body: new URLSearchParams({ action: 'login', email, password })
    });
    
    const data = await res.json();
    hideLoading();
    
    if (data.success) {
      showToast(data.message);
      setTimeout(() => window.location.href = data.redirect, 1000);
      return;
    }
    
    // Handle failed login
    showToast(data.message);
    
    // Show progressive warnings
    const warningEl = document.getElementById('attemptsWarning');
    const lockoutEl = document.getElementById('lockoutWarning');
    
    if (data.require_verification) {
      // Account requires email verification after multiple lockouts
      lockoutEl.innerHTML = `
        🔒 <strong>Security Alert:</strong> Multiple lockouts detected. 
        <button class="btn btn-sm btn-warning mt-2" onclick="requestUnlock('${email}')">
          Verify Email to Unlock
        </button>
      `;
      lockoutEl.classList.remove('d-none');
      document.getElementById('loginBtn').disabled = true;
      return;
    }
    
    if (data.show_captcha) {
      // Show CAPTCHA requirement warning
      warningEl.innerHTML = `⚠️ <strong>Warning:</strong> ${data.attempts_left} attempt(s) remaining. Security verification required for next attempt.`;
      warningEl.classList.remove('d-none');
      warningEl.style.backgroundColor = '#fff3cd';
      warningEl.style.padding = '10px';
      warningEl.style.borderRadius = '5px';
      warningEl.style.border = '1px solid #ffc107';
      warningEl.style.color = '#856404';
    } else if (data.attempts_left !== undefined) {
      const color = data.warning_level === 'high' ? '#dc3545' : 
                    data.warning_level === 'medium' ? '#ffc107' : '#6c757d';
      warningEl.innerHTML = `⚠️ ${data.attempts_left} attempt(s) remaining`;
      warningEl.style.color = color;
      warningEl.style.backgroundColor = 'transparent';
      warningEl.style.padding = '5px 0';
      warningEl.style.border = 'none';
      warningEl.classList.remove('d-none');
    }
    
    if (data.locked) {
      // Account is locked
      const lockoutNum = data.total_lockouts > 0 ? ` (Lockout #${data.total_lockouts})` : '';
      lockoutEl.innerHTML = `
        🔒 <strong>Account Locked${lockoutNum}</strong><br>
        ${data.message}<br>
        <button class="btn btn-sm btn-info mt-2" onclick="requestUnlock('${email}')">
          Unlock via Email
        </button>
      `;
      lockoutEl.classList.remove('d-none');
      document.getElementById('loginBtn').disabled = true;
      
      // Auto re-enable after lockout expires (only if not requiring verification)
      if (!data.require_verification && data.lockout_duration) {
        setTimeout(() => {
          document.getElementById('loginBtn').disabled = false;
          lockoutEl.classList.add('d-none');
          warningEl.classList.add('d-none');
          showToast('Lockout expired. You can try again.');
        }, data.lockout_duration * 60 * 1000);
      }
    }
    
  } catch (error) {
    hideLoading();
    console.error('Login error:', error);
    showToast('An error occurred. Please try again.');
  }
});

// Request account unlock via email
async function requestUnlock(email) {
  showLoading();
  
  try {
    const res = await fetch('', {
      method: 'POST',
      body: new URLSearchParams({ action: 'request_unlock', email })
    });
    
    const data = await res.json();
    hideLoading();
    showToast(data.message);
    
    if (data.success) {
      // Create and show unlock OTP modal
      const unlockModalHTML = `
        <div class="modal fade" id="unlockModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content p-3">
              <h5>🔓 Unlock Your Account</h5>
              <p class="small">Enter the 6-digit code sent to <strong>${email}</strong></p>
              <input type="text" id="unlockOtpInput" class="form-control my-2" placeholder="Enter OTP" maxlength="6">
              <button class="btn btn-success w-100 mb-2" onclick="verifyUnlock()">Verify & Unlock</button>
              <div class="text-center">
                <small class="text-muted">Code expires in 10 minutes</small>
              </div>
            </div>
          </div>
        </div>
      `;
      
      // Remove existing unlock modal if present
      const existingModal = document.getElementById('unlockModal');
      if (existingModal) {
        existingModal.remove();
      }
      
      document.body.insertAdjacentHTML('beforeend', unlockModalHTML);
      const unlockModal = new bootstrap.Modal(document.getElementById('unlockModal'));
      unlockModal.show();
    }
  } catch (error) {
    hideLoading();
    console.error('Unlock request error:', error);
    showToast('Failed to send unlock request. Please try again.');
  }
}

// Verify unlock OTP
async function verifyUnlock() {
  const otp = document.getElementById('unlockOtpInput').value.trim();
  
  if (otp.length !== 6) {
    showToast('Please enter a valid 6-digit OTP.');
    return;
  }
  
  showLoading();
  
  try {
    const res = await fetch('', {
      method: 'POST',
      body: new URLSearchParams({ action: 'verify_unlock', otp })
    });
    
    const data = await res.json();
    hideLoading();
    showToast(data.message);
    
    if (data.success) {
      const unlockModalEl = document.getElementById('unlockModal');
      if (unlockModalEl) {
        const unlockModal = bootstrap.Modal.getInstance(unlockModalEl);
        if (unlockModal) {
          unlockModal.hide();
        }
        unlockModalEl.remove();
      }
      
      // Re-enable login
      document.getElementById('loginBtn').disabled = false;
      document.getElementById('lockoutWarning').classList.add('d-none');
      document.getElementById('attemptsWarning').classList.add('d-none');
    }
  } catch (error) {
    hideLoading();
    console.error('Unlock verification error:', error);
    showToast('Failed to verify unlock code. Please try again.');
  }
}

document.getElementById('toggleLoginPassword').addEventListener('click', function() {
  const field = document.getElementById('loginPassword');
  const icon = this.querySelector('i');
  if (field.type === 'password') {
    field.type = 'text';
    icon.classList.replace('bi-eye', 'bi-eye-slash');
  } else {
    field.type = 'password';
    icon.classList.replace('bi-eye-slash', 'bi-eye');
  }
});

document.getElementById('forgotPasswordLink').addEventListener('click', (e) => {
  e.preventDefault();
  new bootstrap.Modal('#forgotModal').show();
});

document.getElementById('sendOtpBtn').addEventListener('click', async () => {
  const email = document.getElementById('forgotEmail').value.trim();
  if (!email) return showToast('Enter your email.');
  
  showLoading();
  
  try {
    const res = await fetch('', {
      method: 'POST',
      body: new URLSearchParams({ action: 'send_otp', email })
    });
    
    const data = await res.json();
    hideLoading();
    showToast(data.message);
    
    if (data.success) {
      document.getElementById('emailDisplay').textContent = email;
      bootstrap.Modal.getInstance(document.getElementById('forgotModal')).hide();
      new bootstrap.Modal('#otpModal').show();
      startCountdown(60);
    }
  } catch (error) {
    hideLoading();
    console.error('Send OTP error:', error);
    showToast('Failed to send OTP. Please try again.');
  }
});

document.getElementById('verifyOtpBtn').addEventListener('click', async () => {
  const otp = document.getElementById('otpInput').value.trim();
  
  if (otp.length !== 6) {
    showToast('Please enter a valid 6-digit OTP.');
    return;
  }
  
  showLoading();
  
  try {
    const res = await fetch('', {
      method: 'POST',
      body: new URLSearchParams({ action: 'verify_otp', otp })
    });
    
    const data = await res.json();
    hideLoading();
    showToast(data.message || (data.success ? 'OTP Verified!' : 'Error'));
    
    if (data.success) {
      clearInterval(countdownInterval);
      bootstrap.Modal.getInstance(document.getElementById('otpModal')).hide();
      new bootstrap.Modal('#resetModal').show();
    }
  } catch (error) {
    hideLoading();
    console.error('Verify OTP error:', error);
    showToast('Failed to verify OTP. Please try again.');
  }
});

document.getElementById('resetPassBtn').addEventListener('click', async () => {
  const newPass = document.getElementById('newPass').value.trim();
  const retypePass = document.getElementById('retypePass').value.trim();

  if (newPass.length < 8) return showToast('Password must be at least 8 characters.');
  if (newPass !== retypePass) return showToast('Passwords do not match.');
  
  if (!newPass.match(/^(?=.*[A-Z])(?=.*[!@#$%^&*(),.?":{}|<>]).{8,}$/)) {
    return showToast('Password must include one uppercase letter and one special character.');
  }

  showLoading();
  
  try {
    const res = await fetch('', {
      method: 'POST',
      body: new URLSearchParams({ 
        action: 'reset_password', 
        new_password: newPass,
        retype_password: retypePass
      })
    });
    
    const data = await res.json();
    hideLoading();
    showToast(data.message);
    
    if (data.success) {
      bootstrap.Modal.getInstance(document.getElementById('resetModal')).hide();
      document.getElementById('loginEmail').value = '';
      document.getElementById('loginPassword').value = '';
      document.getElementById('newPass').value = '';
      document.getElementById('retypePass').value = '';
    }
  } catch (error) {
    hideLoading();
    console.error('Reset password error:', error);
    showToast('Failed to reset password. Please try again.');
  }
});

const resendOTP = async () => {
  showLoading();
  
  try {
    const res = await fetch('', {
      method: 'POST',
      body: new URLSearchParams({ action: 'resend_otp' })
    });
    
    const data = await res.json();
    hideLoading();
    showToast(data.message);
    
    if (data.success) startCountdown(60);
  } catch (error) {
    hideLoading();
    console.error('Resend OTP error:', error);
    showToast('Failed to resend OTP. Please try again.');
  }
};

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