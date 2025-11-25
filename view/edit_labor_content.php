<?php
session_start();
include '../controls/connection.php';

if (!isset($_POST['job_id']) || !isset($_SESSION['user_id'])) exit();

$job_id = intval($_POST['job_id']);
$user_id = $_SESSION['user_id'];

// Fetch job info
$sql = "SELECT uj.job_description, uj.job_image, j.job_name
        FROM user_jobs uj
        INNER JOIN jobs j ON j.job_id = uj.job_id
        WHERE uj.job_id = ? AND uj.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $job_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$job = $result->fetch_assoc();
$stmt->close();

if (!$job) {
    echo "<p class='text-danger text-center'>Job not found.</p>";
    exit();
}
?>

<form action="edit_labor.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="job_id" value="<?php echo $job_id; ?>">

    <div class="mb-3">
        <label>Job Name:</label>
        <input type="text" class="form-control" value="<?php echo htmlspecialchars($job['job_name']); ?>" readonly>
    </div>

    <div class="mb-3">
        <label>Job Description:</label>
        <textarea name="new_description" class="form-control" rows="4" required><?php echo htmlspecialchars($job['job_description']); ?></textarea>
    </div>

    <div class="mb-3">
        <label>Current Image:</label><br>
        <?php if(!empty($job['job_image'])): ?>
            <img src="../uploads/<?php echo htmlspecialchars($job['job_image']); ?>" class="img-fluid rounded mb-2" style="width:120px; height:120px; object-fit:cover;">
        <?php else: ?>
            <p class="text-muted">No image</p>
        <?php endif; ?>
    </div>

    <div class="mb-3">
        <label>Change Image:</label>
        <input type="file" name="job_image" class="form-control" accept="image/*">
    </div>

    <div class="text-end">
        <button type="submit" class="btn btn-success">Save Changes</button>
    </div>
</form>
