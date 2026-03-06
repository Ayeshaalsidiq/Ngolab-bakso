<?php
require_once '../../config/config.php';
if (!isKaryawan()) {
    redirect('../../login.php');
}

$user_id = $_SESSION['user_id'];

// Ambil riwayat penggajian
$stmt = $pdo->prepare("SELECT * FROM penggajian WHERE user_id=? ORDER BY bulan DESC");
$stmt->execute([$user_id]);
$gaji = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

<div id="page-content-wrapper">
    <nav class="top-navbar">
        <h4 class="mb-0 fw-bold">Slip & Riwayat Gaji</h4>
    </nav>

    <div class="container-fluid">
        <div class="card bg-success text-white shadow-sm border-0 mb-4 p-2">
            <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-center">
                <div class="d-flex align-items-center mb-3 mb-md-0">
                    <div class="bg-white text-success rounded-circle d-flex align-items-center justify-content-center me-3" style="width:60px; height:60px;">
                        <i class="fas fa-wallet fa-2x"></i>
                    </div>
                    <div>
                        <h6 class="text-white-50 text-uppercase ls-1 mb-1">Total Gaji Diterima (Sepanjang Waktu)</h6>
                        <h2 class="mb-0 fw-bold"><?= formatRupiah($total_diterima) ?></h2>
                    </div>
                </div>
                <div class="text-md-end text-center">
                    <small class="opacity-75 d-block mb-1">Total <?= count($gaji) ?> kali pembayaran</small>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                <h5 class="fw-bold mb-0"><i class="fas fa-file-invoice-dollar text-primary me-2"></i>Riwayat Pembayaran Bulanan</h5>
            </div>
            <div class="card-body">
                <?php if(count($gaji) > 0): ?>
                    <div class="row g-4">
                        <?php foreach($gaji as $g): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card border border-light-subtle h-100 hover-card">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="badge bg-primary px-3 py-2 rounded-pill shadow-sm">
                                            <?= date('F Y', strtotime($g['bulan'].'-01')) ?>
                                        </span>
                                        <small class="text-muted"><i class="fas fa-clock me-1"></i> <?= date('d M Y', strtotime($g['tanggal_kirim'])) ?></small>
                                    </div>
                                    <h3 class="fw-bold text-dark mb-4"><?= formatRupiah($g['nominal']) ?></h3>
                                    
                                    <div class="d-grid mt-auto">
                                        <?php if(!empty($g['bukti_transfer'])): ?>
                                        <a href="../../uploads/gaji/<?= $g['bukti_transfer'] ?>" target="_blank" class="btn btn-outline-success border-2 fw-bold d-flex justify-content-center align-items-center gap-2">
                                            <i class="fas fa-download"></i> Unduh Bukti Pembayaran
                                        </a>
                                        <?php else: ?>
                                        <button class="btn btn-light text-muted" disabled>Bukti Tidak Tersedia</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-box-open fa-4x text-muted opacity-25 mb-3"></i>
                        <h5 class="text-muted">Belum ada riwayat penggajian.</h5>
                        <p class="text-muted small">Hubungi Admin SDM jika Anda merasa ada kesalahan.</p>
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
