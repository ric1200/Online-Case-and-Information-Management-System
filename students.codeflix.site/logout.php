<?php
// Start the session
session_start();

// Destroy all session data
session_unset();
session_destroy();

// Redirect to login page
header("Location: /index.php"); // replace with your login page URL
exit();
?>