<?php
require_once '../../config/config.php';
if (!isKaryawan()) {
    redirect('../../login.php');
}

$user_id = $_SESSION['user_id'];

// Ambil riwayat penilaian kinerja
$stmt = mysqli_execute_query($koneksi, "SELECT * FROM penilaian_kinerja WHERE user_id=? ORDER BY bulan DESC", [$user_id]);
$performa = mysqli_fetch_all($stmt, MYSQLI_ASSOC);

// Kalkulasi rata-rata overall jika ada
$rata_kerajinan = 0;
$rata_sikap = 0;
$total_data = count($performa);
if ($total_data > 0) {
    $sum_kerajinan = 0;
    $sum_sikap = 0;
    foreach($performa as $p) {
        $sum_kerajinan += $p['nilai_kerajinan'];
        $sum_sikap += $p['nilai_sikap'];
    }
    $rata_kerajinan = round($sum_kerajinan / $total_data, 1);
    $rata_sikap = round($sum_sikap / $total_data, 1);
}

include '../layouts/header.php';
include '../layouts/sidebar_karyawan.php';
?>

<div id="page-content-wrapper" class="bg-light pb-5">
    <nav class="top-navbar mb-4 border-0 shadow-sm" style="border-radius: 1rem; background: #fff;">
        <h4 class="mb-0 fw-bold" style="color: #000; letter-spacing: -0.5px;">Laporan Performa & Kinerja</h4>
    </nav>

    <div class="container-fluid px-0">
        <!-- Ringkasan Performa Keseluruhan -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <!-- Card Rata-rata Kerajinan -->
                <div class="card border-0 shadow-sm h-100 position-relative overflow-hidden" style="border-radius: 1.5rem; background-color: #000; color: #fff;">
                    <div class="position-absolute" style="width: 150px; height: 150px; background-color: #F99451; border-radius: 50%; top: -30px; right: -30px; opacity: 0.15; filter: blur(20px);"></div>
                    <div class="card-body p-4 p-md-5 d-flex align-items-center position-relative z-1">
                        <div class="rounded-circle d-flex flex-shrink-0 align-items-center justify-content-center me-4" style="width: 70px; height: 70px; background-color: rgba(249, 148, 81, 0.2);">
                            <i class="fas fa-briefcase fa-2x" style="color: #F99451;"></i>
                        </div>
                        <div>
                            <h6 class="text-uppercase mb-1" style="color: #9ca3af; letter-spacing: 1px; font-size: 0.85rem;">Rata-rata Kerajinan</h6>
                            <h1 class="display-4 fw-bold mb-0 text-white" style="letter-spacing: -1px;"><?= $rata_kerajinan ?></h1>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <!-- Card Rata-rata Sikap -->
                <div class="card border-0 shadow-sm h-100 position-relative overflow-hidden" style="border-radius: 1.5rem; background-color: #111111; color: #fff;">
                    <div class="position-absolute" style="width: 150px; height: 150px; background-color: #ffffff; border-radius: 50%; bottom: -30px; right: -30px; opacity: 0.05; filter: blur(20px);"></div>
                    <div class="card-body p-4 p-md-5 d-flex align-items-center position-relative z-1">
                        <div class="rounded-circle d-flex flex-shrink-0 align-items-center justify-content-center me-4" style="width: 70px; height: 70px; background-color: rgba(255, 255, 255, 0.1);">
                            <i class="fas fa-handshake fa-2x" style="color: #fff;"></i>
                        </div>
                        <div>
                            <h6 class="text-uppercase mb-1" style="color: #9ca3af; letter-spacing: 1px; font-size: 0.85rem;">Rata-rata Sikap</h6>
                            <h1 class="display-4 fw-bold mb-0 text-white" style="letter-spacing: -1px;"><?= $rata_sikap ?></h1>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0" style="border-radius: 1.5rem; overflow: hidden;">
            <div class="card-header bg-white border-0 py-4 px-4 d-flex align-items-center">
                <i class="fas fa-star me-2" style="color: #F99451; font-size: 1.2rem;"></i>
                <h5 class="fw-bold mb-0" style="color: #000;">Histori Penilaian Bulanan</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 border-top">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4 text-muted fw-bold" style="font-size: 0.75rem; width: 20%;">PERIODE</th>
                                <th class="text-muted fw-bold" style="font-size: 0.75rem; width: 25%;">KERAJINAN</th>
                                <th class="text-muted fw-bold" style="font-size: 0.75rem; width: 25%;">SIKAP</th>
                                <th class="text-muted fw-bold" style="font-size: 0.75rem; width: 10%;">NILAI AKHIR</th>
                                <th class="pe-4 text-muted fw-bold" style="font-size: 0.75rem; width: 20%;">CATATAN EVALUASI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($total_data > 0): ?>
                                <?php foreach($performa as $p): ?>
                                    <?php $avg = round(($p['nilai_kerajinan'] + $p['nilai_sikap']) / 2, 1); ?>
                                    <tr>
                                        <td class="ps-4 align-middle">
                                            <div class="d-inline-flex align-items-center px-3 py-1 rounded-pill" style="background-color: #f8fafc; border: 1px solid #f1f5f9;">
                                                <i class="far fa-calendar-alt me-2" style="color: #F99451;"></i>
                                                <span class="fw-bold text-dark" style="font-size: 0.9rem;"><?= date('F Y', strtotime($p['bulan'].'-01')) ?></span>
                                            </div>
                                        </td>
                                        <td class="align-middle pe-4">
                                            <div class="d-flex justify-content-between align-items-end mb-2">
                                                <span class="fw-bold text-dark" style="font-size: 0.85rem;"><?= $p['nilai_kerajinan'] ?></span>
                                                <span class="text-muted small">/100</span>
                                            </div>
                                            <div class="progress" style="height: 6px; background-color: #f1f5f9; border-radius: 10px;">
                                                <div class="progress-bar rounded-pill" role="progressbar" style="width: <?= $p['nilai_kerajinan'] ?>%; background-color: #F99451;"></div>
                                            </div>
                                        </td>
                                        <td class="align-middle pe-4">
                                            <div class="d-flex justify-content-between align-items-end mb-2">
                                                <span class="fw-bold text-dark" style="font-size: 0.85rem;"><?= $p['nilai_sikap'] ?></span>
                                                <span class="text-muted small">/100</span>
                                            </div>
                                            <div class="progress" style="height: 6px; background-color: #f1f5f9; border-radius: 10px;">
                                                <div class="progress-bar rounded-pill" role="progressbar" style="width: <?= $p['nilai_sikap'] ?>%; background-color: #000;"></div>
                                            </div>
                                        </td>
                                        <td class="align-middle">
                                            <div class="d-flex align-items-center justify-content-center fw-bold fs-5 rounded-3" style="width: 50px; height: 50px; background-color: #f8fafc; border: 1px solid #e2e8f0; color: #000;">
                                                <?= $avg ?>
                                            </div>
                                        </td>
                                        <td class="pe-4 align-middle">
                                            <div class="p-3 rounded-3" style="background-color: #f8fafc; border-left: 3px solid #e2e8f0;">
                                                <p class="mb-0 text-muted fst-italic mx-0" style="font-size: 0.85rem; line-height: 1.4;">"<?= htmlspecialchars($p['catatan']) ?>"</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5">
                                        <div class="d-inline-flex flex-column align-items-center justify-content-center p-4">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 80px; height:80px; background-color: #f8fafc;">
                                                <i class="fas fa-clipboard-list fs-1" style="color: #cbd5e1;"></i>
                                            </div>
                                            <h5 class="fw-bold mb-1" style="color: #000;">Belum Ada Penilaian</h5>
                                            <p class="text-muted">Riwayat performa Anda masih kosong.</p>
                                        </div>
                                    </td>
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
