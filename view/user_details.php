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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="../styles/signup.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<title>Sign Up</title>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg fixed-top bg-white shadow-sm">
  <div class="container">
    <a class="navbar-brand" href="../view/index.php">Servify</a>
    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav">
        <li class="nav-item"><a class="nav-link" href="../view/signup.php">Sign Up</a></li>
        <li class="nav-item"><a class="nav-link">|</a></li>
        <li class="nav-item"><a class="nav-link" href="../view/login.php">Login</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="signup-container text-center mt-5 pt-5">
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
</body>
</html>
