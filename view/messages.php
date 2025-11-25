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

<?php include '../nav.php' ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Messages</title>
  <link rel="stylesheet" type="text/css" href="../styles/landing_page.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
  background: #f5f7fa;
  margin: 0;
  padding: 0;
  height: 100vh;
  overflow: hidden;
  padding-top: 56px;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
}

html {
  height: 100%;
  margin: 0;
  padding: 0;
}

a {
  text-decoration: none;
  color: #fff;
}

.chat-app-container {
  display: flex;
  height: calc(100vh - 56px);
  max-width: 100%;
  margin: 0;
  overflow: hidden;
  gap: 0;
  background: #fff;
}

.chat-sidebar {
  width: 360px;
  background: #ffffff;
  border-right: 1px solid #e1e4e8;
  display: flex;
  flex-direction: column;
  flex-shrink: 0;
}

.search-bar {
  flex-shrink: 0;
  background: #ffffff;
  padding: 20px;
  border-bottom: 1px solid #e9ecef;
}

.search-bar h3 {
  font-size: 24px;
  font-weight: 600;
  color: #1a202c;
  margin-bottom: 16px;
  letter-spacing: -0.3px;
}

.search-bar .form-control {
  border-radius: 8px;
  border: 1px solid #e1e4e8;
  background: #f8f9fa;
  padding: 10px 16px;
  font-size: 14px;
  transition: all 0.2s ease;
}

.search-bar .form-control:focus {
  outline: none;
  background: #ffffff;
  border-color: #027d8d;
  box-shadow: 0 0 0 3px rgba(2, 125, 141, 0.1);
}

.client-list {
  flex: 1;
  overflow-y: auto;
  padding: 8px 0;
}

.client-list::-webkit-scrollbar {
  width: 6px;
}

.client-list::-webkit-scrollbar-track {
  background: transparent;
}

.client-list::-webkit-scrollbar-thumb {
  background: #cbd5e0;
  border-radius: 3px;
}

.client-list::-webkit-scrollbar-thumb:hover {
  background: #a0aec0;
}

.contact-item {
  padding: 12px 16px;
  margin: 2px 8px;
  cursor: pointer;
  transition: all 0.2s ease;
  border-radius: 8px;
  background: transparent;
  position: relative;
}

.contact-item::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  height: 100%;
  width: 3px;
  background: #027d8d;
  border-radius: 0 3px 3px 0;
  opacity: 0;
  transition: opacity 0.2s ease;
}

.contact-item:hover {
  background: #f0f8f9;
  transform: translateX(2px);
}

.contact-item:hover::before {
  opacity: 1;
}

.contact-item.active {
  background: #e8f4f6;
}

.contact-item:hover .rounded-circle {
  box-shadow: 0 2px 8px rgba(2, 125, 141, 0.2);
}

.contact-item a {
  text-decoration: none;
  color: inherit;
}

.contact-item .rounded-circle {
  width: 48px;
  height: 48px;
  object-fit: cover;
  border: 2px solid #e9ecef;
}

.contact-item .fw-bold {
  font-size: 15px;
  font-weight: 600;
  color: #1a202c;
  margin-bottom: 4px;
}

.contact-item .text-muted {
  font-size: 13px;
  color: #718096;
  font-weight: 400;
}

.chat-panel {
  flex: 1;
  display: flex;
  flex-direction: column;
  background: #ffffff;
  height: 100%;
  overflow: hidden;
}

.chat-header {
  padding: 12px 16px;
  background: linear-gradient(135deg, #b4e3e7 0%, #9fd4d8 100%);
  color: #050505;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-shrink: 0;
  min-height: 90px;
  border-bottom: 1px solid #d4e9ec;
}

.chat-header .text-white {
  color: #050505 !important;
  font-weight: 600;
  font-size: 15px;
}

.chat-messages-scroll {
  flex: 1;
  overflow-y: auto;
  padding: 24px;
  display: flex;
  flex-direction: column;
  background: #f8f9fa;
}

.chat-messages-scroll::-webkit-scrollbar {
  width: 6px;
}

.chat-messages-scroll::-webkit-scrollbar-track {
  background: transparent;
}

.chat-messages-scroll::-webkit-scrollbar-thumb {
  background: #cbd5e0;
  border-radius: 3px;
}

.chat-input-fixed {
  flex-shrink: 0;
  background: #ffffff;
  padding: 16px 20px;
  border-top: 1px solid #e9ecef;
}

.contact-info-sidebar {
  width: 320px;
  background: #ffffff;
  border-left: 1px solid #e1e4e8;
  display: flex;
  flex-direction: column;
  flex-shrink: 0;
  overflow-y: auto;
}

.contact-info-sidebar::-webkit-scrollbar {
  width: 6px;
}

.contact-info-sidebar::-webkit-scrollbar-track {
  background: transparent;
}

.contact-info-sidebar::-webkit-scrollbar-thumb {
  background: #cbd5e0;
  border-radius: 3px;
}

.contact-info-header {
  padding: 20px;
  border-bottom: 1px solid #e9ecef;
  background: #ffffff;
}

.contact-info-title {
  font-size: 16px;
  font-weight: 600;
  color: #1a202c;
  margin: 0;
  text-align: center;
}

.contact-user-info {
  padding: 32px 20px;
  text-align: center;
  border-bottom: 1px solid #e9ecef;
  background: #ffffff;
}

.contact-user-avatar {
  width: 96px;
  height: 96px;
  border-radius: 50%;
  object-fit: cover;
  margin: 0 auto 16px;
  display: block;
  border: 3px solid #e9ecef;
}

.contact-user-name {
  font-size: 18px;
  font-weight: 600;
  color: #1a202c;
  margin: 0 0 6px 0;
}

.contact-user-email {
  font-size: 14px;
  color: #718096;
  margin: 0;
  font-weight: 400;
}

.contact-actions-list {
  padding: 8px 0;
}

.contact-action-item {
  padding: 14px 16px;
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
  transition: background 0.15s ease;
  border: none;
  width: 100%;
  background: transparent;
  text-align: left;
}

.contact-action-item:hover {
  background: #f8f9fa;
}

.contact-action-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #e8f4f6;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  color: #027d8d;
}

.contact-action-text {
  font-size: 15px;
  color: #1a202c;
  font-weight: 500;
}

.message-group {
  display: flex;
  flex-direction: column;
  margin-bottom: 16px;
}

.message-group.sent {
  align-items: flex-end;
}

.message-group.received {
  align-items: flex-start;
}

.message-date {
  font-size: 11px;
  color: #718096;
  margin-bottom: 10px;
  text-align: center;
  padding: 4px 12px;
  background: rgba(0, 0, 0, 0.04);
  border-radius: 12px;
  display: inline-block;
  font-weight: 500;
}

