<?php
// 1. Burahin lahat ng session data
$_SESSION = array();

// 2. Patayin ang cookie ng session para siguradong out na talaga
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// 3. I-destroy ang session sa server
session_destroy();

// 4. Redirect sa iyong login page URL
header("Location: /reports/");
exit;
?>
