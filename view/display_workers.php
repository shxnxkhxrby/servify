<?php
include '../controls/connection.php';

session_start();
if ($_SESSION['role'] != 'laborer' && $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];

// Retrieve job_id from query parameter
$job_id = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;
if ($job_id === 0) {
    die("Invalid job ID.");
}

// Fetch job details
$job_query = "SELECT job_name FROM jobs WHERE job_id = ?";
$job_stmt = $conn->prepare($job_query);
$job_stmt->bind_param("i", $job_id);
$job_stmt->execute();
$job_result = $job_stmt->get_result();
$job = $job_result->fetch_assoc();
$job_stmt->close();

if (!$job) {
    die("Job not found.");
}

$worker_query = "SELECT users.user_id, users.firstname, users.middlename, users.lastname, users.location, users.is_verified, users.rating
                 FROM users
                 INNER JOIN user_jobs ON users.user_id = user_jobs.user_id
                 WHERE user_jobs.job_id = ?";

$worker_stmt = $conn->prepare($worker_query);
$worker_stmt->bind_param("i", $job_id);
$worker_stmt->execute();
$worker_result = $worker_stmt->get_result();
$worker_stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($job['job_name']); ?> Workers</title>
    <link rel="stylesheet" type="text/css" href="../styles/styles.css">
    <style>
        body { background-color: #f9f9f9; font-family: Arial, sans-serif; }
        .container { max-width: 800px; margin: 20px auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        h2 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #f2f2f2; }
        .verified { color: green; }
        .not-verified { color: red; }
        .profile-link { margin-right: 10px; }
    </style>
</head>
<body>
    <div class="navbar">
        <span>Labor Finder</span>
        <div class="search-container">
            <input type="text" id="search-input" placeholder="Search jobs..." onkeyup="filterJobs()">
        </div>
        <a href="login.php" class="profile-icon">👤</a>
    </div>

    <div class="container">
        <h2><?php echo htmlspecialchars($job['job_name']); ?> Workers</h2>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Location</th>
                    <th>Verification Status</th>
                    <th>Actions</th>
                    <th>Rating</th>
                </tr>
            </thead>
            <tbody id="worker-table-body">
                <?php
                if ($worker_result->num_rows > 0) {
                    while ($worker = $worker_result->fetch_assoc()) {
                        $full_name = htmlspecialchars($worker['firstname'] . ' ' . $worker['middlename'] . ' ' . $worker['lastname']);
                        $location = htmlspecialchars($worker['location']);
                        $is_verified = $worker['is_verified'] ? '<span class="verified">Verified</span>' : '<span class="not-verified">Not Verified</span>';
                        $rating = htmlspecialchars(number_format($worker['rating'], 1));
                        $profile_link = "view_profile.php?user_id=" . $worker['user_id'];
                        echo "<tr>
                                <td>$full_name</td>
                                <td>$location</td>
                                <td>$is_verified</td>
                                <td><a href='$profile_link' class='profile-link'>View Profile</a></td>
                                <td>$rating</td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='5'>No workers found for this job.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <script>
        function filterJobs() {
            const input = document.getElementById('search-input').value.toLowerCase();
            const rows = document.querySelectorAll('#worker-table-body tr');

            rows.forEach(row => {
                const rowText = row.innerText.toLowerCase();
                if (rowText.includes(input)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        }
    </script>
</body>
</html>
