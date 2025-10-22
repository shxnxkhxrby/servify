<?php
include '../controls/connection.php';

/*session_start();
if ($_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}*/

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $location_name = $_POST['location_name'] ?? '';
    $barangay = $_POST['barangay'] ?? '';
    $city = $_POST['city'] ?? '';
    $province = $_POST['province'] ?? '';

    // Simple validation
    if(empty($location_name) || empty($barangay) || empty($city) || empty($province)) {
        die("All fields are required.");
    }

    // Prepare INSERT query
    $insert_query = "INSERT INTO locations (location_name, barangay, city, province) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("ssss", $location_name, $barangay, $city, $province);

    if($stmt->execute()) {
        $stmt->close();
        header("Location: admin_dashboard.php"); // Go back to dashboard
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
} else {
    // If accessed directly without POST
    header("Location: admin_dashboard.php");
    exit();
}
?>
