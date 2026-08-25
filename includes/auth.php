<?php
/**
 * Authentication & Authorization Helper Functions
 * 
 * Provides centralized session checks and route protection.
 */

require_once __DIR__ . '/../config/db.php';

/**
 * Check if a user is currently logged in.
 * 
 * @return bool
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if the currently logged-in user is an Administrator.
 * 
 * @return bool
 */
function isAdmin(): bool {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Require a user to be logged in to view a page.
 * If not logged in, redirect them to the login page with a return message.
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        $_SESSION['flash_error'] = "You must be logged in to access that page.";
        header("Location: " . (defined('SITE_URL') ? 'login.php' : 'login.php'));
        exit;
    }
}

/**
 * Require the user to be an Admin.
 * If not an admin, redirect to homepage with an access denied message.
 */
function requireAdmin(): void {
    if (!isAdmin()) {
        $_SESSION['flash_error'] = "Access Denied. Administrator privileges required.";
        header("Location: ../index.php");
        exit;
    }
}

/**
 * Get the current logged-in user's ID.
 * 
 * @return int|null
 */
function currentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}
?>
