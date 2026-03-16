<?php
require_once '../../config/config.php';
if (!isAdmin()) {
    redirect('../../login.php');
}

// Menghitung statistik sederhana
$result_karyawan = mysqli_query($koneksi, "SELECT COUNT(*) FROM users WHERE role='karyawan'");
$total_karyawan = mysqli_fetch_row($result_karyawan)[0];

$result_izin = mysqli_query($koneksi, "SELECT COUNT(*) FROM izin_karyawan WHERE status='pending'");
$izin_pending = mysqli_fetch_row($result_izin)[0];

// Ambil jadwal shift hari ini
$hari_ini = date('Y-m-d');
$result_shift = mysqli_execute_query($koneksi, "SELECT j.*, u.nama FROM jadwal_shift j JOIN users u ON j.user_id = u.id WHERE j.tanggal = ? ORDER BY j.jam_mulai ASC", [$hari_ini]);
$shift_hari_ini = mysqli_fetch_all($result_shift, MYSQLI_ASSOC);

include '../layouts/header.php';
include '../layouts/sidebar_admin.php';
?>

<!-- Page Content -->
<div id="page-content-wrapper">
    <nav class="top-navbar">
        <div class="d-flex align-items-center">
            <h4 class="mb-0 fw-bold">Admin Dashboard</h4>
        </div>
        <div class="user-profile d-flex align-items-center gap-2">
            <?php if(!empty($_SESSION['foto_profil'])): ?>
                <img src="../../uploads/profil/<?= htmlspecialchars($_SESSION['foto_profil']) ?>" class="rounded-circle shadow-sm" style="width: 45px; height: 45px; object-fit: cover; border: 2px solid var(--primary-color);">
            <?php else: ?>
                <div class="rounded-circle text-white d-flex align-items-center justify-content-center shadow-sm" style="width: 45px; height: 45px; background-color: var(--primary-color);">
                    <i class="fas fa-user-shield"></i>
                </div>
            <?php endif; ?>
            <span class="fw-bold d-none d-md-block text-dark ms-1">Halo, <?= htmlspecialchars($_SESSION['nama']) ?></span>
        </div>
    </nav>

    <div class="container-fluid">
        <!-- Statistik -->
        <div class="row mb-4">
            <div class="col-md-6 mb-3 mb-md-0">
                <div class="stat-card primary h-100">
                    <div class="icon-wrapper">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <p>Total Karyawan</p>
                        <h2><?= $total_karyawan ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stat-card warning h-100">
                    <div class="icon-wrapper">
                        <i class="fas fa-envelope-open-text"></i>
                    </div>
                    <div class="stat-info">
                        <p>Izin Pending</p>
                        <h2><?= $izin_pending ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Jadwal Hari Ini -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Jadwal Shift Hari Ini (<?= tgl_indo($hari_ini) ?>)</span>
                <a href="jadwal.php" class="btn btn-sm btn-primary">Kelola Jadwal</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Nama Karyawan</th>
                                <th>Jam Shift</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($shift_hari_ini) > 0): ?>
                                <?php foreach($shift_hari_ini as $s): ?>
                                <tr>
                                    <td class="fw-medium"><?= htmlspecialchars($s['nama']) ?></td>
                                    <td><?= substr($s['jam_mulai'],0,5) ?> - <?= substr($s['jam_selesai'],0,5) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $s['status'] == 'aktif' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($s['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">Tidak ada jadwal shift hari ini</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
