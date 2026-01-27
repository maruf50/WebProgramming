<?php
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page (located in pages/)
header("Location: pages/loginstudent.php");
exit;
?>