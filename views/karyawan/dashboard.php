<?php
require_once '../../config/config.php';
if (!isKaryawan()) {
    redirect('../../login.php');
}

$user_id = $_SESSION['user_id'];
$hari_ini = date('Y-m-d');

// Ambil shift hari ini
$stmt_shift = mysqli_execute_query($koneksi, "SELECT * FROM jadwal_shift WHERE user_id = ? AND tanggal = ? AND status='aktif'", [$user_id, $hari_ini]);
$shift_hari_ini = mysqli_fetch_assoc($stmt_shift);

// Kehadiran bulan ini
$bulan_ini = date('m');
$tahun_ini = date('Y');
$stmt_hadir = mysqli_execute_query($koneksi, "SELECT COUNT(*) as total_hadir FROM absensi WHERE user_id = ? AND MONTH(waktu_check_in) = ? AND YEAR(waktu_check_in) = ?", [$user_id, $bulan_ini, $tahun_ini]);
$data_hadir = mysqli_fetch_assoc($stmt_hadir);
$total_hadir = $data_hadir['total_hadir'];

// Nilai performa rata-rata
$stmt_nilai = mysqli_execute_query($koneksi, "SELECT AVG(nilai_kerajinan) as avg_rajin, AVG(nilai_sikap) as avg_sikap FROM penilaian_kinerja WHERE user_id = ?", [$user_id]);
$data_nilai = mysqli_fetch_assoc($stmt_nilai);
$avg_rajin = $data_nilai['avg_rajin'] ? round($data_nilai['avg_rajin']) : 0;
$avg_sikap = $data_nilai['avg_sikap'] ? round($data_nilai['avg_sikap']) : 0;

include '../layouts/header.php';
include '../layouts/sidebar_karyawan.php';
?>

