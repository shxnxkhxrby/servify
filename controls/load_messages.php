<?php
session_start();
include '../controls/connection.php';

if (!isset($_SESSION['user_id'])) exit;

$sender_id = $_SESSION['user_id'];
$receiver_id = isset($_GET['receiver_id']) ? intval($_GET['receiver_id']) : 0;

$sql = "SELECT m.*, u.firstname, u.lastname 
        FROM messages m
        JOIN users u ON m.sender_id = u.user_id
        WHERE (m.sender_id = ? AND m.receiver_id = ?) 
           OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.timestamp ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiii", $sender_id, $receiver_id, $receiver_id, $sender_id);
$stmt->execute();
$messages = $stmt->get_result();
$stmt->close();

while ($row = $messages->fetch_assoc()):
    if ($row['sender_id'] == $sender_id): ?>
        <div class="chat-message msg-sent">
            <?php echo htmlspecialchars($row['message']); ?>
            <div><small><?php echo $row['timestamp']; ?></small></div>
        </div>
    <?php else: ?>
        <div class="chat-message msg-received">
            <span class="sender"><?php echo htmlspecialchars($row['firstname']); ?>:</span>
            <?php echo htmlspecialchars($row['message']); ?>
            <div><small><?php echo $row['timestamp']; ?></small></div>
        </div>
    <?php endif;
endwhile;
