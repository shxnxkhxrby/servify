<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
include '../controls/connection.php';

$current_user_id = $_SESSION['user_id'];

$search   = $_GET['search']   ?? '';
$location = $_GET['location'] ?? '';
$sort     = $_GET['sort']     ?? 'name';
$order    = $_GET['order']    ?? 'asc';
$verified = isset($_GET['verified']) ? 1 : 0;
$rating   = isset($_GET['rating']) ? intval($_GET['rating']) : 0;

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

function removeFilter($key) {
    $params = $_GET;
    unset($params[$key]);
    $params['page'] = 1;
    return '?' . http_build_query($params);
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
</style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm fixed-top">
    <div class="container">
      <a class="navbar-brand fw-bold text-white" href="index.php">Servify</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
        <ul class="navbar-nav">
          <?php if ($current_user_id): ?>
            <li class="nav-item position-relative">
              <a class="nav-link" href="../view/messages.php">
                <i class="bi bi-chat-dots"></i> Messages
                <span id="unread-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none"></span>
              </a>
            </li>
            <li class="nav-item"><a class="nav-link" href="../view/profile.php"><i class="bi bi-person-circle"></i> Profile</a></li>
            <li class="nav-item"><a class="nav-link" href="../controls/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
          <?php else: ?>
            <li class="nav-item"><a class="nav-link" href="../view/signup.php">Sign Up</a></li>
            <li class="nav-item"><a class="nav-link">|</a></li>
            <li class="nav-item"><a class="nav-link" href="../view/login.php">Login</a></li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>

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
</body>
</html>