.chat-message {
  margin-bottom: 4px;
  max-width: 65%;
  padding: 10px 14px;
  border-radius: 12px;
  word-wrap: break-word;
  word-break: break-word;
  overflow-wrap: break-word;
  display: inline-block;
  white-space: normal;
  position: relative;
  font-size: 14px;
  line-height: 1.4;
}

.msg-sent {
  background: #027d8d;
  color: white;
  align-self: flex-end;
  border-bottom-right-radius: 4px;
}

.msg-received {
  background: #e9ecef;
  color: #1a202c;
  align-self: flex-start;
  border-bottom-left-radius: 4px;
}

.message-time {
  font-size: 10px;
  color: rgba(255, 255, 255, 0.8);
  margin-top: 4px;
  display: block;
  font-weight: 400;
}

.msg-received .message-time {
  color: rgba(0, 0, 0, 0.5);
}

.call-actions-group {
  display: flex;
  gap: 8px;
  align-items: center;
}

.call-btn-header {
  background: #ffffff;
  border: none;
  color: #027d8d;
  padding: 10px 14px;
  border-radius: 20px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: all 0.2s ease;
  font-size: 14px;
  font-weight: 600;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.call-btn-header:hover {
  background: #027d8d;
  color: #ffffff;
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(2, 125, 141, 0.3);
}

.call-btn-header i {
  font-size: 16px;
}

.info-btn-header {
  background: #ffffff;
  border: none;
  color: #027d8d;
  padding: 8px;
  border-radius: 50%;
  cursor: pointer;
  display: none;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  font-size: 18px;
  width: 36px;
  height: 36px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.info-btn-header:hover {
  background: #027d8d;
  color: #ffffff;
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(2, 125, 141, 0.3);
}

.profile-sidebar {
  position: fixed;
  top: 0;
  right: -400px;
  width: 400px;
  height: 100vh;
  background: #ffffff;
  box-shadow: -2px 0 12px rgba(0, 0, 0, 0.15);
  z-index: 9999;
  transition: right 0.3s ease;
  overflow-y: auto;
  display: none;
}

.profile-sidebar.active {
  right: 0;
}

.profile-sidebar-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100vh;
  background: rgba(0, 0, 0, 0.5);
  z-index: 9998;
  display: none;
}

.profile-sidebar-overlay.active {
  display: block;
}

.profile-sidebar-header {
  padding: 16px 20px;
  border-bottom: 1px solid #e9ecef;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #ffffff;
}

.profile-sidebar-back {
  background: transparent;
  border: none;
  color: #1a202c;
  font-size: 20px;
  width: 36px;
  height: 36px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s ease;
  border-radius: 50%;
}

.profile-sidebar-back:hover {
  background: #f8f9fa;
}

.profile-sidebar-title {
  font-size: 16px;
  font-weight: 600;
  color: #1a202c;
  margin: 0;
}

.video-call-modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.98);
  z-index: 10000;
  display: none;
  align-items: center;
  justify-content: center;
}

.video-call-container {
  position: relative;
  width: 95%;
  max-width: 1400px;
  height: 90vh;
  background: #000000;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
}

.video-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 0;
  height: 100%;
  position: relative;
}

.video-wrapper {
  position: relative;
  background: #1a1a1a;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
}

