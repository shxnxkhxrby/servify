<?php 
include '../controls/connection.php';

/*session_start();
if ($_SESSION['role'] != 'barangay_staff') {
    header("Location: index.php");
    exit();
}*/

// Fetch pending verification requests
$verification_query = "SELECT v.request_id, v.user_id, v.id_proof, v.supporting_doc, v.status, u.firstname, u.lastname 
                       FROM verification_requests v 
                       JOIN users u ON v.user_id = u.user_id
                       WHERE v.status = 'pending'";
$verification_result = $conn->query($verification_query);

// Fetch pending reports
$report_query = "SELECT r.report_id, r.user_id, r.reason, r.additional_details, r.status, u.firstname, u.lastname 
                 FROM reports r 
                 JOIN users u ON r.user_id = u.user_id 
                 WHERE r.status = 'pending'";
$report_result = $conn->query($report_query);

// Fetch accepted hires
$hires_query = "SELECT h.*, 
                       e.firstname AS employer_firstname, e.middlename AS employer_middlename, e.lastname AS employer_lastname,
                       l.firstname AS laborer_firstname, l.middlename AS laborer_middlename, l.lastname AS laborer_lastname
                FROM hires h
                JOIN users e ON h.employer_id = e.user_id
                JOIN users l ON h.laborer_id = l.user_id
                WHERE h.status='accepted'
                ORDER BY h.created_at DESC";
