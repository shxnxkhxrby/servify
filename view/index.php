<?php
session_start();
include '../controls/connection.php';

$sql = "SELECT job_id, job_name, job_description FROM jobs";
$result = $conn->query($sql);

$is_logged_in = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Servify - Home</title>
  <link rel="stylesheet" type="text/css" href="../styles/landing_page.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

/* --- Modal --- */ 
.modal{display:flex;position:fixed;inset:0;background:rgba(0,0,0,.5);align-items:center;justify-content:center;padding:20px;}
.modal-content{background:#fff;padding:30px;width:50%;text-align:center;border-radius:8px;max-height:80vh;overflow-y:auto;box-shadow:0 4px 10px rgba(0,0,0,.2);}
.modal-content h3{margin:20px 0 10px;}
.modal-content ul{margin:10px 0;padding-left:20px;text-align:left;}
.hidden{display:none;}
.btn{padding:10px 15px;margin:15px 10px 0;border:none;cursor:pointer;border-radius:5px;}
.accept-btn{background:green;color:#fff;}
.decline-btn{background:red;color:#fff;}
.button.active{background:#0d6efd;color:#fff;}
/* --- Profile & Filters --- */ 
.profile-img{height:150px;width:auto;border-radius:50%;object-fit:cover;}
.filters-section{display:flex;justify-content:flex-end;gap:10px;margin-right:210px;align-items:center;}
/* --- Categories --- */ 
.categories-wrapper{margin:20px auto;max-width:1650px;padding:0 10px;overflow-x:hidden;}
.buttons-container{width:100%;overflow-x:visible;}
.categories-wrapper .buttons{display:grid;grid-template-columns:repeat(6,1fr);gap:16px;justify-items:center;align-items:start;width:100%;box-sizing:border-box;}
.categories-wrapper .buttons .button{width:100%;height:120px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;border-radius:10px;border:1px solid #e3e3e3;background:#fff;padding:10px;gap:6px;cursor:pointer;transition:.15s;color:#333;}
.categories-wrapper .buttons .button i{font-size:28px;}
.categories-wrapper .buttons .button span{display:block;font-size:13px;margin-top:4px;word-break:break-word;}
.categories-wrapper .buttons .button:hover,.categories-wrapper .buttons .button.active{background:#0d6efd;color:#fff;border-color:#0d6efd;transform:scale(1.03);box-shadow:0 4px 10px rgba(0,0,0,.12);}
.categories-wrapper .buttons {display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; justify-content: center; align-items: start; width: 100%; box-sizing: border-box;}
/* --- Pagination --- */ 
#workers-pagination{display:flex;justify-content:center;gap:6px;margin-top:18px;list-style:none;padding-left:0;}
#workers-pagination .page-item{margin:0 4px;}
#workers-pagination .page-link{cursor:pointer;}
/* --- How It Works --- */ 
.step-card{border-radius:15px;transition:.3s;}
.step-card:hover{transform:translateY(-8px);box-shadow:0 8px 18px rgba(0,0,0,.15);}
#howItWorks .icon i{transition:.4s;}
#howItWorks .step-card:hover .icon i{transform:scale(1.2) rotate(10deg);}
/* --- Announcement Image --- */ 
.announcement-image{display:block;width:100%;max-width:940px;height:320px;object-fit:cover;object-position:center;margin:0 auto 20px;border:none;border-radius:8px;transition:.4s;}
.announcement-image:hover{transform:scale(1.02);}
.card{border-radius:12px;}
@media(max-width:1024px){.announcement-image{height:260px;}}
@media(max-width:768px){.announcement-image{height:200px;}}
@media(max-width:480px){.announcement-image{height:150px;}}
/* --- Category Arrows --- */ 
.nav-arrow{background:#fff;border:2px solid #0d6efd;color:#0d6efd;font-size:1.6rem;width:55px;height:55px;border-radius:50%;display:flex;align-items:center;justify-content:center;transition:.3s;box-shadow:0 3px 8px rgba(0,0,0,.15);}
.nav-arrow:hover{background:#0d6efd;color:#fff;transform:scale(1.1);box-shadow:0 5px 12px rgba(13,110,253,.4);}
.nav-arrow:disabled{opacity:.4;cursor:not-allowed;transform:none;box-shadow:none;}
@media(max-width:768px){.nav-arrow{font-size:1.3rem;width:45px;height:45px;}}
/* --- Responsive Grid --- */ 
@media(max-width:1200px){.categories-wrapper .buttons{grid-template-columns:repeat(4,1fr);}}
@media(max-width:900px){.categories-wrapper .buttons{grid-template-columns:repeat(3,1fr);}}
@media(max-width:600px){.categories-wrapper .buttons{grid-template-columns:repeat(2,1fr);}.categories-wrapper .buttons .button{height:110px;}}
@media(max-width:400px){.categories-wrapper .buttons{grid-template-columns:1fr;}.categories-wrapper .buttons .button{height:96px;}}

/* HEADER */
a {text-decoration: none;color: #fff;}
header {width: 100%;height: 4rem;background-image: linear-gradient(to left, #027d8d, #035a68);box-shadow: 0 4px 8px rgba(0,0,0,0.2), 0 6px 20px rgba(0,0,0,0.19);display: flex;align-items: center;justify-content: center;padding: 0 1rem;  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1000; /* ensures it stays above other content */}
.header-content {width: 100%;max-width: 1200px;display: flex;justify-content: space-between;align-items: center;}
.brand {font-size: 1.5rem;font-weight: bold;color: #fff;}
.menu-container {position: relative;}
.wrapper-2 {display: flex;align-items: center;gap: 1rem;color: #fff;}
.wrapper-2 p {margin: 0;padding: 0.5rem 1rem;}
.wrapper-2 .login {border: 1px solid #fff;border-radius: 10px;}
.wrapper-2 .login:hover {background-color: #fff;color: #000 !important;}
.wrapper-2 .login:hover a {color: #000 !important;}
.burger {display: none;font-size: 1.8rem;color: white;cursor: pointer;}

/* RESPONSIVE */
@media only screen and (max-width: 780px){.burger {display: block;} .wrapper-2 {display: none;position: absolute;top: 4rem;right: 1rem;background-color: #fff;box-shadow: 0 4px 8px rgba(0,0,0,0.2);padding: 1rem 2rem;flex-direction: column;gap: 1rem;align-items: flex-start;z-index: 100;border-radius: 10px;} .wrapper-2.active {display: flex;} .wrapper-2 p, .wrapper-2 a {color: #000 !important;font-size: 0.95rem;} .wrapper-2 .login {border: 1px solid #000;background-color: transparent;} .wrapper-2 .login:hover {background-color: #000;color: #fff !important;} .wrapper-2 .login:hover a {color: #fff !important;}.wrapper-2 .divider {display: none;}}

html, body {
  margin: 0;
  padding: 0;
}

body{
   padding-top: 4rem;
}

/* Hide burger by default */
.burger {
  display: none;
}

/* Responsive visibility */
@media (max-width: 991px) {
  .burger {
    display: inline-block;
  }
}

/* Nav layout */
.wrapper-2 {
  display: flex;
  align-items: center;
  gap: 15px;
}

.wrapper-2 p {
  margin: 0;
  vertical-align: middle;
}

/* Profile icon and dropdown */
.profile-wrapper {
  position: relative;
  display: inline-block;
}

.profile-icon .icon {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  cursor: pointer;
  vertical-align: middle;
}

.profile-menu {
  position: absolute;
  right: 0;
  top: 40px;
  background-color: white;
  color: black;
  border-radius: 5px;
  box-shadow: 0 4px 8px rgba(0,0,0,0.1);
  padding: 10px;
  min-width: 200px;
  z-index: 1000;
}

.profile-menu a {
  display: block;
  padding: 8px 10px;
  text-decoration: none;
  color: black;
}

.profile-menu a:hover {
  background-color: #f1f1f1;
}

.user-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-bottom: 8px;
  border-bottom: 1px solid #ccc;
}

/* Hide top nav items on mobile/tablet */
@media (max-width: 991px) {
  .wrapper-2 p:not(.divider):not(.login) {
    display: none;
  }
}
.bottom-nav {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background-color: white;
  display: flex;
  justify-content: space-around; /* evenly spaced items */
  align-items: center;
  padding: 8px 0;
  z-index: 999;
  box-shadow: 0 -2px 6px rgba(0,0,0,0.1);
  border-top: 1px solid #ccc;
}

.bottom-nav .nav-item {
  text-align: center;
  cursor: pointer;
  color: #333;
  text-decoration: none;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;
  min-width: 60px; /* ensures tap target size */
}

.bottom-nav .nav-item i {
  font-size: 20px;
  color: inherit;
}

.bottom-nav .nav-item span {
  font-size: 12px;
  color: inherit;
}

.bottom-nav .nav-item.active {
  color: teal;
  font-weight: bold;
}


/* More popup menu */
.more-popup {
  position: fixed;
  bottom: 60px;
  right: 10px;
  background-color: white;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
  padding: 10px;
  z-index: 1000;
}

.more-popup a {
  display: block;
  padding: 8px 10px;
  color: black;
  text-decoration: none;
  font-size: 14px;
}

.more-popup a:hover {
  background-color: #f1f1f1;
}

/* Responsive visibility */
.mobile-only {
  display: none;
}

@media (max-width: 991px) {
  .mobile-only {
    display: flex;
  }
}

@media (max-width: 991px) {
  .wrapper-2 {
    display: none;
  }
}

/*FULL SCREEN TOGGLE MENU*/
.fullscreen-menu {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: white; /* solid white to fully cover */
  z-index: 10000;
  padding: 20px;
  overflow-y: auto;
}

/* Optional: if you want a centered card-style panel */
.menu-panel {
  max-width: 400px;
  margin: 0 auto;
}

/* OR: if you want full width layout */
.menu-panel {
  width: 100%;
}

/* Keep the rest of your styles */
.menu-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}

.menu-title {
  font-size: 20px;
  color: teal;
  margin: 0;
}

.close-btn {
  font-size: 20px;
  cursor: pointer;
  color: black;
  background-color: #e0e0e0; /* light gray */
  border-radius: 50%;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
}


.user-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 16px;
  margin-bottom: 20px;
}

.edit-icon {
  font-size: 18px;
  cursor: pointer;
  color: gray;
}

.menu-options {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.menu-options a {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 16px;
  text-decoration: none;
  color: black;
}

.menu-options a:hover {
  background-color: #f1f1f1;
  padding: 8px;
  border-radius: 6px;
}

/*PROFILE IN MENU TOGGLE*/
.user-section {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.profile-info {
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
}

.menu-title {
  font-size: 2rem; /* roughly 32px */
  font-weight: bold;
  padding-bottom: 10px;
  font-style: italic;
}

.icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
}

.user-name {
  margin: 0;
  font-size: 18px;
  color: #333;
}

.edit-icon {
  font-size: 20px;
  color: gray;
  cursor: pointer;
}

.profile-link {
  display: flex;
  align-items: center;
  gap: 10px;
  text-decoration: none;
  color: inherit;
}


/*DIVIDER*/
.section-divider {
  height: 1px;
  background-color: #ccc;
  margin: 15px 0;
}


/*FOR NON USERS*/
.fullscreen-menu {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: white;
  z-index: 10000;
  padding: 20px;
  overflow-y: auto;
}

.menu-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.menu-title {
  font-size: 28px;
  color: teal;
  margin: 0;
}

.close-btn {
  font-size: 20px;
  cursor: pointer;
  color: black;
  background-color: #e0e0e0;
  border-radius: 50%;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.menu-options {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.menu-options a {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 16px;
  text-decoration: none;
  color: black;
}

.menu-options a:hover {
  background-color: #f1f1f1;
  padding: 8px;
  border-radius: 6px;
}


/*CAROUSEL*/

.carousel-item {
    position: relative;
}
.carousel-item img {
    filter: brightness(30%);
}

.carousel-caption {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
    padding: 15px;
    max-width: 90%;
    width: 500px;
}

.carousel-caption h1 {
    font-size: 3rem;
    font-weight: bold;
    color: #fff;
    margin-bottom: 10px;
}

.carousel-caption p {
    font-size: 0.80rem;
    color: #ddd;
    margin: 0 auto;
    max-width: 90%;
}

.animate-text {
  opacity: 0;
  transform: translateY(30px);
  animation: fadeUp 1s ease forwards;
}

/* Stagger the second text */
.animate-text:nth-child(2) {
  animation-delay: 0.3s;
}

@keyframes fadeUp {
  to {
    opacity: 1;
    transform: translateY(0);
  }
}


/* Tablet */
@media (max-width: 768px) {
    .carousel-caption {
        max-width: 90%;
        padding: 10px;
    }

    .carousel-caption h1 {
        font-size: 1.8rem;
    }

    .carousel-caption p {
        font-size: 1rem;
    }
}

/* Mobile */
@media (max-width: 576px) {
    .carousel-caption {
        top: 60%;
        transform: translate(-50%, -60%);
        max-width: 95%;
        padding: 5px 15px;
    }

    .carousel-caption h1 {
        font-size: 1.4rem;
    }

    .carousel-caption p {
        font-size: 0.95rem;
        line-height: 1.4;
    }
}

@media (max-width: 1024px) {
  .categories-wrapper {
    overflow-x: auto;
    padding-bottom: 10px;
  }

  .buttons-container {
    overflow-x: auto;
  }

  .categories-wrapper .buttons {
    display: flex !important; /* override grid */
    flex-wrap: nowrap;
    gap: 12px;
    justify-content: flex-start;
    align-items: stretch;
    width: max-content;
    min-width: 100%;
    overflow-x: auto;
    scroll-snap-type: x mandatory;
    -webkit-overflow-scrolling: touch;
  }

  .categories-wrapper .buttons .button {
    flex: 0 0 auto;
    width: 140px;
    height: 110px;
    scroll-snap-align: start;
    box-sizing: border-box;
  }

  .categories-wrapper .buttons .button i {
    font-size: 24px;
  }

  .categories-wrapper .buttons .button span {
    font-size: 12px;
  }
}


</style>
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



 <!-- CAROUSEL -->
<div class="container-fluid p-0">
    <div id="carouselExampleAutoplaying" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner">
            <div class="carousel-item active">
                <img src="../image/bg2.png" class="d-block" style="width: 100%; height: 350px; object-fit: cover;" alt="...">
                <div class="carousel-caption text-center">
                    <h1 class="display-5 text-white animate-text">Welcome to Servify</h1>
                    <p class="text-white animate-text">Connecting you with the right laborer, creating opportunities and maximizing potential earnings.</p>
                </div>
            </div>
            <div class="carousel-item">
                <img src="../image/electrician.jpg" class="d-block" style="width: 100%; height: 350px; object-fit: cover;" alt="...">
                <div class="carousel-caption text-center">
                    <h5 class="text-white">Find Electricians</h5>
                    <p class="text-white">Get electrical services for your needs.</p>
                </div>
            </div>
            <div class="carousel-item">
                <img src="../image/plumber.png" class="d-block" style="width: 100%; height: 350px; object-fit: cover;" alt="...">
                <div class="carousel-caption text-center">
                    <h5 class="text-white">Hire Plumbers</h5>
                    <p class="text-white">Reliable plumbing solutions for your home and business.</p>
                </div>
            </div>
            <div class="carousel-item">
                <img src="../image/catering.jpeg" class="d-block" style="width: 100%; height: 350px; object-fit: cover;" alt="...">
                <div class="carousel-caption text-center">
                    <h5 class="text-white">Book Caterers</h5>
                    <p class="text-white">Delicious catering services for all your events.</p>
                </div>
            </div>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleAutoplaying" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleAutoplaying" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
    </div>
</div>

<!-- CATEGORIES -->
<div class="container text-center mt-5">
  <h4 class="fw-bold">Browse Categories</h4>
  <p class="text-muted">Find the right laborer for your needs</p>
</div>
<div class="categories-wrapper d-flex align-items-center justify-content-center">
  <!-- Left arrow -->
  <button id="prev-btn" class="nav-arrow"><i class="bi bi-chevron-left"></i></button>
  <!-- Categories grid -->
  <div class="buttons-container flex-grow-1">
    <div class="buttons" id="category-buttons">
      <!-- Categories loaded dynamically -->
    </div>
  </div>
  <!-- Right arrow -->
  <button id="next-btn" class="nav-arrow"><i class="bi bi-chevron-right"></i></button>
</div>

<!-- Laborer's Section -->
<div class="container text-center mt-5">
  <h4 class="fw-bold">Search for Laborers</h4>
  <p class="text-muted">Connect with skilled laborers in your area</p>
</div>

<div class="text-center mt-3">
  <a href="browse.php" class="btn btn-primary" id="browseBtn">Browse More</a>
</div>

<!-- Existing Laborers Container -->
<div id="workers-container" class="container p-4">
    <!-- Laborers will be displayed here -->
</div>

<!-- Workers pagination (hidden if backend doesn't return pages) -->
<nav class="d-flex justify-content-center">
  <ul id="workers-pagination" class="pagination" style="display:none;"></ul>
</nav>

<!-- Barangay Announcements Display -->
<div class="bg-white p-4 rounded-2xl mb-10">
  <h3 class="text-center text-success fw-bold mb-4">📢 Barangay Announcements 📢</h3>
  <?php
  $ann_query = "SELECT * FROM barangay_announcements ORDER BY date_posted DESC";
  $ann_result = $conn->query($ann_query);

  if ($ann_result && $ann_result->num_rows > 0):
      while ($ann = $ann_result->fetch_assoc()):
  ?>
    <!-- single announcement wrapper - centered and limited width -->
    <div class="mx-auto mb-5" style="max-width: 980px;">
      <div class="card border-0 shadow-sm overflow-hidden">
        <!-- image (centered) -->
        <?php if (!empty($ann['image_path'])): ?>
          <div class="d-flex justify-content-center bg-light">
            <img
              src="<?php echo htmlspecialchars($ann['image_path']); ?>"
              alt="Announcement Image"
              class="announcement-image"
            >
          </div>
        <?php endif; ?>

        <!-- text -->
        <div class="card-body text-center">
          <h4 class="card-title fw-semibold mb-2"><?php echo htmlspecialchars($ann['title']); ?></h4>
          <p class="card-text text-muted mb-3"><?php echo nl2br(htmlspecialchars($ann['content'])); ?></p>
          <p class="small text-secondary mb-0">📅 Posted on <?php echo date('F j, Y, g:i A', strtotime($ann['date_posted'])); ?></p>
        </div>
      </div>
    </div>
  <?php
      endwhile;
  else:
      echo '<p class="text-center text-muted">No announcements available at the moment.</p>';
  endif;
  ?>
</div>

<!-- HOW IT WORKS SECTION -->
<section id="howItWorks">
  <div class="container text-center">
    <h4 class="fw-bold">How It Works</h4>
    <p class="text-muted mb-5">Connecting you with reliable laborers in just a few simple steps.</p>

    <div class="row g-4 justify-content-center">
      <!-- Step 1 -->
      <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100 p-3 step-card" data-aos="fade-up" data-aos-delay="100">
          <div class="icon mb-3">
            <i class="bi bi-person-plus-fill fs-1 text-primary"></i>
          </div>
          <h5 class="fw-semibold">1. Create an Account</h5>
          <p class="text-muted small">Sign up easily as a user or laborer and start connecting today.</p>
        </div>
      </div>

      <!-- Step 2 -->
      <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100 p-3 step-card" data-aos="fade-up" data-aos-delay="200">
          <div class="icon mb-3">
            <i class="bi bi-search fs-1 text-success"></i>
          </div>
          <h5 class="fw-semibold">2. Find a Laborer</h5>
          <p class="text-muted small">Browse skilled workers by category or location in just a few clicks.</p>
        </div>
      </div>

      <!-- Step 3 -->
      <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100 p-3 step-card" data-aos="fade-up" data-aos-delay="300">
          <div class="icon mb-3">
            <i class="bi bi-hand-thumbs-up-fill fs-1 text-warning"></i>
          </div>
          <h5 class="fw-semibold">3. Hire & Transact</h5>
          <p class="text-muted small">Connect directly, agree on terms, and complete your task smoothly.</p>
        </div>
      </div>

      <!-- Step 4 -->
      <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100 p-3 step-card" data-aos="fade-up" data-aos-delay="400">
          <div class="icon mb-3">
            <i class="bi bi-star-fill fs-1 text-danger"></i>
          </div>
          <h5 class="fw-semibold">4. Rate & Review</h5>
          <p class="text-muted small">Leave feedback to help others choose trusted and skilled laborers.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- DISCLAIMER MODAL -->
<div id="disclaimerModal" class="modal">
  <div class="modal-content">
    <h2>Welcome to Servify!</h2>
    <p>
      Servify is a community-based platform that connects residents with local workers 
      for various services. We provide the digital space, while Barangay Staff and 
      Administrators help oversee the smooth operation of the platform.
    </p>
    <h3>Terms of Use</h3>
    <p>By using Servify, you acknowledge and agree to the following:</p>
    <ul>
      <li>Servify only serves as a platform to connect residents and workers; it does not employ or control them.</li>
      <li>Barangay Staff may help monitor user and worker activities, but final agreements are strictly between users and workers.</li>
      <li>We do not guarantee the quality of services, and we are not responsible for disputes or misconduct.</li>
      <li>Users are responsible for verifying the credibility of workers before engaging in any service.</li>
      <li>Servify is not liable for damages, losses, or issues arising from transactions made outside the platform.</li>
    </ul>
    <h3>Privacy Policy</h3>
    <p>
      Servify collects only the necessary information to operate the platform, such as 
      account details and contact information. Your data will not be shared without consent, 
      except when required by law or for community safety.
    </p>
    <!-- Checkbox Agreement -->
    <div style="margin-top: 20px; text-align: left;">
      <input type="checkbox" id="acceptCheckbox">
      <label for="acceptCheckbox"> I accept and agree to the Terms of Use and Privacy Policy of Servify.</label>
    </div>

    <!-- Accept Button -->
    <button id="agreeBtn" class="btn accept-btn" disabled>Accept & Continue</button>
  </div>
</div>

<script>
// ================== DISCLAIMER MODAL ==================
document.addEventListener("DOMContentLoaded", function () {
  const checkbox = document.getElementById("acceptCheckbox");
  const agreeBtn = document.getElementById("agreeBtn");
  const modal = document.getElementById("disclaimerModal");

  // change if the version was updated to reappear the modal
  const TERMS_VERSION = "v1";
  const acceptedVersion = localStorage.getItem("acceptedTermsVersion");

  if (checkbox && agreeBtn && modal) {
    if (acceptedVersion !== TERMS_VERSION) {
      modal.style.display = "flex";
    } else {
      modal.style.display = "none";
    }

    checkbox.addEventListener("change", function () {
      agreeBtn.disabled = !this.checked;
    });

    agreeBtn.addEventListener("click", function () {
      localStorage.setItem("acceptedTermsVersion", TERMS_VERSION);
      modal.style.display = "none";
    });
  }
});

// ================== CATEGORIES (Dynamic + Arrows) ==================
document.addEventListener("DOMContentLoaded", function () {
  const categoriesContainer = document.getElementById("category-buttons");
  const prevBtn = document.getElementById("prev-btn");
  const nextBtn = document.getElementById("next-btn");

  let currentCategoryPage = 1;
  const categoriesPerPage = 6;
  let totalCategoryPages = 1;

function loadCategories(page = 1) {
  fetch("fetch_categories.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `page=${page}&limit=${categoriesPerPage}`,
  })
    .then((res) => res.json())
    .then((data) => {
      categoriesContainer.innerHTML = data.categories
        .map(
          (cat) => `
          <button class="button" data-job-id="${cat.id}">
            <span class="d-block fs-3">${cat.emoji}</span>
            <span>${cat.name}</span>
          </button>
        `
        )
        .join("");

      currentCategoryPage = data.page;
      totalCategoryPages = Math.ceil(data.total / data.limit);
      updateCategoryArrows();

      // ✅ attach worker fetching logic to new buttons
      attachCategoryClick();
    })
    .catch((err) => console.error("Error loading categories:", err));
}

  function updateCategoryArrows() {
    prevBtn.disabled = currentCategoryPage <= 1;
    nextBtn.disabled = currentCategoryPage >= totalCategoryPages;
  }

  prevBtn.addEventListener("click", () => {
    if (currentCategoryPage > 1) loadCategories(currentCategoryPage - 1);
  });

  nextBtn.addEventListener("click", () => {
    if (currentCategoryPage < totalCategoryPages) {
      loadCategories(currentCategoryPage + 1);
    }
  });

  loadCategories(); // ✅ Initial load
});

// ================== WORKERS FETCHING & PAGINATION ==================
document.addEventListener("DOMContentLoaded", function () {
  const filterBySelect = document.getElementById("filter_by_select");
  const sortOrderSelect = document.getElementById("sort_order_select");
  const workersContainer = document.getElementById("workers-container");
  const workersPagination = document.getElementById("workers-pagination");

  let currentJobId = null;
  let currentPage = 1;

  function shuffleElements(container) {
    let items = Array.from(container.children);
    for (let i = items.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      container.appendChild(items[j]);
      items.splice(j, 1);
    }
  }

  function fetchLaborers(job_id, page = 1) {
    const filterBy = filterBySelect ? filterBySelect.value : "labor";
    const sortOrder = sortOrderSelect ? sortOrderSelect.value : "ASC";

    const params = new URLSearchParams();
    params.append("job_id", job_id);
    params.append("filter_by", filterBy);
    params.append("sort_order", sortOrder);
    params.append("page", page);

    fetch("fetch_workers.php", {
      method: "POST",
      body: params,
    })
      .then((response) => response.text())
      .then((text) => {
        let parsed;
        try {
          parsed = JSON.parse(text);
        } catch (e) {
          parsed = null;
        }

        if (parsed && parsed.html !== undefined) {
          workersContainer.innerHTML = parsed.html;
          if (job_id === "all") shuffleElements(workersContainer);
          setupWorkersPagination(
            parsed.total_pages || 1,
            parsed.current_page || 1
          );
        } else {
          workersContainer.innerHTML = text;
          if (job_id === "all") shuffleElements(workersContainer);
          workersPagination.style.display = "none";
        }
      })
      .catch((error) => console.error("Error fetching workers:", error));
  }

  function setupWorkersPagination(totalPages, activePage) {
    workersPagination.innerHTML = "";
    workersPagination.style.display = totalPages > 1 ? "" : "none";

    const makePageItem = (label, page, isActive, isDisabled) => {
      const li = document.createElement("li");
      li.className =
        "page-item" +
        (isActive ? " active" : "") +
        (isDisabled ? " disabled" : "");
      li.innerHTML = `<a class="page-link" href="#" data-page="${page}">${label}</a>`;
      return li;
    };

    // Prev
    workersPagination.appendChild(
      makePageItem("Prev", Math.max(1, activePage - 1), false, activePage <= 1)
    );

    const maxVisible = 7;
    let start = Math.max(1, activePage - Math.floor(maxVisible / 2));
    let end = Math.min(totalPages, start + maxVisible - 1);
    if (end - start < maxVisible - 1) start = Math.max(1, end - maxVisible + 1);

    if (start > 1) {
      workersPagination.appendChild(makePageItem("1", 1, false, false));
      if (start > 2) {
        const dots = document.createElement("li");
        dots.className = "page-item disabled";
        dots.innerHTML = `<span class="page-link">…</span>`;
        workersPagination.appendChild(dots);
      }
    }

    for (let p = start; p <= end; p++) {
      workersPagination.appendChild(
        makePageItem(p, p, p === activePage, false)
      );
    }

    if (end < totalPages) {
      if (end < totalPages - 1) {
        const dots = document.createElement("li");
        dots.className = "page-item disabled";
        dots.innerHTML = `<span class="page-link">…</span>`;
        workersPagination.appendChild(dots);
      }
      workersPagination.appendChild(
        makePageItem(totalPages, totalPages, false, false)
      );
    }

    // Next
    workersPagination.appendChild(
      makePageItem(
        "Next",
        Math.min(totalPages, activePage + 1),
        false,
        activePage >= totalPages
      )
    );

    workersPagination.querySelectorAll(".page-link").forEach((link) => {
      link.addEventListener("click", function (e) {
        e.preventDefault();
        const page = parseInt(this.getAttribute("data-page")) || 1;
        if (page === currentPage) return;
        currentPage = page;
        fetchLaborers(currentJobId, currentPage);
      });
    });
  }

window.attachCategoryClick = function () {
  const categoryButtons = document.querySelectorAll(".button");
  categoryButtons.forEach((btn) => {
    btn.addEventListener("click", function () {
      categoryButtons.forEach((b) => b.classList.remove("active"));
      this.classList.add("active");
      const job_id = this.getAttribute("data-job-id");
      currentJobId = job_id;
      currentPage = 1;
      fetchLaborers(job_id, currentPage);

      // ✅ Update Browse More button dynamically
      document.getElementById("browseBtn").href = `browse.php?jobs[]=${job_id}`;
      console.log("Browse button now points to:", document.getElementById("browseBtn").href);
    });
  });
};


  // ✅ Initial load of workers
  fetchLaborers(null, 1);
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


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<div style="margin-top:100px;">
  <?php include '../view/footer.php'; ?>
</div>
<!-- AOS Animation Library -->
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
  AOS.init({
    duration: 1500,
    once: true
  });
</script>
<style>
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fadeIn {
  animation: fadeIn 0.8s ease forwards;
}
</style>
</body>
</html>
