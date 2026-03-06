<?php
require_once '../../config/config.php';
if (!isKaryawan()) {
    redirect('../../login.php');
}

$user_id = $_SESSION['user_id'];
$hari_ini = date('Y-m-d');

// 1. Proses Mengajukan Tukar Jadwal
if (isset($_POST['ajukan_tukar'])) {
    $jadwal_saya_id = $_POST['jadwal_saya'];
    $jadwal_teman_id = $_POST['jadwal_teman'];
    $alasan = $_POST['alasan'];

    if ($jadwal_saya_id == $jadwal_teman_id || empty($jadwal_teman_id)) {
        $error = "Pilih jadwal teman yang valid.";
    } else {
        // Cari penerima_id dari jadwal teman
        $stmt_teman = $pdo->prepare("SELECT user_id FROM jadwal_shift WHERE id=?");
        $stmt_teman->execute([$jadwal_teman_id]);
        $teman = $stmt_teman->fetch();

        if ($teman) {
            try {
                // Cek apakah sudah ada pengajuan pending
                $cek = $pdo->prepare("SELECT id FROM tukar_jadwal WHERE (jadwal_pengaju_id=? OR jadwal_penerima_id=?) AND status IN ('pending_karyawan','pending_admin')");
                $cek->execute([$jadwal_saya_id, $jadwal_saya_id]);
                if ($cek->rowCount() > 0) {
                    $error = "Jadwal ini sedang dalam proses tukar yang belum selesai.";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO tukar_jadwal (pengaju_id, penerima_id, jadwal_pengaju_id, jadwal_penerima_id, alasan) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$user_id, $teman['user_id'], $jadwal_saya_id, $jadwal_teman_id, $alasan]);
                    $sukses = "Pengajuan berhasil dikirim! Menunggu konfirmasi teman Anda.";
                }
            } catch(PDOException $e) {
                $error = "Gagal memproses pengajuan. ".$e->getMessage();
            }
        }
    }
}

// 2. Proses Konfirmasi Teman
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $action = $_GET['action'];
    
    // Pastikan ini adalah penerima
    $cek_milik = $pdo->prepare("SELECT * FROM tukar_jadwal WHERE id=? AND penerima_id=? AND status='pending_karyawan'");
    $cek_milik->execute([$id, $user_id]);
    if ($cek_milik->rowCount() > 0) {
        if ($action == 'terima') {
            $pdo->prepare("UPDATE tukar_jadwal SET status='pending_admin' WHERE id=?")->execute([$id]);
            $sukses = "Anda menerima tukar shift. Sekarang menunggu persetujuan Admin.";
            redirect("tukar_jadwal.php?msg=terima");
        } elseif ($action == 'tolak') {
            $pdo->prepare("UPDATE tukar_jadwal SET status='ditolak' WHERE id=?")->execute([$id]);
            $sukses = "Anda menolak permintaan tukar shift.";
            redirect("tukar_jadwal.php?msg=tolak");
        }
    }
}

// Data form dropdown
// Jadwal shift saya yang bisa ditukar (mulai besok ke depan)
$besok = date('Y-m-d', strtotime('+1 day'));
$stmt_saya = $pdo->prepare("SELECT * FROM jadwal_shift WHERE user_id=? AND tanggal >= ? AND status='aktif' ORDER BY tanggal ASC");
$stmt_saya->execute([$user_id, $besok]);
$jadwal_saya = $stmt_saya->fetchAll(PDO::FETCH_ASSOC);

// Jadwal shift karyawan lain yang bisa ditukar (mulai besok ke depan)
$stmt_lain = $pdo->prepare("SELECT j.*, u.nama FROM jadwal_shift j JOIN users u ON j.user_id=u.id WHERE j.user_id!=? AND j.tanggal >= ? AND j.status='aktif' ORDER BY j.tanggal ASC, u.nama ASC");
$stmt_lain->execute([$user_id, $besok]);
$jadwal_lain = $stmt_lain->fetchAll(PDO::FETCH_ASSOC);

