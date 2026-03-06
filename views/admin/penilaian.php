<?php
require_once '../../config/config.php';
if (!isAdmin()) {
    redirect('../../login.php');
}

$filter_bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m');

// Proses Simpan/Update Penilaian
if (isset($_POST['simpan_nilai'])) {
    $user_id = $_POST['user_id'];
    $nilai_kerajinan = $_POST['nilai_kerajinan'];
    $nilai_sikap = $_POST['nilai_sikap'];
    $catatan = $_POST['catatan'];

    // Cek apakah sudah ada penilaian bulan ini
    $cek = $pdo->prepare("SELECT id FROM penilaian_kinerja WHERE user_id=? AND bulan=?");
    $cek->execute([$user_id, $filter_bulan]);
    $ada = $cek->fetch();

    try {
        if ($ada) {
            $stmt = $pdo->prepare("UPDATE penilaian_kinerja SET nilai_kerajinan=?, nilai_sikap=?, catatan=? WHERE id=?");
            $stmt->execute([$nilai_kerajinan, $nilai_sikap, $catatan, $ada['id']]);
            $sukses = "Penilaian berhasil diupdate!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO penilaian_kinerja (user_id, bulan, nilai_kerajinan, nilai_sikap, catatan) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $filter_bulan, $nilai_kerajinan, $nilai_sikap, $catatan]);
            $sukses = "Penilaian berhasil disimpan!";
        }
    } catch(PDOException $e) {
        $error = "Terjadi kesalahan sistem saat menyimpan nilai.";
    }
}

// Ambil list karyawan berserta nilai bulan ini (jika ada) menggunakan LEFT JOIN
$stmt_karyawan = $pdo->prepare("
    SELECT u.id, u.nama, p.id as nilai_id, p.nilai_kerajinan, p.nilai_sikap, p.catatan 
    FROM users u 
    LEFT JOIN penilaian_kinerja p ON u.id = p.user_id AND p.bulan = ? 
    WHERE u.role = 'karyawan' 
    ORDER BY u.nama ASC
");
$stmt_karyawan->execute([$filter_bulan]);
$karyawan = $stmt_karyawan->fetchAll(PDO::FETCH_ASSOC);

include '../layouts/header.php';
include '../layouts/sidebar_admin.php';
?>

<div id="page-content-wrapper">
    <nav class="top-navbar">
        <h4 class="mb-0 fw-bold">Evaluasi Kinerja Karyawan</h4>
    </nav>

    <div class="container-fluid">
        <?php if(isset($sukses)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $sukses ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if(isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-center">
                    <div class="col-auto">
                        <label for="bulan" class="col-form-label fw-medium">Pilih Periode Evaluasi:</label>
                    </div>
                    <div class="col-auto">
                        <input type="month" id="bulan" name="bulan" class="form-control fw-bold text-primary" value="<?= $filter_bulan ?>" required>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-outline-primary"><i class="fas fa-search me-1"></i> Tampilkan</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 fw-bold pb-0">
                <i class="fas fa-star text-warning me-1"></i> Daftar Kinerja Periode (<?= date('F Y', strtotime($filter_bulan . '-01')) ?>)
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Karyawan</th>
                                <th>Nilai Kerajinan</th>
                                <th>Nilai Sikap</th>
                                <th>Rata-rata</th>
                                <th>Catatan Evaluasi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($karyawan as $k): ?>
                                <?php 
                                    $hasScore = !empty($k['nilai_id']);
                                    $avg = $hasScore ? round(($k['nilai_kerajinan'] + $k['nilai_sikap']) / 2, 1) : 0;
                                ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($k['nama']) ?></td>
                                <td>
                                    <?php if($hasScore): ?>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-info" role="progressbar" style="width: <?= $k['nilai_kerajinan'] ?>%;" aria-valuenow="<?= $k['nilai_kerajinan'] ?>" aria-valuemin="0" aria-valuemax="100"><?= $k['nilai_kerajinan'] ?></div>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small">Belum Dinilai</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($hasScore): ?>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: <?= $k['nilai_sikap'] ?>%;" aria-valuenow="<?= $k['nilai_sikap'] ?>" aria-valuemin="0" aria-valuemax="100"><?= $k['nilai_sikap'] ?></div>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small">Belum Dinilai</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($hasScore): ?>
                                        <span class="badge bg-primary fs-6"><?= $avg ?></span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($hasScore && $k['catatan']): ?>
                                        <small class="text-muted d-block text-truncate" style="max-width: 150px;" title="<?= htmlspecialchars($k['catatan']) ?>">
                                            <?= htmlspecialchars($k['catatan']) ?>
                                        </small>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm <?= $hasScore ? 'btn-outline-warning' : 'btn-primary' ?>" data-bs-toggle="modal" data-bs-target="#nilaiModal<?= $k['id'] ?>">
                                        <i class="fas fa-edit me-1"></i> <?= $hasScore ? 'Edit Nilai' : 'Beri Nilai' ?>
                                    </button>
                                </td>
                            </tr>

                            <!-- Modal Penilaian -->
                            <div class="modal fade" id="nilaiModal<?= $k['id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Penilaian: <?= htmlspecialchars($k['nama']) ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="user_id" value="<?= $k['id'] ?>">
                                                
                                                <div class="mb-3">
                                                    <label class="form-label d-flex justify-content-between">
                                                        <span>Nilai Kerajinan (0-100)</span>
                                                        <span id="label_rajin_<?= $k['id'] ?>" class="fw-bold text-info"><?= $hasScore ? $k['nilai_kerajinan'] : 50 ?></span>
                                                    </label>
                                                    <input type="range" class="form-range" name="nilai_kerajinan" min="0" max="100" value="<?= $hasScore ? $k['nilai_kerajinan'] : 50 ?>" oninput="document.getElementById('label_rajin_<?= $k['id'] ?>').innerText=this.value">
                                                    <small class="text-muted">Kerapihan kedatangan, jarang izin/absen, dll.</small>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label d-flex justify-content-between">
                                                        <span>Nilai Sikap (0-100)</span>
                                                        <span id="label_sikap_<?= $k['id'] ?>" class="fw-bold text-success"><?= $hasScore ? $k['nilai_sikap'] : 50 ?></span>
                                                    </label>
                                                    <input type="range" class="form-range" name="nilai_sikap" min="0" max="100" value="<?= $hasScore ? $k['nilai_sikap'] : 50 ?>" oninput="document.getElementById('label_sikap_<?= $k['id'] ?>').innerText=this.value">
                                                    <small class="text-muted">Performa saat jaga, attitude dengan customer, dll.</small>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">Catatan Evaluasi / Pesan Khusus</label>
                                                    <textarea class="form-control" name="catatan" rows="3"><?= $hasScore ? htmlspecialchars($k['catatan']) : '' ?></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer text-end">
                                                <button type="button" class="btn btn-secondary text-white" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" name="simpan_nilai" class="btn btn-warning text-dark fw-bold">Simpan Evaluasi</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
