// notification.js
// Handles Firebase Web Push Notifications

// Import Firebase SDK
import { initializeApp } from "https://www.gstatic.com/firebasejs/10.12.0/firebase-app-compat.js";
import { getMessaging, getToken, onMessage } from "https://www.gstatic.com/firebasejs/10.12.0/firebase-messaging-compat.js";

// ✅ Replace these with your Firebase config
const firebaseConfig = {
  apiKey: "YOUR_API_KEY",
  authDomain: "YOUR_AUTH_DOMAIN",
  projectId: "YOUR_PROJECT_ID",
  messagingSenderId: "YOUR_SENDER_ID",
  appId: "YOUR_APP_ID"
};

// Initialize Firebase
const app = firebase.initializeApp(firebaseConfig);
const messaging = firebase.messaging();

// Ask for permission
function requestNotificationPermission() {
  Notification.requestPermission().then((permission) => {
    if (permission === "granted") {
      console.log("✅ Notification permission granted.");
      getFCMToken();
    } else {
      console.warn("❌ Notification permission denied.");
    }
  });
}

// Get FCM token
function getFCMToken() {
  messaging
    .getToken({ vapidKey: "YOUR_VAPID_KEY_FROM_FIREBASE" })
    .then((currentToken) => {
      if (currentToken) {
        console.log("📩 FCM Token:", currentToken);

        // Optional: send token to your backend
        fetch("save_token.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ token: currentToken })
        });
      } else {
        console.warn("⚠️ No registration token available.");
      }
    })
    .catch((err) => console.error("❌ Token error:", err));
}

// Handle messages while browser is open
messaging.onMessage((payload) => {
  console.log("📬 Message received:", payload);
  const { title, body, icon } = payload.notification;
  new Notification(title, { body, icon });
});

// Auto-run when page loads
document.addEventListener("DOMContentLoaded", requestNotificationPermission);
