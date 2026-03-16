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
    $cek = mysqli_execute_query($koneksi, "SELECT id FROM penilaian_kinerja WHERE user_id=? AND bulan=?", [$user_id, $filter_bulan]);
    $ada = mysqli_fetch_assoc($cek);

    try {
        if ($ada) {
            mysqli_execute_query($koneksi, "UPDATE penilaian_kinerja SET nilai_kerajinan=?, nilai_sikap=?, catatan=? WHERE id=?", [$nilai_kerajinan, $nilai_sikap, $catatan, $ada['id']]);
            $sukses = "Penilaian berhasil diupdate!";
        } else {
            mysqli_execute_query($koneksi, "INSERT INTO penilaian_kinerja (user_id, bulan, nilai_kerajinan, nilai_sikap, catatan) VALUES (?, ?, ?, ?, ?)", [$user_id, $filter_bulan, $nilai_kerajinan, $nilai_sikap, $catatan]);
            $sukses = "Penilaian berhasil disimpan!";
        }
    } catch(Exception $e) {
        $error = "Terjadi kesalahan sistem saat menyimpan nilai.";
    }
}

// Ambil list karyawan berserta nilai bulan ini (jika ada) menggunakan LEFT JOIN
$stmt_karyawan = mysqli_execute_query($koneksi, "
    SELECT u.id, u.nama, p.id as nilai_id, p.nilai_kerajinan, p.nilai_sikap, p.catatan 
    FROM users u 
    LEFT JOIN penilaian_kinerja p ON u.id = p.user_id AND p.bulan = ? 
    WHERE u.role = 'karyawan' 
    ORDER BY u.nama ASC
", [$filter_bulan]);
$karyawan = mysqli_fetch_all($stmt_karyawan, MYSQLI_ASSOC);

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

        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-center">
                    <div class="col-auto">
                        <label for="bulan" class="col-form-label fw-bold text-muted small">PILIH PERIODE EVALUASI:</label>
                    </div>
                    <div class="col-auto">
                        <input type="month" id="bulan" name="bulan" class="form-control fw-medium text-dark" value="<?= $filter_bulan ?>" required>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-outline-primary"><i class="fas fa-search me-1"></i> Tampilkan</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header border-bottom-0 pb-0 pt-4 bg-white">
                <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-star text-warning me-2"></i>Daftar Kinerja Periode (<?= date('F Y', strtotime($filter_bulan . '-01')) ?>)</h6>
            </div>
            <div class="card-body px-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">KARYAWAN</th>
                                <th>NILAI KERAJINAN</th>
                                <th>NILAI SIKAP</th>
                                <th>RATA-RATA</th>
                                <th>CATATAN EVALUASI</th>
                                <th class="pe-4">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($karyawan as $k): ?>
                                <?php 
                                    $hasScore = !empty($k['nilai_id']);
                                    $avg = $hasScore ? round(($k['nilai_kerajinan'] + $k['nilai_sikap']) / 2, 1) : 0;
                                ?>
                            <tr>
                                <td class="ps-4 fw-medium text-dark"><?= htmlspecialchars($k['nama']) ?></td>
                                <td>
                                    <?php if($hasScore): ?>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $k['nilai_kerajinan'] ?>%;" aria-valuenow="<?= $k['nilai_kerajinan'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <div class="small text-muted mt-1"><?= $k['nilai_kerajinan'] ?>/100</div>
                                    <?php else: ?>
                                        <span class="text-muted small">Belum Dinilai</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($hasScore): ?>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar bg-info" role="progressbar" style="width: <?= $k['nilai_sikap'] ?>%;" aria-valuenow="<?= $k['nilai_sikap'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <div class="small text-muted mt-1"><?= $k['nilai_sikap'] ?>/100</div>
                                    <?php else: ?>
                                        <span class="text-muted small">Belum Dinilai</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($hasScore): ?>
                                        <span class="badge bg-light text-dark border fw-medium"><?= $avg ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
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
                                <td class="pe-4">
                                    <button class="btn btn-sm <?= $hasScore ? 'btn-outline-primary' : 'btn-primary' ?>" data-bs-toggle="modal" data-bs-target="#nilaiModal<?= $k['id'] ?>">
                                        <i class="fas fa-edit me-1"></i> <?= $hasScore ? 'Edit Nilai' : 'Beri Nilai' ?>
                                    </button>
                                </td>
                            </tr>

                            <!-- Modal Penilaian -->
                            <div class="modal fade" id="nilaiModal<?= $k['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 shadow">
                                        <form method="POST">
                                            <div class="modal-header border-bottom-0 pb-0 pt-4">
                                                <h5 class="modal-title fw-bold">Penilaian: <?= htmlspecialchars($k['nama']) ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="user_id" value="<?= $k['id'] ?>">
                                                
                                                <div class="mb-4">
                                                    <label class="form-label d-flex justify-content-between text-muted small fw-bold">
                                                        <span>NILAI KERAJINAN (0-100)</span>
                                                        <span id="label_rajin_<?= $k['id'] ?>" class="text-primary"><?= $hasScore ? $k['nilai_kerajinan'] : 50 ?></span>
                                                    </label>
                                                    <input type="range" class="form-range" name="nilai_kerajinan" min="0" max="100" value="<?= $hasScore ? $k['nilai_kerajinan'] : 50 ?>" oninput="document.getElementById('label_rajin_<?= $k['id'] ?>').innerText=this.value">
                                                    <small class="text-muted">Kerapihan kedatangan, jarang izin/absen, dll.</small>
                                                </div>

                                                <div class="mb-4">
                                                    <label class="form-label d-flex justify-content-between text-muted small fw-bold">
                                                        <span>NILAI SIKAP (0-100)</span>
                                                        <span id="label_sikap_<?= $k['id'] ?>" class="text-info"><?= $hasScore ? $k['nilai_sikap'] : 50 ?></span>
                                                    </label>
                                                    <input type="range" class="form-range" name="nilai_sikap" min="0" max="100" value="<?= $hasScore ? $k['nilai_sikap'] : 50 ?>" oninput="document.getElementById('label_sikap_<?= $k['id'] ?>').innerText=this.value">
                                                    <small class="text-muted">Performa saat jaga, attitude dengan customer, dll.</small>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label text-muted small fw-bold">CATATAN EVALUASI / PESAN KHUSUS</label>
                                                    <textarea class="form-control" name="catatan" rows="3" placeholder="Masukkan catatan..."><?= $hasScore ? htmlspecialchars($k['catatan']) : '' ?></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-top-0 pt-0">
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" name="simpan_nilai" class="btn btn-primary px-4">Simpan Evaluasi</button>
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
