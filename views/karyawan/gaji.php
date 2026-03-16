<?php
require_once '../../config/config.php';
if (!isKaryawan()) {
    redirect('../../login.php');
}

$user_id = $_SESSION['user_id'];

// Ambil riwayat penggajian
$stmt = mysqli_execute_query($koneksi, "SELECT * FROM penggajian WHERE user_id=? ORDER BY bulan DESC", [$user_id]);
$gaji = mysqli_fetch_all($stmt, MYSQLI_ASSOC);

function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

// Hitung total pendapatan dari riwayat
$total_diterima = 0;
foreach($gaji as $g) {
    $total_diterima += $g['nominal'];
}

include '../layouts/header.php';
include '../layouts/sidebar_karyawan.php';
?>

<div id="page-content-wrapper" class="bg-light pb-5">
    <nav class="top-navbar mb-4 border-0 shadow-sm" style="border-radius: 1rem; background: #fff;">
        <h4 class="mb-0 fw-bold" style="color: #000; letter-spacing: -0.5px;">Slip & Riwayat Gaji</h4>
    </nav>

    <div class="container-fluid px-0">
        <!-- Hero Summary Card -->
        <div class="card shadow-sm border-0 mb-4" style="border-radius: 1.5rem; background-color: #000; color: #fff; overflow: hidden; position: relative;">
            <!-- Decorative Accent -->
            <div class="position-absolute" style="width: 200px; height: 200px; background-color: #F99451; border-radius: 50%; top: -50px; right: -50px; opacity: 0.1; filter: blur(30px);"></div>
            
            <div class="card-body p-4 p-md-5 d-flex flex-column flex-md-row justify-content-between align-items-center position-relative z-1">
                <div class="d-flex align-items-center mb-3 mb-md-0">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-4" style="width: 70px; height: 70px; background-color: rgba(249, 148, 81, 0.2);">
                        <i class="fas fa-wallet fa-2x" style="color: #F99451;"></i>
                    </div>
                    <div>
                        <h6 class="text-uppercase mb-1" style="color: #9ca3af; letter-spacing: 1px; font-size: 0.85rem;">Total Gaji Diterima (Sepanjang Waktu)</h6>
                        <h1 class="mb-0 fw-bold" style="letter-spacing: -1px;"><?= formatRupiah($total_diterima) ?></h1>
                    </div>
                </div>
                <div class="text-md-end text-center p-3 rounded-4" style="background-color: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);">
                    <span class="d-block text-uppercase fw-bold" style="color: #F99451; font-size: 0.8rem; letter-spacing: 1px;">Frekuensi Pembayaran</span>
                    <span class="fs-4 fw-bold"><?= count($gaji) ?> <small class="text-white-50 fs-6">Kali</small></span>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0" style="border-radius: 1.5rem;">
            <div class="card-header bg-white border-0 py-4 px-4 d-flex align-items-center">
                <i class="fas fa-file-invoice-dollar me-2" style="color: #F99451; font-size: 1.2rem;"></i>
                <h5 class="fw-bold mb-0" style="color: #000;">Riwayat Pembayaran Bulanan</h5>
            </div>
            <div class="card-body p-4 pt-0">
                <?php if(count($gaji) > 0): ?>
                    <div class="row g-4">
                        <?php foreach($gaji as $g): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card border-0 shadow-sm h-100 hover-card" style="border-radius: 1rem; border: 1px solid #f1f5f9 !important; overflow: hidden;">
                                <div class="card-body p-4 d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-start mb-4">
                                        <div class="badge rounded-pill fw-bold" style="background-color: rgba(249,148,81,0.1); color: #F99451; padding: 0.5rem 1rem;">
                                            <?= date('F Y', strtotime($g['bulan'].'-01')) ?>
                                        </div>
                                        <div class="text-end">
                                            <small class="text-muted d-block" style="font-size: 0.7rem;">Diterima pada</small>
                                            <span class="fw-medium text-dark" style="font-size: 0.85rem;"><?= date('d M Y', strtotime($g['tanggal_kirim'])) ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <h6 class="text-muted small mb-1">Nominal Penerimaan</h6>
                                        <h2 class="fw-bold mb-0" style="color: #000; letter-spacing: -0.5px;"><?= formatRupiah($g['nominal']) ?></h2>
                                    </div>
                                    
                                    <div class="d-grid mt-auto pt-3 border-top" style="border-color: #f1f5f9 !important;">
                                        <?php if(!empty($g['bukti_transfer'])): ?>
                                        <a href="../../uploads/gaji/<?= $g['bukti_transfer'] ?>" target="_blank" class="btn d-flex justify-content-center align-items-center fw-bold" style="background-color: #f8fafc; color: #000; border: 1px solid #e2e8f0; border-radius: 0.5rem; transition: background 0.2s;">
                                            <i class="fas fa-download me-2" style="color: #F99451;"></i> Unduh Slip / Bukti
                                        </a>
                                        <?php else: ?>
                                        <button class="btn" style="background-color: #f3f4f6; color: #9ca3af; border: border-radius: 0.5rem; cursor: not-allowed;" disabled>
                                            Bukti Belum Diunggah
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <div class="d-inline-flex flex-column align-items-center justify-content-center p-4">
                            <div class="rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 80px; height:80px; background-color: #f8fafc;">
                                <i class="fas fa-box-open fs-1" style="color: #cbd5e1;"></i>
                            </div>
                            <h5 class="fw-bold mb-1" style="color: #000;">Belum Ada Riwayat</h5>
                            <p class="text-muted">Riwayat penggajian Anda masih kosong.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.ls-1 { letter-spacing: 1px; }
.hover-card { transition: all 0.2s ease-in-out; }
.hover-card:hover { transform: translateY(-5px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; }
</style>

<?php include '../layouts/footer.php'; ?>
