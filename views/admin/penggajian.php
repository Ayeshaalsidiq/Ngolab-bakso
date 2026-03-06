<?php
require_once '../../config/config.php';
if (!isAdmin()) {
    redirect('../../login.php');
}

$filter_bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m');

// Proses Bayar Gaji
if (isset($_POST['bayar_gaji'])) {
    $user_id = $_POST['user_id'];
    $nominal = $_POST['nominal'];
    
    // Validasi apakah sudah digaji bulan ini
    $cek = $pdo->prepare("SELECT id FROM penggajian WHERE user_id=? AND bulan=?");
    $cek->execute([$user_id, $filter_bulan]);
    if ($cek->rowCount() > 0) {
        $error = "Karyawan ini sudah menerima gaji untuk bulan $filter_bulan.";
    } else {
        $bukti_transfer = "";
        if (isset($_FILES['bukti_transfer']) && $_FILES['bukti_transfer']['error'] == 0) {
            $ext = pathinfo($_FILES['bukti_transfer']['name'], PATHINFO_EXTENSION);
            if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'pdf'])) {
                $newName = "gaji_".$user_id."_".$filter_bulan."_".time().".".$ext;
                if (move_uploaded_file($_FILES['bukti_transfer']['tmp_name'], "../../uploads/gaji/".$newName)) {
                    $bukti_transfer = $newName;
                }
            } else {
                $error = "Format bukti transfer tidak valid (Harap PDF/JPG/PNG).";
            }
        } else {
            $error = "Bukti transfer wajib diunggah.";
        }

        if (!isset($error)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO penggajian (user_id, bulan, nominal, bukti_transfer) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user_id, $filter_bulan, $nominal, $bukti_transfer]);
                $sukses = "Gaji berhasil dicatat dan bukti transfer dikirim ke karyawan.";
            } catch(PDOException $e) {
                $error = "Terjadi kesalahan sistem saat menyimpan gaji.";
            }
        }
    }
}

// Proses Hapus/Batalkan Gaji
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    // Ambil info file transfer
    $stmt_cek = $pdo->prepare("SELECT bukti_transfer FROM penggajian WHERE id=?");
    $stmt_cek->execute([$id]);
    $gaji = $stmt_cek->fetch();
    
    if ($gaji) {
        if (!empty($gaji['bukti_transfer'])) {
            @unlink("../../uploads/gaji/".$gaji['bukti_transfer']);
        }
        $pdo->prepare("DELETE FROM penggajian WHERE id=?")->execute([$id]);
        redirect('penggajian.php?msg=deleted&bulan='.$filter_bulan);
    }
}

