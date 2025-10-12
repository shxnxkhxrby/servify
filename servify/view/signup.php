<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header('Content-Type: application/json'); // JSON response

    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    // Password validation: 8+ chars, 1 uppercase, 1 special char
    if (!preg_match("/^(?=.*[A-Z])(?=.*[!@#$%^&*(),.?\":{}|<>]).{8,}$/", $password)) {
        echo json_encode(["success" => false, "message" => "Password must be at least 8 characters long, contain one uppercase letter, and one special character."]);
        exit;
    }

    // Simple email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["success" => false, "message" => "Please enter a valid email address."]);
        exit;
    }

    // Ensure role is selected
    if (!in_array($role, ["client", "laborer"])) {
        echo json_encode(["success" => false, "message" => "Please select a valid role."]);
        exit;
    }

    // Store session values if valid
    $_SESSION['email'] = $email;
    $_SESSION['password'] = $password;
    $_SESSION['role'] = $role; // client/laborer

    echo json_encode(["success" => true, "redirect" => "user_details.php"]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" type="text/css" href="../styles/signup.css">
<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<title>Sign Up</title>
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
        <li class="nav-item"><a class="nav-link">|</a></li>
        <li class="nav-item"><a class="nav-link" href="../view/login.php">Login</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- SIGNUP -->
<div class="signup-container text-center mt-5 pt-5">
    <h3>Sign Up</h3>

    <form id="signupForm" class="mt-3">
      <div class="mb-3">
          <input type="email" class="form-control" name="email" placeholder="Email" required>
      </div>
      <div class="mb-3 input-group">
          <input type="password" class="form-control" name="password" id="password" 
                 placeholder="Password" required>
          <button class="btn btn-outline-secondary" type="button" id="togglePassword">
            <i class="fa fa-eye"></i>
          </button>
      </div>
      <div class="mb-3">
          <label class="form-label">I want to sign up as:</label>
          <select name="role" class="form-select" required>
              <option value="client">Client</option>
              <option value="laborer">Laborer</option>
          </select>
      </div>
      <button type="submit" class="btn btn-primary w-100">Next</button>
    </form>

    <p class="small-text mt-3">Already have an account? <a href="../view/login.php">Login</a></p>
</div>

<!-- Toast -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
  <div id="toastMessage" class="toast align-items-center text-bg-danger border-0" role="alert">
    <div class="d-flex">
      <div class="toast-body"></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById("signupForm").addEventListener("submit", async function(e) {
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

// Show/Hide password
document.getElementById("togglePassword").addEventListener("click", function() {
    const passField = document.getElementById("password");
    const icon = this.querySelector("i");
    if (passField.type === "password") {
        passField.type = "text";
        icon.classList.replace("fa-eye", "fa-eye-slash");
    } else {
        passField.type = "password";
        icon.classList.replace("fa-eye-slash", "fa-eye");
    }
});
</script>

</body>
</html>
