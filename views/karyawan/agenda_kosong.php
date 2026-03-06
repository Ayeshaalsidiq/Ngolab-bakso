<?php
require_once '../../config/config.php';
if (!isKaryawan()) {
    redirect('../../login.php');
}

$user_id = $_SESSION['user_id'];

// Proses Set Agenda Kosong
if (isset($_POST['set_agenda'])) {
    $hari = $_POST['hari'];
    $jam_mulai = $_POST['jam_mulai'];
    $jam_selesai = $_POST['jam_selesai'];
    $keterangan = $_POST['keterangan'];

    // Handle File Upload
    $bukti_kuliah = "";
    if (isset($_FILES['bukti_kuliah']) && $_FILES['bukti_kuliah']['error'] == 0) {
        $allowed = array('jpg', 'jpeg', 'png', 'pdf');
        $filename = $_FILES['bukti_kuliah']['name'];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if (in_array(strtolower($ext), $allowed)) {
            $newName = "bukti_".$user_id."_".time().".".$ext;
            $destination = "../../uploads/bukti_kuliah/".$newName;
            if (move_uploaded_file($_FILES['bukti_kuliah']['tmp_name'], $destination)) {
                $bukti_kuliah = $newName;
            } else {
                $error = "Gagal mengunggah file bukti kuliah.";
            }
        } else {
            $error = "Format file tidak didukung. Harap upload JPG/PNG/PDF.";
        }
    }

    if (!isset($error)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO ketersediaan_karyawan (user_id, hari, jam_mulai, jam_selesai, bukti_kuliah, keterangan) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $hari, $jam_mulai, $jam_selesai, $bukti_kuliah, $keterangan]);
            $sukses = "Jadwal kosong berhasil disubmit. Admin akan memverifikasi ketersediaan Anda.";
        } catch(PDOException $e) {
            $error = "Gagal menyimpan jadwal kosong.";
        }
    }
}

// Proses Hapus Agenda Kosong
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    // Cek kepemilikan
    $stmt_cek = $pdo->prepare("SELECT * FROM ketersediaan_karyawan WHERE id=? AND user_id=?");
    $stmt_cek->execute([$id, $user_id]);
    $data_hapus = $stmt_cek->fetch();
    if ($data_hapus) {
        if (!empty($data_hapus['bukti_kuliah'])) {
            @unlink("../../uploads/bukti_kuliah/".$data_hapus['bukti_kuliah']);
        }
        $pdo->prepare("DELETE FROM ketersediaan_karyawan WHERE id=?")->execute([$id]);
        redirect('agenda_kosong.php?msg=deleted');
    }
}

// Ambil riwayat input jadwal kosong
$stmt_agenda = $pdo->prepare("SELECT * FROM ketersediaan_karyawan WHERE user_id=? ORDER BY id DESC");
$stmt_agenda->execute([$user_id]);
$agenda = $stmt_agenda->fetchAll(PDO::FETCH_ASSOC);

include '../layouts/header.php';
include '../layouts/sidebar_karyawan.php';
?>

<div id="page-content-wrapper">
    <nav class="top-navbar">
        <h4 class="mb-0 fw-bold">Jadwal Kosong / Ketersediaan</h4>
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
        <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                Jadwal ketersediaan berhasil dihapus.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white border-0">
                        <i class="fas fa-plus-circle me-1"></i> Input Agenda Kosong
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label">Hari Kosong</label>
                                <select class="form-select" name="hari" required>
                                    <option value="">-- Pilih Hari --</option>
                                    <option value="Senin">Senin</option>
                                    <option value="Selasa">Selasa</option>
                                    <option value="Rabu">Rabu</option>
                                    <option value="Kamis">Kamis</option>
                                    <option value="Jumat">Jumat</option>
                                    <option value="Sabtu">Sabtu</option>
                                    <option value="Minggu">Minggu</option>
                                </select>
                            </div>
                            <div class="row mb-3">
                                <div class="col-6">
                                    <label class="form-label">Jam Mulai</label>
                                    <input type="time" class="form-control" name="jam_mulai" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Jam Selesai</label>
                                    <input type="time" class="form-control" name="jam_selesai" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Keterangan / Alasan</label>
                                <textarea class="form-control" name="keterangan" rows="2" placeholder="Contoh: Pulang kuliah jam 12, siap jaga siang." required></textarea>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Bukti Jadwal Kuliah (Opsional, PDF/JPG)</label>
                                <input class="form-control" type="file" name="bukti_kuliah" accept=".jpg,.jpeg,.png,.pdf">
                                <small class="text-muted">Upload KRS atau screenshot jadwal kuliah sebagai bukti ke Admin.</small>
                            </div>
                            <button type="submit" name="set_agenda" class="btn btn-primary w-100">
                                Submit Ketersediaan
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <i class="fas fa-list me-1"></i> Riwayat Jadwal ketersediaan Saya
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Hari</th>
                                        <th>Waktu Luang</th>
                                        <th>Keterangan</th>
                                        <th>Bukti</th>
                                        <th class="pe-3">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($agenda) > 0): ?>
                                        <?php foreach($agenda as $a): ?>
                                        <tr>
                                            <td class="ps-3 fw-medium"><?= $a['hari'] ?></td>
                                            <td><span class="badge bg-info text-dark"><?= substr($a['jam_mulai'],0,5) ?> - <?= substr($a['jam_selesai'],0,5) ?></span></td>
                                            <td><?= htmlspecialchars($a['keterangan']) ?></td>
                                            <td>
                                                <?php if(!empty($a['bukti_kuliah'])): ?>
                                                    <a href="../../uploads/bukti_kuliah/<?= $a['bukti_kuliah'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                                                        <i class="fas fa-file-alt"></i> Lihat
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted small">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="pe-3">
                                                <a href="agenda_kosong.php?hapus=<?= $a['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus inputan jadwal ini?');">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">Belum ada data ketersediaan yang Anda inputkan.</td>
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
