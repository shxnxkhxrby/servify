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

// Check login status
$is_logged_in = isset($_SESSION['user_id']);
$current_user_id = $is_logged_in ? intval($_SESSION['user_id']) : null;

// Fetch logged-in user's name and profile picture
$current_user_name = '';
$profile_picture = 'uploads/profile_pics/default.jpg'; // Default fallback

if ($is_logged_in && $current_user_id) {
    $stmt = $conn->prepare("SELECT firstname, middlename, lastname, profile_picture FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $current_user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $user_row = $res->fetch_assoc();

        // Build full name
        $current_user_name = $user_row['firstname'];
        if (!empty($user_row['middlename'])) {
            $current_user_name .= ' ' . $user_row['middlename'];
        }
        $current_user_name .= ' ' . $user_row['lastname'];

        // Use profile picture if available
        if (!empty($user_row['profile_picture'])) {
            $profile_picture = $user_row['profile_picture'];
        }
    }
    $stmt->close();
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

    /* HEADER */
a {text-decoration: none;color: #fff;}
header {width: 100%;height: 4rem;background-image: linear-gradient(to left, #027d8d, #035a68);box-shadow: 0 4px 8px rgba(0,0,0,0.2), 0 6px 20px rgba(0,0,0,0.19);display: flex;align-items: center;justify-content: center;padding: 0 1rem;  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1000; /* ensures it stays above other content */}
.header-content {width: 100%;max-width: 1200px;display: flex;justify-content: space-between;align-items: center;}
.brand {font-size: 1.5rem;font-weight: bold;color: #fff;}
.menu-container {position: relative;}
.wrapper-2 {display: flex;align-items: center;gap: 1rem;color: #fff;}
.wrapper-2 p {margin: 0;padding: 0.5rem 1rem;}
.wrapper-2 .login {border: 1px solid #fff;border-radius: 10px;}
.wrapper-2 .login:hover {background-color: #fff;color: #000 !important;}
.wrapper-2 .login:hover a {color: #000 !important;}
.burger {display: none;font-size: 1.8rem;color: white;cursor: pointer;}

/* RESPONSIVE */
@media only screen and (max-width: 780px){.burger {display: block;} .wrapper-2 {display: none;position: absolute;top: 4rem;right: 1rem;background-color: #fff;box-shadow: 0 4px 8px rgba(0,0,0,0.2);padding: 1rem 2rem;flex-direction: column;gap: 1rem;align-items: flex-start;z-index: 100;border-radius: 10px;} .wrapper-2.active {display: flex;} .wrapper-2 p, .wrapper-2 a {color: #000 !important;font-size: 0.95rem;} .wrapper-2 .login {border: 1px solid #000;background-color: transparent;} .wrapper-2 .login:hover {background-color: #000;color: #fff !important;} .wrapper-2 .login:hover a {color: #fff !important;}.wrapper-2 .divider {display: none;}}

html, body {
  margin: 0;
  padding: 0;
}

body{
   padding-top: 6rem;
}

/* Hide burger by default */
.burger {
  display: none;
}

/* Responsive visibility */
@media (max-width: 991px) {
  .burger {
    display: inline-block;
  }
}

/* Nav layout */
.wrapper-2 {
  display: flex;
  align-items: center;
  gap: 15px;
}

.wrapper-2 p {
  margin: 0;
  vertical-align: middle;
}

/* Profile icon and dropdown */
.profile-wrapper {
  position: relative;
  display: inline-block;
}

.profile-icon .icon {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  cursor: pointer;
  vertical-align: middle;
}

.profile-menu {
  position: absolute;
  right: 0;
  top: 40px;
  background-color: white;
  color: black;
  border-radius: 5px;
  box-shadow: 0 4px 8px rgba(0,0,0,0.1);
  padding: 10px;
  min-width: 200px;
  z-index: 1000;
}

.profile-menu a {
  display: block;
  padding: 8px 10px;
  text-decoration: none;
  color: black;
}

.profile-menu a:hover {
  background-color: #f1f1f1;
}

.user-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-bottom: 8px;
  border-bottom: 1px solid #ccc;
}

/* Hide top nav items on mobile/tablet */
@media (max-width: 991px) {
  .wrapper-2 p:not(.divider):not(.login) {
    display: none;
  }
}
.bottom-nav {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background-color: white;
  display: flex;
  justify-content: space-around; /* evenly spaced items */
  align-items: center;
  padding: 8px 0;
  z-index: 999;
  box-shadow: 0 -2px 6px rgba(0,0,0,0.1);
  border-top: 1px solid #ccc;
}

.bottom-nav .nav-item {
  text-align: center;
  cursor: pointer;
  color: #333;
  text-decoration: none;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;
  min-width: 60px; /* ensures tap target size */
}

.bottom-nav .nav-item i {
  font-size: 20px;
  color: inherit;
}

.bottom-nav .nav-item span {
  font-size: 12px;
  color: inherit;
}

.bottom-nav .nav-item.active {
  color: teal;
  font-weight: bold;
}


/* More popup menu */
.more-popup {
  position: fixed;
  bottom: 60px;
  right: 10px;
  background-color: white;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
  padding: 10px;
  z-index: 1000;
}

.more-popup a {
  display: block;
  padding: 8px 10px;
  color: black;
  text-decoration: none;
  font-size: 14px;
}

.more-popup a:hover {
  background-color: #f1f1f1;
}

/* Responsive visibility */
.mobile-only {
  display: none;
}

@media (max-width: 991px) {
  .mobile-only {
    display: flex;
  }
}

@media (max-width: 991px) {
  .wrapper-2 {
    display: none;
  }
}

/*FULL SCREEN TOGGLE MENU*/
.fullscreen-menu {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: white; /* solid white to fully cover */
  z-index: 10000;
  padding: 20px;
  overflow-y: auto;
}

/* Optional: if you want a centered card-style panel */
.menu-panel {
  max-width: 400px;
  margin: 0 auto;
}

/* OR: if you want full width layout */
.menu-panel {
  width: 100%;
}

/* Keep the rest of your styles */
.menu-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}

.menu-title {
  font-size: 20px;
  color: teal;
  margin: 0;
}

.close-btn {
  font-size: 20px;
  cursor: pointer;
  color: black;
  background-color: #e0e0e0; /* light gray */
  border-radius: 50%;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
}


.user-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 16px;
  margin-bottom: 20px;
}

.edit-icon {
  font-size: 18px;
  cursor: pointer;
  color: gray;
}

.menu-options {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.menu-options a {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 16px;
  text-decoration: none;
  color: black;
}

.menu-options a:hover {
  background-color: #f1f1f1;
  padding: 8px;
  border-radius: 6px;
}

/*PROFILE IN MENU TOGGLE*/
.user-section {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.profile-info {
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
}

.menu-title {
  font-size: 2rem; /* roughly 32px */
  font-weight: bold;
  padding-bottom: 10px;
  font-style: italic;
}

.icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
}

.user-name {
  margin: 0;
  font-size: 18px;
  color: #333;
}

.edit-icon {
  font-size: 20px;
  color: gray;
  cursor: pointer;
}

.profile-link {
  display: flex;
  align-items: center;
  gap: 10px;
  text-decoration: none;
  color: inherit;
}


/*DIVIDER*/
.section-divider {
  height: 1px;
  background-color: #ccc;
  margin: 15px 0;
}


/*FOR NON USERS*/
.fullscreen-menu {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: white;
  z-index: 10000;
  padding: 20px;
  overflow-y: auto;
}

.menu-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.menu-title {
  font-size: 28px;
  color: teal;
  margin: 0;
}

.close-btn {
  font-size: 20px;
  cursor: pointer;
  color: black;
  background-color: #e0e0e0;
  border-radius: 50%;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.menu-options {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.menu-options a {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 16px;
  text-decoration: none;
  color: black;
}

.menu-options a:hover {
  background-color: #f1f1f1;
  padding: 8px;
  border-radius: 6px;
}


/***************************************************/
/* === Base Reset === */
html, body {
  height: 100%;
  margin: 0;
  padding-top: 2rem;
  overflow: hidden;

}

/* === Main Container === */
.chat-app-container {
  display: flex;
  height: 100%;
  max-height: 100vh;
  border: 1px solid #ddd;
  border-radius: 10px;
  overflow: hidden;
  max-width: 1200px;
  margin: 0 auto;
}

/* === Sidebar === */
.chat-sidebar {
  width: 300px;
  background-color: #fff;
  border-right: 1px solid #ccc;
  display: flex;
  flex-direction: column;
}

/* === Chat Panel === */
.chat-panel {
  flex: 1;
  display: flex;
  flex-direction: column;
  background-color: #fff;
  height: 100%;
  overflow: hidden;
}

/* === Chat Header === */
.chat-header {
  flex-shrink: 0;
  padding: 1rem;
  background-color: #007bff;
  color: white;
  border-bottom: 1px solid #ccc;
}
/*Back Arrow Mobile/tablet */
.back-arrow {
  font-size: 1.25rem;
  cursor: pointer;
  line-height: 1;
  display: inline-block;
  margin-right: 0.5rem;
}

/* === Scrollable Messages === */
.chat-messages-scroll {
  flex: 1;
  overflow-y: auto;
  padding: 1rem;
  display: flex;
  flex-direction: column;
}

/* === Message Bubbles === */
.chat-message {
  margin-bottom: 10px;
  max-width: 60%;
  padding: 10px 14px;
  border-radius: 15px;
  word-wrap: break-word;
  display: inline-block;
  white-space: normal;
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

/* === Chat Input === */
.chat-input-fixed {
  position: sticky;
  bottom: 3.5rem; /* height of bottom nav */
  background-color: #f8f9fa;
  padding: 1rem;
  border-top: 1px solid #ddd;
  z-index: 100;
}

.chat-input-fixed .input-group {
  display: flex;
  align-items: center;
}

.chat-input-fixed input[type="text"],
.chat-input-fixed .btn-primary {
  height: 48px;
  font-size: 1rem;
  line-height: 1;
  padding: 0 1rem;
  border: 1px solid #ccc;
  font-family: inherit;
  font-weight: 400;
}

.chat-input-fixed input[type="text"] {
  flex: 1;
  border-radius: 10px 0 0 10px;
  border-right: none;
}

.chat-input-fixed .btn-primary {
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 0 10px 10px 0;
  box-shadow: 0 2px 6px rgba(0, 123, 255, 0.3);
  margin: 0;
}

/* === Bottom Navigation === */
.bottom-nav {
  position: fixed;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 3.5rem;
  background-color: #fff;
  border-top: 1px solid #ccc;
  display: flex;
  justify-content: space-around;
  align-items: center;
  z-index: 99999;
}

/* === Responsive Behavior === */
@media (max-width: 768px) {
  body {
    height: 100vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
  }

  .chat-app-container {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    width: 100%;
    max-width: 100%;
  }

  #contactList {
    display: block;
    width: 100%;
  }

  #chatBox {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100vh;
    display: none;
    flex-direction: column;
    z-index: 999;
    background-color: #fff;
  }

  body.show-chat #contactList {
    display: none !important;
  }

  body.show-chat #chatBox {
    display: flex !important;
  }

  .chat-panel {
    flex: 1;
    display: flex;
    flex-direction: column;
    height: 100%;
    overflow: hidden;
  }

  .chat-header {
    position: sticky;
    top: 63px;
    z-index: 100;
  }

  .chat-messages-scroll {
    flex: 1;
    overflow-y: auto;
    padding-top: 5rem;
    padding-bottom: 4rem;
  }

  .chat-input-fixed {
    position: sticky;
    bottom: 3.5rem;
  }

  .back-btn {
    display: inline-block;
  }

  html, body{
    padding-top: 2rem;
  }
}

@media (min-width: 769px) {
  #contactList {
    display: block;
  }

  #chatBox {
    display: flex;
  }

  .back-btn {
    display: none;
  }

  .bottom-nav {
    display: none;
  }

  .chat-input-fixed {
    position: relative; /* remove sticky */
    bottom: auto;
    padding: 1rem;
    border-top: 1px solid #ddd;
    z-index: 10;
  }

  .chat-panel {
    display: flex;
    flex-direction: column;
    height: 100%;
    overflow: hidden;
  }

  .chat-messages-scroll {
    flex: 1;
    overflow-y: auto;
    padding: 1rem;
    margin: 0;
  }
}


/* Hide bottom nav on desktop */
@media (min-width: 769px) {
  .bottom-nav {
    display: none;
  }
}

/* Show bottom nav on mobile/tablet */
@media (max-width: 768px) {
  .bottom-nav {
    display: flex;
  }
}

  </style>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>


<!-- NAVIGATION BAR -->
<header>
  <div class="header-content">
    <div class="brand"><a href="../view/index.php">Servify</a></div>
    <div class="menu-container">
      <nav class="wrapper-2" id="menu">
        <p><a href="../view/browse.php">Services</a></p>
        <!-- <p><a href="#">Become a laborer</a></p> -->
        <p class="divider">|</p>

        <?php if ($is_logged_in): ?>
          <p class="profile-wrapper">
            <span class="profile-icon" onclick="toggleProfileMenu()">
              <img src="../<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile Picture" class="icon">
            </span>
            <div id="profile-menu" class="profile-menu d-none">
              <a href="../view/profile.php" class="user-info-link">
                <div class="user-info">
                  <span><?php echo htmlspecialchars($current_user_name); ?></span>
                  <i class="bi bi-pencil-square"></i>
                </div>
              </a>
              <a href="../view/messages.php"><i class="bi bi-chat-dots"></i> Inbox</a>
              <a href="#"><i class="bi bi-bell"></i> Notifications</a>
              <a href="#"><i class="bi bi-grid"></i> Dashboard</a>
              <a href="../controls/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </div>
          </p>
        <?php else: ?>
          <p class="login"><a href="../view/login.php"><i class="bi bi-box-arrow-in-right"></i> Login / Signup</a></p>
        <?php endif; ?>
      </nav>
    </div>
  </div>
</header>

<!-- Bottom Navigation (Mobile/Tablet Only) -->
<div class="bottom-nav mobile-only">
  <a href="../view/index.php">
    <div class="nav-item active" onclick="goToHome()">
      <i class="bi bi-house"></i>
      <span>Home</span>
    </div>
  </a>
  <a href="../view/browse.php">
    <div class="nav-item" onclick="goToServices()">
      <i class="bi bi-search"></i>
      <span>Services</span>
    </div>
  </a>
  
  <div class="nav-item" onclick="toggleMoreMenu()">
    <i class="bi bi-three-dots"></i>
    <span>More</span>
  </div>
</div>


<!-- Fullscreen More Menu -->
<div id="more-menu" class="fullscreen-menu d-none">
  <div class="menu-panel">
    <div class="menu-header">
      <h1 class="menu-title">SERVIFY</h1>
      <span class="close-btn" onclick="toggleMoreMenu()">✕</span>
    </div>

    <?php if ($is_logged_in): ?>
      <!-- Logged-in User Menu -->
      <div class="user-section">
        <div class="profile-info" onclick="toggleProfileMenu()">
          <a href="../view/profile.php" class="profile-link">
            <img src="../<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile" class="icon">
            <h3 class="user-name"><?php echo htmlspecialchars($current_user_name); ?></h3>
          </a>
        </div>
        <i class="bi bi-pencil-square edit-icon"></i>
      </div>

      <div class="section-divider"></div>

      <div class="menu-options">
        <a href="../view/messages.php"><i class="bi bi-chat-dots"></i> Inbox</a>
        <a href="#"><i class="bi bi-bell"></i> Notifications</a>
        <a href="#"><i class="bi bi-grid"></i> Dashboard</a>
        <a href="../controls/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
      </div>

    <?php else: ?>
      <!-- Non-User Menu -->
      <div class="menu-options">
        <a href="../view/become-laborer.php"><i class="bi bi-person-workspace"></i> Become a laborer</a>
        <a href="../view/login.php"><i class="bi bi-person-circle"></i> Signin / Signup</a>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- ************************************** -->
<div class="chat-app-container">
  <!-- Sidebar: Search + Clients -->
  <div class="chat-sidebar" id="contactList">
    <div class="search-bar p-3 border-bottom">
      <input type="text" class="form-control" placeholder="Search clients...">
    </div>
    <div class="client-list p-2">
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
          if ($row['user_id'] == $sender_id) continue;
      ?>
        <div class="p-2 border-bottom bg-body-tertiary contact-item" data-contact="<?php echo $row['user_id']; ?>">
          <a href="messages.php?receiver_id=<?php echo $row['user_id']; ?>" class="d-flex justify-content-between">
            <div class="d-flex flex-row">
              <img src="../image/man.png" alt="avatar" class="rounded-circle me-3 shadow-1-strong" width="65px">
              <div class="pt-1">
                <p class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?></p>
                <p class="small text-muted">Tap to open chat</p>
              </div>
            </div>
          </a>
        </div>
      <?php endwhile; $stmt->close(); ?>
    </div>
  </div>

  <!-- Chat Panel -->
  <div class="chat-panel" id="chatBox">
    <?php if ($receiver): ?>
    <div class="chat-header bg-primary text-white p-3 d-flex align-items-center">
     <span class="back-arrow me-2 d-lg-none" id="backToContacts">←</span>
     <span>Chat with Arvilyn Kate Nicerio</span>
    </div>

      <div class="chat-messages-scroll" id="chat-messages">
        <!-- Messages will be loaded here via AJAX -->
      </div>

      <div class="chat-input-fixed">
        <form id="chat-form">
          <div class="input-group">
            <input type="text" name="message" id="message" class="form-control" placeholder="Type a message..." required>
            <button type="submit" class="btn btn-primary">Send</button>
          </div>
        </form>
      </div>
    <?php else: ?>
      <div class="chat-header bg-light p-3 border rounded">Select a conversation</div>
    <?php endif; ?>
  </div>
</div>


<script>
$(document).ready(function () {
  const receiver_id = <?php echo $receiver_id ?: '0'; ?>;

  function loadMessages() {
    if (receiver_id === 0) return;

    const chatBox = $("#chat-messages");
    const isNearBottom =
      chatBox.scrollTop() + chatBox.innerHeight() >= chatBox[0].scrollHeight - 50;

    $.ajax({
      url: "../controls/load_messages.php",
      type: "GET",
      data: { receiver_id: receiver_id },
      success: function (data) {
        chatBox.html(data);
        if (isNearBottom) {
          chatBox.scrollTop(chatBox[0].scrollHeight);
        }
      },
    });
  }

  setInterval(loadMessages, 2000);
  loadMessages();

  $("#chat-form").on("submit", function (e) {
    e.preventDefault();
    const message = $("#message").val();
    $.ajax({
      url: "../controls/send_message.php",
      type: "POST",
      data: { receiver_id: receiver_id, message: message },
      success: function () {
        $("#message").val("");
        loadMessages();
      },
    });
  });

  const backBtn = document.getElementById("backToContacts");
  if (backBtn) {
    backBtn.addEventListener("click", () => {
      document.body.classList.remove("show-chat");
    });
  }

  <?php if ($receiver): ?>
    document.body.classList.add("show-chat");
  <?php endif; ?>
});
</script>



<script>
  function toggleMenu() {
    const menu = document.getElementById('menu');
    menu.classList.toggle('active');
  }
</script>

<script>
  // Toggle profile dropdown
  function toggleProfileMenu() {
    const menu = document.getElementById('profile-menu');
    menu.classList.toggle('d-none');
  }

  // Toggle fullscreen "More" menu
  function toggleMoreMenu() {
    const menu = document.getElementById('more-menu');
    menu.classList.toggle('d-none');
  }

  // Navigation actions
  function goToHome() {
    window.location.href = '../view/home.php';
  }

  function goToServices() {
    window.location.href = '../view/services.php';
  }
</script>

</body>
</html>

