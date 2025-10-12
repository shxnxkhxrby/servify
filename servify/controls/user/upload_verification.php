<?php
session_start();
include '../connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $upload_dir = '../../uploads/';
    $allowed_types = ['jpg', 'jpeg', 'png', 'pdf'];
    $max_size = 5 * 1024 * 1024; // 5MB

    $id_proof = $_FILES['id_proof'];
    $supporting_doc = $_FILES['supporting_doc'];

    $errors = [];

    // Validate ID Proof
    $id_ext = strtolower(pathinfo($id_proof['name'], PATHINFO_EXTENSION));
    if (!in_array($id_ext, $allowed_types) || $id_proof['size'] > $max_size) {
        $errors[] = "Invalid ID Proof file type or size.";
    }

    // Validate Supporting Doc
    $supp_ext = strtolower(pathinfo($supporting_doc['name'], PATHINFO_EXTENSION));
    if (!in_array($supp_ext, $allowed_types) || $supporting_doc['size'] > $max_size) {
        $errors[] = "Invalid Supporting Document file type or size.";
    }

    if (empty($errors)) {
        $id_filename = time() . "_id_" . basename($id_proof['name']);
        $supp_filename = time() . "_proof_" . basename($supporting_doc['name']);

        $id_path = $upload_dir . $id_filename;
        $supp_path = $upload_dir . $supp_filename;

        if (move_uploaded_file($id_proof['tmp_name'], $id_path) &&
            move_uploaded_file($supporting_doc['tmp_name'], $supp_path)) {

            // Save to DB
            $stmt = $conn->prepare("INSERT INTO verification_requests (user_id, id_proof, supporting_doc, status) VALUES (?, ?, ?, 'pending')");
            $stmt->bind_param("iss", $user_id, $id_filename, $supp_filename);

            if ($stmt->execute()) {
                $_SESSION['verification_message'] = "✅ Verification request submitted successfully.";
            } else {
                $_SESSION['verification_message'] = "❌ Database error. Please try again.";
            }

            $stmt->close();
        } else {
            $_SESSION['verification_message'] = "❌ Failed to upload files.";
        }
    } else {
        $_SESSION['verification_message'] = implode('<br>', $errors);
    }

    $conn->close();
    header("Location: ../../view/profile.php");
    exit();
}
?>
