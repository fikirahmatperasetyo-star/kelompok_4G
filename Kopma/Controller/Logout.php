<?php
// Mulai session untuk bisa mengakses session yang sedang aktif
session_start();

// Kosongkan semua data di dalam session
$_SESSION = [];

// Hancurkan session sepenuhnya dari server
session_destroy();

// Arahkan admin kembali ke halaman halaman Login
header("Location: ../Admin/A1-log-in.php");
exit;
?>