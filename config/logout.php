
<?php

session_start();

// Clear all session variables.
$_SESSION = [];

// Destroy the active session.
session_destroy();

// Redirect the user to the login page.
header("Location: ../user-login.php");
exit;

?>
