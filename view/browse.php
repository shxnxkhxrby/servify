<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
include '../controls/connection.php';

$current_user_id = $_SESSION['user_id'];
$selected_jobs = isset($_GET['jobs']) ? (array)$_GET['jobs'] : [];

$search   = $_GET['search']   ?? '';
$location = $_GET['location'] ?? '';
$sort     = $_GET['sort']     ?? 'name';
$order    = $_GET['order']    ?? 'asc';
$verified = isset($_GET['verified']) ? 1 : 0;
$rating   = isset($_GET['rating']) ? intval($_GET['rating']) : 0;
$selected_jobs = isset($_GET['jobs']) ? (array)$_GET['jobs'] : [];

$page     = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit    = 6;
$offset   = ($page - 1) * $limit;

$sortColumn = "u.firstname";
if ($sort === "location") $sortColumn = "u.location";
if ($sort === "rating")   $sortColumn = "rating";

$orderSQL = strtoupper($order) === "DESC" ? "DESC" : "ASC";

$sql = "
    SELECT u.user_id, u.firstname, u.middlename, u.lastname, u.location, 
           u.profile_picture, u.is_verified, 
           COALESCE(AVG(r.rating),0) as rating,
           GROUP_CONCAT(DISTINCT j.job_name ORDER BY j.job_name SEPARATOR ', ') as jobs
    FROM users u
    LEFT JOIN laborer_ratings r ON u.user_id = r.laborer_id
    LEFT JOIN user_jobs uj ON u.user_id = uj.user_id
    LEFT JOIN jobs j ON uj.job_id = j.job_id
    WHERE u.role = 'laborer'
      AND u.user_id != ?
";
$params = [$current_user_id];
$types = "i";

if ($search !== '') {
    $sql .= " AND (u.firstname LIKE ? OR u.middlename LIKE ? OR u.lastname LIKE ?) ";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "sss";
}
if ($location !== '') {
    $sql .= " AND u.location LIKE ? ";
    $params[] = "%$location%";
    $types .= "s";
}
if ($verified) {
    $sql .= " AND u.is_verified = 1 ";
}
if (!empty($selected_jobs)) {
    $placeholders = implode(',', array_fill(0, count($selected_jobs), '?'));
    $sql .= " AND j.job_id IN ($placeholders) ";
    $params = array_merge($params, $selected_jobs);
    $types .= str_repeat('i', count($selected_jobs));
}

$sql .= " GROUP BY u.user_id ";

if ($rating > 0) {
    $sql .= " HAVING rating >= ? ";
    $params[] = $rating;
    $types .= "i";
}

