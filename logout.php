<?php
// logout.php
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Clear session cookie
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

// Optional: Clear any additional custom cookies if you have them
setcookie("remember_me", "", time() - 3600, "/"); // if you use remember me feature

// Redirect to login page
header("Location: student/index.php");
exit();
?>