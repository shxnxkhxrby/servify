<?php
include '../controls/connection.php';

if (!isset($_GET['user_id'])) {
    header("Location: admin_dashboard.php");
    exit();
}

$user_id = $_GET['user_id'];

// Fetch user details
$user_query = "SELECT user_id, firstname, lastname, email, location, contact, rating, credit_score, is_verified FROM users WHERE user_id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<p>User not found.</p>";
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="max-w-4xl mx-auto mt-10 bg-white p-6 rounded-lg shadow-lg">
        <h2 class="text-2xl font-bold mb-4">User Profile</h2>
        <table class="w-full border-collapse border border-gray-300">
            <tr><td class="p-2 border font-semibold">User ID:</td><td class="p-2 border"><?php echo $user['user_id']; ?></td></tr>
            <tr><td class="p-2 border font-semibold">Name:</td><td class="p-2 border"><?php echo $user['firstname'] . " " . $user['lastname']; ?></td></tr>
            <tr><td class="p-2 border font-semibold">Email:</td><td class="p-2 border"><?php echo $user['email']; ?></td></tr>
            <tr><td class="p-2 border font-semibold">Location:</td><td class="p-2 border"><?php echo $user['location']; ?></td></tr>
            <tr><td class="p-2 border font-semibold">Contact:</td><td class="p-2 border"><?php echo $user['contact']; ?></td></tr>
            <tr><td class="p-2 border font-semibold">Rating:</td><td class="p-2 border"><?php echo $user['rating']; ?></td></tr>
            <tr><td class="p-2 border font-semibold">Credit Score:</td><td class="p-2 border"><?php echo $user['credit_score']; ?></td></tr>
            <tr><td class="p-2 border font-semibold">Verified:</td><td class="p-2 border"><?php echo ($user['is_verified'] == 1) ? '✅ Yes' : '❌ No'; ?></td></tr>
        </table>
        <div class="mt-4">
            <a href="admin_dashboard.php" class="text-blue-500">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>

<?php $conn->close(); ?>