$sql .= " ORDER BY $sortColumn $orderSQL LIMIT ? OFFSET ? ";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$count_sql = "
    SELECT COUNT(*) as total FROM (
        SELECT u.user_id
        FROM users u
        LEFT JOIN laborer_ratings r ON u.user_id = r.laborer_id
        LEFT JOIN user_jobs uj ON u.user_id = uj.user_id
        LEFT JOIN jobs j ON uj.job_id = j.job_id
        WHERE u.role = 'laborer'
          AND u.user_id != ?
";
$count_params = [$current_user_id];
$count_types = "i";

if ($search !== '') {
    $count_sql .= " AND (u.firstname LIKE ? OR u.lastname LIKE ?) ";
    $count_params[] = "%$search%";
    $count_params[] = "%$search%";
    $count_types .= "ss";
}
if ($location !== '') {
    $count_sql .= " AND u.location LIKE ? ";
    $count_params[] = "%$location%";
    $count_types .= "s";
}
if ($verified) {
    $count_sql .= " AND u.is_verified = 1 ";
}
if (!empty($selected_jobs)) {
    $placeholders = implode(',', array_fill(0, count($selected_jobs), '?'));
    $count_sql .= " AND j.job_id IN ($placeholders) ";
    $count_params = array_merge($count_params, $selected_jobs);
    $count_types .= str_repeat('i', count($selected_jobs));
}

$count_sql .= " GROUP BY u.user_id ";

if ($rating > 0) {
    $count_sql .= " HAVING COALESCE(AVG(r.rating),0) >= ? ";
    $count_params[] = $rating;
    $count_types .= "i";
}

$count_sql .= ") as subquery";

$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param($count_types, ...$count_params);
$count_stmt->execute();
$total_workers = $count_stmt->get_result()->fetch_assoc()['total'] ?? 0;
$total_pages = ceil($total_workers / $limit);

$locations_res = $conn->query("SELECT location_name FROM locations ORDER BY location_name ASC");
$all_locations = [];
if ($locations_res && $locations_res->num_rows > 0) {
    while ($row = $locations_res->fetch_assoc()) {
        $all_locations[] = $row['location_name'];
    }
}

// Fetch all job options
$jobs_res = $conn->query("SELECT job_id, job_name FROM jobs ORDER BY job_name ASC");
$all_jobs = [];
if ($jobs_res && $jobs_res->num_rows > 0) {
    while ($row = $jobs_res->fetch_assoc()) {
        $all_jobs[$row['job_id']] = $row['job_name'];
    }
}

function removeFilter($key) {
    $params = $_GET;
    unset($params[$key]);
    $params['page'] = 1;
    return '?' . http_build_query($params);
}


// Check login status
$is_logged_in = isset($_SESSION['user_id']);
$current_user_id = $is_logged_in ? intval($_SESSION['user_id']) : null;

// Fetch logged-in user's name and profile picture
$current_user_name = '';
$profile_picture = 'uploads/profile_pics/default.jpg'; // Default fallback

if ($is_logged_in && $current_user_id) {
    $stmt = $conn->prepare("SELECT firstname, middlename, lastname, profile_picture FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $current_user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $user_row = $res->fetch_assoc();

        // Build full name
        $current_user_name = $user_row['firstname'];
        if (!empty($user_row['middlename'])) {
            $current_user_name .= ' ' . $user_row['middlename'];
        }
        $current_user_name .= ' ' . $user_row['lastname'];

        // Use profile picture if available
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
<meta charset="UTF-8">
<title>Browse Laborers</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="../styles/landing_page.css">
<style>
.worker-card { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; background: #fff; border: 1px solid #ddd; border-radius: 12px; padding: 15px; margin-bottom: 15px; cursor: pointer; transition: box-shadow 0.3s ease; }
.worker-card:hover { box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
.worker-left { display: flex; align-items: center; flex: 1; min-width: 200px; }
.worker-card img { width: 70px; height: 70px; border-radius: 50%; object-fit: cover; margin-right: 15px; }
.worker-info { flex-grow: 1; min-width: 120px; }
.worker-name { font-weight: bold; font-size: 1rem; margin: 0; }
.worker-location { margin: 2px 0; font-size: 0.85rem; }
.worker-jobs { margin: 2px 0; font-size: 0.85rem; color: #555; }
.worker-right { text-align: right; min-width: 80px; }
.rating { font-size: 0.85rem; margin-bottom: 0; }
.pagination { justify-content: center; flex-wrap: wrap; }
.filter-tag { display: inline-block; background: #e2e3e5; color: #333; padding: 3px 8px; border-radius: 15px; margin-right: 5px; margin-bottom: 5px; font-size: 0.85rem; }
.filter-tag a { color: #333; text-decoration: none; margin-left: 5px; font-weight: bold; }


/* GLOBAL RESET */
html, body {
  margin: 0;
  padding: 0;
  
}

body{
   padding-top: 4rem;
}

/* GLOBAL LINK STYLE */
a {
  text-decoration: none;
  color: #fff;
}
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

</style>
</head>
<body class="bg-light">
<!-- NAVIGATION BAR -->
<header>
  <div class="header-content">
    <div class="brand"><a href="../view/index.php">Servify</a></div>
    <div class="menu-container">
      <nav class="wrapper-2" id="menu">
        <p><a href="../view/browse.php">Services</a></p>
        <!-- <p><a href="#">Become a laborer</a></p> -->
        <p class="divider">|</p>

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

    <?php if ($current_user_id): ?>
      <div class="user-section">
        <div class="profile-info" onclick="toggleProfileMenu()">
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
        <a href="#"><i class="bi bi-bell"></i> Notifications</a>
        <a href="#"><i class="bi bi-grid"></i> Dashboard</a>
        <a href="../controls/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
      </div>
    <?php else: ?>
      <div class="menu-options">
        <a href="../view/become-laborer.php"><i class="bi bi-person-workspace"></i> Become a laborer</a>
        <a href="../view/login.php"><i class="bi bi-person-circle"></i> Signin / Signup</a>
      </div>
    <?php endif; ?>
  </div>
</div>


<div class="container py-5">
<h3 class="fw-bold text-center mb-4">Browse Laborers</h3>

<form method="get" class="row g-3 mb-3">
  <div class="col-md-4">
    <label class="form-label fw-semibold">Search</label>
    <input type="text" name="search" class="form-control" placeholder="🔍 Search by name..." value="<?= htmlspecialchars($search) ?>">
  </div>
  <div class="col-md-4">
    <label class="form-label fw-semibold">Location</label>
    <select name="location" class="form-select">
      <option value="">📍 All Locations</option>
      <?php foreach ($all_locations as $loc): ?>
        <option value="<?= htmlspecialchars($loc) ?>" <?= $location===$loc?'selected':'' ?>><?= htmlspecialchars($loc) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-2">
    <label class="form-label fw-semibold">Minimum Rating</label>
    <select name="rating" class="form-select">
      <option value="0" <?= $rating===0?'selected':'' ?>>Any</option>
      <option value="5" <?= $rating===5?'selected':'' ?>>5★</option>
      <option value="4" <?= $rating===4?'selected':'' ?>>4★ & up</option>
      <option value="3" <?= $rating===3?'selected':'' ?>>3★ & up</option>
      <option value="2" <?= $rating===2?'selected':'' ?>>2★ & up</option>
      <option value="1" <?= $rating===1?'selected':'' ?>>1★ & up</option>
    </select>
  </div>
  <div class="col-md-2 d-flex align-items-center">
    <div class="form-check mt-4">
      <input class="form-check-input" type="checkbox" name="verified" value="1" <?= $verified?'checked':'' ?>>
      <label class="form-check-label">Verified Only</label>
    </div>
  </div>

  <div class="col-md-12">
    <label class="form-label fw-semibold">Filter by Jobs</label><br>
    <?php foreach ($all_jobs as $id => $job): ?>
      <div class="form-check form-check-inline">
        <input class="form-check-input" type="checkbox" name="jobs[]" value="<?= $id ?>" id="job_<?= $id ?>" <?= in_array($id, $selected_jobs) ? 'checked' : '' ?>>
        <label class="form-check-label" for="job_<?= $id ?>"><?= htmlspecialchars($job) ?></label>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="col-md-3">
    <label class="form-label fw-semibold">Sort By</label>
    <select name="sort" class="form-select">
      <option value="name" <?= $sort==='name'?'selected':'' ?>>Name</option>
      <option value="location" <?= $sort==='location'?'selected':'' ?>>Location</option>
      <option value="rating" <?= $sort==='rating'?'selected':'' ?>>Rating</option>
    </select>
  </div>
  <div class="col-md-3">
    <label class="form-label fw-semibold">Order</label>
    <select name="order" class="form-select">
      <option value="asc" <?= $order==='asc'?'selected':'' ?>>Ascending</option>
      <option value="desc" <?= $order==='desc'?'selected':'' ?>>Descending</option>
    </select>
  </div>
  <div class="col-md-12 text-end">
    <button type="submit" class="btn btn-primary">Apply Filters</button>
  </div>
</form>

<div class="mb-3">
  <?php if($search): ?><span class="filter-tag">Search: <?= htmlspecialchars($search) ?> <a href="<?= removeFilter('search') ?>">×</a></span><?php endif; ?>
  <?php if($location): ?><span class="filter-tag">Location: <?= htmlspecialchars($location) ?> <a href="<?= removeFilter('location') ?>">×</a></span><?php endif; ?>
  <?php if($verified): ?><span class="filter-tag">Verified Only <a href="<?= removeFilter('verified') ?>">×</a></span><?php endif; ?>
  <?php if($rating > 0): ?><span class="filter-tag">Rating: <?= $rating ?>★ & up <a href="<?= removeFilter('rating') ?>">×</a></span><?php endif; ?>
  <?php if($sort && $sort!=='name'): ?><span class="filter-tag">Sort: <?= htmlspecialchars(ucfirst($sort)) ?> <a href="<?= removeFilter('sort') ?>">×</a></span><?php endif; ?>
  <?php if($order && $order!=='asc'): ?><span class="filter-tag">Order: <?= htmlspecialchars(ucfirst($order)) ?> <a href="<?= removeFilter('order') ?>">×</a></span><?php endif; ?>
  <?php if (!empty($selected_jobs)): ?>
    <?php foreach ($selected_jobs as $jid): ?>
      <?php if (isset($all_jobs[$jid])): ?>
        <span class="filter-tag">
          Job: <?= htmlspecialchars($all_jobs[$jid]) ?> <a href="<?= removeFilter('jobs') ?>">×</a>
        </span>
      <?php endif; ?>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<div class="mt-3">
<?php
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $id       = $row['user_id'];
        $name     = htmlspecialchars(trim($row['firstname']." ".$row['middlename']." ".$row['lastname']));
        $location = htmlspecialchars($row['location'] ?? 'Unknown');
        $jobs     = htmlspecialchars($row['jobs'] ?? 'No jobs listed');
        $pic      = !empty($row['profile_picture'])
                    ? 'http://localhost/servify/' . htmlspecialchars($row['profile_picture'])
                    : 'http://localhost/servify/uploads/profile_pics/default.jpg';
        $verified_badge = $row['is_verified'] ? '<span class="badge bg-success ms-1">Verified</span>' : '';
        $rating_value   = intval(round($row['rating']));
        $stars          = str_repeat("⭐", $rating_value) . str_repeat("☆", 5 - $rating_value);
        ?>
        <div class="worker-card" onclick="window.location='view_profile2.php?user_id=<?= $id ?>'">
          <div class="worker-left">
            <img src="<?= $pic ?>" alt="Profile">
            <div class="worker-info">
              <p class="worker-name"><?= $name ?> <?= $verified_badge ?></p>
              <p class="worker-location">📍 <?= $location ?></p>
              <p class="worker-jobs">🛠 <?= $jobs ?></p>
            </div>
          </div>
          <div class="worker-right"><p class="rating"><?= $stars ?></p></div>
        </div>
        <?php
    }
} else {
    echo "<p class='text-muted'>No laborers available.</p>";
}
?>
</div>

<?php if ($total_pages > 1): ?>
<nav>
  <ul class="pagination mt-3">
    <?php if ($page > 1): ?>
      <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page'=>$page-1])) ?>">Prev</a></li>
    <?php endif; ?>
    <?php for ($p=1; $p <= $total_pages; $p++): ?>
      <li class="page-item <?= $p==$page?'active':'' ?>">
        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page'=>$p])) ?>"><?= $p ?></a>
      </li>
    <?php endfor; ?>
    <?php if ($page < $total_pages): ?>
      <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page'=>$page+1])) ?>">Next</a></li>
    <?php endif; ?>
  </ul>
</nav>
<?php endif; ?>
</div>

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