.video-wrapper video {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.local-video {
  position: absolute;
  bottom: 180px;
  right: 20px;
  width: 240px;
  height: 180px;
  border-radius: 8px;
  border: 3px solid #ffffff;
  z-index: 10;
  overflow: hidden;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
}

.local-video video {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.call-status {
  position: absolute;
  top: 30px;
  left: 50%;
  transform: translateX(-50%);
  background: rgba(0, 0, 0, 0.8);
  color: white;
  padding: 10px 20px;
  border-radius: 20px;
  z-index: 100;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  font-weight: 500;
}

.call-status .pulse-dot {
  width: 8px;
  height: 8px;
  background: #28a745;
  border-radius: 50%;
}

.call-timer {
  position: absolute;
  top: 30px;
  right: 30px;
  background: rgba(0, 0, 0, 0.8);
  color: white;
  padding: 8px 16px;
  border-radius: 16px;
  font-size: 14px;
  font-weight: 500;
}

.video-controls {
  position: absolute;
  bottom: 100px;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  gap: 16px;
  z-index: 100000;
  padding: 12px 24px;
  background: rgba(0, 0, 0, 0.5);
  border-radius: 40px;
}

.control-btn {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  border: none;
  color: white;
  font-size: 20px;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.control-btn:hover {
  transform: scale(1.05);
}

.control-btn.mute-btn {
  background: #667eea;
}

.control-btn.mute-btn.active {
  background: #dc3545;
}

.control-btn.video-btn {
  background: #f093fb;
}

.control-btn.video-btn.active {
  background: #dc3545;
}

.control-btn.end-btn {
  background: #fa709a;
}

.control-btn.end-btn:hover {
  background: #dc3545;
}

.participant-info {
  position: absolute;
  bottom: 20px;
  left: 20px;
  background: rgba(0, 0, 0, 0.7);
  padding: 10px 18px;
  border-radius: 12px;
  color: white;
  font-size: 15px;
  font-weight: 500;
}

.global-incoming-call {
  position: fixed;
  top: 80px;
  right: 20px;
  z-index: 999999;
  display: none;
}

.call-notification-card {
  background: #667eea;
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4);
  min-width: 340px;
  max-width: 400px;
  border: none;
}

.caller-info {
  display: flex;
  align-items: center;
  gap: 14px;
  margin-bottom: 20px;
}

.caller-avatar {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  object-fit: cover;
  border: 3px solid rgba(255, 255, 255, 0.9);
}

.caller-details h4 {
  margin: 0;
  font-size: 1.2rem;
  color: white;
  font-weight: 600;
}

.call-subtitle {
  margin: 6px 0 0 0;
  color: rgba(255, 255, 255, 0.9);
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  gap: 6px;
  font-weight: 500;
}

.call-actions {
  display: flex;
  gap: 12px;
  justify-content: space-between;
}

.call-btn {
  flex: 1;
  padding: 12px 18px;
  border: none;
  border-radius: 8px;
  font-size: 0.95rem;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: all 0.15s ease;
}

.accept-btn {
  background: #11998e;
  color: white;
}

.accept-btn:hover {
  background: #0f8679;
}

.decline-btn {
  background: #eb3349;
  color: white;
}

.decline-btn:hover {
  background: #d62c3e;
}

.call-type-badge {
  position: absolute;
  top: -10px;
  right: -10px;
  background: rgba(255, 255, 255, 0.2);
  padding: 6px 12px;
  border-radius: 10px;
  font-size: 10px;
  color: white;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.audio-only-indicator {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
  z-index: 5;
}

.audio-only-indicator .avatar-large {
  width: 140px;
  height: 140px;
  border-radius: 50%;
  border: 4px solid rgba(255, 255, 255, 0.3);
  object-fit: cover;
  margin-bottom: 20px;
}

.audio-only-indicator .caller-name {
  font-size: 26px;
  color: white;
  font-weight: 600;
  margin-bottom: 10px;
}

.audio-only-indicator .call-status-text {
  font-size: 15px;
  color: rgba(255, 255, 255, 0.8);
  font-weight: 400;
}

.back-arrow {
  font-size: 1.25rem;
  cursor: pointer;
  line-height: 1;
  display: none;
  margin-right: 0.75rem;
  color: #050505;
  transition: all 0.2s ease;
}

.back-arrow:hover {
  transform: translateX(-2px);
}

.chat-input-fixed .input-group {
  display: flex;
  align-items: center;
}

.chat-input-fixed input[type="text"] {
  height: 44px;
  font-size: 14px;
  line-height: 1.4;
  padding: 0 16px;
  border: 1px solid #e1e4e8;
  font-family: inherit;
  font-weight: 400;
  border-radius: 8px 0 0 8px;
  background: #f8f9fa;
  transition: all 0.2s ease;
  flex: 1;
}

.chat-input-fixed input[type="text"]:focus {
  outline: none;
  border-color: #027d8d;
  background: #ffffff;
  box-shadow: 0 0 0 3px rgba(2, 125, 141, 0.1);
}

.chat-input-fixed .btn-primary {
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 0 8px 8px 0;
  margin: 0;
  background: #027d8d;
  border: none;
  height: 44px;
  padding: 0 24px;
  font-weight: 600;
  transition: all 0.15s ease;
}

.chat-input-fixed .btn-primary:hover {
  background: #026570;
}

.empty-chat-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  padding: 2rem;
  text-align: center;
  background: #f8f9fa;
}

.empty-chat-icon {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  background: #e9ecef;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1.5rem;
}

.empty-chat-icon i {
  font-size: 3rem;
  color: #718096;
}

.empty-chat-title {
  font-size: 1.4rem;
  font-weight: 600;
  color: #1a202c;
  margin-bottom: 0.5rem;
}

.empty-chat-text {
  font-size: 1rem;
  color: #718096;
  margin: 0;
  font-weight: 400;
}

@media (max-width: 768px) {
  body {
    padding-top: 56px;
    height: 100vh;
    overflow: hidden;
  }

  .chat-app-container {
    flex-direction: column;
    height: calc(100vh - 56px);
  }

  .contact-info-sidebar {
    display: none !important;
  }

  .profile-sidebar {
    display: block;
  }

  .info-btn-header {
    display: flex !important;
  }

  #contactList {
    display: flex;
    flex-direction: column;
    width: 100%;
    height: 100%;
    flex: 1;
  }

  .chat-sidebar {
    width: 100%;
    flex: 1;
    display: flex;
    flex-direction: column;
  }

  #chatBox {
    position: fixed;
    top: 56px;
    left: 0;
    width: 100%;
    height: calc(100vh - 56px);
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

  .chat-header {
    padding: 12px 16px;
    min-height: 90px;
    background: linear-gradient(135deg, #b4e3e7 0%, #9fd4d8 100%);
    border-bottom: 1px solid #d4e9ec;
  }

  .back-arrow {
    display: inline-block;
  }

  .video-call-container {
    width: 100%;
    height: 100vh;
    border-radius: 0;
  }

  .local-video {
    width: 120px;
    height: 90px;
    bottom: 180px;
    right: 15px;
    border-width: 2px;
  }

  .video-controls {
    bottom: 100px;
    gap: 15px;
    padding: 12px 20px;
  }

  .control-btn {
    width: 50px;
    height: 50px;
    font-size: 18px;
  }

  .global-incoming-call {
    top: 70px;
    right: 10px;
    left: 10px;
  }

  .call-notification-card {
    min-width: auto;
    width: 100%;
    padding: 20px;
  }

  .caller-avatar {
    width: 56px;
    height: 56px;
    border-width: 3px;
  }

  .caller-details h4 {
    font-size: 1.1rem;
  }

  .call-btn {
    padding: 12px 16px;
    font-size: 0.9rem;
    gap: 8px;
  }

  .call-actions-group {
    gap: 8px;
    flex-wrap: wrap;
  }

  .call-btn-header {
    padding: 6px 12px;
    font-size: 13px;
  }

  .call-btn-header i {
    font-size: 14px;
  }

  .call-btn-header span {
    display: none;
  }

  .info-btn-header {
    width: 36px;
    height: 36px;
    font-size: 16px;
    padding: 0;
  }

  .call-status {
    font-size: 12px;
    padding: 10px 20px;
    top: 20px;
  }

  .call-timer {
    font-size: 12px;
    padding: 6px 12px;
    top: 20px;
    right: 20px;
  }

  .participant-info {
    font-size: 14px;
    padding: 10px 16px;
  }

  .audio-only-indicator .avatar-large {
    width: 110px;
    height: 110px;
    border-width: 4px;
  }

  .audio-only-indicator .caller-name {
    font-size: 22px;
  }

  .audio-only-indicator .call-status-text {
    font-size: 14px;
  }

  .profile-sidebar {
    width: 100%;
    right: -100%;
  }
}

@media (max-width: 480px) {
  .call-actions-group {
    gap: 6px;
  }

  .call-btn-header {
    padding: 5px 10px;
    font-size: 12px;
    gap: 6px;
  }

  .call-btn-header i {
    font-size: 13px;
  }

  .info-btn-header {
    width: 32px;
    height: 32px;
    font-size: 13px;
  }

  .chat-header {
    padding: 10px 12px;
  }

  .global-incoming-call {
    top: 65px;
    right: 8px;
    left: 8px;
  }

  .call-notification-card {
    padding: 18px;
  }

  .caller-avatar {
    width: 52px;
    height: 52px;
  }

  .caller-details h4 {
    font-size: 1rem;
  }

  .call-subtitle {
    font-size: 0.85rem;
  }

  .call-btn {
    padding: 10px 14px;
    font-size: 0.85rem;
  }
}

* {
  scrollbar-width: thin;
  scrollbar-color: #cbd5e0 transparent;
}

*::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}

*::-webkit-scrollbar-track {
  background: transparent;
}

*::-webkit-scrollbar-thumb {
  background: #cbd5e0;
  border-radius: 3px;
}

*::-webkit-scrollbar-thumb:hover {
  background: #a0aec0;
}
</style>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/peerjs@1.5.4/dist/peerjs.min.js"></script>
</head>
<body>

<!-- Global Incoming Call Notification -->
<div class="global-incoming-call" id="globalIncomingCall">
  <div class="call-notification-card" id="callNotificationCard">
    <div class="call-type-badge" id="callTypeBadge">Video Call</div>
    <div class="caller-info">
      <img src="../image/man.png" alt="Caller" class="caller-avatar" id="globalCallerAvatar">
      <div class="caller-details">
        <h4 id="globalCallerName">Someone</h4>
        <p class="call-subtitle">
          <i class="bi bi-telephone-fill"></i>
          <span id="callTypeText">Incoming Video Call</span>
        </p>
      </div>
    </div>
    <div class="call-actions">
      <button class="call-btn accept-btn" onclick="acceptGlobalCall()">
        <i class="bi bi-check-circle-fill"></i>
        Accept
      </button>
      <button class="call-btn decline-btn" onclick="declineGlobalCall()">
        <i class="bi bi-x-circle-fill"></i>
        Decline
      </button>
    </div>
  </div>
</div>

<!-- Global Video Call Modal -->
<div class="video-call-modal" id="globalVideoModal">
  <div class="video-call-container">
    <div class="call-status" id="globalCallStatus">
      <div class="pulse-dot"></div>
      <span>Connecting...</span>
    </div>
    
    <div class="call-timer" id="callTimer" style="display: none;">00:00</div>
    
    <div class="video-grid" id="videoGrid">
      <div class="video-wrapper" id="remoteVideoWrapper">
        <video id="globalRemoteVideo" autoplay playsinline></video>
        <div class="audio-only-indicator" id="audioOnlyIndicator" style="display: none;">
          <img src="../image/man.png" alt="Caller" class="avatar-large" id="audioCallerAvatar">
          <div class="caller-name" id="audioCallerName">Audio Call</div>
          <div class="call-status-text">Voice only</div>
        </div>
      </div>
    </div>
    
    <div class="local-video" id="localVideoContainer">
      <video id="globalLocalVideo" autoplay muted playsinline></video>
    </div>
    
    <div class="participant-info" id="participantInfo">
      <span id="participantName">Connecting...</span>
    </div>
    
    <div class="video-controls">
      <button class="control-btn mute-btn" id="globalMuteBtn" onclick="toggleGlobalMute()" title="Mute/Unmute">
        <i class="bi bi-mic-fill"></i>
      </button>
      <button class="control-btn video-btn" id="globalVideoBtn" onclick="toggleGlobalVideo()" title="Camera On/Off">
        <i class="bi bi-camera-video-fill"></i>
      </button>
      <button class="control-btn end-btn" onclick="endGlobalCall()" title="End Call">
        <i class="bi bi-telephone-x-fill"></i>
      </button>
    </div>
  </div>
</div>

<!-- Mobile Profile Info Sidebar (Overlay) -->
<div class="profile-sidebar-overlay" id="profileOverlay" onclick="closeProfileSidebar()"></div>
<div class="profile-sidebar" id="profileSidebar">
  <div class="profile-sidebar-header">
    <button class="profile-sidebar-back" onclick="closeProfileSidebar()">
      <i class="bi bi-arrow-left"></i>
    </button>
    <h3 class="profile-sidebar-title">Contact Info</h3>
    <div style="width: 36px;"></div>
  </div>
  
  <div class="contact-user-info">
    <img src="../image/man.png" alt="Profile" class="contact-user-avatar" id="sidebarProfileAvatar">
    <h3 class="contact-user-name" id="sidebarProfileName">User Name</h3>
    <p class="contact-user-email" id="sidebarProfileEmail">email@example.com</p>
  </div>
  
  <div class="contact-actions-list">
    <button class="contact-action-item" onclick="viewFullProfile()">
      <div class="contact-action-icon">
        <i class="bi bi-person-circle"></i>
      </div>
      <span class="contact-action-text">View Profile</span>
    </button>
    
    <button class="contact-action-item" onclick="startCall('audio')">
      <div class="contact-action-icon">
        <i class="bi bi-telephone-fill"></i>
      </div>
      <span class="contact-action-text">Audio Call</span>
    </button>
    
    <button class="contact-action-item" onclick="startCall('video')">
      <div class="contact-action-icon">
        <i class="bi bi-camera-video-fill"></i>
      </div>
      <span class="contact-action-text">Video Call</span>
    </button>
  </div>
</div>

<div class="chat-app-container">
  <!-- Left: Contact List -->
  <div class="chat-sidebar" id="contactList">
    <div class="search-bar">
      <h3 class="text-2xl font-semibold mb-3">Messages</h3>
      <input type="text" class="form-control" id="searchInput" placeholder="🔎  |  Search">
    </div>
    <div class="client-list p-2">
      <?php
        $stmt = $conn->prepare("
          SELECT u.user_id, u.firstname, u.lastname, u.profile_picture, u.email, u.contact, MAX(m.timestamp) AS last_msg_time
          FROM users u
          INNER JOIN messages m 
            ON (u.user_id = m.sender_id OR u.user_id = m.receiver_id)
          WHERE m.sender_id = ? OR m.receiver_id = ?
          GROUP BY u.user_id, u.firstname, u.lastname, u.profile_picture, u.email, u.contact
          ORDER BY last_msg_time DESC
        ");
        $stmt->bind_param("ii", $sender_id, $sender_id);
        $stmt->execute();
        $users_result = $stmt->get_result();

        while ($row = $users_result->fetch_assoc()):
          if ($row['user_id'] == $sender_id) continue;
          $contact_pic = !empty($row['profile_picture']) ? $row['profile_picture'] : 'image/man.png';
      ?>
        <div class="p-2 border-bottom bg-body-tertiary contact-item" 
             data-contact="<?php echo $row['user_id']; ?>" 
             data-name="<?php echo strtolower($row['firstname'] . ' ' . $row['lastname']); ?>"
             data-profile="<?php echo htmlspecialchars($contact_pic); ?>"
             data-email="<?php echo htmlspecialchars($row['email'] ?? 'Not available'); ?>"
             data-contact-num="<?php echo htmlspecialchars($row['contact'] ?? 'Not available'); ?>">
          <a href="messages.php?receiver_id=<?php echo $row['user_id']; ?>" class="d-flex justify-content-between">
            <div class="d-flex flex-row">
              <img src="../<?php echo htmlspecialchars($contact_pic); ?>" alt="avatar" class="rounded-circle me-3 shadow-1-strong" width="65px">
              <div class="pt-1">
                <p class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?></p>
                <p class="small text-muted">Tap to open chat</p>
              </div>
            </div>
          </a>
        </div>
      <?php endwhile; $stmt->close(); ?>
    </div>
    <div id="noResults" class="text-muted p-3" style="display: none;">No clients found.</div>
  </div>

  <!-- Middle: Chat Panel -->
  <div class="chat-panel" id="chatBox">
    <?php if ($receiver): ?>
      <?php
        $stmt = $conn->prepare("SELECT firstname, lastname, profile_picture, email, contact FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $receiver_id);
        $stmt->execute();
        $receiver_result = $stmt->get_result();
        $receiver_data = $receiver_result->fetch_assoc();
        $stmt->close();
        $receiver_pic = !empty($receiver_data['profile_picture']) ? $receiver_data['profile_picture'] : 'image/man.png';
        $receiver_name = htmlspecialchars($receiver_data['firstname'] . ' ' . $receiver_data['lastname']);
        $receiver_email = htmlspecialchars($receiver_data['email'] ?? 'Not available');
        $receiver_contact = htmlspecialchars($receiver_data['contact'] ?? 'Not available');
      ?>
      <div class="chat-header">
        <div class="d-flex align-items-center">
          <span class="back-arrow me-2" id="backToContacts">←</span>
          <span class="mb-0">
            Chat with <?php echo $receiver_name; ?>
          </span>
        </div>
        <div class="call-actions-group">
          <button class="call-btn-header" onclick="startCall('audio')" title="Voice Call">
            <i class="bi bi-telephone-fill"></i>
            <span class="d-none d-sm-inline">Call</span>
          </button>
          <button class="call-btn-header" onclick="startCall('video')" title="Video Call">
            <i class="bi bi-camera-video-fill"></i>
            <span class="d-none d-sm-inline">Video</span>
          </button>
          <button class="info-btn-header" onclick="openProfileModal()" title="Contact Info">
            <i class="bi bi-info-circle-fill"></i>
          </button>
        </div>
      </div>

      <div class="chat-messages-scroll" id="chat-messages">
      </div>

      <div class="chat-input-fixed">
        <form id="chat-form">
          <div class="input-group">
            <input type="text" name="message" id="message" class="form-control" placeholder="Type a message..." required>
            <button type="submit" class="btn btn-primary">Send</button>
          </div>
        </form>
      </div>
      
      <script>
        window.currentReceiverData = {
          name: '<?php echo $receiver_name; ?>',
          avatar: '../<?php echo htmlspecialchars($receiver_pic); ?>',
          email: '<?php echo $receiver_email; ?>',
          contact: '<?php echo $receiver_contact; ?>',
          userId: <?php echo $receiver_id; ?>
        };
      </script>
    <?php else: ?>
      <div class="chat-header">
        <span>Select a conversation</span>
      </div>
      <div class="empty-chat-placeholder">
        <div class="empty-chat-icon">
          <i class="bi bi-chat-dots"></i>
        </div>
        <h3 class="empty-chat-title">Start a Conversation</h3>
        <p class="empty-chat-text">Select a contact from the list to begin messaging</p>
      </div>
    <?php endif; ?>
  </div>

  <!-- Right: Contact Info Sidebar (Desktop Only) -->
  <?php if ($receiver): ?>
  <div class="contact-info-sidebar">
    <div class="contact-info-header">
      <h3 class="contact-info-title">Contact Info</h3>
    </div>
    
    <div class="contact-user-info">
      <img src="../<?php echo htmlspecialchars($receiver_pic); ?>" alt="Profile" class="contact-user-avatar">
      <h3 class="contact-user-name"><?php echo $receiver_name; ?></h3>
      <p class="contact-user-email"><?php echo $receiver_email; ?></p>
    </div>
    
    <div class="contact-actions-list">
      <button class="contact-action-item" onclick="viewFullProfile()">
        <div class="contact-action-icon">
          <i class="bi bi-person-circle"></i>
        </div>
        <span class="contact-action-text">View Profile</span>
      </button>
      
      <button class="contact-action-item" onclick="startCall('audio')">
        <div class="contact-action-icon">
          <i class="bi bi-telephone-fill"></i>
        </div>
        <span class="contact-action-text">Audio Call</span>
      </button>
      
      <button class="contact-action-item" onclick="startCall('video')">
        <div class="contact-action-icon">
          <i class="bi bi-camera-video-fill"></i>
        </div>
        <span class="contact-action-text">Video Call</span>
      </button>
    </div>
  </div>
  <?php endif; ?>
</div>

<script>
document.getElementById('searchInput').addEventListener('input', function () {
  const query = this.value.toLowerCase();
  const contacts = document.querySelectorAll('.contact-item');
  const noResults = document.getElementById('noResults');
  let visibleCount = 0;

  contacts.forEach(contact => {
    const name = contact.getAttribute('data-name');
    const isVisible = name.includes(query);
    contact.style.display = isVisible ? 'block' : 'none';
    if (isVisible) visibleCount++;
  });

  noResults.style.display = visibleCount === 0 ? 'block' : 'none';
});

function openProfileModal() {
  if (window.currentReceiverData) {
    document.getElementById('sidebarProfileName').textContent = window.currentReceiverData.name;
    document.getElementById('sidebarProfileAvatar').src = window.currentReceiverData.avatar;
    document.getElementById('sidebarProfileEmail').textContent = window.currentReceiverData.email;
    document.getElementById('profileSidebar').classList.add('active');
    document.getElementById('profileOverlay').classList.add('active');
  }
}

function closeProfileSidebar() {
  document.getElementById('profileSidebar').classList.remove('active');
  document.getElementById('profileOverlay').classList.remove('active');
}

function viewFullProfile() {
  if (window.currentReceiverData) {
    window.location.href = '../view/view_profile2.php?user_id=' + window.currentReceiverData.userId;
  }
}
</script>

<script>
// ===== FIXED WEBRTC VIDEO & VOICE CALL SYSTEM =====
(function() {
  'use strict';
  
  const currentUserId = <?php echo $sender_id; ?>;
  const receiverId = <?php echo $receiver_id ?: '0'; ?>;
  
  if (!currentUserId) {
    console.log('User not logged in, call system disabled');
    return;
  }

  const rtcConfig = {
    iceServers: [
      { urls: 'stun:stun.l.google.com:19302' },
      { urls: 'stun:stun1.l.google.com:19302' },
      { urls: 'stun:stun2.l.google.com:19302' },
      { urls: 'stun:stun3.l.google.com:19302' },
      { urls: 'stun:stun4.l.google.com:19302' }
    ],
    iceCandidatePoolSize: 10
  };

  let globalPeer = null;
  let globalLocalStream = null;
  let globalRemoteStream = null;
  let globalCurrentCall = null;
  let globalIsMuted = false;
  let globalIsVideoOff = false;
  let peerInitialized = false;
  let incomingCallData = null;
  let callStartTime = null;
  let callTimerInterval = null;
  let currentCallType = 'video';
  let isInCall = false;

  const myPeerId = 'user_' + currentUserId;

  function initializeGlobalPeer() {
    if (peerInitialized) {
      console.log('Peer already initialized');
      return;
    }

    console.log('🌐 Initializing global peer connection with ID:', myPeerId);
    
    globalPeer = new Peer(myPeerId, {
      config: rtcConfig,
      debug: 0  // Reduced debug level
    });

    globalPeer.on('open', (id) => {
      console.log('✅ Global peer connected! My ID:', id);
      peerInitialized = true;
    });

    globalPeer.on('call', (call) => {
      // Only accept incoming calls if not already in a call
      if (isInCall) {
        console.log('🚫 Already in a call, rejecting incoming call');
        call.close();
        return;
      }
      
      console.log('📞 Incoming call from:', call.peer);
      console.log('📞 Call metadata:', call.metadata);
      
      const callerIdMatch = call.peer.match(/^user_(\d+)$/);
      if (!callerIdMatch) {
        console.error('Invalid caller peer ID format:', call.peer);
        return;
      }
      
      const callerId = callerIdMatch[1];
      currentCallType = call.metadata && call.metadata.type ? call.metadata.type : 'video';
      
      console.log('📞 Caller user ID:', callerId);
      console.log('📞 Call type:', currentCallType);
      
      const callerElement = document.querySelector(`[data-contact="${callerId}"]`);
      let callerName = 'Someone';
      let callerAvatar = '../image/man.png';
      
      if (callerElement) {
        const nameElement = callerElement.querySelector('.fw-bold');
        if (nameElement) {
          callerName = nameElement.textContent.trim();
        }
        const profilePic = callerElement.getAttribute('data-profile');
        if (profilePic) {
          callerAvatar = '../' + profilePic;
        }
      }
      
      console.log('📞 Caller name:', callerName);
      
      document.getElementById('globalCallerName').textContent = callerName;
      document.getElementById('globalCallerAvatar').src = callerAvatar;
      document.getElementById('callTypeBadge').textContent = currentCallType === 'audio' ? 'Voice Call' : 'Video Call';
      document.getElementById('callTypeText').textContent = currentCallType === 'audio' ? 'Incoming Voice Call' : 'Incoming Video Call';
      
      const notification = document.getElementById('globalIncomingCall');
      const notificationCard = document.getElementById('callNotificationCard');
      notification.style.display = 'block';
      notificationCard.classList.add('ringing');
      
      console.log('🔔 Showing incoming call notification');
      
      globalCurrentCall = call;
      incomingCallData = { callerId, callerName, callerAvatar, callType: currentCallType };
      
      setTimeout(() => {
        if (globalCurrentCall === call && notification.style.display === 'block') {
          console.log('⏰ Call timeout - auto declining');
          declineGlobalCall();
        }
      }, 30000);
    });

    globalPeer.on('error', (err) => {
      console.error('❌ Global peer error:', err.type, err.message);
      
      if (err.type === 'unavailable-id') {
        console.error('❌ Peer ID already in use. This usually means:');
        console.error('   1. You have multiple tabs open');
        console.error('   2. Previous connection was not properly closed');
        console.error('   Solution: Close other tabs or wait 60 seconds');
        alert('Connection error: This user is already connected in another tab. Please close other tabs and refresh.');
      } else if (err.type === 'peer-unavailable') {
        console.error('❌ Remote peer unavailable:', err.message);
      } else if (err.type === 'network') {
        console.error('❌ Network error:', err.message);
        alert('Network error. Please check your internet connection.');
      }
    });

    globalPeer.on('disconnected', () => {
      console.log('⚠️ Global peer disconnected');
      peerInitialized = false;
      
      setTimeout(() => {
        if (globalPeer && !globalPeer.destroyed && !isInCall) {
          console.log('🔄 Attempting to reconnect...');
          globalPeer.reconnect();
        }
      }, 3000);
    });

    globalPeer.on('close', () => {
      console.log('🔴 Peer connection closed');
      peerInitialized = false;
    });
  }

  function startCallTimer() {
    callStartTime = Date.now();
    const timerElement = document.getElementById('callTimer');
    timerElement.style.display = 'block';
    
    callTimerInterval = setInterval(() => {
      const elapsed = Math.floor((Date.now() - callStartTime) / 1000);
      const minutes = Math.floor(elapsed / 60);
      const seconds = elapsed % 60;
      timerElement.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    }, 1000);
  }

  function stopCallTimer() {
    if (callTimerInterval) {
      clearInterval(callTimerInterval);
      callTimerInterval = null;
    }
    document.getElementById('callTimer').style.display = 'none';
    callStartTime = null;
  }

  window.acceptGlobalCall = async function() {
    const notification = document.getElementById('globalIncomingCall');
    const notificationCard = document.getElementById('callNotificationCard');
    notification.style.display = 'none';
    notificationCard.classList.remove('ringing');

    console.log('✅ Accepting incoming', currentCallType, 'call...');
    isInCall = true;

    try {
      console.log('🎤 Requesting media access...');
      const mediaConstraints = currentCallType === 'audio' 
        ? { 
            video: false, 
            audio: { 
              echoCancellation: true, 
              noiseSuppression: true, 
              autoGainControl: true 
            } 
          }
        : { 
            video: { 
              width: { ideal: 1280 }, 
              height: { ideal: 720 }, 
              facingMode: 'user' 
            }, 
            audio: { 
              echoCancellation: true, 
              noiseSuppression: true, 
              autoGainControl: true 
            } 
          };
      
      globalLocalStream = await navigator.mediaDevices.getUserMedia(mediaConstraints);
      console.log('✅ Got local media stream:', globalLocalStream.getTracks().map(t => t.kind));

      document.getElementById('globalLocalVideo').srcObject = globalLocalStream;
      document.getElementById('globalVideoModal').style.display = 'flex';
      document.getElementById('globalCallStatus').innerHTML = '<div class="pulse-dot"></div><span>Connecting...</span>';

      if (currentCallType === 'audio') {
        document.getElementById('localVideoContainer').style.display = 'none';
        document.getElementById('audioOnlyIndicator').style.display = 'flex';
        document.getElementById('globalVideoBtn').style.display = 'none';
        if (incomingCallData) {
          document.getElementById('audioCallerAvatar').src = incomingCallData.callerAvatar;
          document.getElementById('audioCallerName').textContent = incomingCallData.callerName;
        }
      } else {
        document.getElementById('localVideoContainer').style.display = 'block';
        document.getElementById('audioOnlyIndicator').style.display = 'none';
        document.getElementById('globalVideoBtn').style.display = 'flex';
      }

      console.log('📞 Answering call...');
      globalCurrentCall.answer(globalLocalStream);
      console.log('✅ Call answered with local stream');

      globalCurrentCall.on('stream', (stream) => {
        console.log('✅ Received remote stream:', stream.getTracks().map(t => t.kind));
        globalRemoteStream = stream;
        document.getElementById('globalRemoteVideo').srcObject = stream;
        document.getElementById('globalCallStatus').innerHTML = '<div class="pulse-dot"></div><span>Connected</span>';
        document.getElementById('participantInfo').querySelector('#participantName').textContent = 
          incomingCallData ? incomingCallData.callerName : 'Connected';
        
        startCallTimer();
        
        setTimeout(() => {
          document.getElementById('globalCallStatus').style.display = 'none';
        }, 2000);
      });

      globalCurrentCall.on('close', () => {
        console.log('🔴 Call closed by remote peer');
        endGlobalCall();
      });

      globalCurrentCall.on('error', (err) => {
        console.error('❌ Call error:', err);
        alert('Call connection failed: ' + err.message);
        endGlobalCall();
      });

    } catch (error) {
      console.error('❌ Error accepting call:', error);
      
      if (error.name === 'NotAllowedError') {
        alert('Camera/microphone access denied. Please allow permissions and try again.');
      } else if (error.name === 'NotFoundError') {
        alert('No camera or microphone found on this device.');
      } else if (error.name === 'NotReadableError') {
        alert('Camera/microphone is already in use. Please close other apps and try again.');
      } else {
        alert('Could not accept call: ' + error.message);
      }
      endGlobalCall();
    }
  };

  window.declineGlobalCall = function() {
    console.log('❌ Declining call');
    const notification = document.getElementById('globalIncomingCall');
    const notificationCard = document.getElementById('callNotificationCard');
    notification.style.display = 'none';
    notificationCard.classList.remove('ringing');
    
    if (globalCurrentCall) {
      globalCurrentCall.close();
      globalCurrentCall = null;
    }
    incomingCallData = null;
  };

  window.endGlobalCall = function() {
    console.log('🔴 Ending call...');
    
    isInCall = false;
    stopCallTimer();
    
    if (globalLocalStream) {
      globalLocalStream.getTracks().forEach(track => {
        track.stop();
        console.log('🛑 Stopped track:', track.kind);
      });
    }

    if (globalCurrentCall) {
      globalCurrentCall.close();
    }

    document.getElementById('globalVideoModal').style.display = 'none';
    document.getElementById('globalLocalVideo').srcObject = null;
    document.getElementById('globalRemoteVideo').srcObject = null;
    document.getElementById('globalCallStatus').style.display = 'block';
    document.getElementById('globalCallStatus').innerHTML = '<div class="pulse-dot"></div><span>Connecting...</span>';
    document.getElementById('localVideoContainer').style.display = 'block';
    document.getElementById('audioOnlyIndicator').style.display = 'none';
    document.getElementById('globalVideoBtn').style.display = 'flex';
    
    globalIsMuted = false;
    globalIsVideoOff = false;
    document.getElementById('globalMuteBtn').classList.remove('active');
    document.getElementById('globalVideoBtn').classList.remove('active');
    document.getElementById('globalMuteBtn').innerHTML = '<i class="bi bi-mic-fill"></i>';
    document.getElementById('globalVideoBtn').innerHTML = '<i class="bi bi-camera-video-fill"></i>';
    
    globalLocalStream = null;
    globalRemoteStream = null;
    globalCurrentCall = null;
    incomingCallData = null;
    currentCallType = 'video';
  };

  window.toggleGlobalMute = function() {
    if (!globalLocalStream) return;
    
    const audioTrack = globalLocalStream.getAudioTracks()[0];
    if (audioTrack) {
      audioTrack.enabled = !audioTrack.enabled;
      globalIsMuted = !audioTrack.enabled;
      
      const muteBtn = document.getElementById('globalMuteBtn');
      if (globalIsMuted) {
        muteBtn.classList.add('active');
        muteBtn.innerHTML = '<i class="bi bi-mic-mute-fill"></i>';
      } else {
        muteBtn.classList.remove('active');
        muteBtn.innerHTML = '<i class="bi bi-mic-fill"></i>';
      }
      console.log('🎤 Audio', globalIsMuted ? 'muted' : 'unmuted');
    }
  };

  window.toggleGlobalVideo = function() {
    if (!globalLocalStream || currentCallType === 'audio') return;
    
    const videoTrack = globalLocalStream.getVideoTracks()[0];
    if (videoTrack) {
      videoTrack.enabled = !videoTrack.enabled;
      globalIsVideoOff = !videoTrack.enabled;
      
      const videoBtn = document.getElementById('globalVideoBtn');
      if (globalIsVideoOff) {
        videoBtn.classList.add('active');
        videoBtn.innerHTML = '<i class="bi bi-camera-video-off-fill"></i>';
      } else {
        videoBtn.classList.remove('active');
        videoBtn.innerHTML = '<i class="bi bi-camera-video-fill"></i>';
      }
      console.log('🎹 Video', globalIsVideoOff ? 'off' : 'on');
    }
  };

  window.startCall = async function(callType = 'video') {
    if (receiverId === 0) {
      alert('Please select a contact first');
      return;
    }

    if (isInCall) {
      alert('Already in a call');
      return;
    }

    currentCallType = callType;
    isInCall = true;
    console.log('📞 Starting', callType, 'call to user:', receiverId);

    try {
      if (!peerInitialized) {
        console.log('⚠️ Peer not initialized, waiting...');
        
        await new Promise((resolve, reject) => {
          const timeout = setTimeout(() => {
            reject(new Error('Peer initialization timeout. Please refresh the page.'));
          }, 10000);
          
          const checkPeer = setInterval(() => {
            if (peerInitialized) {
              clearInterval(checkPeer);
              clearTimeout(timeout);
              resolve();
            }
          }, 100);
        });
      }
      
      console.log('🎤 Requesting media access for', callType, 'call...');
      const mediaConstraints = callType === 'audio' 
        ? { 
            video: false, 
            audio: { 
              echoCancellation: true, 
              noiseSuppression: true, 
              autoGainControl: true 
            } 
          }
        : { 
            video: { 
              width: { ideal: 1280 }, 
              height: { ideal: 720 }, 
              facingMode: 'user' 
            }, 
            audio: { 
              echoCancellation: true, 
              noiseSuppression: true, 
              autoGainControl: true 
            } 
          };
      
      globalLocalStream = await navigator.mediaDevices.getUserMedia(mediaConstraints);
      console.log('✅ Got local media stream:', globalLocalStream.getTracks().map(t => t.kind));

      document.getElementById('globalLocalVideo').srcObject = globalLocalStream;
      document.getElementById('globalVideoModal').style.display = 'flex';
      
      const receiverName = '<?php echo isset($receiver) ? addslashes($receiver["firstname"] . " " . $receiver["lastname"]) : ""; ?>';
      document.getElementById('globalCallStatus').innerHTML = '<div class="pulse-dot"></div><span>Calling ' + receiverName + '...</span>';
      document.getElementById('participantInfo').querySelector('#participantName').textContent = 'Calling ' + receiverName + '...';

      if (callType === 'audio') {
        document.getElementById('localVideoContainer').style.display = 'none';
        document.getElementById('audioOnlyIndicator').style.display = 'flex';
        document.getElementById('globalVideoBtn').style.display = 'none';
        document.getElementById('audioCallerAvatar').src = '../<?php echo htmlspecialchars($receiver_pic ?? "image/man.png"); ?>';
        document.getElementById('audioCallerName').textContent = receiverName;
      } else {
        document.getElementById('localVideoContainer').style.display = 'block';
        document.getElementById('audioOnlyIndicator').style.display = 'none';
        document.getElementById('globalVideoBtn').style.display = 'flex';
      }

      const remotePeerId = 'user_' + receiverId;
      console.log('📞 Calling peer:', remotePeerId);
      
      globalCurrentCall = globalPeer.call(remotePeerId, globalLocalStream, {
        metadata: { type: callType }
      });

      if (!globalCurrentCall) {
        throw new Error('Failed to initiate call');
      }

      console.log('✅ Call initiated');

      const callTimeout = setTimeout(() => {
        const statusText = document.getElementById('globalCallStatus').querySelector('span');
        if (statusText && statusText.textContent.includes('Calling')) {
          console.log('⏰ Call timeout - no answer');
          alert('Call not answered. The other user may not be online or is not accepting calls.');
          endGlobalCall();
        }
      }, 30000);

      globalCurrentCall.on('stream', (stream) => {
        console.log('✅ Received remote stream:', stream.getTracks().map(t => t.kind));
        clearTimeout(callTimeout);
        globalRemoteStream = stream;
        document.getElementById('globalRemoteVideo').srcObject = stream;
        document.getElementById('globalCallStatus').innerHTML = '<div class="pulse-dot"></div><span>Connected</span>';
        document.getElementById('participantInfo').querySelector('#participantName').textContent = receiverName;
        
        startCallTimer();
        
        setTimeout(() => {
          document.getElementById('globalCallStatus').style.display = 'none';
        }, 2000);
      });

      globalCurrentCall.on('close', () => {
        console.log('🔴 Call closed by remote peer');
        clearTimeout(callTimeout);
        endGlobalCall();
      });

      globalCurrentCall.on('error', (err) => {
        console.error('❌ Call error:', err);
        clearTimeout(callTimeout);
        
        if (err.type === 'peer-unavailable') {
          alert('User is not available. They may be offline or not accepting calls.');
        } else {
          alert('Call failed: ' + err.message);
        }
        endGlobalCall();
      });

    } catch (error) {
      console.error('❌ Error in startCall:', error);
      isInCall = false;
      
      if (error.name === 'NotAllowedError') {
        alert('Camera/microphone access denied. Please allow permissions and try again.');
      } else if (error.name === 'NotFoundError') {
        alert('No camera or microphone found on this device.');
      } else if (error.name === 'NotReadableError') {
        alert('Camera/microphone is already in use. Please close other apps and try again.');
      } else {
        alert('Could not start call: ' + error.message);
      }
      endGlobalCall();
    }
  };

  // Only initialize peer when user is on the messages page
  console.log('🚀 Call system ready. Peer will initialize when needed.');
  
  // Initialize peer with a delay to avoid conflicts
  setTimeout(() => {
    if (!peerInitialized) {
      initializeGlobalPeer();
    }
  }, 2000);

  window.addEventListener('beforeunload', () => {
    console.log('🧹 Cleaning up...');
    if (globalLocalStream) {
      globalLocalStream.getTracks().forEach(track => track.stop());
    }
    if (globalPeer && !globalPeer.destroyed) {
      globalPeer.destroy();
    }
  });

})();
</script>

<script>
$(document).ready(function () {
  const receiver_id = <?php echo $receiver_id ?: '0'; ?>;
  let lastMessageId = 0;
  let isLoadingMessages = false;
  let isInitialLoad = true;

  function loadMessages() {
    if (receiver_id === 0 || isLoadingMessages) return;

    isLoadingMessages = true;
    const chatBox = $("#chat-messages");
    const scrollHeight = chatBox[0].scrollHeight;
    const scrollTop = chatBox.scrollTop();
    const clientHeight = chatBox.innerHeight();
    const isScrolledToBottom = scrollHeight - scrollTop - clientHeight < 50;

    $.ajax({
      url: "../controls/load_messages.php",
      type: "GET",
      data: { 
        receiver_id: receiver_id,
        last_message_id: isInitialLoad ? 0 : lastMessageId
      },
      dataType: 'json',
      success: function (data) {
        if (data.messages && data.messages.length > 0) {
          data.messages.forEach(msg => {
            const messageHtml = formatMessage(msg);
            chatBox.append(messageHtml);
            lastMessageId = Math.max(lastMessageId, msg.id);
          });
          
          // Auto scroll on initial load or if user is at bottom
          if (isInitialLoad || isScrolledToBottom) {
            chatBox.scrollTop(chatBox[0].scrollHeight);
          }
          
          isInitialLoad = false;
        }
        isLoadingMessages = false;
      },
      error: function(xhr, status, error) {
        console.error("Error loading messages:", error);
        isLoadingMessages = false;
      }
    });
  }

  function formatMessage(msg) {
    if (msg.is_sent) {
      return `
        <div class="chat-message msg-sent">
          ${msg.message}
          <span class="message-time">${msg.time}</span>
        </div>
      `;
    } else {
      return `
        <div class="chat-message msg-received">
          <span class="sender">${msg.firstname}:</span>
          ${msg.message}
          <span class="message-time">${msg.time}</span>
        </div>
      `;
    }
  }

  // Poll for new messages every 1.5 seconds
  setInterval(loadMessages, 1500);
  
  // Initial load
  loadMessages();

  $("#chat-form").on("submit", function (e) {
    e.preventDefault();
    const message = $("#message").val().trim();
    
    if (message === "") return;
    
    $.ajax({
      url: "../controls/send_message.php",
      type: "POST",
      data: { receiver_id: receiver_id, message: message },
      success: function () {
        $("#message").val("");
        // Immediately check for the new message
        setTimeout(loadMessages, 200);
      },
      error: function(xhr, status, error) {
        console.error("Error sending message:", error);
        alert("Failed to send message. Please try again.");
      }
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

</body>
</html>