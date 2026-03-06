<?php
require_once '../../config/config.php';
if (!isAdmin()) {
    redirect('../../login.php');
}

// Proses Approval Izin
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $action = $_GET['action'];

    if (in_array($action, ['disetujui', 'ditolak'])) {
        try {
            // Update status izin
            $stmt = $pdo->prepare("UPDATE izin_karyawan SET status=? WHERE id=?");
            $stmt->execute([$action, $id]);
            
            // Jika disetujui, dan hari ini karyawan tersebut punya jadwal aktif, mungkin jadwal dihapus/diubah.
            // Di sini kita catat sekedar pesan sukses
            $sukses = "Izin berhasil di-$action.";
            redirect("izin.php?msg=$action");
        } catch(PDOException $e) {
            $error = "Gagal memproses persetujuan.";
        }
    }
}

// Ambil semua data izin
$stmt = $pdo->query("SELECT i.*, u.nama FROM izin_karyawan i JOIN users u ON i.user_id = u.id ORDER BY i.id DESC");
$data_izin = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../layouts/header.php';
include '../layouts/sidebar_admin.php';
?>

<div id="page-content-wrapper">
    <nav class="top-navbar">
        <h4 class="mb-0 fw-bold">Persetujuan Izin Karyawan</h4>
    </nav>

    <div class="container-fluid">
        <?php if(isset($_GET['msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Izin berhasil <strong><?= htmlspecialchars($_GET['msg']) ?></strong>.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white pb-0 border-0">
                <i class="fas fa-tasks text-primary me-1"></i> Daftar Pengajuan Izin
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal Pengajuan</th>
                                <th>Karyawan</th>
                                <th>Periode Cuti/Izin</th>
                                <th>Alasan</th>
                                <th>Bukti</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($data_izin) > 0): ?>
                                <?php foreach($data_izin as $i): ?>
                                <tr>
                                    <td class="text-muted small">
                                        <!-- Simulasi dari ID untuk kesederhanaan, jika ada created_at, gunakan created_at -->
                                        ID-<?= $i['id'] ?>
                                    </td>
                                    <td class="fw-bold text-primary"><?= htmlspecialchars($i['nama']) ?></td>
                                    <td>
                                        <?= date('d/m/Y', strtotime($i['tanggal_mulai'])) ?> 
                                        <?php if($i['tanggal_mulai'] != $i['tanggal_selesai']): ?>
                                            - <?= date('d/m/Y', strtotime($i['tanggal_selesai'])) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-light border" data-bs-toggle="tooltip" data-bs-placement="top" title="<?= htmlspecialchars($i['alasan']) ?>">
                                            Lihat Alasan
                                        </button>
                                    </td>
                                    <td>
                                        <?php if(!empty($i['bukti_izin'])): ?>
                                            <a href="../../uploads/izin/<?= $i['bukti_izin'] ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                                <i class="fas fa-file-download"></i> Bukti
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small fst-italic">Tidak Ada</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                            if ($i['status'] == 'pending') echo '<span class="badge bg-secondary">Pending</span>';
                                            else if ($i['status'] == 'disetujui') echo '<span class="badge bg-success">Disetujui</span>';
                                            else echo '<span class="badge bg-danger">Ditolak</span>';
                                        ?>
                                    </td>
                                    <td>
                                        <?php if($i['status'] == 'pending'): ?>
                                            <a href="izin.php?action=disetujui&id=<?= $i['id'] ?>" class="btn btn-sm btn-success me-1" onclick="return confirm('Apakah Anda yakin menyetujui izin ini?');">Setujui</a>
                                            <a href="izin.php?action=ditolak&id=<?= $i['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin menolak izin ini?');">Tolak</a>
                                        <?php else: ?>
                                            <span class="text-muted small"><i class="fas fa-check"></i> Selesai</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">Belum ada pengajuan izin.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Aktifkan tooltip bootstrap jika diperlukan
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
  return new bootstrap.Tooltip(tooltipTriggerEl)
})
</script>

<?php include '../layouts/footer.php'; ?>