<!-- Page Content -->
<div id="page-content-wrapper" class="bg-light pb-5">

    <!-- Top Navbar modern -->
    <nav class="d-flex justify-content-between align-items-center mb-5 pb-3 border-bottom border-light">
        <div>
            <h4 class="mb-0 fw-bold" style="color: #000; letter-spacing: -0.5px;">Dashboard Karyawan</h4>
        </div>
        <div class="user-profile d-flex align-items-center gap-3">
            <div class="text-end d-none d-md-block">
                <h6 class="mb-0 fw-bold" style="color: #000; font-size: 0.95rem;"><?= htmlspecialchars($_SESSION['nama']) ?></h6>
                <small class="text-muted" style="font-weight: 500; font-size: 0.8rem; letter-spacing: 0.5px;">Karyawan</small>
            </div>
            <?php if(!empty($_SESSION['foto_profil'])): ?>
                <img src="../../uploads/profil/<?= htmlspecialchars($_SESSION['foto_profil']) ?>" class="rounded-circle shadow-sm" style="width: 45px; height: 45px; object-fit: cover; border: 2px solid #F99451;">
            <?php else: ?>
                <div class="rounded-circle text-white d-flex align-items-center justify-content-center shadow-sm" style="width: 45px; height: 45px; background-color: #000; border: 2px solid #F99451;">
                    <i class="fas fa-user"></i>
                </div>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container-fluid px-0">
        
        <!-- Tab & Action Header -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-3">
            <div>
                <h5 class="fw-bolder mb-1" style="color: #000; letter-spacing: -0.5px;">Ringkasan Aktivitas</h5>
                <p class="text-muted mb-0" style="font-size: 0.9rem;">Pantau jadwal, rekap kehadiran, dan riwayat performa.</p>
            </div>
            <div class="d-flex align-items-center gap-2 mt-3 mt-md-0">
                <a href="dashboard.php" class="btn btn-white border shadow-sm d-flex align-items-center justify-content-center bg-white" style="border-radius: 10px; width: 42px; height: 42px; color: #000;" title="Refresh Data">
                    <i class="fas fa-sync-alt"></i>
                </a>
                <a href="absensi.php" class="btn text-white shadow-sm d-flex align-items-center px-4" style="border-radius: 10px; height: 42px; background-color: #000; font-weight: 600; font-size: 0.9rem;">
                    <i class="fas fa-fingerprint me-2" style="color: #F99451;"></i> Check In Sekarang
                </a>
            </div>
        </div>

        <!-- Top Overview Cards -->
        <div class="row g-4 mb-4">
            <!-- Card 1 -->
            <div class="col-xl-6">
                <!-- Status Jaga Card (Black Base) -->
                <div class="card border-0 text-white h-100 position-relative overflow-hidden" style="border-radius: 1.5rem; background-color: #000; box-shadow: 0 15px 35px rgba(0,0,0,0.15);">
                    <!-- Decorative Accents -->
                    <div class="position-absolute" style="width: 250px; height: 250px; background: linear-gradient(135deg, #F99451 0%, rgba(249,148,81,0) 100%); border-radius: 50%; top: -80px; right: -80px; opacity: 0.2; filter: blur(40px);"></div>
                    
                    <div class="card-body p-4 p-xl-5 position-relative z-1 d-flex justify-content-between align-items-center h-100">
                        <div>
                            <h6 class="text-uppercase fw-bold mb-3" style="letter-spacing: 1.5px; color: #F99451; font-size: 0.8rem;">STATUS HARI INI</h6>
                            <?php if($shift_hari_ini): ?>
                                <h1 class="fw-bold mb-2 text-white" style="font-size: 3rem; letter-spacing: -1.5px; line-height: 1;">BERTUGAS</h1>
                                <p class="text-white-50 mb-0 mt-3" style="font-size: 0.95rem;">
                                    Jadwal Shift: <strong class="text-white"><?= substr($shift_hari_ini['jam_mulai'],0,5) ?> - <?= substr($shift_hari_ini['jam_selesai'],0,5) ?> WIB</strong>
                                </p>
                            <?php else: ?>
                                <h1 class="fw-bold mb-2 text-white" style="font-size: 3rem; letter-spacing: -1.5px; line-height: 1;">LIBUR</h1>
                                <p class="text-white-50 mb-0 mt-3" style="font-size: 0.95rem;">Anda tidak memiliki jadwal shift hari ini.</p>
                            <?php endif; ?>
                        </div>
                        <div class="rounded-circle d-flex align-items-center justify-content-center shadow-sm flex-shrink-0" style="width: 80px; height: 80px; background-color: #F99451; color: #000;">
                            <i class="fas <?= $shift_hari_ini ? 'fa-briefcase' : 'fa-bed' ?>" style="font-size: 2.2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 2 -->
            <div class="col-xl-6">
                <!-- Kehadiran Bulanan (White Border Base) -->
                <div class="card border h-100 position-relative" style="border-radius: 1.5rem; background: #fff; border-color: #e5e7eb; box-shadow: 0 10px 30px rgba(0,0,0,0.03);">
                    <!-- Orange Left Bar -->
                    <div class="position-absolute start-0 top-0 h-100" style="width: 8px; background-color: #F99451; border-top-left-radius: 1.5rem; border-bottom-left-radius: 1.5rem;"></div>

                    <div class="card-body p-4 p-xl-5 d-flex justify-content-between align-items-center h-100 ps-5">
                        <div class="ps-2">
                            <h6 class="text-uppercase fw-bold mb-3" style="letter-spacing: 1.5px; color: #64748b; font-size: 0.8rem;">KEHADIRAN BULAN INI</h6>
                            <div class="d-flex align-items-end mb-2">
                                <h1 class="fw-bold mb-0 text-dark" style="font-size: 3.5rem; letter-spacing: -1.5px; line-height: 1;"><?= $total_hadir ?></h1>
                                <span class="ms-2 mb-2 text-muted fw-bold">Hari</span>
                            </div>
                            <p class="text-muted mb-0 mt-2" style="font-size: 0.95rem;">Total absensi tercatat di bulan <?= date('F Y') ?>.</p>
                        </div>
                        <div class="rounded-circle d-flex flex-shrink-0 align-items-center justify-content-center" style="width: 80px; height: 80px; background-color: #f8fafc; color: #000; border: 1px solid #f1f5f9;">
                            <i class="fas fa-calendar-check" style="font-size: 2.2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Row -->
        <div class="row g-4">
            <!-- Donut 1 -->
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 h-100 text-center d-flex flex-column align-items-center justify-content-center p-4 p-xl-5" style="border-radius: 1.5rem; background-color: #fff; box-shadow: 0 10px 30px rgba(0,0,0,0.03);">
                    <h6 class="text-uppercase fw-bold mb-4" style="color: #64748b; letter-spacing: 1px; font-size: 0.8rem;">RATA-RATA KERAJINAN</h6>
                    <div class="donut donut-rajin" style="--percentage: <?= $avg_rajin ?>;">
                        <span class="donut-text"><?= $avg_rajin ?></span>
                    </div>
                    <p class="text-muted mt-4 mb-0" style="font-size: 0.8rem; font-weight: 500;">Berdasarkan riwayat performa.</p>
                </div>
            </div>

            <!-- Donut 2 -->
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 h-100 text-center d-flex flex-column align-items-center justify-content-center p-4 p-xl-5" style="border-radius: 1.5rem; background-color: #fff; box-shadow: 0 10px 30px rgba(0,0,0,0.03);">
                    <h6 class="text-uppercase fw-bold mb-4" style="color: #64748b; letter-spacing: 1px; font-size: 0.8rem;">RATA-RATA SIKAP</h6>
                    <div class="donut donut-sikap" style="--percentage: <?= $avg_sikap ?>;">
                        <span class="donut-text"><?= $avg_sikap ?></span>
                    </div>
                    <p class="text-muted mt-4 mb-0" style="font-size: 0.8rem; font-weight: 500;">Berdasarkan riwayat evaluasi.</p>
                </div>
            </div>

            <!-- List Menu -->
            <div class="col-xl-6">
                <div class="card border-0 h-100 p-4 p-xl-5" style="border-radius: 1.5rem; background: #fff; box-shadow: 0 10px 30px rgba(0,0,0,0.03);">
                    <div class="d-flex justify-content-between align-items-center mb-4 pb-2">
                        <h5 class="fw-bolder m-0" style="color: #000; letter-spacing: -0.5px; text-transform: uppercase;">Akses Modul Cepat</h5>
                        <span class="badge bg-light text-dark px-3 py-2 border rounded-pill" style="font-weight: 600;">NAVIGASI</span>
                    </div>
                    
                    <div class="d-flex flex-column gap-3 justify-content-between h-100">
                        <!-- Item 1 -->
                        <a href="absensi.php" class="action-item">
                            <div class="action-icon" style="background-color: #000; color: #F99451;"><i class="fas fa-fingerprint"></i></div>
                            <div class="action-info">
                                <h6 class="text-dark">AUTENTIKASI KEHADIRAN</h6>
                                <p class="text-muted">Catat kehadiran shift & lokasi Anda saat ini</p>
                            </div>
                            <div class="action-go"><i class="fas fa-arrow-right"></i></div>
                        </a>

                        <!-- Item 2 -->
                        <a href="tukar_jadwal.php" class="action-item">
                            <div class="action-icon" style="background-color: rgba(249,148,81,0.15); color: #F99451; border: 1px solid rgba(249,148,81,0.3);"><i class="fas fa-exchange-alt"></i></div>
                            <div class="action-info">
                                <h6 class="text-dark">TUKAR SHIFT</h6>
                                <p class="text-muted">Ajukan dan setujui pergantian jadwal antar rekan</p>
                            </div>
                            <div class="action-go"><i class="fas fa-arrow-right"></i></div>
                        </a>

                        <!-- Item 3 -->
                        <a href="gaji.php" class="action-item">
                            <div class="action-icon" style="background-color: #f8fafc; color: #000; border: 1px solid #e2e8f0;"><i class="fas fa-file-invoice-dollar"></i></div>
                            <div class="action-info">
                                <h6 class="text-dark">SLIP GAJI</h6>
                                <p class="text-muted">Lihat rincian penerimaan dan rekapan gaji</p>
                            </div>
                            <div class="action-go"><i class="fas fa-arrow-right"></i></div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
