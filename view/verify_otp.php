<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $entered_otp = $_POST['otp'] ?? '';

    if ($entered_otp == $_SESSION['otp']) {
        unset($_SESSION['otp']); // OTP no longer needed
        echo json_encode(["success" => true, "redirect" => "user_details.php"]);
    } else {
        echo json_encode(["success" => false, "message" => "Invalid OTP. Please try again."]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Verify Email - Servify</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="text-center p-5">
<h3>Verify Your Email</h3>
<p>We’ve sent a 6-digit verification code to your email: <b><?php echo htmlspecialchars($_SESSION['email']); ?></b></p>

<form id="otpForm" class="mt-3">
  <input type="text" class="form-control mb-3" name="otp" placeholder="Enter OTP" required>
  <button type="submit" class="btn btn-primary w-100">Verify</button>
</form>

<div class="position-fixed bottom-0 end-0 p-3">
  <div id="toastMessage" class="toast align-items-center text-bg-danger border-0" role="alert">
    <div class="d-flex">
      <div class="toast-body"></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById("otpForm").addEventListener("submit", async function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    const response = await fetch("", {
        method: "POST",
        body: formData
    });
    const result = await response.json();

    if (result.success) {
        window.location.href = result.redirect;
    } else {
        showToast(result.message);
    }
});

function showToast(message) {
    const toastEl = document.getElementById('toastMessage');
    toastEl.querySelector('.toast-body').textContent = message;
    const toast = new bootstrap.Toast(toastEl);
    toast.show();
}
</script>
</body>
</html>
