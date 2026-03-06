<?php
require_once '../../config/config.php';
if (!isKaryawan()) {
    redirect('../../login.php');
}

$user_id = $_SESSION['user_id'];
$hari_ini = date('Y-m-d');

// Ambil shift hari ini
$stmt_shift = $pdo->prepare("SELECT * FROM jadwal_shift WHERE user_id = ? AND tanggal = ? AND status='aktif'");
$stmt_shift->execute([$user_id, $hari_ini]);
$shift_hari_ini = $stmt_shift->fetch(PDO::FETCH_ASSOC);

include '../layouts/header.php';
include '../layouts/sidebar_karyawan.php';
?>

<!-- Page Content -->
<div id="page-content-wrapper">
    <nav class="top-navbar">
        <div class="d-flex align-items-center">
            <h4 class="mb-0 fw-bold">Dashboard Karyawan</h4>
        </div>
        <div class="user-profile d-flex align-items-center gap-2">
            <?php if(!empty($_SESSION['foto_profil'])): ?>
                <img src="../../uploads/profil/<?= htmlspecialchars($_SESSION['foto_profil']) ?>" class="rounded-circle shadow-sm" style="width: 45px; height: 45px; object-fit: cover; border: 2px solid var(--primary-color);">
            <?php else: ?>
                <div class="rounded-circle text-white d-flex align-items-center justify-content-center shadow-sm" style="width: 45px; height: 45px; background-color: var(--primary-color);">
                    <i class="fas fa-user"></i>
                </div>
            <?php endif; ?>
            <span class="fw-bold d-none d-md-block text-dark ms-1">Halo, <?= htmlspecialchars($_SESSION['nama']) ?></span>
        </div>
    </nav>

    <div class="container-fluid">
        <!-- Notifikasi Shift Hari ini -->
        <?php if($shift_hari_ini): ?>
        <div class="card border-0 shadow-sm mb-4 bg-primary text-white" style="border-radius: 1rem; overflow: hidden;">
            <div class="card-body p-4 d-flex align-items-center position-relative">
                <div class="position-absolute end-0 top-0 h-100" style="width: 150px; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1));"></div>
                <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center me-4 shadow-sm z-1" style="width: 65px; height: 65px;">
                    <i class="fas fa-bell fs-2"></i>
                </div>
                <div class="z-1">
                    <h4 class="fw-bold mb-1">Jadwal Jaga Hari Ini</h4>
                    <p class="mb-0 fs-5"><i class="fas fa-clock me-2"></i> <?= substr($shift_hari_ini['jam_mulai'],0,5) ?> - <?= substr($shift_hari_ini['jam_selesai'],0,5) ?></p>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 1rem; background: linear-gradient(135deg, var(--dark-bg) 0%, #2A2A2A 100%); color: var(--white);">
            <div class="card-body p-4 d-flex align-items-center">
                <div class="bg-secondary text-dark rounded-circle d-flex align-items-center justify-content-center me-4 shadow-sm" style="width: 65px; height: 65px;">
                    <i class="fas fa-mug-hot fs-2"></i>
                </div>
                <div>
                    <h4 class="fw-bold mb-1">Hari Libur Jaga</h4>
                    <p class="mb-0 opacity-75">Anda tidak memiliki jadwal shift hari ini. Selamat beristirahat!</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Quick Access Menu -->
        <h5 class="fw-bold mb-3 text-dark"><i class="fas fa-bolt text-warning me-2"></i>Akses Cepat</h5>
        <div class="row g-4">
            <div class="col-6 col-md-3">
                <a href="absensi.php" class="text-decoration-none">
                    <div class="card h-100 text-center hover-card border-0 shadow-sm" style="border-radius: 1rem;">
                        <div class="card-body p-4">
                            <div class="icon-box bg-primary-subtle text-primary rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 70px; height: 70px; background-color: rgba(255,127,17,0.1);">
                                <i class="fas fa-fingerprint fa-2x"></i>
                            </div>
                            <h6 class="text-dark fw-bold mb-0">Check-In Absen</h6>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="agenda_kosong.php" class="text-decoration-none">
                    <div class="card h-100 text-center hover-card border-0 shadow-sm" style="border-radius: 1rem;">
                        <div class="card-body p-4">
                            <div class="icon-box text-success rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 70px; height: 70px; background-color: rgba(16,185,129,0.1);">
                                <i class="fas fa-calendar-plus fa-2x"></i>
                            </div>
                            <h6 class="text-dark fw-bold mb-0">Jadwal Kosong</h6>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="tukar_jadwal.php" class="text-decoration-none">
                    <div class="card h-100 text-center hover-card border-0 shadow-sm" style="border-radius: 1rem;">
                        <div class="card-body p-4">
                            <div class="icon-box text-warning rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 70px; height: 70px; background-color: rgba(255,183,3,0.1);">
                                <i class="fas fa-exchange-alt fa-2x"></i>
                            </div>
                            <h6 class="text-dark fw-bold mb-0">Tukar Shift</h6>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="gaji.php" class="text-decoration-none">
                    <div class="card h-100 text-center hover-card border-0 shadow-sm" style="border-radius: 1rem;">
                        <div class="card-body p-4">
                            <div class="icon-box text-info rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 70px; height: 70px; background-color: rgba(13,202,240,0.1);">
                                <i class="fas fa-file-invoice-dollar fa-2x"></i>
                            </div>
                            <h6 class="text-dark fw-bold mb-0">Slip Gaji</h6>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.hover-card {
    transition: transform 0.2s, box-shadow 0.2s;
    border: 1px solid #eee;
}
.hover-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.08);
    border-color: var(--primary-color);
}
</style>

<?php include '../layouts/footer.php'; ?>