/* Dashboard Layout overrrides */
body, .bg-light {
    background-color: #F8FAFC !important;
}

/* Donut Chart */
.donut {
    width: 170px;
    height: 170px;
    border-radius: 50%;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}
.donut-rajin {
    background: conic-gradient(#F99451 calc(var(--percentage) * 1%), #f1f5f9 0);
}
.donut-sikap {
    background: conic-gradient(#000000 calc(var(--percentage) * 1%), #f1f5f9 0);
}
.donut::before {
    content: '';
    position: absolute;
    width: 130px;
    height: 130px;
    background: #fff;
    border-radius: 50%;
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
}
.donut-text {
    position: relative;
    z-index: 1;
    font-size: 2.5rem;
    font-weight: 900;
    letter-spacing: -1.5px;
    color: #000;
}
.donut-text small {
    font-size: 1rem;
    font-weight: 800;
    color: #64748b;
    margin-left: 2px;
}

/* Action List */
.action-item {
    display: flex;
    align-items: center;
    padding: 1.25rem;
    border-radius: 1.25rem;
    background: #ffffff;
    text-decoration: none;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    border: 1px solid #f1f5f9;
}
.action-item:hover {
    background: #f8fafc;
    border-color: #e2e8f0;
    box-shadow: 0 10px 20px rgba(0,0,0,0.02);
    transform: translateY(-2px);
}
.action-icon {
    width: 50px;
    height: 50px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    margin-right: 1.5rem;
    transition: transform 0.3s ease;
}
.action-item:hover .action-icon {
    transform: scale(1.05);
}
.action-info {
    flex-grow: 1;
}
.action-info h6 {
    margin: 0 0 0.2rem 0;
    font-weight: 800;
    font-size: 0.9rem;
    letter-spacing: 0.5px;
}
.action-info p {
    margin: 0;
    font-size: 0.85rem;
    font-weight: 500;
}
.action-go {
    color: #cbd5e1;
    font-size: 1.2rem;
    transition: color 0.3s, transform 0.3s;
}
.action-item:hover .action-go {
    color: #F99451;
    transform: translateX(4px);
}
</style>

<?php include '../layouts/footer.php'; ?>