// Ambil list karyawan berserta rekapan gaji bulan ini
$stmt_karyawan = $pdo->prepare("
    SELECT u.id, u.nama, g.id as gaji_id, g.nominal, g.bukti_transfer, g.tanggal_kirim 
    FROM users u 
    LEFT JOIN penggajian g ON u.id = g.user_id AND g.bulan = ? 
    WHERE u.role = 'karyawan' 
    ORDER BY u.nama ASC
");
$stmt_karyawan->execute([$filter_bulan]);
$karyawan = $stmt_karyawan->fetchAll(PDO::FETCH_ASSOC);

function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

include '../layouts/header.php';
include '../layouts/sidebar_admin.php';
?>

<div id="page-content-wrapper">
    <nav class="top-navbar">
        <h4 class="mb-0 fw-bold">Manajemen Penggajian Karyawan</h4>
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
                Data gaji berhasil dihapus.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-4 border-0 bg-primary text-white">
            <div class="card-body py-3 d-flex justify-content-between align-items-center flex-wrap">
                <div class="d-flex align-items-center mb-2 mb-md-0">
                    <i class="fas fa-money-check-alt fs-3 me-3 opacity-75"></i>
                    <div>
                        <h5 class="mb-0">Periode Pembayaran: <strong><?= date('F Y', strtotime($filter_bulan . '-01')) ?></strong></h5>
                        <small class="opacity-75">Upload bukti transfer ke masing-masing karyawan</small>
                    </div>
                </div>
                <form method="GET" class="d-flex align-items-center gap-2">
                    <input type="month" name="bulan" class="form-control form-control-sm text-primary fw-bold" value="<?= $filter_bulan ?>" required style="max-width: 150px;">
                    <button type="submit" class="btn btn-sm btn-light text-primary fw-bold">Ubah Periode</button>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Nama Lengkap</th>
                                <th>Status Pembayaran</th>
                                <th>Nominal Ditransfer</th>
                                <th>Bukti Transfer</th>
                                <th>Waktu Kirim</th>
                                <th class="pe-4 text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($karyawan as $k): ?>
                                <?php $sudah_dibayar = !empty($k['gaji_id']); ?>
                            <tr>
                                <td class="ps-4 fw-bold"><?= htmlspecialchars($k['nama']) ?></td>
                                <td>
                                    <?php if($sudah_dibayar): ?>
                                        <span class="badge bg-success"><i class="fas fa-check-double me-1"></i> Lunas Dikirim</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger"><i class="fas fa-hourglass-half me-1"></i> Belum Dibayar</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($sudah_dibayar): ?>
                                        <span class="fw-bold text-success"><?= formatRupiah($k['nominal']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($sudah_dibayar && $k['bukti_transfer']): ?>
                                        <a href="../../uploads/gaji/<?= $k['bukti_transfer'] ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                            <i class="fas fa-receipt me-1"></i> Lihat Resi
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted small">
                                    <?= $sudah_dibayar ? date('d/m/Y H:i', strtotime($k['tanggal_kirim'])) : '-' ?>
                                </td>
                                <td class="pe-4 text-end">
                                    <?php if($sudah_dibayar): ?>
                                        <a href="penggajian.php?hapus=<?= $k['gaji_id'] ?>&bulan=<?= $filter_bulan ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus data gaji ini? Bukti transfer akan ikut terhapus.');">
                                            <i class="fas fa-undo me-1"></i> Batalkan
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-warning fw-bold text-dark" data-bs-toggle="modal" data-bs-target="#bayarModal<?= $k['id'] ?>">
                                            <i class="fas fa-paper-plane me-1"></i> Bayar & Kirim TF
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <?php if(!$sudah_dibayar): ?>
                            <!-- Modal Bayar Gaji -->
                            <div class="modal fade" id="bayarModal<?= $k['id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content border-0 shadow">
                                        <form method="POST" enctype="multipart/form-data">
                                            <div class="modal-header bg-light border-0">
                                                <h5 class="modal-title fw-bold">Transfer Gaji: <?= htmlspecialchars($k['nama']) ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body p-4">
                                                <input type="hidden" name="user_id" value="<?= $k['id'] ?>">
                                                
                                                <div class="mb-4">
                                                    <label class="form-label text-muted fw-bold">Nominal Gaji (Rp)</label>
                                                    <div class="input-group input-group-lg shadow-sm">
                                                        <span class="input-group-text bg-white border-end-0 text-success fw-bold">Rp</span>
                                                        <input type="number" class="form-control border-start-0 ps-0 fw-bold" name="nominal" placeholder="Contoh: 1500000" required>
                                                    </div>
                                                </div>

                                                <div class="mb-2">
                                                    <label class="form-label text-muted fw-bold">Upload Bukti Transfer</label>
                                                    <input type="file" class="form-control form-control-lg" name="bukti_transfer" accept=".jpg,.jpeg,.png,.pdf" required>
                                                    <small class="text-muted mt-2 d-block"><i class="fas fa-info-circle me-1"></i>Karyawan dapat melihat dan mengunduh bukti ini dari dashboard mereka.</small>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-0 bg-light">
                                                <button type="button" class="btn btn-secondary text-white border-0" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" name="bayar_gaji" class="btn btn-success fw-bold px-4 shadow-sm border-0">Simpan & Kirim</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
