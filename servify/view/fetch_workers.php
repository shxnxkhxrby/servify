<?php
session_start();
include '../controls/connection.php';

// --- Logged in user ---
$current_user_id = $_SESSION['user_id'] ?? 0; // fallback to 0 if not logged in

// --- Inputs ---
$job_id = $_POST['job_id'] ?? '';
if ($job_id === 'null' || $job_id === '' || $job_id === null) {
    $job_id = null;
} else {
    $job_id = intval($job_id);
}

$filter_by = $_POST['filter_by'] ?? 'labor';
$sort_order = $_POST['sort_order'] ?? 'ASC';
$page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
$limit = 12; // workers per page
$offset = ($page - 1) * $limit;

// --- Valid filters ---
$valid_filters = ['name', 'location', 'labor'];
if (!in_array($filter_by, $valid_filters)) {
    $filter_by = 'labor';
}

// --- Map filter to column ---
switch ($filter_by) {
    case 'location':
        $order_by = 'users.location';
        break;
    case 'labor':
        $order_by = 'users.user_id'; // fallback
        break;
    case 'name':
    default:
        $order_by = "CONCAT(users.firstname, ' ', users.lastname)";
        break;
}

if ($job_id === null) {
    // --- Show 5 random laborers, excluding self ---
    $sql = "SELECT users.user_id, users.firstname, users.lastname, users.location, 
                   users.is_verified, users.profile_picture
            FROM users
            WHERE users.role = 'laborer' 
              AND users.user_id != ?
            ORDER BY RAND()
            LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $current_user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();

    $total_pages = 1;
    $page = 1;
} else {
    // --- Count total workers (excluding self) ---
    $count_sql = "SELECT COUNT(DISTINCT users.user_id) AS total
                  FROM users
                  INNER JOIN user_jobs ON users.user_id = user_jobs.user_id
                  WHERE users.role = 'laborer' 
                    AND users.user_id != ? 
                    AND user_jobs.job_id = ?";
    $stmt = $conn->prepare($count_sql);
    $stmt->bind_param("ii", $current_user_id, $job_id);
    $stmt->execute();
    $count_res = $stmt->get_result();
    $total_workers = intval($count_res->fetch_assoc()['total']);
    $stmt->close();

    $total_pages = max(1, ceil($total_workers / $limit));

    // --- Fetch workers by category (excluding self) ---
    $sql = "SELECT users.user_id, users.firstname, users.lastname, users.location, 
                   users.is_verified, users.profile_picture
            FROM users
            INNER JOIN user_jobs ON users.user_id = user_jobs.user_id
            WHERE users.role = 'laborer' 
              AND users.user_id != ? 
              AND user_jobs.job_id = ?
            GROUP BY users.user_id
            ORDER BY $order_by $sort_order
            LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $current_user_id, $job_id, $limit, $offset);
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();
}

// --- Emoji mapping for jobs ---
$icons = [
    "Electrician" => "⚡",
    "Mechanic" => "🔧",
    "Plumber" => "🪠",
    "Carpentry" => "🔨",
    "Welder" => "⚙️",
    "Handyman" => "🛠️",
    "Personal Assistant" => "🗂️",
    "Gaming Coach" => "🎮",
    "Tutor" => "📖",
    "Cook" => "🍳",
    "Driver" => "🚚",
    "Cleaning Service" => "🧹",
    "Pest Control" => "🐜",
    "Personal Shopper" => "🛒",
    "Babysitter" => "👶",
    "Caretaker" => "❤️",
    "Massage" => "💆‍♀️",
    "Beauty Care" => "💅",
    "Labor" => "👷",
    "Arts" => "🎨",
    "Photography" => "📷",
    "Videography" => "🎥",
    "Performer" => "🎭",
    "Seamstress" => "✂️",
    "Graphic Designer" => "🖌️",
    "IT Support" => "💻",
    "Event Organizer" => "📅",
    "DJ & Audio Services" => "🎧",
    "Writing & Editing" => "✏️",
    "Pet Care" => "🐾",
    "Dog Walker" => "🐕",
    "Companion Service" => "🧑‍🤝‍🧑",
    "Party Performer" => "🎉",
    "Street Performer" => "🎤",
    "Delivery Service" => "📦",
    "Fitness Trainer" => "🏋️",
    "Furniture Assembler" => "🚪",
    "Personal Stylist" => "💇‍♀️",
    "Gardener" => "🌱",
    "Laundry Service" => "🧺"
];

// --- Build HTML ---
$html = '';
if ($res && $res->num_rows > 0) {
    $html .= '<div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-6 justify-content-center">';
    while ($worker = $res->fetch_assoc()) {
        $status = $worker['is_verified'] ? 'Verified' : 'Not Verified';
        $pic = !empty($worker['profile_picture'])
            ? 'http://localhost/servify/' . $worker['profile_picture']
            : 'http://localhost/servify/uploads/profile_pics/default.jpg';

        // --- Fetch all jobs for this worker ---
        $job_sql = "SELECT jobs.job_name
                    FROM user_jobs
                    INNER JOIN jobs ON user_jobs.job_id = jobs.job_id
                    WHERE user_jobs.user_id = ?";
        $job_stmt = $conn->prepare($job_sql);
        $job_stmt->bind_param("i", $worker['user_id']);
        $job_stmt->execute();
        $job_res = $job_stmt->get_result();
        $job_stmt->close();

        $job_html = '';
        while ($job = $job_res->fetch_assoc()) {
            $job_name = $job['job_name'];
            $icon = $icons[$job_name] ?? "❓";
            $job_html .= '<span class="me-1">' . htmlspecialchars($icon) . '</span>';
        }

        if (empty($job_html)) {
            $job_html = '<span style="font-size:12px; color:#6c757d;">No labor posted</span>';
        }

        $html .= '
        <div class="col mb-4 labor-card">
            <a href="../view/view_profile2.php?user_id=' . $worker['user_id'] . '" style="text-decoration:none;color:inherit;">
              <div class="card mx-auto border-0 shadow-sm" style="width:12rem;cursor:pointer;">
                <img src="' . htmlspecialchars($pic) . '" alt="Profile Picture" class="profile-pic" style="height:150px;object-fit:cover;">
                <div class="card-body p-2">
                  <div class="d-flex justify-content-between align-items-center w-100" style="gap:20px;">
                    <h6 class="card-title mb-0 labor-name text-truncate" style="font-size:18px;flex-grow:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                      ' . htmlspecialchars($worker['firstname'] . ' ' . $worker['lastname']) . '
                    </h6>
                    <span class="badge ' . ($worker['is_verified'] ? 'bg-success' : 'bg-danger') . '" style="font-size:12px;white-space:nowrap;">
                      ' . $status . '
                    </span>
                  </div>
                  <p class="card-text mt-1 mb-0 text-muted labor-job text-truncate" style="font-size:18px;">
                    ' . $job_html . '
                  </p>
                  <p class="card-text mb-1 text-muted labor-location text-truncate" style="font-size:10px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    ' . htmlspecialchars($worker['location']) . '
                  </p>
                </div>
              </div>
            </a>
        </div>';
    }
    $html .= '</div>';
} else {
    $html = '<p>No workers available.</p>';
}

// --- Return JSON ---
echo json_encode([
    'html' => $html,
    'total_pages' => $total_pages,
    'current_page' => $page
]);

$conn->close();
?>
