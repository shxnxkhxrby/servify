<?php
session_start();
include '../controls/connection.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['job_id'])) {
    header("Location: profile.php");
    exit();
}

$job_id = $_POST['job_id'];
$user_id = $_SESSION['user_id'];

$sql = "SELECT job_description, job_image FROM user_jobs WHERE job_id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $job_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$job = $result->fetch_assoc();
$stmt->close();

if (!$job) {
    echo "Job not found or unauthorized.";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_description'])) {
    $new_description = $_POST['new_description'];
    $image_name = $job['job_image']; // Default to existing image

    // Handle image upload
    if (isset($_FILES['job_image']) && $_FILES['job_image']['error'] === UPLOAD_ERR_OK) {
        $image_tmp = $_FILES['job_image']['tmp_name'];
        $image_ext = pathinfo($_FILES['job_image']['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid("job_", true) . "." . $image_ext;

        $upload_dir = "../uploads/";
        $upload_path = $upload_dir . $new_filename;

        if (move_uploaded_file($image_tmp, $upload_path)) {
            $image_name = $new_filename;
        } else {
            echo "Image upload failed.";
            exit();
        }
    }

    // Update job in database
    $update_sql = "UPDATE user_jobs SET job_description = ?, job_image = ? WHERE job_id = ? AND user_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssii", $new_description, $image_name, $job_id, $user_id);

    if ($update_stmt->execute()) {
        header("Location: profile.php");
        exit();
    } else {
        echo "Error updating job.";
    }
    $update_stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Job</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f5f5f5;
            color: #333;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 600px;
        }

        h2 {
            text-align: center;
            color: #007bff;
            font-size: 28px;
            margin-bottom: 20px;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        textarea {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 5px;
            min-height: 150px;
            resize: vertical;
            font-family: 'Arial', sans-serif;
        }

        button {
            background-color: #007bff;
            color: white;
            padding: 12px;
            font-size: 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #0056b3;
        }

        a {
            text-decoration: none;
            color: #007bff;
            font-size: 16px;
            text-align: center;
            display: inline-block;
            margin-top: 10px;
        }

        a:hover {
            text-decoration: underline;
        }

        .cancel-btn {
            display: block;
            text-align: center;
            margin-top: 10px;
        }

        .editable-description {
            border: 1px solid #ccc;
            padding: 12px;
            border-radius: 5px;
            background-color: #fafafa;
            cursor: pointer;
        }

        .editable-description:focus {
            background-color: #fff;
            border-color: #007bff;
        }

        img {
            max-width: 100%;
            border-radius: 5px;
        }

        input[type="file"] {
            font-size: 14px;
        }
    </style>
    <script>
        function enableEdit() {
            var description = document.getElementById('description');
            var textArea = document.createElement('textarea');
            textArea.name = 'new_description';
            textArea.id = 'new_description';
            textArea.value = description.textContent.trim();
            description.parentNode.replaceChild(textArea, description);
            document.getElementById('submit-btn').style.display = 'block';
        }
    </script>
</head>
<body>
    <div class="container">
        <h2>Edit Job Description</h2>
        <form action="edit_labor.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="job_id" value="<?php echo htmlspecialchars($job_id); ?>">

            <!-- Current Job Image -->
            <?php if (!empty($job['job_image'])): ?>
                <img src="../uploads/<?php echo htmlspecialchars($job['job_image']); ?>" alt="Job Image">
            <?php endif; ?>

            <!-- Upload New Job Image -->
            <label for="job_image">Upload New Image:</label>
            <input type="file" name="job_image" accept="image/*">

            <!-- Description -->
            <div id="description" class="editable-description" onclick="enableEdit()">
                <?php echo htmlspecialchars($job['job_description']); ?>
            </div>

            <button type="submit" id="submit-btn" style="display:none;">Update</button>
        </form>
        <a class="cancel-btn" href="profile.php">Cancel</a>
    </div>
</body>
</html>
