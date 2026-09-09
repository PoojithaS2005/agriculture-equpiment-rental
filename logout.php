<?php
// 1. Initialize the session
session_start();

// Save the current language preference before clearing session data
$lang = $_SESSION['lang'] ?? 'en';

// 2. Unset all session variables
$_SESSION = array();

// 3. Destroy the session cookie if it exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Destroy the session completely
session_destroy();

// 5. Redirect to login page with persistent language setting
header("Location: login.php?lang=" . urlencode($lang));
exit();
?>