<?php
session_start();
include '../controls/connection.php';

// --- Current user ---
$current_user_id = $_SESSION['user_id'] ?? 0;

// --- Inputs ---
$page       = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
$search     = $_POST['search'] ?? '';
$location   = $_POST['location'] ?? '';
$sort       = $_POST['sort'] ?? 'name';
$limit      = 6; // workers per page
$offset     = ($page - 1) * $limit;

// --- Sort options ---
$sortColumn = "firstname ASC";
if ($sort === "location") $sortColumn = "location ASC";
if ($sort === "verified") $sortColumn = "is_verified DESC";

// --- Base query ---
$sql = "
    SELECT user_id, firstname, middlename, lastname, location, profile_picture, is_verified, rating
    FROM users
    WHERE role = 'worker'
      AND user_id != ?
";

// --- Filtering ---
$params = [$current_user_id];
$types = "i";

if ($search !== '') {
    $sql .= " AND (firstname LIKE ? OR lastname LIKE ?) ";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}

if ($location !== '') {
    $sql .= " AND location LIKE ? ";
    $params[] = "%$location%";
    $types .= "s";
}

$sql .= " ORDER BY $sortColumn LIMIT ? OFFSET ? ";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

// --- Prepare & execute ---
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// --- Count total workers for pagination ---
$count_sql = "
    SELECT COUNT(*) as total
    FROM users
    WHERE role = 'laborer' AND user_id != ?
";
$count_params = [$current_user_id];
$count_types = "i";

if ($search !== '') {
    $count_sql .= " AND (firstname LIKE ? OR lastname LIKE ?) ";
    $count_params[] = "%$search%";
    $count_params[] = "%$search%";
    $count_types .= "ss";
}

if ($location !== '') {
    $count_sql .= " AND location LIKE ? ";
    $count_params[] = "%$location%";
    $count_types .= "s";
}

$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param($count_types, ...$count_params);
$count_stmt->execute();
$count_result = $count_stmt->get_result()->fetch_assoc();
$total_workers = $count_result['total'];
$total_pages = ceil($total_workers / $limit);

// --- Build HTML ---
$html = "";
while ($row = $result->fetch_assoc()) {
    $id         = $row['user_id'];
    $name       = htmlspecialchars(trim($row['firstname']." ".$row['middlename']." ".$row['lastname']));
    $location   = htmlspecialchars($row['location'] ?? 'Unknown');
    $profilePic = !empty($row['profile_picture']) ? htmlspecialchars($row['profile_picture']) : "default.png";
    $verified   = $row['is_verified'] ? '<span class="badge bg-success ms-1">Verified</span>' : '';
    $rating     = intval($row['rating']);

    // --- Rating stars ---
    $stars = str_repeat("⭐", $rating) . str_repeat("☆", 5 - $rating);

    $html .= "
    <div class='worker-card' onclick=\"window.location='view_profile2.php?user_id=$id'\">
      <img src='$profilePic' alt='Profile'>
      <div class='worker-info'>
        <p class='worker-name'>$name $verified</p>
        <p class='worker-location'>📍 $location</p>
        <p class='rating'>$stars</p>
        <a href='report_worker.php?worker_id=$id' class='report-link' onclick='event.stopPropagation();'>Report</a>
      </div>
    </div>";
}

if ($html === "") {
    $html = "<p class='text-muted'>No workers available.</p>";
}

// --- Output ---
echo json_encode([
    "html" => $html,
    "total_pages" => $total_pages,
    "current_page" => $page
]);
