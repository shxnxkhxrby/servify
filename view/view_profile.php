<?php
include '../controls/connection.php';

// Get user_id from URL or set it to 0 if not present
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($user_id === 0) {
    // Redirect to a 404 page or show an error message (instead of using die())
    header("Location: 404.php");
    exit();
}

// Fetch user details from the database
$sql = "SELECT firstname, middlename, lastname, fb_link, email, location, date_created, contact, is_verified, rating FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    // Redirect to a 404 page if user is not found
    header("Location: 404.php");
    exit();
}

// Fetch services offered by the user
$services_sql = "SELECT jobs.job_name, user_jobs.job_description, jobs.job_image FROM jobs
                 INNER JOIN user_jobs ON jobs.job_id = user_jobs.job_id
                 WHERE user_jobs.user_id = ?";
$services_stmt = $conn->prepare($services_sql);
$services_stmt->bind_param("i", $user_id);
$services_stmt->execute();
$services_result = $services_stmt->get_result();
$services_stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?>'s Profile</title>
    <link rel="stylesheet" type="text/css" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { 
            background-color: #f9f9f9; 
            font-family: Arial, sans-serif; 
            margin: 0;
            padding: 0;
        }
        .container { 
            max-width: 800px; 
            margin: 30px auto; 
            background: white; 
            padding: 20px; 
            border-radius: 10px; 
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .navbar { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            background-color: #333; 
            padding: 10px;
            color: white;
        }
        .navbar span { 
            font-size: 20px;
        }
        .header {
            position: relative;
            margin-bottom: 20px;
        }
        .header h2 {
            margin: 0;
            font-size: 24px;
        }
        .report-btn {
            position: absolute;
            right: 0;
            top: 0;
            padding: 8px 15px; 
            background-color: red; 
            color: white; 
            text-decoration: none; 
            border-radius: 5px; 
            font-size: 14px; 
            font-weight: bold;
            margin-top: 10px;
        }
        .social-icons {
            margin-top: 10px;
        }
        .social-icons a {
            text-decoration: none;
            font-size: 24px;
            color: #333;
            margin-right: 15px;
            transition: color 0.3s;
        }
        .social-icons a:hover {
            color: #1877f2;
        }
        .profile-detail {
            margin-bottom: 15px;
        }
        .profile-detail strong {
            display: inline-block;
            width: 150px;
            font-weight: bold;
        }
       /* .verified { 
            color: green; 
        }
        .not-verified { 
            color: red; 
        }*/
        .services { 
            margin-top: 30px; 
        }
        .services h3 { 
            margin-bottom: 15px; 
            font-size: 18px;
        }
        .service-list { 
            display: flex; 
            flex-wrap: wrap; 
            gap: 20px; 
        }
        .service-item { 
            background: #fff; 
            border: 1px solid #ddd; 
            border-radius: 5px; 
            overflow: hidden; 
            width: calc(33.333% - 20px); 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .service-item img { 
            width: 100%; 
            height: auto; 
        }
        .service-info { 
            padding: 15px; 
        }
        .service-info h4 { 
            margin: 0 0 10px; 
            font-size: 16px;
        }
        .service-info p { 
            margin: 0; 
            font-size: 14px; 
            color: #555; 
        }
        @media (max-width: 768px) {
            .service-item { 
                width: calc(50% - 20px); 
            }
        }
        @media (max-width: 480px) {
            .service-item { 
                width: 100%; 
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <span>Labor Finder</span>
        <a href="login.php" class="profile-icon">👤</a>
    </div>

    <div class="container">
        <div class="header">
            <h2><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['middlename'] . ' ' . $user['lastname']); ?></h2>
            <div class="social-icons">
                <?php if (!empty($user['fb_link'])): ?>
                    <a href="<?php echo htmlspecialchars($user['fb_link']); ?>" target="_blank" title="Facebook">
                        <i class="fa-brands fa-facebook"></i>
                    </a>
                <?php endif; ?>
                <?php if (!empty($user['email'])): ?>
                    <a href="mailto:<?php echo htmlspecialchars($user['email']); ?>" title="Email">
                        <i class="fa-solid fa-envelope"></i>
                    </a>
                <?php endif; ?>
            </div>
            <a href="report.php?user_id=<?php echo $user_id; ?>" class="report-btn">🚨 Report</a>
        </div>
        <div class="profile-detail">
            <strong>Contact:</strong> <?php echo htmlspecialchars($user['contact']); ?>
        </div>
        <div class="profile-detail">
            <strong>Location:</strong> <?php echo htmlspecialchars($user['location']); ?>
        </div>
        <div class="profile-detail">
            <strong>Date Joined:</strong> <?php echo htmlspecialchars(date("F j, Y", strtotime($user['date_created']))); ?>
        </div>
        <div class="profile-detail">
            <strong>Rating:</strong> <?php echo htmlspecialchars(number_format($user['rating'], 1)); ?> / 5
        </div>
        <div class="profile-detail">
            <?php echo $user['is_verified'] ? '<span class="verified">✅ Verified</span>' : '<span class="not-verified">❌ Not Verified</span>'; ?>
        </div>

        <div class="services">
            <h3>Services Offered</h3>
            <div class="service-list">
                <?php while ($service = $services_result->fetch_assoc()): ?>
                    <div class="service-item">
                        <img src="<?php echo htmlspecialchars($service['job_image']); ?>" alt="<?php echo htmlspecialchars($service['job_name']); ?>">
                        <div class="service-info">
                            <h4><?php echo htmlspecialchars($service['job_name']); ?></h4>
                            <p><?php echo htmlspecialchars($service['job_description']); ?></p>
                        </div>
                    </div>
                <?php endwhile; ?>
                <?php if ($services_result->num_rows === 0): ?>
                    <p>No services offered.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