$hires_result = $conn->query($hires_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Staff Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <aside class="w-64 text-white p-5" style="background-color: #035B69;">
            <h1 class="text-2xl font-bold mb-6">Barangay Panel</h1>
            <nav>
                <ul>
                    <li class="mb-4"><a href="barangay_staff_dashboard.php" class="block p-2 hover:bg-green-700 rounded">Dashboard</a></li>
                    <li><a href="../controls/logout.php" class="block p-2 hover:bg-green-700 rounded">Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main class="flex-1 p-6">
            <h2 class="text-3xl font-semibold mb-6">Barangay Staff Dashboard</h2>

            <!-- Verification Applications Section -->
            <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                <h3 class="text-2xl font-semibold mb-4">Verification Applications</h3>
                <table class="w-full border-collapse border border-gray-200">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border p-2">User ID</th>
                            <th class="border p-2">Name</th>
                            <th class="border p-2">ID Proof</th>
                            <th class="border p-2">Supporting Document</th>
                            <th class="border p-2">Status</th>
                            <th class="border p-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $verification_result->fetch_assoc()): ?>
                            <tr class="border">
                                <td class="border p-2"><?php echo $row['user_id']; ?></td>
                                <td class="border p-2"><?php echo $row['firstname'] . " " . $row['lastname']; ?></td>
                                <td class="border p-2"><a href="../uploads/<?php echo $row['id_proof']; ?>" target="_blank">View ID</a></td>
                                <td class="border p-2"><a href="../uploads/<?php echo $row['supporting_doc']; ?>" target="_blank">View Document</a></td>
                                <td class="border p-2"><?php echo ucfirst($row['status']); ?></td>
                                <td class="border p-2">
                                    <a href="view_user.php?user_id=<?php echo $row['user_id']; ?>" class="text-blue-500">View Profile</a> | 
                                    <a href="../controls/admin/approve_verification.php?request_id=<?php echo $row['request_id']; ?>" class="text-green-500">Approve</a> | 
                                    <a href="../controls/admin/reject_verification.php?request_id=<?php echo $row['request_id']; ?>" class="text-red-500">Reject</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Reports Section -->
            <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                <h3 class="text-2xl font-semibold mb-4">Pending Reports</h3>
                <table class="w-full border-collapse border border-gray-200">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border p-2">Report ID</th>
                            <th class="border p-2">User ID</th>
                            <th class="border p-2">Name</th>
                            <th class="border p-2">Reason</th>
                            <th class="border p-2">Details</th>
                            <th class="border p-2">Status</th>
                            <th class="border p-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $report_result->fetch_assoc()): ?>
                            <tr class="border">
                                <td class="border p-2"><?php echo $row['report_id']; ?></td>
                                <td class="border p-2"><?php echo $row['user_id']; ?></td>
                                <td class="border p-2"><?php echo $row['firstname'] . " " . $row['lastname']; ?></td>
                                <td class="border p-2"><?php echo ucfirst($row['reason']); ?></td>
                                <td class="border p-2"><?php echo $row['additional_details']; ?></td>
                                <td class="border p-2"><?php echo ucfirst($row['status']); ?></td>
                                <td class="border p-2">
                                    <a href="view_user.php?user_id=<?php echo $row['user_id']; ?>" class="text-blue-500">View Profile</a>  
                                    <a href="?confirm=<?php echo $row['report_id']; ?>&user_id=<?php echo $row['user_id']; ?>&reason=<?php echo $row['reason']; ?>" class="text-green-500">Confirm</a> | 
                                    <a href="?reject=<?php echo $row['report_id']; ?>" class="text-red-500" onclick="return confirm('Are you sure you want to reject this report?')">Reject</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>    
                </table>
            </div>

            <!-- Accepted Hires Section -->
            <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                <h3 class="text-2xl font-semibold mb-4">Accepted Hires</h3>
                <table class="w-full border-collapse border border-gray-200">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border p-2">Employer</th>
                            <th class="border p-2">Laborer</th>
                            <th class="border p-2">Message</th>
                            <th class="border p-2">Meeting Location</th>
                            <th class="border p-2">Status</th>
                            <th class="border p-2">Date/Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($hire = $hires_result->fetch_assoc()): ?>
                            <tr class="border">
                                <td class="border p-2"><?php echo htmlspecialchars($hire['employer_firstname'] . " " . $hire['employer_middlename'] . " " . $hire['employer_lastname']); ?></td>
                                <td class="border p-2"><?php echo htmlspecialchars($hire['laborer_firstname'] . " " . $hire['laborer_middlename'] . " " . $hire['laborer_lastname']); ?></td>
                                <td class="border p-2"><?php echo htmlspecialchars($hire['message']); ?></td>
                                <td class="border p-2"><?php echo htmlspecialchars($hire['meeting_location']); ?></td>
                                <td class="border p-2"><?php echo ucfirst($hire['status']); ?></td>
                                <td class="border p-2"><?php echo date('F j, Y, g:i A', strtotime($hire['created_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Announcements Section -->
<div class="bg-white p-6 rounded-lg shadow-md mb-6">
    <h3 class="text-2xl font-semibold mb-4">Barangay Announcements</h3>

    <?php
    // Handle Add Announcement
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_announcement'])) {
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $image_path = null;

        if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = "../uploads/announcements/";
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);

            $file_tmp = $_FILES['image']['tmp_name'];
            $file_name = time() . "_" . basename($_FILES['image']['name']);
            $file_path = $upload_dir . $file_name;

            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                move_uploaded_file($file_tmp, $file_path);
                $image_path = "../uploads/announcements/" . $file_name;
            }
        }

        if (!empty($title) && !empty($content)) {
            $stmt = $conn->prepare("INSERT INTO barangay_announcements (title, content, image_path) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $title, $content, $image_path);
            $stmt->execute();
            echo "<p class='text-green-600 font-medium mb-3'>✅ Announcement added successfully!</p>";
        }
    }

    // Handle Delete
    if (isset($_POST['delete_announcement'])) {
        $id = intval($_POST['announcement_id']);
        $res = $conn->query("SELECT image_path FROM barangay_announcements WHERE announcement_id=$id");
        if ($row = $res->fetch_assoc()) {
            if (!empty($row['image_path']) && file_exists("../" . $row['image_path'])) {
                unlink("../" . $row['image_path']);
            }
        }
        $conn->query("DELETE FROM barangay_announcements WHERE announcement_id=$id");
        echo "<p class='text-red-600 font-medium mb-3'>🗑️ Announcement deleted.</p>";
    }

    // Handle Edit
    if (isset($_POST['edit_announcement'])) {
        $id = intval($_POST['announcement_id']);
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $image_path = $_POST['existing_image'];

        if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = "../uploads/announcements/";
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);

            $file_tmp = $_FILES['image']['tmp_name'];
            $file_name = time() . "_" . basename($_FILES['image']['name']);
            $file_path = $upload_dir . $file_name;

            move_uploaded_file($file_tmp, $file_path);
            $image_path = "../uploads/announcements/" . $file_name;
        }

        $stmt = $conn->prepare("UPDATE barangay_announcements SET title=?, content=?, image_path=? WHERE announcement_id=?");
        $stmt->bind_param("sssi", $title, $content, $image_path, $id);
        $stmt->execute();
        echo "<p class='text-blue-600 font-medium mb-3'>✏️ Announcement updated successfully!</p>";
    }

    // Fetch announcements
    $ann_query = "SELECT * FROM barangay_announcements ORDER BY date_posted DESC";
    $ann_result = $conn->query($ann_query);
    ?>

    <!-- Add New Announcement -->
    <form method="POST" enctype="multipart/form-data" class="mb-6 bg-gray-50 p-4 rounded-lg">
        <h4 class="font-semibold text-lg mb-3">Add New Announcement</h4>
        <div class="mb-3">
            <label class="block font-medium mb-1">Title</label>
            <input type="text" name="title" required class="w-full p-2 border border-gray-300 rounded">
        </div>
        <div class="mb-3">
            <label class="block font-medium mb-1">Content</label>
            <textarea name="content" required rows="3" class="w-full p-2 border border-gray-300 rounded"></textarea>
        </div>
        <div class="mb-3">
            <label class="block font-medium mb-1">Image (optional)</label>
            <input type="file" name="image" accept="image/*" class="w-full p-2 border border-gray-300 rounded">
        </div>
        <button type="submit" name="add_announcement" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">Post Announcement</button>
    </form>

    <!-- List Announcements -->
    <div class="overflow-x-auto">
        <table class="w-full border-collapse border border-gray-200 text-sm">
            <thead>
                <tr class="bg-gray-100 text-left">
                    <th class="border p-2">Title</th>
                    <th class="border p-2 w-1/2">Content</th>
                    <th class="border p-2">Image</th>
                    <th class="border p-2">Date</th>
                    <th class="border p-2 text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($ann = $ann_result->fetch_assoc()): ?>
                    <tr class="border hover:bg-gray-50 transition">
                        <td class="border p-2 font-semibold"><?php echo htmlspecialchars($ann['title']); ?></td>
                        <td class="border p-2"><?php echo nl2br(htmlspecialchars($ann['content'])); ?></td>
                        <td class="border p-2 text-center">
                            <?php if (!empty($ann['image_path'])): ?>
                                <img src="<?php echo htmlspecialchars($ann['image_path']); ?>" class="w-16 h-16 object-cover rounded mx-auto">
                            <?php else: ?>
                                <span class="text-gray-400 italic">No image</span>
                            <?php endif; ?>
                        </td>
                        <td class="border p-2 text-gray-600"><?php echo date('F j, Y, g:i A', strtotime($ann['date_posted'])); ?></td>
                        <td class="border p-2 text-center">
                            <!-- Edit Button (Toggles Form) -->
                            <button type="button" onclick="toggleEditForm(<?php echo $ann['announcement_id']; ?>)" class="text-blue-600 hover:text-blue-800 font-medium mr-3">Edit</button>
                            <form method="POST" class="inline">
                                <input type="hidden" name="announcement_id" value="<?php echo $ann['announcement_id']; ?>">
                                <button type="submit" name="delete_announcement" class="text-red-600 hover:text-red-800 font-medium" onclick="return confirm('Delete this announcement?');">Delete</button>
                            </form>
                        </td>
                    </tr>

                    <!-- Hidden Edit Form -->
                    <tr id="editForm<?php echo $ann['announcement_id']; ?>" class="hidden bg-gray-50">
                        <td colspan="5" class="p-4">
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="announcement_id" value="<?php echo $ann['announcement_id']; ?>">
                                <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($ann['image_path']); ?>">

                                <div class="mb-3">
                                    <label class="block font-medium mb-1">Title</label>
                                    <input type="text" name="title" value="<?php echo htmlspecialchars($ann['title']); ?>" required class="w-full p-2 border border-gray-300 rounded">
                                </div>
                                <div class="mb-3">
                                    <label class="block font-medium mb-1">Content</label>
                                    <textarea name="content" rows="3" required class="w-full p-2 border border-gray-300 rounded"><?php echo htmlspecialchars($ann['content']); ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="block font-medium mb-1">Image (optional)</label>
                                    <?php if ($ann['image_path']): ?>
                                        <img src="../<?php echo htmlspecialchars($ann['image_path']); ?>" class="w-20 h-20 object-cover rounded mb-2">
                                    <?php endif; ?>
                                    <input type="file" name="image" accept="image/*" class="w-full p-2 border border-gray-300 rounded">
                                </div>
                                <button type="submit" name="edit_announcement" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Save</button>
                                <button type="button" onclick="toggleEditForm(<?php echo $ann['announcement_id']; ?>)" class="ml-2 px-4 py-2 border rounded text-gray-600 hover:bg-gray-200">Cancel</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

        </main>
    </div>

<script>
function toggleEditForm(id) {
    const form = document.getElementById('editForm' + id);
    form.classList.toggle('hidden');
}
</script>
</body>
</html>

<?php $conn->close(); ?>
