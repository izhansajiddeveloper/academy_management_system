<?php
// Include DB config
require_once __DIR__ . "/../config/db.php";

/**
 * Redirect to a URL and exit
 */
function redirect($url)
{
    header("Location: " . $url);
    exit;
}

/**
 * Check if user is logged in
 */
function isLoggedIn()
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get the logged-in user's ID
 */
function getUserId()
{
    return isLoggedIn() ? $_SESSION['user_id'] : null;
}

/**
 * Get the logged-in user's type
 */
function getUserType()
{
    return isset($_SESSION['user_type']) ? $_SESSION['user_type'] : null;
}

/**
 * Check if user has a specific role
 */
function isRole($role)
{
    return getUserType() === $role;
}

/**
 * Format date in a readable way
 */
function formatDate($date)
{
    if (!$date) return "";
    return date("d M Y", strtotime($date));
}

/**
 * Flash message helper (store in session)
 */
function setFlash($key, $message)
{
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }
    $_SESSION['flash'][$key] = $message;
}

/**
 * Get and clear flash message
 */
function getFlash($key)
{
    if (isset($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}
