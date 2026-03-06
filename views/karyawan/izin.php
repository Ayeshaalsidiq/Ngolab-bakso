<?php
require_once '../../config/config.php';
if (!isKaryawan()) {
    redirect('../../login.php');
}

$user_id = $_SESSION['user_id'];

// Proses Pengajuan Izin
if (isset($_POST['ajukan_izin'])) {
    $tanggal_mulai = $_POST['tanggal_mulai'];
    $tanggal_selesai = $_POST['tanggal_selesai'];
    $alasan = $_POST['alasan'];

    // Handle Upload Bukti Izin (Surat Dokter / Foto dll)
    $bukti_izin = "";
    if (isset($_FILES['bukti_izin']) && $_FILES['bukti_izin']['error'] == 0) {
        $ext = pathinfo($_FILES['bukti_izin']['name'], PATHINFO_EXTENSION);
        if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'pdf'])) {
            $newName = "izin_".$user_id."_".time().".".$ext;
            if (move_uploaded_file($_FILES['bukti_izin']['tmp_name'], "../../uploads/izin/".$newName)) {
                $bukti_izin = $newName;
            }
        } else {
            $error = "Format file tidak didukung. Harap upload JPG/PNG/PDF.";
        }
    }

    if (!isset($error)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO izin_karyawan (user_id, tanggal_mulai, tanggal_selesai, alasan, bukti_izin, status) 
                                   VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$user_id, $tanggal_mulai, $tanggal_selesai, $alasan, $bukti_izin]);
            $sukses = "Pengajuan izin berhasil dikirim. Menunggu persetujuan Admin.";
            
            // Jika izin menyangkut jadwal hari ini, ubah status shift sementara menjadi 'tukar' atau dibiarkan saja
            // Biarkan admin yang mem-proses untuk memberikan cuti
        } catch(PDOException $e) {
            $error = "Terjadi kesalahan sistem saat mengajukan izin.";
        }
    }
}

// Ambil Riwayat Izin
$stmt_izin = $pdo->prepare("SELECT * FROM izin_karyawan WHERE user_id = ? ORDER BY id DESC");
$stmt_izin->execute([$user_id]);
$data_izin = $stmt_izin->fetchAll(PDO::FETCH_ASSOC);

include '../layouts/header.php';
include '../layouts/sidebar_karyawan.php';
?>

<div id="page-content-wrapper">
    <nav class="top-navbar">
        <h4 class="mb-0 fw-bold">Pengajuan Izin / Cuti</h4>
        <a href="absensi.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Kembali ke Absensi
        </a>
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

        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-warning text-dark border-0 fw-bold">
                        <i class="fas fa-file-signature me-1"></i> Buat Form Izin Baru
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label">Tanggal Mulai Izin</label>
                                <input type="date" name="tanggal_mulai" class="form-control" required min="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tanggal Selesai Izin</label>
                                <input type="date" name="tanggal_selesai" class="form-control" required min="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Alasan Mendetail</label>
                                <textarea name="alasan" class="form-control" rows="3" required placeholder="Contoh: Sakit tipes dan harus dirawat inap."></textarea>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Surat/Bukti Izin (Opsional)</label>
                                <input type="file" name="bukti_izin" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                                <small class="text-muted">Dapat berupa surat dokter, dll.</small>
                            </div>
                            <button type="submit" name="ajukan_izin" class="btn btn-warning w-100 fw-bold shadow-sm">
                                Submit Izin
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white fw-bold">
                        <i class="fas fa-history me-1 text-secondary"></i> Riwayat Pengajuan Izin
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Periode Izin</th>
                                        <th>Alasan</th>
                                        <th>Bukti</th>
                                        <th>Status Admin</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($data_izin) > 0): ?>
                                        <?php foreach($data_izin as $i): ?>
                                        <tr>
                                            <td class="ps-3 fw-medium">
                                                <?= date('d/m/Y', strtotime($i['tanggal_mulai'])) ?> 
                                                <?php if($i['tanggal_mulai'] != $i['tanggal_selesai']): ?>
                                                    - <?= date('d/m/Y', strtotime($i['tanggal_selesai'])) ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($i['alasan']) ?></td>
                                            <td>
                                                <?php if(!empty($i['bukti_izin'])): ?>
                                                    <a href="../../uploads/izin/<?= $i['bukti_izin'] ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                                        <i class="fas fa-paperclip"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                    if ($i['status'] == 'pending') {
                                                        echo '<span class="badge bg-secondary">Pending</span>';
                                                    } else if ($i['status'] == 'disetujui') {
                                                        echo '<span class="badge bg-success">Disetujui</span>';
                                                    } else {
                                                        echo '<span class="badge bg-danger">Ditolak</span>';
                                                    }
                                                ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">
                                                Belum ada riwayat izin.
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
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
