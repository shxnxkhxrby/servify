<?php
session_start();
include '../controls/connection.php';

// --- Logged in user ---
$current_user_id = $_SESSION['user_id'] ?? 0;

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
$limit = 12;
$offset = ($page - 1) * $limit;

// --- Valid filters ---
$valid_filters = ['name', 'location', 'labor', 'rating'];
if (!in_array($filter_by, $valid_filters)) {
    $filter_by = 'labor';
}

// --- Map filter to column ---
switch ($filter_by) {
    case 'location':
        $order_by = 'users.location';
        break;
    case 'rating':
        $order_by = 'rating';
        break;
    case 'name':
        $order_by = "CONCAT(users.firstname, ' ', users.lastname)";
        break;
    default:
        $order_by = 'users.user_id';
        break;
}

if ($job_id === null) {
    // --- Show 6 random laborers, excluding self ---
    $sql = "
        SELECT 
            users.user_id, users.firstname, users.lastname, users.location,
            users.is_verified, users.profile_picture,
            COALESCE(AVG(r.rating), 0) AS rating
        FROM users
        LEFT JOIN laborer_ratings r ON users.user_id = r.laborer_id
        WHERE users.role = 'laborer' 
          AND users.user_id != ?
          AND users.is_verified = 1
        GROUP BY users.user_id
        ORDER BY RAND()
        LIMIT 6
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $current_user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();

    $total_pages = 1;
    $page = 1;
} else {
    // --- Count total workers ---
    $count_sql = "
        SELECT COUNT(DISTINCT users.user_id) AS total
        FROM users
        INNER JOIN user_jobs ON users.user_id = user_jobs.user_id
        WHERE users.role = 'laborer'
          AND users.user_id != ?
          AND user_jobs.job_id = ?
    ";
    $stmt = $conn->prepare($count_sql);
    $stmt->bind_param("ii", $current_user_id, $job_id);
    $stmt->execute();
    $count_res = $stmt->get_result();
    $total_workers = intval($count_res->fetch_assoc()['total']);
    $stmt->close();

    $total_pages = max(1, ceil($total_workers / $limit));

    // --- Fetch workers with rating ---
    $sql = "
        SELECT 
            users.user_id, users.firstname, users.lastname, users.location,
            users.is_verified, users.profile_picture,
            COALESCE(AVG(r.rating), 0) AS rating
        FROM users
        INNER JOIN user_jobs ON users.user_id = user_jobs.user_id
        LEFT JOIN laborer_ratings r ON users.user_id = r.laborer_id
        WHERE users.role = 'laborer'
          AND users.user_id != ?
          AND user_jobs.job_id = ?
          AND is_verified = 1
        GROUP BY users.user_id
        ORDER BY $order_by $sort_order
        LIMIT ? OFFSET ?
    ";
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
    $html .= '<div class="row">';
    while ($worker = $res->fetch_assoc()) {
        $pic = !empty($worker['profile_picture'])
            ? $worker['profile_picture']
            : 'uploads/profile_pics/default.jpg';

        // --- Fetch all jobs ---
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
            $icon = $icons[$job['job_name']] ?? "❓";
            $job_html .= '<span class="me-1">' . htmlspecialchars($icon) . '</span>';
        }
        if (empty($job_html)) {
            $job_html = '<span style="font-size:12px; color:#6c757d;">No labor posted</span>';
        }

        // --- Render rating stars dynamically ---
        $avg_rating = round($worker['rating'], 1);
        $full_stars = floor($avg_rating);
        $half_star = ($avg_rating - $full_stars) >= 0.5 ? 1 : 0;
        $empty_stars = 5 - $full_stars - $half_star;

        $stars_html = str_repeat('<i class="fa-solid fa-star" style="color:#FFD700;"></i>', $full_stars);
        if ($half_star) $stars_html .= '<i class="fa-solid fa-star-half-stroke" style="color:#FFD700;"></i>';
        $stars_html .= str_repeat('<i class="fa-regular fa-star" style="color:#FFD700;"></i>', $empty_stars);

        $html .= '
        <div class="col labor-card worker-card pre-animate">
            <a href="view/view_profile2.php?user_id=' . $worker['user_id'] . '" class="card-link">
                <div class="card">
                    ' . ($worker['is_verified'] ? '<h6 id="verification">✓ Verified</h6>' : '') . '
                    <img src="' . htmlspecialchars($pic) . '" class="card-img-top" alt="Profile Image">
                    <div class="card-body">
                        <div class="name-rating">
                            <h5>' . htmlspecialchars($worker['firstname'] . ' ' . $worker['lastname']) . '</h5>
                            <div id="rating">' . $stars_html . '</div>
                        </div>
                        <p id="services">' . $job_html . '</p>
                        <p id="location">' . htmlspecialchars($worker['location']) . '</p>
                    </div>
                </div>
            </a>
        </div>';
    }
    $html .= '</div>';
} else {
    $html = '
<div class="no-workers-container text-center py-5">
    <div class="no-workers-icon-wrapper">
        <div class="search-icon-circle">
            <i class="bi bi-search"></i>
        </div>
        <div class="floating-dots">
            <span class="dot dot-1"></span>
            <span class="dot dot-2"></span>
            <span class="dot dot-3"></span>
        </div>
    </div>
    <h5 class="no-workers-title">No Workers Available</h5>
    <p class="no-workers-text">We couldn\'t find any workers in this category yet.<br>Try browsing other categories or check back later.</p>
</div>';
}

// --- Return JSON ---
echo json_encode([
    'html' => $html,
    'total_pages' => $total_pages,
    'current_page' => $page
]);

$conn->close();
?>