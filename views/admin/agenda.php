<?php
require_once '../../config/config.php';
if (!isAdmin()) {
    redirect('../../login.php');
}

// Ambil semua data agenda beserta nama user
$stmt = $pdo->query("SELECT k.*, u.nama FROM ketersediaan_karyawan k JOIN users u ON k.user_id = u.id ORDER BY k.hari ASC, u.nama ASC");
$agenda = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Map hari untuk sorting manual di PHP jika dibutuhkan
$hari_order = ['Senin'=>1, 'Selasa'=>2, 'Rabu'=>3, 'Kamis'=>4, 'Jumat'=>5, 'Sabtu'=>6, 'Minggu'=>7];

// Custom Sort by Hari
usort($agenda, function($a, $b) use ($hari_order) {
    if ($hari_order[$a['hari']] == $hari_order[$b['hari']]) return 0;
    return ($hari_order[$a['hari']] < $hari_order[$b['hari']]) ? -1 : 1;
});

include '../layouts/header.php';
include '../layouts/sidebar_admin.php';
?>

<div id="page-content-wrapper">
    <nav class="top-navbar">
        <div class="d-flex align-items-center">
            <h4 class="mb-0 fw-bold">Cek Agenda & Ketersediaan Karyawan</h4>
        </div>
        <a href="jadwal.php" class="btn btn-primary d-none d-md-block">
            <i class="fas fa-calendar-plus me-1"></i> Buat Shift Sekarang
        </a>
    </nav>

    <div class="container-fluid">
        <div class="card bg-info text-white mb-4 border-0 shadow-sm">
            <div class="card-body py-3 d-flex align-items-center">
                <i class="fas fa-info-circle fs-3 me-3 opacity-75"></i>
                <div class="mb-0">
                    Gunakan tabel ini sebagai acuan saat <strong>Menentukan Jadwal Shift</strong>. Pastikan Anda tidak memberikan shift pada jam di luar ketersediaan mereka.
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header border-0 pb-0 bg-white">
                <i class="fas fa-list me-1"></i> Rekap Ketersediaan Mingguan
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Hari</th>
                                <th>Nama Karyawan</th>
                                <th>Jam Tersedia</th>
                                <th>Keterangan Tambahan</th>
                                <th>Bukti (Jika Ada)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($agenda) > 0): ?>
                                <?php foreach($agenda as $a): ?>
                                <tr>
                                    <td class="fw-bold text-primary"><?= $a['hari'] ?></td>
                                    <td class="fw-medium"><?= htmlspecialchars($a['nama']) ?></td>
                                    <td>
                                        <span class="badge bg-secondary font-monospace fs-6">
                                            <?= substr($a['jam_mulai'],0,5) ?> - <?= substr($a['jam_selesai'],0,5) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($a['keterangan']) ?></td>
                                    <td>
                                        <?php if(!empty($a['bukti_kuliah'])): ?>
                                            <a href="../../uploads/bukti_kuliah/<?= $a['bukti_kuliah'] ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                                <i class="fas fa-download me-1"></i> Lihat Dokumen
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted fst-italic small">Tidak dilampirkan</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="fas fa-folder-open mb-3 fs-3"></i><br>
                                        Belum ada karyawan yang memasukkan jadwal kosong.
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

<style>
.badge.bg-secondary {
    background-color: var(--dark-bg) !important;
}
</style>

<?php include '../layouts/footer.php'; ?>
