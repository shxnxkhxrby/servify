<?php
session_start();
include '../controls/connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

// Fetch available jobs
$job_sql = "SELECT job_id, job_name FROM jobs";
$job_result = $conn->query($job_sql);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty($_POST['job_id']) && !empty($_POST['job_description'])) {
        $job_id = $_POST['job_id'];
        $job_description = trim($_POST['job_description']);
        $job_image = '../uploads/profile_pics/default.jpg'; // Default image path

        // Handle image upload if it's set
        if (isset($_FILES['job_image']) && $_FILES['job_image']['error'] == 0) {
            $image_tmp = $_FILES['job_image']['tmp_name'];
            $image_name = $_FILES['job_image']['name'];
            $image_size = $_FILES['job_image']['size'];
            $image_ext = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));

            // Validate file type and size
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            $max_size = 5 * 1024 * 1024; // 5MB limit

            if (in_array($image_ext, $allowed_extensions) && $image_size <= $max_size) {
                $upload_dir = '../uploads/';
                $image_path = $upload_dir . time() . "_" . basename($image_name);

                if (move_uploaded_file($image_tmp, $image_path)) {
                    $job_image = $image_path; // Overwrite default with uploaded path
                } else {
                    $message = "Error uploading the image. Default image will be used.";
                }
            } else {
                $message = "Invalid file type or size. Default image will be used.";
            }
        }

        // Check if the job is already assigned
        $check_sql = "SELECT * FROM user_jobs WHERE user_id = ? AND job_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ii", $user_id, $job_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $message = "You have already added this job!";
        } else {
            // Insert new job entry
            $assign_sql = "INSERT INTO user_jobs (user_id, job_id, job_description, job_image) VALUES (?, ?, ?, ?)";
            $assign_stmt = $conn->prepare($assign_sql);
            $assign_stmt->bind_param("iiss", $user_id, $job_id, $job_description, $job_image);

            if ($assign_stmt->execute()) {
                $message = "Labor added successfully!";
            } else {
                $message = "Error adding labor.";
            }
            $assign_stmt->close();
        }
        $check_stmt->close();
    } else {
        $message = "Please select a labor and enter a description.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Labor</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            text-align: center;
        }

        .container {
            max-width: 500px;
            margin: 50px auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 15px;
            text-align: left;
        }

        select, textarea, button, input[type="file"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
        }

        button {
            background: #28a745;
            color: white;
            font-size: 16px;
            border: none;
            cursor: pointer;
        }

        button:hover {
            background: #218838;
        }

        .message {
            margin-top: 10px;
            font-weight: bold;
            color: red;
        }

        .back-link {
            display: block;
            margin-top: 15px;
            text-decoration: none;
            color: #007bff;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Add Labor</h2>
    
    <?php if (!empty($message)): ?>
        <p class="message"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="job_id">Select a Labor:</label>
            <select name="job_id" id="job_id" required>
                <option value="">-- Choose --</option>
                <?php while ($job = $job_result->fetch_assoc()): ?>
                    <option value="<?php echo $job['job_id']; ?>">
                        <?php echo htmlspecialchars($job['job_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="job_description">Describe your skills in this labor:</label>
            <textarea name="job_description" id="job_description" rows="4" placeholder="Enter details about your experience and skills in this job..." required></textarea>
        </div>

        <div class="form-group">
            <label for="job_image">Upload a Job Image (optional):</label>
            <input type="file" name="job_image" id="job_image" accept="image/*">
        </div>

        <button type="submit">Add Labor</button>
    </form>

    <a href="profile.php" class="back-link">← Back to Profile</a>
</div>

</body>
</html>
