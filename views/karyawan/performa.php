<?php
require_once '../../config/config.php';
if (!isKaryawan()) {
    redirect('../../login.php');
}

$user_id = $_SESSION['user_id'];

// Ambil riwayat penilaian kinerja
$stmt = $pdo->prepare("SELECT * FROM penilaian_kinerja WHERE user_id=? ORDER BY bulan DESC");
$stmt->execute([$user_id]);
$performa = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

<div id="page-content-wrapper">
    <nav class="top-navbar">
        <h4 class="mb-0 fw-bold">Laporan Performa & Kinerja</h4>
    </nav>

    <div class="container-fluid">
        <!-- Ringkasan Performa Keseluruhan -->
        <div class="row mb-4">
            <div class="col-md-6 mb-3 mb-md-0">
                <div class="card bg-info text-white shadow-sm border-0 h-100 align-items-center justify-content-center py-4">
                    <div class="d-flex flex-column align-items-center">
                        <h6 class="text-white-50 text-uppercase fw-bold ls-1 mb-2">Rata-rata Kerajinan</h6>
                        <h1 class="display-3 fw-bold mb-0"><?= $rata_kerajinan ?></h1>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-success text-white shadow-sm border-0 h-100 align-items-center justify-content-center py-4">
                    <div class="d-flex flex-column align-items-center">
                        <h6 class="text-white-50 text-uppercase fw-bold ls-1 mb-2">Rata-rata Sikap</h6>
                        <h1 class="display-3 fw-bold mb-0"><?= $rata_sikap ?></h1>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pb-0">
                <h6 class="fw-bold"><i class="fas fa-history text-secondary me-2"></i>Histori Penilaian Bulanan</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Periode Bulan</th>
                                <th style="width: 20%;">Kerajinan</th>
                                <th style="width: 20%;">Sikap</th>
                                <th>Nilai Akhir</th>
                                <th>Catatan Evaluasi (Admin)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($total_data > 0): ?>
                                <?php foreach($performa as $p): ?>
                                    <?php $avg = round(($p['nilai_kerajinan'] + $p['nilai_sikap']) / 2, 1); ?>
                                    <tr>
                                        <td class="fw-bold text-primary"><?= date('F Y', strtotime($p['bulan'].'-01')) ?></td>
                                        <td>
                                            <div class="d-flex justify-content-between small mb-1">
                                                <span><?= $p['nilai_kerajinan'] ?>/100</span>
                                            </div>
                                            <div class="progress" style="height: 10px;">
                                                <div class="progress-bar bg-info" role="progressbar" style="width: <?= $p['nilai_kerajinan'] ?>%;"></div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-between small mb-1">
                                                <span><?= $p['nilai_sikap'] ?>/100</span>
                                            </div>
                                            <div class="progress" style="height: 10px;">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: <?= $p['nilai_sikap'] ?>%;"></div>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-warning text-dark fs-6"><?= $avg ?></span></td>
                                        <td><p class="mb-0 text-muted fst-italic">"<?= htmlspecialchars($p['catatan']) ?>"</p></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">Belum ada penilaian kinerja yang disimpan oleh Admin.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
.ls-1 { letter-spacing: 1px; }
</style>

<?php include '../layouts/footer.php'; ?>
