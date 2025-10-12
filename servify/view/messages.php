<?php
session_start();
include '../controls/connection.php';

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$sender_id = $_SESSION['user_id'];
$receiver_id = isset($_GET['receiver_id']) ? intval($_GET['receiver_id']) : 0;

$is_logged_in = isset($_SESSION['user_id']);

// Fetch receiver details only if a receiver is selected
$receiver = null;
if ($receiver_id !== 0) {
    $receiver_sql = $conn->prepare("SELECT firstname, lastname FROM users WHERE user_id = ?");
    $receiver_sql->bind_param("i", $receiver_id);
    $receiver_sql->execute();
    $receiver_result = $receiver_sql->get_result();
    $receiver = $receiver_result->fetch_assoc();
    $receiver_sql->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Messages</title>
  <link rel="stylesheet" type="text/css" href="../styles/landing_page.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f8f9fa; }
    .chat-container { display: flex; max-width: 1000px; margin: 80px auto; height: 600px; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    .user-list { width: 30%; background: #fff; border-right: 1px solid #ddd; overflow-y: auto; }
    .user-item { padding: 15px; border-bottom: 1px solid #f1f1f1; cursor: pointer; }
    .user-item:hover { background: #f8f9fa; }
    .chat-box { flex: 1; display: flex; flex-direction: column; background: white; }
    .chat-header { padding: 15px; background: #007bff; color: white; }
    .chat-messages { flex: 1; padding: 15px; overflow-y: auto; display: flex; flex-direction: column; }
    
    /* Message bubbles */
    .chat-message {
      margin-bottom: 10px;
      max-width: 60%;              /* bubble width */
      padding: 10px 14px;
      border-radius: 15px;
      word-wrap: break-word;
      word-break: break-word;
      overflow-wrap: break-word;
      display: inline-block;       /* bubble shrinks to text length */
      white-space: normal;         /* clean wrapping */
    }
    .msg-sent {
      background: #007bff;
      color: white;
      align-self: flex-end;
      border-bottom-right-radius: 0;
    }
    .msg-received {
      background: #e9ecef;
      color: black;
      align-self: flex-start;
      border-bottom-left-radius: 0;
    }

    .chat-input { padding: 15px; border-top: 1px solid #ddd; }
  </style>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg fixed-top bg-white shadow-sm">
  <div class="container">
    <a class="navbar-brand" href="../view/index.php">Servify</a>

    <!-- Burger Menu -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav">
        <?php if ($is_logged_in): ?>
          <li class="nav-item">
            <a class="nav-link" href="../view/profile.php">
              <i class="bi bi-person-circle"></i> Profile
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="../controls/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
          </li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="../view/signup.php">Sign Up</a></li>
          <li class="nav-item"><a class="nav-link">|</a></li>
          <li class="nav-item"><a class="nav-link" href="../view/login.php">Login</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<div class="chat-container">
  <!-- Left: User list -->
  <div class="user-list">
    <?php
      $stmt = $conn->prepare("
        SELECT u.user_id, u.firstname, u.lastname, MAX(m.timestamp) AS last_msg_time
        FROM users u
        INNER JOIN messages m 
          ON (u.user_id = m.sender_id OR u.user_id = m.receiver_id)
        WHERE m.sender_id = ? OR m.receiver_id = ?
        GROUP BY u.user_id, u.firstname, u.lastname
        ORDER BY last_msg_time DESC
      ");
      $stmt->bind_param("ii", $sender_id, $sender_id);
      $stmt->execute();
      $users_result = $stmt->get_result();

      while ($row = $users_result->fetch_assoc()):
        if ($row['user_id'] == $sender_id) continue; // skip self
    ?>
      <div class="user-item" onclick="window.location.href='messages.php?receiver_id=<?php echo $row['user_id']; ?>'">
        <div class="fw-bold"><?php echo htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?></div>
      </div>
    <?php endwhile; $stmt->close(); ?>
  </div>

  <!-- RIGHT: Chat Box -->
  <div class="chat-box">
    <?php if ($receiver): ?>
      <div class="chat-header">
        Chat with <?php echo htmlspecialchars($receiver['firstname'] . ' ' . $receiver['lastname']); ?>
      </div>
      <div class="chat-messages" id="chat-messages"></div>
      <div class="chat-input">
        <form id="chat-form">
          <div class="input-group">
            <input type="text" name="message" id="message" class="form-control" placeholder="Type a message..." required>
            <button type="submit" class="btn btn-primary">Send</button>
          </div>
        </form>
      </div>
    <?php else: ?>
      <div class="chat-header">Select a conversation</div>
    <?php endif; ?>
  </div>
</div>

<script>
$(document).ready(function() {
    let receiver_id = <?php echo $receiver_id ?: '0'; ?>;

    function loadMessages() {
        if(receiver_id === 0) return;

        let chatBox = $("#chat-messages");
        // check if user is near bottom
        let isNearBottom = chatBox.scrollTop() + chatBox.innerHeight() >= chatBox[0].scrollHeight - 50;

        $.ajax({
            url: "../controls/load_messages.php",
            type: "GET",
            data: { receiver_id: receiver_id },
            success: function(data) {
                chatBox.html(data);
                // only auto scroll if user was near bottom
                if (isNearBottom) {
                    chatBox.scrollTop(chatBox[0].scrollHeight);
                }
            }
        });
    }

    setInterval(loadMessages, 2000);
    loadMessages();

    $("#chat-form").on("submit", function(e) {
        e.preventDefault();
        let message = $("#message").val();
        $.ajax({
            url: "../controls/send_message.php",
            type: "POST",
            data: { receiver_id: receiver_id, message: message },
            success: function() {
                $("#message").val("");
                loadMessages();
            }
        });
    });
});
</script>

</body>
</html>
