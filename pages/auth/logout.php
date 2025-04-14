<?php
// Start the session if it hasn't been started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set success message
$_SESSION['message'] = 'Вы успешно вышли из системы';
$_SESSION['message_type'] = 'success';

// Destroy the session
session_destroy();

// Use JavaScript for redirect to avoid header issues
?>
<script>
window.location.href = 'index.php?page=login';
</script> 