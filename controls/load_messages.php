<?php
session_start();
include '../controls/connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['messages' => []]);
    exit;
}

$sender_id = $_SESSION['user_id'];
$receiver_id = isset($_GET['receiver_id']) ? intval($_GET['receiver_id']) : 0;
$last_message_id = isset($_GET['last_message_id']) ? intval($_GET['last_message_id']) : 0;

$sql = "SELECT m.message_id, m.sender_id, m.message, m.timestamp, u.firstname, u.lastname 
        FROM messages m
        JOIN users u ON m.sender_id = u.user_id
        WHERE ((m.sender_id = ? AND m.receiver_id = ?) 
           OR (m.sender_id = ? AND m.receiver_id = ?))
        AND m.message_id > ?
        ORDER BY m.timestamp ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiiii", $sender_id, $receiver_id, $receiver_id, $sender_id, $last_message_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = [
        'id' => $row['message_id'],
        'sender_id' => $row['sender_id'],
        'message' => htmlspecialchars($row['message']),
        'firstname' => htmlspecialchars($row['firstname']),
        'lastname' => htmlspecialchars($row['lastname']),
        'time' => date('g:i A', strtotime($row['timestamp'])),
        'is_sent' => ($row['sender_id'] == $sender_id)
    ];
}

$stmt->close();
echo json_encode(['messages' => $messages]);
?>