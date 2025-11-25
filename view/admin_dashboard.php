<?php 
include '../controls/connection.php';

// --- HANDLE DELETE LABORER ---
if (isset($_GET['delete_laborer'])) {
    $user_id = $_GET['delete_laborer'];
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_dashboard.php");
    exit();
}

// --- HANDLE DELETE LOCATION ---
if (isset($_GET['delete_location'])) {
    $location_id = $_GET['delete_location'];
    $stmt = $conn->prepare("DELETE FROM locations WHERE location_id = ?");
    $stmt->bind_param("i", $location_id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_dashboard.php");
    exit();
}

// --- HANDLE ADD LOCATION ---
$default_barangay = "Sta. Rita";
$default_city = "Guiguinto";
$default_province = "Bulacan";

if (isset($_POST['add_location'])) {
    $location_name = $_POST['location_name'];
    $stmt = $conn->prepare("INSERT INTO locations (location_name, barangay, city, province) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $location_name, $default_barangay, $default_city, $default_province);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_dashboard.php");
    exit();
}

// --- HANDLE EDIT LOCATION ---
if (isset($_POST['edit_location'])) {
    $location_id = $_POST['location_id'];
    $location_name = $_POST['location_name'];
    $stmt = $conn->prepare("UPDATE locations SET location_name = ? WHERE location_id = ?");
    $stmt->bind_param("si", $location_name, $location_id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_dashboard.php");
    exit();
}

// --- HANDLE ADD USER ---
if (isset($_POST['add_user'])) {
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    $contact = $_POST['contact'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'] ?? 'laborer';
    $is_verified = $_POST['is_verified'] ?? 0;

    $stmt = $conn->prepare("INSERT INTO users (firstname, lastname, email, contact, password, role, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssi", $firstname, $lastname, $email, $contact, $password, $role, $is_verified);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_dashboard.php");
    exit();
}

// --- HANDLE EDIT USER ---
if (isset($_POST['edit_user'])) {
    $user_id = $_POST['user_id'];
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    $contact = $_POST['contact'];
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'];
    $is_verified = $_POST['is_verified'];

    if (!empty($password)) {
        $password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET firstname=?, lastname=?, email=?, contact=?, password=?, role=?, is_verified=? WHERE user_id=?");
        $stmt->bind_param("ssssssii", $firstname, $lastname, $email, $contact, $password, $role, $is_verified, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET firstname=?, lastname=?, email=?, contact=?, role=?, is_verified=? WHERE user_id=?");
        $stmt->bind_param("sssssii", $firstname, $lastname, $email, $contact, $role, $is_verified, $user_id);
    }

    $stmt->execute();
    $stmt->close();
    header("Location: admin_dashboard.php");
    exit();
}

// --- PAGINATION ---
$limit_options = [10, 25, 50, 100];
$limit = isset($_GET['limit']) && in_array($_GET['limit'], $limit_options) ? $_GET['limit'] : 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$start = ($page - 1) * $limit;

// --- FETCH TOTALS ---
$total_users = $conn->query("SELECT COUNT(*) AS total_users FROM users")->fetch_assoc()['total_users'] ?? 0;
$total_locations = $conn->query("SELECT COUNT(*) AS total_locations FROM locations")->fetch_assoc()['total_locations'] ?? 0;

// --- FETCH USERS ---
$laborers_result = $conn->query("SELECT * FROM users ORDER BY user_id ASC LIMIT $start, $limit");

// --- FETCH LOCATIONS ---
$locations_result = $conn->query("SELECT * FROM locations");
$total_pages = ceil($total_users / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
<div class="flex h-screen">

<!-- Sidebar -->
<aside class="w-64 bg-teal-800 text-white p-5 flex flex-col">
  <h1 class="text-2xl font-bold mb-6">Admin Panel</h1>
  <nav class="flex-1">
    <ul class="space-y-2">
      <li><button onclick="showSection('users')" class="w-full text-left p-2 rounded hover:bg-teal-700">Users Management</button></li>
      <li><button onclick="showSection('locations')" class="w-full text-left p-2 rounded hover:bg-teal-700">Locations Management</button></li>
      <li><a href="../controls/logout.php" class="w-full block p-2 rounded hover:bg-teal-700">Logout</a></li>
    </ul>
  </nav>
</aside>

<!-- Main Content -->
<main class="flex-1 p-6 overflow-auto">

<!-- Users Section -->
<div id="users" class="section hidden">
<h2 class="text-3xl font-semibold mb-6">Users Management</h2>

<!-- Add User Form -->
<button id="toggle-add-user" class="px-4 py-2 mb-4 border border-gray-400 rounded">Add New User</button>
<div id="add-user-form" class="mb-4 hidden">
<form method="POST" class="bg-gray-100 p-4 rounded grid grid-cols-1 md:grid-cols-2 gap-4">
<input type="text" name="firstname" placeholder="First Name" class="p-2 border rounded w-full" required>
<input type="text" name="lastname" placeholder="Last Name" class="p-2 border rounded w-full" required>
<input type="email" name="email" placeholder="Email" class="p-2 border rounded w-full" required>
<input type="text" name="contact" placeholder="Contact" class="p-2 border rounded w-full" required>
<input type="password" name="password" placeholder="Password" class="p-2 border rounded w-full">
<select name="role" class="p-2 border rounded w-full">
<option value="admin">Admin</option>
<option value="staff">Barangay Staff</option>
<option value="laborer" selected>Laborer</option>
<option value="user">User</option>
</select>
<select name="is_verified" class="p-2 border rounded w-full">
<option value="0">Not Verified</option>
<option value="1">Verified</option>
</select>
<button type="submit" name="add_user" class="bg-green-500 text-white px-4 py-2 rounded mt-2 col-span-2">Save</button>
</form>
</div>

<!-- Users Table -->
<table class="w-full border-collapse border border-gray-200 mb-6">
<thead>
<tr class="bg-gray-100">
<th class="border p-2">ID</th>
<th class="border p-2">First Name</th>
<th class="border p-2">Last Name</th>
<th class="border p-2">Email</th>
<th class="border p-2">Contact</th>
<th class="border p-2">Password</th>
<th class="border p-2">Role</th>
<th class="border p-2">Verified</th>
<th class="border p-2">Actions</th>
</tr>
</thead>
<tbody>
<?php while($row = $laborers_result->fetch_assoc()): ?>
<tr class="border">
<form method="POST">
<td class="border p-2"><?php echo $row['user_id']; ?><input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>"></td>
<td class="border p-2"><input type="text" name="firstname" value="<?php echo $row['firstname']; ?>" class="p-1 border rounded w-full"></td>
<td class="border p-2"><input type="text" name="lastname" value="<?php echo $row['lastname']; ?>" class="p-1 border rounded w-full"></td>
<td class="border p-2"><input type="email" name="email" value="<?php echo $row['email']; ?>" class="p-1 border rounded w-full"></td>
<td class="border p-2"><input type="text" name="contact" value="<?php echo $row['contact']; ?>" class="p-1 border rounded w-full"></td>
<td class="border p-2"><input type="password" name="password" placeholder="New Password" class="p-1 border rounded w-full"></td>
<td class="border p-2">
<select name="role" class="p-1 border rounded w-full">
<option value="admin" <?php echo $row['role']=='admin'?'selected':''; ?>>Admin</option>
<option value="staff" <?php echo $row['role']=='staff'?'selected':''; ?>>Barangay Staff</option>
<option value="laborer" <?php echo $row['role']=='laborer'?'selected':''; ?>>Laborer</option>
<option value="user" <?php echo $row['role']=='user'?'selected':''; ?>>User</option>
</select>
</td>
<td class="border p-2">
<select name="is_verified" class="p-1 border rounded w-full">
<option value="0" <?php echo $row['is_verified']==0?'selected':''; ?>>No</option>
<option value="1" <?php echo $row['is_verified']==1?'selected':''; ?>>Yes</option>
</select>
</td>
<td class="border p-2 whitespace-nowrap">
<button type="submit" name="edit_user" class="bg-blue-500 text-white px-2 py-1 rounded mr-1">Save</button>
<a href="?delete_laborer=<?php echo $row['user_id']; ?>" class="bg-red-500 text-white px-2 py-1 rounded" onclick="return confirm('Delete this user?');">Delete</a>
</td>
</form>
</tr>
<?php endwhile; ?>
</tbody>
</table>

<!-- Pagination -->
<div class="flex justify-between items-center mb-6">
<div>
<?php if($page>1): ?>
<a href="?page=<?php echo $page-1; ?>&limit=<?php echo $limit; ?>" class="px-3 py-1 border rounded">Prev</a>
<?php endif; ?>
<?php if($page<$total_pages): ?>
<a href="?page=<?php echo $page+1; ?>&limit=<?php echo $limit; ?>" class="px-3 py-1 border rounded">Next</a>
<?php endif; ?>
</div>
<div>
<form method="GET" class="flex items-center">
<label class="mr-2">Rows:</label>
<select name="limit" onchange="this.form.submit()" class="p-1 border rounded">
<?php foreach($limit_options as $opt): ?>
<option value="<?php echo $opt; ?>" <?php echo $limit==$opt?'selected':''; ?>><?php echo $opt; ?></option>
<?php endforeach; ?>
</select>
</form>
</div>
</div>
</div>

<!-- Locations Section -->
<div id="locations" class="section hidden">
<h2 class="text-3xl font-semibold mb-6">Locations Management</h2>

<!-- Add Location Form -->
<button id="toggle-add-location" class="px-4 py-2 mb-4 border border-gray-400 rounded">Add New Location</button>
<div id="add-location-form" class="mb-4 hidden">
<form method="POST" class="bg-gray-100 p-4 rounded grid grid-cols-1 md:grid-cols-2 gap-4">
<input type="text" name="location_name" placeholder="Location Name" class="p-2 border rounded w-full" required>
<button type="submit" name="add_location" class="bg-green-500 text-white px-4 py-2 rounded mt-2 col-span-2">Save</button>
</form>
</div>

<!-- Locations Table -->
<table class="w-full border-collapse border border-gray-200 mb-6">
<thead>
<tr class="bg-gray-100">
<th class="border p-2">ID</th>
<th class="border p-2">Location Name</th>
<th class="border p-2">Barangay</th>
<th class="border p-2">City</th>
<th class="border p-2">Province</th>
<th class="border p-2">Actions</th>
</tr>
</thead>
<tbody>
<?php while($loc = $locations_result->fetch_assoc()): ?>
<tr class="border">
<form method="POST">
<td class="border p-2"><?php echo $loc['location_id']; ?><input type="hidden" name="location_id" value="<?php echo $loc['location_id']; ?>"></td>
<td class="border p-2"><input type="text" name="location_name" value="<?php echo $loc['location_name']; ?>" class="p-1 border rounded w-full"></td>
<td class="border p-2"><?php echo $loc['barangay']; ?></td>
<td class="border p-2"><?php echo $loc['city']; ?></td>
<td class="border p-2"><?php echo $loc['province']; ?></td>
<td class="border p-2 whitespace-nowrap">
<button type="submit" name="edit_location" class="bg-blue-500 text-white px-2 py-1 rounded mr-1">Save</button>
<a href="?delete_location=<?php echo $loc['location_id']; ?>" class="bg-red-500 text-white px-2 py-1 rounded" onclick="return confirm('Delete this location?');">Delete</a>
</td>
</form>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>

</main>
</div>

<script>
function showSection(id) {
    document.querySelectorAll('.section').forEach(s => s.classList.add('hidden'));
    document.getElementById(id).classList.remove('hidden');
}

// Add/Remove hidden for add forms
document.getElementById('toggle-add-user').addEventListener('click', () => {
    document.getElementById('add-user-form').classList.toggle('hidden');
});
document.getElementById('toggle-add-location').addEventListener('click', () => {
    document.getElementById('add-location-form').classList.toggle('hidden');
});

// Show default section
showSection('users');
</script>
</body>
</html>
