<?php
session_start();
include '../controls/connection.php';

if (!isset($_SESSION['user_id'])) exit;

$sender_id = $_SESSION['user_id'];
$receiver_id = isset($_POST['receiver_id']) ? intval($_POST['receiver_id']) : 0;
$message = trim($_POST['message']);

if ($receiver_id > 0 && !empty($message)) {
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $sender_id, $receiver_id, $message);
    $stmt->execute();
    $stmt->close();
}
