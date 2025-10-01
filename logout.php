<?php
/**
 * Logout Handler
 * Destroys session and redirects to appropriate login page
 */

session_start();

// Store the user type before destroying session
$user_type = $_SESSION['user_type'] ?? '';

// Unset all session variables
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect based on user type
if ($user_type === 'pharmacist') {
    header("Location: pharmacist_login.php?logout=success");
} elseif ($user_type === 'customer') {
    header("Location: customer_login.php?logout=success");
} else {
    header("Location: index.php");
}
exit();
?>
