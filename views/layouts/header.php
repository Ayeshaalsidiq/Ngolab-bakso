<?php
// Pastikan config/config.php di-include di file index.php masing-masing (dikarenakan depth directory berbeda)
// Kita anggap header ini dipanggil dari /views/admin/ atau /views/karyawan/
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HRIS Bakso Mas Yanto</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body class="<?= isset($_SESSION['role']) ? 'role-'.$_SESSION['role'] : '' ?>">
<div id="wrapper">
    <!-- Tombol Toggle Mobile (Disembunyikan di Desktop via CSS) -->
    <button class="btn d-md-none position-absolute top-0 start-0 m-3 z-3" id="menu-toggle" style="background:var(--white);box-shadow:0 1px 3px rgba(0,0,0,0.1);border-radius:0.5rem;border:1px solid var(--border-color);">
        <i class="fas fa-bars"></i>
    </button>
