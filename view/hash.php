<?php
/**
 * Hash all plain-text passwords in db_servify.users
 */

function hashAllPasswordsInDB(PDO $pdo) {
    // Fetch all users
    $stmt = $pdo->query("SELECT user_id, password FROM users");
    $update = $pdo->prepare("UPDATE users SET password = :hash WHERE user_id = :user_id");

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $user_id = $row['user_id'];
        $pw = $row['password'];

        // Skip if already hashed (bcrypt or Argon2)
        if (preg_match('/^\$2[aby]\$/', $pw) || str_starts_with($pw, '$argon2')) {
            continue;
        }

        // Hash the plain-text password
        $hash = password_hash($pw, PASSWORD_DEFAULT);
        if ($hash === false) {
            throw new RuntimeException("Failed to hash password for user ID {$user_id}");
        }

        // Update the database
        $update->execute([':hash' => $hash, ':user_id' => $user_id]);
    }

    echo "All passwords hashed successfully.\n";
}

// ----------------- Usage -----------------
try {
    // Use 'root' with empty password
    $pdo = new PDO('mysql:host=localhost;dbname=db_servify;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    hashAllPasswordsInDB($pdo);

} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
