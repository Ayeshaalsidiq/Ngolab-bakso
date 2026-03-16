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
            mysqli_execute_query($koneksi, "UPDATE izin_karyawan SET status=? WHERE id=?", [$action, $id]);
            
            // Jika disetujui, dan hari ini karyawan tersebut punya jadwal aktif, mungkin jadwal dihapus/diubah.
            // Di sini kita catat sekedar pesan sukses
            $sukses = "Izin berhasil di-$action.";
            redirect("izin.php?msg=$action");
        } catch(Exception $e) {
            $error = "Gagal memproses persetujuan.";
        }
    }
}

// Ambil semua data izin
$result = mysqli_query($koneksi, "SELECT i.*, u.nama FROM izin_karyawan i JOIN users u ON i.user_id = u.id ORDER BY i.id DESC");
$data_izin = mysqli_fetch_all($result, MYSQLI_ASSOC);

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

        <div class="card">
            <div class="card-header border-bottom-0 pb-0 pt-4 bg-white">
                <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-tasks me-2 text-primary"></i>Daftar Pengajuan Izin</h6>
            </div>
            <div class="card-body px-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">TANGGAL PENGAJUAN</th>
                                <th>KARYAWAN</th>
                                <th>PERIODE CUTI/IZIN</th>
                                <th>ALASAN</th>
                                <th>BUKTI</th>
                                <th>STATUS</th>
                                <th class="pe-4">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($data_izin) > 0): ?>
                                <?php foreach($data_izin as $i): ?>
                                <tr>
                                    <td class="ps-4 text-muted small fw-medium">
                                        ID-<?= $i['id'] ?>
                                    </td>
                                    <td class="fw-medium text-dark"><?= htmlspecialchars($i['nama']) ?></td>
                                    <td class="text-muted">
                                        <?= date('d/m/Y', strtotime($i['tanggal_mulai'])) ?> 
                                        <?php if($i['tanggal_mulai'] != $i['tanggal_selesai']): ?>
                                            - <?= date('d/m/Y', strtotime($i['tanggal_selesai'])) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-light border text-muted" data-bs-toggle="tooltip" data-bs-placement="top" title="<?= htmlspecialchars($i['alasan']) ?>">
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
                                    <td class="pe-4">
                                        <?php if($i['status'] == 'pending'): ?>
                                            <a href="izin.php?action=disetujui&id=<?= $i['id'] ?>" class="btn btn-sm btn-outline-success me-1" onclick="return confirm('Apakah Anda yakin menyetujui izin ini?');" title="Setujui"><i class="fas fa-check"></i></a>
                                            <a href="izin.php?action=ditolak&id=<?= $i['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Apakah Anda yakin menolak izin ini?');" title="Tolak"><i class="fas fa-times"></i></a>
                                        <?php else: ?>
                                            <span class="text-muted small"><i class="fas fa-check-double"></i> Selesai</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">Belum ada pengajuan izin.</td>
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
