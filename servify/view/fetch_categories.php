<?php
include '../controls/connection.php';

$limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
$page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
$offset = ($page - 1) * $limit;

// Fetch categories
$sql = "SELECT * FROM jobs LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

// Emoji mapping
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

$categories = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = [
            "id" => (int)$row["job_id"],
            "name" => $row["job_name"],
            "emoji" => $icons[$row["job_name"]] ?? "📦"
        ];
    }
}

// Get total count
$total = $conn->query("SELECT COUNT(*) AS total FROM jobs")->fetch_assoc()['total'];

echo json_encode([
    "categories" => $categories,
    "total" => $total,
    "page" => $page,
    "limit" => $limit
]);
