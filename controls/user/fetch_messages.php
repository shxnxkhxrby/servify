<?php
include 'db.php';

$user_id = $_GET['user_id'];
$chat_with = $_GET['chat_with'];

$stmt = $conn->prepare("
    SELECT * FROM messages 
    WHERE (sender_id = ? AND receiver_id = ?) 
       OR (sender_id = ? AND receiver_id = ?) 
    ORDER BY created_at ASC
");
$stmt->bind_param("iiii", $user_id, $chat_with, $chat_with, $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $sender = ($row['sender_id'] == $user_id) ? "You" : "Them";
    echo "<p><b>$sender:</b> " . htmlspecialchars($row['message']) . "</p>";
}
?>
