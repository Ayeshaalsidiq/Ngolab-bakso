<?php
require_once '../../config/config.php';
if (!isKaryawan()) {
    redirect('../../login.php');
}

$user_id = $_SESSION['user_id'];
$filter_bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m');

// Ambil data shift berdasarkan bulan filter untuk SEMUA karyawan
$stmt_shift = $pdo->prepare("
    SELECT j.*, u.nama 
    FROM jadwal_shift j 
    JOIN users u ON j.user_id = u.id 
    WHERE DATE_FORMAT(j.tanggal, '%Y-%m') = ? 
    ORDER BY j.tanggal ASC, j.jam_mulai ASC
");
$stmt_shift->execute([$filter_bulan]);
$jadwal_shift = $stmt_shift->fetchAll(PDO::FETCH_ASSOC);

include '../layouts/header.php';
include '../layouts/sidebar_karyawan.php';
?>

<div id="page-content-wrapper">
    <nav class="top-navbar">
        <h4 class="mb-0 fw-bold">Jadwal Shift Pegawai</h4>
    </nav>

    <div class="container-fluid">
        <div class="card shadow-sm mb-4 border-0">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-center">
                    <div class="col-auto">
                        <label for="bulan" class="col-form-label fw-medium text-muted">Filter Bulan:</label>
                    </div>
                    <div class="col-auto">
                        <input type="month" id="bulan" name="bulan" class="form-control fw-bold text-primary" value="<?= $filter_bulan ?>" required>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary fw-bold"><i class="fas fa-search me-1"></i> Tampilkan Seluruh Shift</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white pb-0 border-0">
                <i class="fas fa-calendar-alt me-1 text-primary"></i> Daftar Shift Pegawai (<?= date('F Y', strtotime($filter_bulan . '-01')) ?>)
            </div>
            <div class="card-body p-0 mt-2">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">No</th>
                                <th>Tanggal</th>
                                <th>Nama Pegawai</th>
                                <th>Jam Shift</th>
                                <th class="pe-4">Status & Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($jadwal_shift) > 0): ?>
                                <?php $no=1; foreach($jadwal_shift as $s): ?>
                                <?php 
                                    // Highlight jadwalku dan jadwal hari ini
                                    $is_today = ($s['tanggal'] == date('Y-m-d'));
                                    $is_mine = ($s['user_id'] == $user_id);
                                    
                                    $row_class = '';
                                    if ($is_mine && $is_today) $row_class = 'table-warning border-start border-warning border-4';
                                    else if ($is_mine) $row_class = 'bg-primary-subtle border-start border-primary border-4';
                                ?>
                                <tr class="<?= $row_class ?>">
                                    <td class="ps-4"><?= $no++ ?></td>
                                    <td class="fw-medium text-nowrap">
                                        <?= tgl_indo($s['tanggal']) ?>
                                        <?php if($is_today): ?>
                                            <span class="badge bg-danger ms-2 animation-pulse">HARI INI</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-2" style="width: 30px; height: 30px; font-size: 0.8rem;">
                                                <?= strtoupper(substr($s['nama'], 0, 1)) ?>
                                            </div>
                                            <strong class="<?= $is_mine ? 'text-primary' : 'text-dark' ?>">
                                                <?= htmlspecialchars($s['nama']) ?> <?= $is_mine ? '(Anda)' : '' ?>
                                            </strong>
                                        </div>
                                    </td>
                                    <td>
                                        <i class="far fa-clock text-muted me-1"></i>
                                        <span class="font-monospace fs-6 fw-bold"><?= substr($s['jam_mulai'],0,5) ?> - <?= substr($s['jam_selesai'],0,5) ?></span>
                                    </td>
                                    <td class="pe-4">
                                        <?php 
                                            // Badge logic minimalis
                                            $st = $s['status'];
                                            if ($st == 'aktif') $badge = 'bg-success';
                                            else if ($st == 'tukar') $badge = 'bg-warning text-dark';
                                            else if ($st == 'selesai') $badge = 'bg-secondary';
                                            else $badge = 'bg-secondary';
                                        ?>
                                        <span class="badge <?= $badge ?> rounded-pill px-3"><?= strtoupper($st) ?></span>
                                        
                                        <?php if($is_today && $st == 'aktif' && $is_mine): ?>
                                            <a href="absensi.php" class="btn btn-sm btn-outline-primary ms-2 rounded-pill fw-bold">Absen Sekarang</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <div class="d-inline-flex flex-column align-items-center">
                                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mb-3" style="width: 80px; height:80px;">
                                                <i class="fas fa-calendar-times fs-1 opacity-50 text-secondary"></i>
                                            </div>
                                            <h5>Belum ada jadwal shift untuk bulan terpilih.</h5>
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

<style>
@keyframes pulse {
  0% { transform: scale(1); }
  50% { transform: scale(1.05); }
  100% { transform: scale(1); }
}
.animation-pulse {
  animation: pulse 1.5s infinite;
}
</style>

<?php include '../layouts/footer.php'; ?>
