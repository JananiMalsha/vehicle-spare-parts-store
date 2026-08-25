<?php
/**
 * Logout Handler
 * 
 * Clears session data, deletes session cookie, and safely terminates the session.
 */

require_once __DIR__ . '/config/db.php';

// Empty the session array
$_SESSION = [];

// Delete session cookie from client browser if cookies are used
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

// Destroy session on the server
session_destroy();

// Start a fresh session to pass the logout confirmation message
session_start();
$_SESSION['flash_success'] = "You have been logged out successfully.";

// Redirect to login page
header("Location: login.php");
exit;
