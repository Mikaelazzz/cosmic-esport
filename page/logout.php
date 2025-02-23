<?php
// Mulai session
session_start();

// Hapus semua data session
session_unset();
session_destroy();

// Redirect ke halaman login
header("Location: ../page/login.php");
exit();
?>