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
$rating   = isset($_GET['rating']) ? intval($_GET['rating']) : 0;
$selected_jobs = isset($_GET['jobs']) ? (array)$_GET['jobs'] : [];

$page     = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit    = 10;
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
      AND u.is_verified = 1
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
          AND u.is_verified = 1
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

$is_logged_in = isset($_SESSION['user_id']);
$current_user_id = $is_logged_in ? intval($_SESSION['user_id']) : null;

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

<?php include '../nav.php' ?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Browse Laborers - Servify</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
* {margin:0;padding:0;box-sizing:border-box;}
body {padding-top:4rem;background:#f8f9fa;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;min-height:100vh;}
a {text-decoration:none;color:inherit;}
.container-fluid {max-width:1400px;padding:0 15px;margin:0 auto;}
.page-header {margin:2rem 0 1.5rem;text-align:center;}
.page-header h1 {font-size:2.5rem;font-weight:700;color:#333;margin-bottom:0.5rem;}
.page-header p {color:#666;font-size:1.1rem;}
.filter-bar {background:white;border-radius:12px;padding:1.5rem;margin-bottom:1.5rem;box-shadow:0 2px 8px rgba(0,0,0,0.08);}
.filter-container {display:flex;align-items:center;gap:1rem;flex-wrap:wrap;}
.search-box {flex:1;min-width:280px;position:relative;}
.search-box input {width:100%;padding:12px 50px 12px 20px;border:2px solid #e0e0e0;border-radius:8px;font-size:15px;transition:all 0.3s ease;}
.search-box input:focus {outline:none;border-color:#027d8d;box-shadow:0 0 0 3px rgba(2,125,141,0.1);}
.search-btn {position:absolute;right:8px;top:50%;transform:translateY(-50%);background:linear-gradient(to left,#027d8d,#035a68);color:white;border:none;padding:8px 18px;border-radius:6px;cursor:pointer;transition:all 0.3s ease;}
.search-btn:hover {transform:translateY(-50%) scale(1.05);box-shadow:0 4px 12px rgba(2,125,141,0.4);}
.filter-group {display:flex;gap:12px;flex-wrap:wrap;align-items:center;}
.filter-btn {background:white;border:2px solid #e0e0e0;padding:10px 20px;border-radius:8px;cursor:pointer;font-size:14px;font-weight:500;transition:all 0.3s ease;display:flex;align-items:center;gap:8px;white-space:nowrap;color:#333;}
.filter-btn:hover {border-color:#027d8d;color:#027d8d;transform:translateY(-2px);box-shadow:0 4px 12px rgba(2,125,141,0.15);}
.filter-btn.active {background:linear-gradient(to left,#027d8d,#035a68);border-color:#027d8d;color:white;}
.filter-dropdown {position:relative;}
.filter-dropdown-menu {display:none;position:absolute;top:calc(100% + 10px);left:0;background:white;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,0.15);padding:12px;min-width:220px;z-index:100;max-height:400px;overflow-y:auto;animation:slideDown 0.3s ease;}
.filter-dropdown.open .filter-dropdown-menu {display:block;}
.filter-option {display:flex;align-items:center;padding:10px 12px;cursor:pointer;border-radius:8px;font-size:14px;transition:all 0.3s ease;}
.filter-option:hover {background:#f5f5f5;}
.filter-option input {margin-right:10px;cursor:pointer;width:18px;height:18px;accent-color:#027d8d;}
@keyframes slideDown {from{opacity:0;transform:translateY(-10px);}to{opacity:1;transform:translateY(0);}}
.sort-bar {display:flex;justify-content:space-between;align-items:center;padding:1rem 1.5rem;background:white;border-radius:12px;margin-bottom:1.5rem;box-shadow:0 2px 8px rgba(0,0,0,0.06);gap:1rem;flex-wrap:wrap;}
.results-info {font-size:15px;color:#666;font-weight:500;}
.sort-controls {display:flex;gap:12px;align-items:center;flex-wrap:wrap;}
.sort-options {display:flex;gap:8px;flex-wrap:wrap;}
.sort-btn {background:white;border:2px solid #e0e0e0;padding:8px 16px;border-radius:8px;cursor:pointer;font-size:14px;font-weight:500;color:#666;transition:all 0.3s ease;display:flex;align-items:center;gap:6px;}
.sort-btn:hover {border-color:#027d8d;color:#027d8d;transform:translateY(-2px);}
.sort-btn.active {background:linear-gradient(to left,#027d8d,#035a68);border-color:#027d8d;color:white;}
.order-controls {display:flex;gap:6px;background:#f8f9fa;padding:4px;border-radius:8px;}
.order-btn {background:transparent;border:none;padding:6px 12px;border-radius:6px;cursor:pointer;font-size:13px;font-weight:500;color:#666;transition:all 0.3s ease;display:flex;align-items:center;gap:4px;}
.order-btn:hover {color:#027d8d;}
.order-btn.active {background:white;color:#027d8d;box-shadow:0 2px 4px rgba(0,0,0,0.1);}
.active-filters {display:flex;flex-wrap:wrap;gap:10px;margin-bottom:1.5rem;}
.filter-tag {display:inline-flex;align-items:center;gap:8px;background:linear-gradient(to left,#027d8d,#035a68);color:white;padding:8px 16px;border-radius:20px;font-size:13px;font-weight:500;box-shadow:0 2px 8px rgba(2,125,141,0.3);}
.filter-tag a {color:white;font-weight:bold;font-size:18px;margin-left:6px;transition:all 0.3s ease;}
.filter-tag a:hover {transform:scale(1.2);}
.workers-grid {display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1.25rem;margin-bottom:2rem;}
.worker-card {background:white;border-radius:12px;overflow:hidden;cursor:pointer;transition:all 0.3s ease;box-shadow:0 2px 8px rgba(0,0,0,0.08);display:flex;flex-direction:column;position:relative;}
.worker-card:hover {transform:translateY(-6px);box-shadow:0 8px 24px rgba(2,125,141,0.2);}
.worker-img-container {position:relative;width:100%;height:160px;overflow:hidden;}
.worker-img {width:100%;height:100%;object-fit:cover;}
.verified-badge {position:absolute;top:8px;right:0;color:white;font-size:0.7rem;padding:4px 12px;background-color:#4CAF50;border-top-left-radius:12px;border-bottom-left-radius:12px;box-shadow:0 2px 4px rgba(0,0,0,.2);z-index:1;font-weight:600;}
.worker-details {padding:1rem;display:flex;flex-direction:column;gap:0.5rem;flex:1;}
.worker-name {font-weight:700;font-size:1rem;color:#333;margin-bottom:0.25rem;line-height:1.3;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.worker-location {font-size:13px;color:#666;display:flex;align-items:center;gap:4px;}
.worker-jobs {font-size:12px;color:#888;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;text-overflow:ellipsis;min-height:34px;}
.worker-rating {display:flex;align-items:center;gap:4px;font-size:14px;color:#ffc107;margin-top:auto;padding-top:0.5rem;border-top:1px solid #f0f0f0;}
.pagination {display:flex;justify-content:center;gap:8px;margin:2rem 0;flex-wrap:wrap;}
.pagination a {padding:10px 16px;border:2px solid #e0e0e0;border-radius:8px;color:#666;font-weight:500;transition:all 0.3s ease;background:white;}
.pagination a:hover {border-color:#027d8d;color:#027d8d;transform:translateY(-2px);}
.pagination a.active {background:linear-gradient(to left,#027d8d,#035a68);color:white;border-color:#027d8d;}
.mobile-filter-btn {display:none;width:100%;padding:14px;background:linear-gradient(to left,#027d8d,#035a68);border:none;border-radius:8px;margin-bottom:1.5rem;text-align:center;cursor:pointer;font-weight:600;color:white;transition:all 0.3s ease;box-shadow:0 2px 8px rgba(2,125,141,0.3);}
.mobile-filter-btn:hover {transform:translateY(-2px);box-shadow:0 4px 12px rgba(2,125,141,0.4);}
.modal-filter {display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:2000;backdrop-filter:blur(5px);}
.modal-filter-content {position:absolute;bottom:0;left:0;right:0;background:white;border-radius:24px 24px 0 0;max-height:85vh;overflow-y:auto;padding:24px;animation:slideUp 0.4s ease;}
@keyframes slideUp {from{transform:translateY(100%);}to{transform:translateY(0);}}
.modal-filter-header {display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;padding-bottom:16px;border-bottom:2px solid #f0f0f0;}
.modal-filter-header h5 {margin:0;font-size:20px;font-weight:700;color:#333;}
.modal-close {font-size:28px;cursor:pointer;color:#999;transition:all 0.3s ease;}
.modal-close:hover {color:#027d8d;transform:rotate(90deg);}
.filter-section {margin-bottom:24px;}
.filter-section-title {font-weight:700;font-size:15px;margin-bottom:12px;color:#333;}
.filter-actions {display:flex;gap:12px;padding-top:20px;border-top:2px solid #f0f0f0;position:sticky;bottom:0;background:white;}
.filter-actions button {flex:1;padding:14px;border:none;border-radius:8px;cursor:pointer;font-weight:700;font-size:15px;transition:all 0.3s ease;}
.btn-reset {background:#f5f5f5;color:#666;}
.btn-reset:hover {background:#e0e0e0;}
.btn-apply {background:linear-gradient(to left,#027d8d,#035a68);color:white;}
.btn-apply:hover {transform:translateY(-2px);box-shadow:0 4px 12px rgba(2,125,141,0.4);}
@media (max-width:768px) {
.page-header h1 {font-size:1.8rem;}
.filter-bar {display:none;}
.mobile-filter-btn {display:block;}
body {padding-bottom:70px;}
.workers-grid {grid-template-columns:repeat(2,1fr);gap:0.75rem;}
.worker-card {border-radius:10px;}
.worker-img-container {height:140px;}
.worker-details {padding:0.75rem;gap:0.4rem;}
.worker-name {font-size:0.9rem;}
.worker-location {font-size:11px;}
.worker-jobs {font-size:11px;min-height:30px;}
.worker-rating {font-size:12px;padding-top:0.4rem;}
.verified-badge {font-size:9px;padding:3px 6px;}
.sort-bar {flex-direction:column;gap:1rem;text-align:center;padding:1rem;}
.sort-controls {width:100%;justify-content:center;}
.filter-container {flex-direction:column;}
.search-box {width:100%;}
.sort-options {width:100%;justify-content:center;}
.sort-btn {flex:1;min-width:70px;padding:6px 12px;font-size:12px;}
.order-controls {width:100%;justify-content:center;}
.order-btn {padding:4px 10px;font-size:11px;}
.active-filters {justify-content:center;}
}
@media (max-width:480px) {
.page-header h1 {font-size:1.5rem;}
.page-header p {font-size:0.95rem;}
.filter-tag {font-size:11px;padding:6px 10px;}
.worker-name {font-size:0.85rem;}
.pagination a {padding:8px 10px;font-size:14px;}
.workers-grid {gap:0.5rem;}
.worker-img-container {height:120px;}
}
@media (min-width:769px) and (max-width:1024px) {
.workers-grid {grid-template-columns:repeat(3,1fr);}
.filter-container {gap:10px;}
.filter-btn {padding:8px 16px;font-size:13px;}
}
@media (min-width:1025px) {
.workers-grid {grid-template-columns:repeat(4,1fr);}
}
@media (min-width:1400px) {
.workers-grid {grid-template-columns:repeat(5,1fr);}
}
.d-none {display:none!important;}
::-webkit-scrollbar {width:8px;}
::-webkit-scrollbar-track {background:#f1f1f1;}
::-webkit-scrollbar-thumb {background:linear-gradient(to left,#027d8d,#035a68);border-radius:10px;}
::-webkit-scrollbar-thumb:hover {background:#027d8d;}
</style>

</head>
<body>

<div class="container-fluid">
  <div class="page-header">
    <h1>Find Your Perfect Laborer</h1>
    <p>Browse verified professionals ready to help with your projects</p>
  </div>
  
  <!-- Mobile Filter Button -->
  <button class="mobile-filter-btn" onclick="openMobileFilter()">
    <i class="bi bi-funnel"></i> Filters & Sort
  </button>

  <!-- Desktop Filter Bar -->
  <div class="filter-bar">
    <form method="get" id="filterForm">
      <div class="filter-container">
        <div class="search-box">
          <input type="text" name="search" id="searchInput" placeholder="Search by name..." value="<?= htmlspecialchars($search) ?>">
          <button type="submit" class="search-btn"><i class="bi bi-search"></i></button>
        </div>
        
        <div class="filter-dropdown" id="locationFilter">
          <button type="button" class="filter-btn <?= $location!==''?'active':'' ?>" onclick="toggleDropdown('locationFilter')">
            <i class="bi bi-geo-alt"></i> Location <i class="bi bi-chevron-down"></i>
          </button>
          <div class="filter-dropdown-menu">
            <?php foreach ($all_locations as $loc): ?>
              <label class="filter-option">
                <input type="radio" name="location" value="<?= htmlspecialchars($loc) ?>" 
                  <?= $location===$loc?'checked':'' ?> onchange="applyFilters()">
                <?= htmlspecialchars($loc) ?>
              </label>
            <?php endforeach; ?>
            <label class="filter-option">
              <input type="radio" name="location" value="" <?= $location===''?'checked':'' ?> onchange="applyFilters()"> All Locations
            </label>
          </div>
        </div>

        <div class="filter-dropdown" id="jobsFilter">
          <button type="button" class="filter-btn <?= !empty($selected_jobs)?'active':'' ?>" onclick="toggleDropdown('jobsFilter')">
            <i class="bi bi-briefcase"></i> Services 
            <?php if(!empty($selected_jobs)): ?>
              <span style="background:#fff;color:#027d8d;padding:2px 8px;border-radius:50%;font-size:12px;font-weight:700;"><?= count($selected_jobs) ?></span>
            <?php endif; ?>
            <i class="bi bi-chevron-down"></i>
          </button>
          <div class="filter-dropdown-menu" style="min-width:280px;">
            <div style="max-height:300px;overflow-y:auto;">
              <?php foreach ($all_jobs as $id => $job): ?>
                <label class="filter-option">
                  <input type="checkbox" name="jobs[]" value="<?= $id ?>" 
                    <?= in_array($id,$selected_jobs)?'checked':'' ?> 
                    onchange="applyFilters()">
                  <?= htmlspecialchars($job) ?>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <div class="filter-dropdown" id="ratingFilter">
          <button type="button" class="filter-btn <?= $rating>0?'active':'' ?>" onclick="toggleDropdown('ratingFilter')">
            <i class="bi bi-star-fill"></i> Rating <i class="bi bi-chevron-down"></i>
          </button>
          <div class="filter-dropdown-menu">
            <label class="filter-option"><input type="radio" name="rating" value="5" <?= $rating===5?'checked':'' ?> onchange="applyFilters()"> ⭐⭐⭐⭐⭐ Only</label>
            <label class="filter-option"><input type="radio" name="rating" value="4" <?= $rating===4?'checked':'' ?> onchange="applyFilters()"> ⭐⭐⭐⭐ & Up</label>
            <label class="filter-option"><input type="radio" name="rating" value="3" <?= $rating===3?'checked':'' ?> onchange="applyFilters()"> ⭐⭐⭐ & Up</label>
            <label class="filter-option"><input type="radio" name="rating" value="0" <?= $rating===0?'checked':'' ?> onchange="applyFilters()"> All Ratings</label>
          </div>
        </div>
      </div>
    </form>
  </div>

  <!-- Sort Bar with Separate ASC/DESC -->
  <div class="sort-bar">
    <span class="results-info">
      <i class="bi bi-funnel"></i> Showing <?= $total_workers ?> result<?= $total_workers !== 1 ? 's' : '' ?>
    </span>
    <div class="sort-controls">
      <div class="sort-options">
        <form method="get" class="d-flex gap-2" style="margin:0;">
          <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
          <input type="hidden" name="location" value="<?= htmlspecialchars($location) ?>">
          <input type="hidden" name="rating" value="<?= $rating ?>">
          <input type="hidden" name="order" value="<?= htmlspecialchars($order) ?>">
          <?php foreach($selected_jobs as $jid): ?><input type="hidden" name="jobs[]" value="<?= $jid ?>"><?php endforeach; ?>
          <button type="submit" name="sort" value="name" class="sort-btn <?= $sort==='name'?'active':'' ?>">
            <i class="bi bi-sort-alpha-down"></i> Name
          </button>
          <button type="submit" name="sort" value="location" class="sort-btn <?= $sort==='location'?'active':'' ?>">
            <i class="bi bi-geo-alt"></i> Location
          </button>
          <button type="submit" name="sort" value="rating" class="sort-btn <?= $sort==='rating'?'active':'' ?>">
            <i class="bi bi-star-fill"></i> Rating
          </button>
        </form>
      </div>
      <div class="order-controls">
        <form method="get" style="display:contents;">
          <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
          <input type="hidden" name="location" value="<?= htmlspecialchars($location) ?>">
          <input type="hidden" name="rating" value="<?= $rating ?>">
          <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
          <?php foreach($selected_jobs as $jid): ?><input type="hidden" name="jobs[]" value="<?= $jid ?>"><?php endforeach; ?>
          <button type="submit" name="order" value="asc" class="order-btn <?= $order==='asc'?'active':'' ?>">
            <i class="bi bi-sort-up"></i> ASC
          </button>
          <button type="submit" name="order" value="desc" class="order-btn <?= $order==='desc'?'active':'' ?>">
            <i class="bi bi-sort-down"></i> DESC
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- Active Filters -->
  <?php if($search || $location || $rating > 0 || !empty($selected_jobs)): ?>
  <div class="active-filters">
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;width:100%;">
      <?php if($search): ?>
        <span class="filter-tag">
          <i class="bi bi-search"></i> Search: <?= htmlspecialchars($search) ?> 
          <a href="<?= removeFilter('search') ?>">×</a>
        </span>
      <?php endif; ?>
      <?php if($location): ?>
        <span class="filter-tag">
          <i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars($location) ?> 
          <a href="<?= removeFilter('location') ?>">×</a>
        </span>
      <?php endif; ?>
      <?php if($rating > 0): ?>
        <span class="filter-tag">
          <i class="bi bi-star-fill"></i> <?= $rating ?>★ & up 
          <a href="<?= removeFilter('rating') ?>">×</a>
        </span>
      <?php endif; ?>
      <?php foreach($selected_jobs as $jid): ?>
        <?php if(isset($all_jobs[$jid])): ?>
          <span class="filter-tag">
            <i class="bi bi-briefcase-fill"></i> <?= htmlspecialchars($all_jobs[$jid]) ?> 
            <a href="javascript:void(0)" onclick="removeJobFilter(<?= $jid ?>)">×</a>
          </span>
        <?php endif; ?>
      <?php endforeach; ?>
      <?php if($search || $location || $rating > 0 || !empty($selected_jobs)): ?>
        <a href="browse.php" style="color:#027d8d;font-weight:600;text-decoration:underline;">Clear All</a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Workers Grid -->
  <div class="workers-grid">
    <?php
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $id = $row['user_id'];
            $name = htmlspecialchars(trim($row['firstname']." ".$row['middlename']." ".$row['lastname']));
            $loc = htmlspecialchars($row['location'] ?? 'Unknown');
            $jobs = htmlspecialchars($row['jobs'] ?? 'No jobs listed');
            $pic = !empty($row['profile_picture']) ? '../'.htmlspecialchars($row['profile_picture']) : '../uploads/profile_pics/default.jpg';
            $verified_badge = $row['is_verified'] ? '<span class="verified-badge">✓ Verified</span>' : '';
            $rating_value = intval(round($row['rating']));
            $stars = str_repeat("⭐", $rating_value);
            ?>
            <div class="worker-card" onclick="window.location='view_profile2.php?user_id=<?= $id ?>'">
                <div class="worker-img-container">
                    <img src="<?= $pic ?>" alt="<?= $name ?>" class="worker-img">
                    <?= $verified_badge ?>
                </div>
                <div class="worker-details">
                    <div>
                        <div class="worker-name"><?= $name ?></div>
                        <div class="worker-location">
                          <i class="bi bi-geo-alt-fill"></i> <?= $loc ?>
                        </div>
                        <div class="worker-jobs">
                          <i class="bi bi-briefcase"></i> <?= $jobs ?>
                        </div>
                    </div>
                    <div class="worker-rating">
                      <?= $stars ?: '☆☆☆☆☆' ?>
                      <?php if($rating_value > 0): ?>
                        <span style="color:#666;font-size:13px;margin-left:4px;">(<?= number_format($row['rating'], 1) ?>)</span>
                      <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php
        }
    } else {
        echo '<div style="grid-column:1/-1;text-align:center;padding:60px 20px;">
                <i class="bi bi-search" style="font-size:64px;color:#ccc;"></i>
                <h3 style="color:#999;margin-top:20px;font-weight:600;">No laborers found</h3>
                <p style="color:#bbb;">Try adjusting your filters or search criteria</p>
              </div>';
    }
    ?>
  </div>

  <!-- Pagination -->
  <?php if ($total_pages > 1): ?>
  <div class="pagination">
    <?php if ($page > 1): ?>
      <a href="?<?= http_build_query(array_merge($_GET, ['page'=>$page-1])) ?>">
        <i class="bi bi-chevron-left"></i> Prev
      </a>
    <?php endif; ?>
    <?php for ($p=max(1,$page-2); $p<=min($total_pages,$page+2); $p++): ?>
      <a href="?<?= http_build_query(array_merge($_GET, ['page'=>$p])) ?>" class="<?= $p==$page?'active':'' ?>"><?= $p ?></a>
    <?php endfor; ?>
    <?php if ($page < $total_pages): ?>
      <a href="?<?= http_build_query(array_merge($_GET, ['page'=>$page+1])) ?>">
        Next <i class="bi bi-chevron-right"></i>
      </a>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>

<!-- Mobile Filter Modal -->
<div id="mobileFilterModal" class="modal-filter">
  <div class="modal-filter-content">
    <div class="modal-filter-header">
      <h5><i class="bi bi-funnel"></i> Filters & Sort</h5>
      <span class="modal-close" onclick="closeMobileFilter()">×</span>
    </div>
    
    <form method="get" id="mobileFilterForm">
      <div class="filter-section">
        <div class="filter-section-title">Search</div>
        <input type="text" name="search" class="form-control" style="padding:12px;border-radius:8px;border:2px solid #e0e0e0;" placeholder="Search by name..." value="<?= htmlspecialchars($search) ?>">
      </div>

      <div class="filter-section">
        <div class="filter-section-title">Location</div>
        <select name="location" class="form-select" style="padding:12px;border-radius:8px;border:2px solid #e0e0e0;">
          <option value="">All Locations</option>
          <?php foreach ($all_locations as $loc): ?>
            <option value="<?= htmlspecialchars($loc) ?>" <?= $location===$loc?'selected':'' ?>><?= htmlspecialchars($loc) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="filter-section">
        <div class="filter-section-title">Services</div>
        <div style="max-height:200px;overflow-y:auto;border:2px solid #e0e0e0;border-radius:8px;padding:12px;">
          <?php foreach ($all_jobs as $id => $job): ?>
            <label class="filter-option" style="margin-bottom:8px;">
              <input type="checkbox" name="jobs[]" value="<?= $id ?>" <?= in_array($id,$selected_jobs)?'checked':'' ?>>
              <?= htmlspecialchars($job) ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="filter-section">
        <div class="filter-section-title">Rating</div>
        <select name="rating" class="form-select" style="padding:12px;border-radius:8px;border:2px solid #e0e0e0;">
          <option value="0" <?= $rating===0?'selected':'' ?>>All Ratings</option>
          <option value="5" <?= $rating===5?'selected':'' ?>>⭐⭐⭐⭐⭐ Only</option>
          <option value="4" <?= $rating===4?'selected':'' ?>>⭐⭐⭐⭐ & Up</option>
          <option value="3" <?= $rating===3?'selected':'' ?>>⭐⭐⭐ & Up</option>
        </select>
      </div>

      <div class="filter-section">
        <div class="filter-section-title">Sort By</div>
        <select name="sort" class="form-select" style="padding:12px;border-radius:8px;border:2px solid #e0e0e0;">
          <option value="name" <?= $sort==='name'?'selected':'' ?>>Name</option>
          <option value="location" <?= $sort==='location'?'selected':'' ?>>Location</option>
          <option value="rating" <?= $sort==='rating'?'selected':'' ?>>Rating</option>
        </select>
      </div>

      <div class="filter-section">
        <div class="filter-section-title">Order</div>
        <select name="order" class="form-select" style="padding:12px;border-radius:8px;border:2px solid #e0e0e0;">
          <option value="asc" <?= $order==='asc'?'selected':'' ?>>Ascending</option>
          <option value="desc" <?= $order==='desc'?'selected':'' ?>>Descending</option>
        </select>
      </div>

      <div class="filter-actions">
        <button type="button" class="btn-reset" onclick="resetFilters()">Reset All</button>
        <button type="submit" class="btn-apply">Apply Filters</button>
      </div>
    </form>
  </div>
</div>

<script>
function toggleProfileMenu() {
  const menu = document.getElementById('profile-menu');
  menu.classList.toggle('d-none');
}

function toggleDropdown(id) {
  const dropdown = document.getElementById(id);
  const allDropdowns = document.querySelectorAll('.filter-dropdown');
  allDropdowns.forEach(d => {
    if(d.id !== id) d.classList.remove('open');
  });
  dropdown.classList.toggle('open');
}

function applyFilters() {
  setTimeout(() => {
    document.getElementById('filterForm').submit();
  }, 100);
}

function removeJobFilter(jobId) {
  const form = document.getElementById('filterForm');
  const checkboxes = form.querySelectorAll('input[name="jobs[]"]');
  checkboxes.forEach(cb => {
    if(parseInt(cb.value) === jobId) {
      cb.checked = false;
    }
  });
  form.submit();
}

document.addEventListener('click', function(e) {
  if (!e.target.closest('.filter-dropdown')) {
    document.querySelectorAll('.filter-dropdown').forEach(d => d.classList.remove('open'));
  }
  if (!e.target.closest('.profile-wrapper')) {
    document.getElementById('profile-menu')?.classList.add('d-none');
  }
});

function openMobileFilter() {
  document.getElementById('mobileFilterModal').style.display = 'block';
  document.body.style.overflow = 'hidden';
}

function closeMobileFilter() {
  document.getElementById('mobileFilterModal').style.display = 'none';
  document.body.style.overflow = 'auto';
}

function resetFilters() {
  window.location.href = 'browse.php';
}

document.getElementById('mobileFilterModal')?.addEventListener('click', function(e) {
  if (e.target === this) {
    closeMobileFilter();
  }
});

document.getElementById('searchInput')?.addEventListener('keypress', function(e) {
  if (e.key === 'Enter') {
    e.preventDefault();
    document.getElementById('filterForm').submit();
  }
});

document.querySelectorAll('.worker-card').forEach(card => {
  card.addEventListener('click', function() {
    this.style.opacity = '0.6';
    this.style.pointerEvents = 'none';
  });
});

document.querySelectorAll('.pagination a').forEach(link => {
  link.addEventListener('click', function() {
    window.scrollTo({top: 0, behavior: 'smooth'});
  });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>