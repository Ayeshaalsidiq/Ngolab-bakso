<?php
require_once 'config/config.php';

// Jika belum login, redirect ke halaman login
if (!isLogged()) {
    redirect('login.php');
}

// Redirect sesuai role
if (isAdmin()) {
    redirect('views/admin/dashboard.php');
} else if (isKaryawan()) {
    redirect('views/karyawan/dashboard.php');
} else {
    // Fallback error
    session_destroy();
    redirect('login.php?error=Role tidak valid');
}
?>
