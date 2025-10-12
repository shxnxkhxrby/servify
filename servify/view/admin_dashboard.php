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
    $rating = $_POST['rating'] ?? 0;
    $is_verified = $_POST['is_verified'] ?? 0;

    $stmt = $conn->prepare("INSERT INTO users (firstname, lastname, email, contact, password, role, rating, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssii", $firstname, $lastname, $email, $contact, $password, $role, $rating, $is_verified);
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
    $password = $_POST['password'] ?? ''; // optional
    $role = $_POST['role'];
    $rating = $_POST['rating'];
    $is_verified = $_POST['is_verified'];

    if (!empty($password)) {
        $password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET firstname=?, lastname=?, email=?, contact=?, password=?, role=?, rating=?, is_verified=? WHERE user_id=?");
        $stmt->bind_param("ssssssiii", $firstname, $lastname, $email, $contact, $password, $role, $rating, $is_verified, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET firstname=?, lastname=?, email=?, contact=?, role=?, rating=?, is_verified=? WHERE user_id=?");
        $stmt->bind_param("sssssiii", $firstname, $lastname, $email, $contact, $role, $rating, $is_verified, $user_id);
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
<aside class="w-64 text-white p-5" style="background-color: #027B8C;">
<h1 class="text-2xl font-bold mb-6">Admin Panel</h1>
<nav>
<ul>
<li><a href="../controls/logout.php" class="block p-2 hover:bg-blue-700 rounded">Logout</a></li>
</ul>
</nav>
</aside>

<main class="flex-1 p-6 overflow-auto">
<h2 class="text-3xl font-semibold mb-6">Admin Dashboard</h2>

<!-- Stats -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
<a href="#laborers-table" class="block">
<div class="bg-white p-6 rounded-lg shadow-md hover:bg-gray-100 cursor-pointer">
<h3 class="text-xl font-semibold">Total Users</h3>
<p class="text-2xl font-bold"><?php echo $total_users; ?></p>
</div>
</a>
<a href="#locations-table" class="block">
<div class="bg-white p-6 rounded-lg shadow-md hover:bg-gray-100 cursor-pointer">
<h3 class="text-xl font-semibold">Total Locations</h3>
<p class="text-2xl font-bold"><?php echo $total_locations; ?></p>
</div>
</a>
</div>

<!-- Add User Form -->
<button id="toggle-add-user" class="bg-blue-500 text-white px-4 py-2 rounded mb-4">Add New User</button>
<div id="add-user-form" class="mb-4 overflow-hidden max-h-0 transition-all duration-500">
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
<input type="number" name="rating" placeholder="Rating" class="p-2 border rounded w-full" value="0">
<select name="is_verified" class="p-2 border rounded w-full">
<option value="0">Not Verified</option>
<option value="1">Verified</option>
</select>
<button type="submit" name="add_user" class="bg-green-500 text-white px-4 py-2 rounded mt-2 col-span-2">Save</button>
</form>
</div>

<!-- Users Table -->
<div id="laborers-table" class="bg-white p-6 rounded-lg shadow-md mb-6">
<h3 class="text-2xl font-semibold mb-4">Users Management</h3>
<form method="GET" class="mb-2">
<label>Show 
<select name="limit" onchange="this.form.submit()">
<?php foreach ($limit_options as $option): ?>
<option value="<?php echo $option; ?>" <?php echo $limit==$option?'selected':''; ?>><?php echo $option; ?></option>
<?php endforeach; ?>
</select>
</label>
<input type="hidden" name="page" value="1">
</form>

<table class="w-full border-collapse border border-gray-200">
<thead>
<tr class="bg-gray-100">
<th class="border p-2">ID</th>
<th class="border p-2">First Name</th>
<th class="border p-2">Last Name</th>
<th class="border p-2">Email</th>
<th class="border p-2">Contact</th>
<th class="border p-2">Password</th>
<th class="border p-2">Role</th>
<th class="border p-2">Rating</th>
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
<td class="border p-2"><input type="password" name="password" placeholder="Leave blank to keep" class="p-1 border rounded w-full"></td>
<td class="border p-2">
<select name="role" class="p-1 border rounded w-full">
<option value="admin" <?php echo $row['role']=='admin'?'selected':''; ?>>Admin</option>
<option value="staff" <?php echo $row['role']=='staff'?'selected':''; ?>>Barangay Staff</option>
<option value="laborer" <?php echo $row['role']=='laborer'?'selected':''; ?>>Laborer</option>
<option value="user" <?php echo $row['role']=='user'?'selected':''; ?>>User</option>
</select>
</td>
<td class="border p-2"><input type="number" name="rating" value="<?php echo $row['rating']; ?>" class="p-1 border rounded w-full"></td>
<td class="border p-2">
<select name="is_verified" class="p-1 border rounded w-full">
<option value="0" <?php echo $row['is_verified']==0?'selected':''; ?>>No</option>
<option value="1" <?php echo $row['is_verified']==1?'selected':''; ?>>Yes</option>
</select>
</td>
<td class="border p-2 flex gap-2">
<button type="submit" name="edit_user" class="text-blue-500">Save</button>
<a href="?delete_laborer=<?php echo $row['user_id']; ?>" class="text-red-500" onclick="return confirm('Are you sure?')">Delete</a>
</td>
</form>
</tr>
<?php endwhile; ?>
</tbody>
</table>

<!-- Pagination -->
<div class="mt-2 flex gap-2">
<?php if($page > 1): ?>
<a href="?page=<?php echo $page-1; ?>&limit=<?php echo $limit; ?>" class="px-3 py-1 bg-gray-200 rounded">Prev</a>
<?php endif; ?>
<?php for($p=1;$p<=$total_pages;$p++): ?>
<a href="?page=<?php echo $p; ?>&limit=<?php echo $limit; ?>" class="px-3 py-1 <?php echo $p==$page?'bg-blue-500 text-white rounded':'bg-gray-200 rounded'; ?>"><?php echo $p; ?></a>
<?php endfor; ?>
<?php if($page < $total_pages): ?>
<a href="?page=<?php echo $page+1; ?>&limit=<?php echo $limit; ?>" class="px-3 py-1 bg-gray-200 rounded">Next</a>
<?php endif; ?>
</div>

</div>

<!-- Locations Table (unchanged) -->
<div id="locations-table" class="bg-white p-6 rounded-lg shadow-md">
<h3 class="text-2xl font-semibold mb-4">Locations Management</h3>

<button id="toggle-add-location" class="bg-blue-500 text-white px-4 py-2 rounded mb-4">Add New Location</button>
<div id="add-location-form" class="mb-4 overflow-hidden max-h-0 transition-all duration-500">
<form method="POST" class="bg-gray-100 p-4 rounded grid grid-cols-1 md:grid-cols-2 gap-4">
<input type="text" name="location_name" placeholder="Location Name" class="p-2 border rounded w-full" required>
<input type="text" name="barangay" value="<?php echo $default_barangay; ?>" readonly class="p-2 border rounded w-full">
<input type="text" name="city" value="<?php echo $default_city; ?>" readonly class="p-2 border rounded w-full">
<input type="text" name="province" value="<?php echo $default_province; ?>" readonly class="p-2 border rounded w-full">
<button type="submit" name="add_location" class="bg-green-500 text-white px-4 py-2 rounded mt-2 col-span-2">Save</button>
</form>
</div>

<table class="w-full border-collapse border border-gray-200">
<thead>
<tr class="bg-gray-100">
<th class="border p-2">Location ID</th>
<th class="border p-2">Location Name</th>
<th class="border p-2">Barangay</th>
<th class="border p-2">City</th>
<th class="border p-2">Province</th>
<th class="border p-2">Created At</th>
<th class="border p-2">Actions</th>
</tr>
</thead>
<tbody>
<?php while($row = $locations_result->fetch_assoc()): ?>
<tr class="border">
<form method="POST">
<td class="border p-2"><?php echo $row['location_id']; ?><input type="hidden" name="location_id" value="<?php echo $row['location_id']; ?>"></td>
<td class="border p-2"><input type="text" name="location_name" value="<?php echo $row['location_name']; ?>" class="p-1 border rounded w-full"></td>
<td class="border p-2"><?php echo $row['barangay']; ?></td>
<td class="border p-2"><?php echo $row['city']; ?></td>
<td class="border p-2"><?php echo $row['province']; ?></td>
<td class="border p-2"><?php echo $row['created_at']; ?></td>
<td class="border p-2 flex gap-2">
<button type="submit" name="edit_location" class="text-blue-500">Save</button>
<a href="?delete_location=<?php echo $row['location_id']; ?>" class="text-red-500" onclick="return confirm('Are you sure?')">Delete</a>
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
const toggleAddUser = document.getElementById('toggle-add-user');
const addUserForm = document.getElementById('add-user-form');
toggleAddUser.addEventListener('click', () => {
addUserForm.style.maxHeight = addUserForm.style.maxHeight && addUserForm.style.maxHeight !== '0px' ? '0px' : addUserForm.scrollHeight + 'px';
});

const toggleAddLocation = document.getElementById('toggle-add-location');
const addLocationForm = document.getElementById('add-location-form');
toggleAddLocation.addEventListener('click', () => {
addLocationForm.style.maxHeight = addLocationForm.style.maxHeight && addLocationForm.style.maxHeight !== '0px' ? '0px' : addLocationForm.scrollHeight + 'px';
});
</script>

</body>
</html>

<?php $conn->close(); ?>
