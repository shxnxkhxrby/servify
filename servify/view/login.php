<?php
session_start();

// If the user is already logged in, redirect them to the profile page
if (isset($_SESSION['user_id'])) {
    header("Location: profile.php");
    exit();  // Always call exit after header redirect
}
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" type="text/css" href="../styles/login.css">
  <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <title>Log in</title>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg fixed-top bg-white shadow-sm">
  <div class="container">
    <a class="navbar-brand" href="../view/index.php">Servify</a>

    <!-- Search Bar -->
    <div class="search-container">
      <form class="d-flex align-items-center" role="search">
        <div class="input-group">
          <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
          <input class="form-control" type="search" placeholder="Search" aria-label="Search">
        </div>
      </form>
    </div>

    <!-- Burger Menu -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link" href="../view/signup.php">Sign Up</a>
        </li>
        <li class="nav-item">
          <a class="nav-link">|</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="../view/login.php">Login</a>
        </li>
      </ul>
    </div>
  </div>
  </a>
</nav>

<!-- LOGIN -->
<div class="login-container text-center">
    <h3>Log in</h3>
    <form action="../controls/login_validation.php" method="POST">
        <div class="mb-3">
            <input name="email" class="form-control" placeholder="Email" required>
        </div>
        <div class="mb-3">
            <input name="password" class="form-control" placeholder="Password" required>
        </div>
        <div class="d-flex justify-content-between mb-3">
            <div class="form-check text-start">
                <input type="checkbox" class="form-check-input">
                <label class="form-check-label">Remember password</label>
            </div>
            <a href="#" class="small-text ">Forgot password?</a>
        </div>
        <button type="submit" class="btn btn-primary w-100">LOGIN</button>
    </form>
    <p class="small-text mt-3">Don't have an account? <a href="#">Sign up</a></p>
</div>

        
<!--     <div class="frame">
        <form action="../controls/login_validation.php" method="POST">
            <label>Log in</label>
            <input  name="email" placeholder="Email" required><br>
            <input  name="password" placeholder="Password" required><br><br>
            <a href="signup.php">Don't have an account yet? Sign Up</a><br><br>
            <button type="submit">Login</button>
        </form>
    </div>
 -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
</body>
</html>
