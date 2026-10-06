<?php
// 1. Initialize the session
session_start();

// Save the current language preference before clearing session data
$lang = $_SESSION['lang'] ?? 'en';

// 2. Get the logged-in user's ID before clearing the session
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

// 3. Load database connection
if (file_exists('includes/config.php')) {
    include('includes/config.php');
}

// 4. Invalidate the Remember Me token in the database
if ($user_id > 0 && isset($conn)) {
    $clear_token = mysqli_prepare(
        $conn,
        "UPDATE users SET remember_token = NULL WHERE user_id = ?"
    );

    if ($clear_token) {
        mysqli_stmt_bind_param($clear_token, "i", $user_id);
        mysqli_stmt_execute($clear_token);
        mysqli_stmt_close($clear_token);
    }
}

// 5. Delete the Remember Me browser cookie
setcookie(
    'remember_token',
    '',
    [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax'
    ]
);

// 6. Unset all session variables
$_SESSION = array();

// 7. Destroy the session cookie if it exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 8. Destroy the session completely
session_destroy();

// 9. Redirect to login page with persistent language setting
header("Location: login.php?lang=" . urlencode($lang));
exit();
?>