// Riwayat Tukar (Sebagai Pengaju)
$stmt_pengaju = $pdo->prepare("
    SELECT t.*, u.nama as nama_penerima, j1.tanggal as tgl_saya, j2.tanggal as tgl_teman 
    FROM tukar_jadwal t 
    JOIN users u ON t.penerima_id = u.id 
    JOIN jadwal_shift j1 ON t.jadwal_pengaju_id = j1.id
    JOIN jadwal_shift j2 ON t.jadwal_penerima_id = j2.id
    WHERE t.pengaju_id=? ORDER BY t.id DESC
");
$stmt_pengaju->execute([$user_id]);
$riwayat_pengaju = $stmt_pengaju->fetchAll(PDO::FETCH_ASSOC);

// Permintaan Tukar (Sebagai Penerima)
$stmt_penerima = $pdo->prepare("
    SELECT t.*, u.nama as nama_pengaju, j1.tanggal as tgl_teman, j2.tanggal as tgl_saya 
    FROM tukar_jadwal t 
    JOIN users u ON t.pengaju_id = u.id 
    JOIN jadwal_shift j1 ON t.jadwal_pengaju_id = j1.id
    JOIN jadwal_shift j2 ON t.jadwal_penerima_id = j2.id
    WHERE t.penerima_id=? ORDER BY t.id DESC
");
$stmt_penerima->execute([$user_id]);
$riwayat_penerima = $stmt_penerima->fetchAll(PDO::FETCH_ASSOC);

include '../layouts/header.php';
include '../layouts/sidebar_karyawan.php';
?>

<div id="page-content-wrapper">
    <nav class="top-navbar">
        <h4 class="mb-0 fw-bold">Tukar Jadwal Shift</h4>
    </nav>

    <div class="container-fluid">
        <?php if(isset($sukses) || isset($_GET['msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Aksi berhasil dijalankan.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if(isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Form Pengajuan -->
            <div class="col-md-5 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white border-0 fw-bold">
                        <i class="fas fa-random me-1"></i> Form Pengajuan Tukar
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-medium text-primary">Shift Anda yang ingin ditukar:</label>
                                <select name="jadwal_saya" class="form-select" required>
                                    <option value="">-- Pilih Shift Saya --</option>
                                    <?php foreach($jadwal_saya as $s): ?>
                                        <option value="<?= $s['id'] ?>">
                                            <?= tgl_indo($s['tanggal']) ?> (<?= substr($s['jam_mulai'],0,5) ?> - <?= substr($s['jam_selesai'],0,5) ?>)
                                        option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="text-center mb-3">
                                <i class="fas fa-exchange-alt fa-2x text-muted"></i>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-medium text-success">Pilih Shift Teman (Target):</label>
                                <select name="jadwal_teman" class="form-select" required>
                                    <option value="">-- Pilih Shift Karyawan Lain --</option>
                                    <?php foreach($jadwal_lain as $l): ?>
                                        <option value="<?= $l['id'] ?>">
                                            <?= htmlspecialchars($l['nama']) ?> - <?= tgl_indo($l['tanggal']) ?> (<?= substr($l['jam_mulai'],0,5) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Alasan Tukar:</label>
                                <textarea name="alasan" class="form-control" rows="2" required placeholder="Jelaskan alasan tukar shift"></textarea>
                            </div>
                            <button type="submit" name="ajukan_tukar" class="btn btn-primary w-100 fw-bold shadow-sm">
                                Ajukan Tukar Shift
                            </button>
                        </form>
                        <small class="text-muted d-block mt-3 text-center">Tukar jadwal shift minimal H-1 sebelum tanggal bertugas.</small>
                    </div>
                </div>
            </div>

            <div class="col-md-7">
                <!-- Tabungan Permintaan (Masuk) -->
                <div class="card shadow-sm border-0 mb-4 border-start border-warning border-4">
                    <div class="card-header bg-white border-0 fw-bold">
                        <i class="fas fa-inbox text-warning me-1"></i> Permintaan Masuk (Dari Teman)
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php if(count($riwayat_penerima) > 0): ?>
                                <?php foreach($riwayat_penerima as $r): ?>
                                    <div class="list-group-item py-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <h6 class="mb-0 fw-bold"><?= htmlspecialchars($r['nama_pengaju']) ?> <span class="badge bg-light text-dark fw-normal border ms-2">Ingin bertukar shift dengan Anda</span></h6>
                                            <small class="text-muted"><?= $r['status'] == 'pending_karyawan' ? 'Baru' : 'Selesai' ?></small>
                                        </div>
                                        <p class="mb-2 text-muted small">Alasan: <?= htmlspecialchars($r['alasan']) ?></p>
                                        <div class="bg-light p-2 rounded mb-2 font-monospace small">
                                            <div class="row text-center align-items-center">
                                                <div class="col-5 text-danger">Shift Anda<br><b><?= tgl_indo($r['tgl_saya']) ?></b></div>
                                                <div class="col-2"><i class="fas fa-arrow-right"></i></div>
                                                <div class="col-5 text-success">Ubah Menjadi<br><b><?= tgl_indo($r['tgl_teman']) ?></b></div>
                                            </div>
                                        </div>
                                        <?php if($r['status'] == 'pending_karyawan'): ?>
                                            <a href="tukar_jadwal.php?action=terima&id=<?= $r['id'] ?>" class="btn btn-sm btn-success px-3 me-2" onclick="return confirm('Yakin SETUJU bertukar shift?');">Setuju</a>
                                            <a href="tukar_jadwal.php?action=tolak&id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-danger px-3">Tolak</a>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?= strtoupper($r['status']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="p-4 text-center text-muted">Belum ada teman yang mengajak tukar shift.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Riwayat Pengajuan (Keluar) -->
                <div class="card shadow-sm border-0 border-start border-info border-4">
                    <div class="card-header bg-white border-0 fw-bold">
                        <i class="fas fa-paper-plane text-info me-1"></i> Pengajuan Saya (Ke Teman)
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 small">
                                <tbody>
                                    <?php if(count($riwayat_pengaju) > 0): ?>
                                        <?php foreach($riwayat_pengaju as $rp): ?>
                                        <tr>
                                            <td>
                                                <b>Ke: <?= htmlspecialchars($rp['nama_penerima']) ?></b><br>
                                                Tgl Saya (<?= date('d/m/y', strtotime($rp['tgl_saya'])) ?>) &rarr; Tgl Teman (<?= date('d/m/y', strtotime($rp['tgl_teman'])) ?>)
                                            </td>
                                            <td class="text-end">
                                                <?php 
                                                    $st = $rp['status'];
                                                    if ($st == 'pending_karyawan') echo '<span class="badge bg-secondary">Menunggu Teman</span>';
                                                    else if ($st == 'pending_admin') echo '<span class="badge bg-warning text-dark">Menunggu Admin</span>';
                                                    else if ($st == 'disetujui') echo '<span class="badge bg-success">Disetujui</span>';
                                                    else echo '<span class="badge bg-danger">Ditolak</span>';
                                                ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="2" class="text-center py-4 text-muted">Belum ada pengajuan.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
