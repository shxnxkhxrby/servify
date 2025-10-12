<?php
include '../controls/connection.php';

// Assuming user_id is passed via session or URL, or use a default value for testing.
session_start();
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($user_id === 0) {
    die("Invalid user ID.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $report_reasons = isset($_POST['report_reason']) ? $_POST['report_reason'] : [];
    $additional_details = isset($_POST['additional_details']) ? $_POST['additional_details'] : "";

    if (empty($report_reasons)) {
        $error_message = "No report reasons selected.";
    } else {
        if ($user_id) {
            $status = 'pending';

            $stmt = $conn->prepare("INSERT INTO reports (user_id, reason, additional_details, status, report_date) VALUES (?, ?, ?, ?, NOW())");

            if (!$stmt) {
                die("Error preparing statement: " . $conn->error);
            }

            // Bind parameters outside of the loop to prevent multiple binding issues
            $stmt->bind_param("isss", $user_id, $reason, $additional_details, $status);

            // Loop through each report reason
            foreach ($report_reasons as $reason) {
                $reason = $conn->real_escape_string($reason);
                if (!$stmt->execute()) {
                    $error_message = "There was an issue submitting your report. Please try again.";
                    break;
                }
            }
            if (!isset($error_message)) {
                $success_message = "Your report has been submitted successfully and is awaiting admin review.";
            }

            $stmt->close();
        }
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report User</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f9f9f9; margin: 0; padding: 0; }
        .navbar { border-bottom: 2px solid black; padding: 10px; display: flex; align-items: center; justify-content: space-between; background: white; }
        .navbar span { font-size: 18px; font-weight: bold; }
        .profile-icon { font-size: 20px; text-decoration: none; color: black; }
        .container { max-width: 600px; margin: 30px auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); text-align: center; }
        h2 { margin-bottom: 15px; }
        label { display: block; font-size: 16px; margin: 10px 0; text-align: left; }
        input[type="checkbox"] { margin-right: 8px; }
        textarea { width: 100%; height: 100px; margin-top: 10px; padding: 10px; border: 1px solid black; border-radius: 5px; resize: none; }
        .button { display: inline-block; padding: 8px 15px; border: none; background: black; color: white; font-size: 14px; cursor: pointer; border-radius: 5px; transition: 0.3s; margin-top: 15px; }
        .button:hover { background: #333; }
    </style>
</head>
<body>

    <div class="navbar">
        <span>Labor Finder</span>
        <a href="profile.php?user_id=<?php echo $user_id; ?>" class="profile-icon">👤</a>
    </div>

    <div class="container">
        <h2>Report User</h2>
        <?php if (isset($error_message)): ?>
            <p style="color: red;"><?php echo htmlspecialchars($error_message); ?></p>
        <?php elseif (isset($success_message)): ?>
            <p style="color: green;"><?php echo htmlspecialchars($success_message); ?></p>
        <?php endif; ?>
        
        <form method="post">
            <div>
                <label><input type="checkbox" name="report_reason[]" value="false_information"> False Information</label>
            </div>
            <div>
                <label><input type="checkbox" name="report_reason[]" value="nudity"> Nudity</label>
            </div>
            <div>
                <label><input type="checkbox" name="report_reason[]" value="harassment"> Harassment</label>
            </div>
            <div>
                <label><input type="checkbox" name="report_reason[]" value="spam"> Spam</label>
            </div>
            <div>
                <label><input type="checkbox" name="report_reason[]" value="hate_speech"> Hate Speech</label>
            </div>
            <div>
                <label><input type="checkbox" name="report_reason[]" value="scam"> Scam/Fraud</label>
            </div>
            <div>
                <label><input type="checkbox" name="report_reason[]" value="other"> Other</label>
            </div>
            <div>
                <label for="additional_details">Additional Details (optional):</label>
                <textarea name="additional_details" id="additional_details" rows="4" cols="50"></textarea>
            </div>
            <button type="submit" class="button">Submit Report</button>
        </form>
    </div>

</body>
</html>
