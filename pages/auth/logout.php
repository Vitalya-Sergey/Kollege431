<?php
// Destroy the session
session_destroy();

// Redirect to login page
header("Location: index.php?page=login");
exit();
?> 