<?php
require_once '../../config/config.php';
if (!isAdmin()) {
    redirect('../../login.php');
}

// Proses Approval Tukar Jadwal
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $action = $_GET['action']; // 'disetujui' atau 'ditolak'

    if (in_array($action, ['disetujui', 'ditolak'])) {
        try {
            // Ambil detail tukar
            $stmt_tukar = mysqli_execute_query($koneksi, "SELECT * FROM tukar_jadwal WHERE id=? AND status='pending_admin'", [$id]);
            $tukar = mysqli_fetch_assoc($stmt_tukar);

            if ($tukar) {
                // Update status di tabel tukar
                mysqli_execute_query($koneksi, "UPDATE tukar_jadwal SET status=? WHERE id=?", [$action, $id]);
                
                if ($action == 'disetujui') {
                    // Update user_id di jadwal_shift (Swap owner)
                    // jadwal_pengaju menjadi milik penerima
                    mysqli_execute_query($koneksi, "UPDATE jadwal_shift SET user_id=? WHERE id=?", [$tukar['penerima_id'], $tukar['jadwal_pengaju_id']]);
                    // jadwal_penerima menjadi milik pengaju
                    mysqli_execute_query($koneksi, "UPDATE jadwal_shift SET user_id=? WHERE id=?", [$tukar['pengaju_id'], $tukar['jadwal_penerima_id']]);
                }
                
                redirect("tukar.php?msg=$action");
            }
        } catch(Exception $e) {
            $error = "Terjadi kesalahan saat memproses.";
        }
    }
}

// Ambil semua daftar tukar jadwal
$stmt = mysqli_query($koneksi, "
    SELECT t.*, u1.nama as nama_pengaju, u2.nama as nama_penerima, 
           j1.tanggal as tgl_1, j1.jam_mulai as jam1_mulai, j1.jam_selesai as jam1_selesai,
           j2.tanggal as tgl_2, j2.jam_mulai as jam2_mulai, j2.jam_selesai as jam2_selesai 
    FROM tukar_jadwal t 
    JOIN users u1 ON t.pengaju_id = u1.id 
    JOIN users u2 ON t.penerima_id = u2.id
    JOIN jadwal_shift j1 ON t.jadwal_pengaju_id = j1.id
    JOIN jadwal_shift j2 ON t.jadwal_penerima_id = j2.id
    ORDER BY t.id DESC
");
$data_tukar = mysqli_fetch_all($stmt, MYSQLI_ASSOC);

include '../layouts/header.php';
include '../layouts/sidebar_admin.php';
?>

<div id="page-content-wrapper">
    <nav class="top-navbar">
        <h4 class="mb-0 fw-bold">Persetujuan Tukar Jadwal Shift</h4>
    </nav>

    <div class="container-fluid">
        <?php if(isset($_GET['msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Pengajuan tukar shift berhasil <strong><?= htmlspecialchars($_GET['msg']) ?></strong>.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header border-bottom-0 pb-0 pt-4 bg-white">
                <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-exchange-alt text-primary me-2"></i>Histori & Approval Tukar Shift Karyawan</h6>
            </div>
            <div class="card-body px-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">PENGAJU</th>
                                <th>SHIFT PENGAJU</th>
                                <th class="text-center"><i class="fas fa-arrows-alt-h text-muted"></i></th>
                                <th>TARGET SHIFT TEMAN</th>
                                <th>ALASAN</th>
                                <th>STATUS PROSES</th>
                                <th class="pe-4">AKSI ADMIN</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($data_tukar) > 0): ?>
                                <?php foreach($data_tukar as $t): ?>
                                <tr>
                                    <td class="ps-4 fw-medium text-dark"><?= htmlspecialchars($t['nama_pengaju']) ?></td>
                                    <td>
                                        <span class="badge bg-light text-muted border d-block text-start mb-1 fw-normal">
                                            <?= tgl_indo($t['tgl_1']) ?>
                                        </span>
                                        <small class="text-muted"><?= substr($t['jam1_mulai'],0,5) ?> - <?= substr($t['jam1_selesai'],0,5) ?></small>
                                    </td>
                                    <td class="text-center text-muted">&rarr;</td>
                                    <td>
                                        <strong class="d-block text-primary fw-medium"><?= htmlspecialchars($t['nama_penerima']) ?></strong>
                                        <span class="badge bg-light text-muted border d-block text-start mb-1 fw-normal">
                                            <?= tgl_indo($t['tgl_2']) ?>
                                        </span>
                                        <small class="text-muted"><?= substr($t['jam2_mulai'],0,5) ?> - <?= substr($t['jam2_selesai'],0,5) ?></small>
                                    </td>
                                    <td><small class="text-muted"><?= htmlspecialchars($t['alasan']) ?></small></td>
                                    <td>
                                        <?php 
                                            $st = $t['status'];
                                            if ($st == 'pending_karyawan') echo '<span class="badge bg-secondary">Menunggu Karyawan</span>';
                                            else if ($st == 'pending_admin') echo '<span class="badge bg-warning text-dark">Menunggu Admin</span>';
                                            else if ($st == 'disetujui') echo '<span class="badge bg-success">Disetujui</span>';
                                            else echo '<span class="badge bg-danger">Ditolak</span>';
                                        ?>
                                    </td>
                                    <td class="pe-4">
                                        <?php if($t['status'] == 'pending_admin'): ?>
                                            <a href="tukar.php?action=disetujui&id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-success me-1 mb-1" onclick="return confirm('Setuju tukar shift ini? Data jadwal mereka akan di-swap.');" title="Setujui"><i class="fas fa-check"></i></a>
                                            <a href="tukar.php?action=ditolak&id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-danger mb-1" onclick="return confirm('Tolak permintaan tukar ini?');" title="Tolak"><i class="fas fa-times"></i></a>
                                        <?php else: ?>
                                            <span class="text-muted small fw-medium">Selesai/Pending</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">Belum ada riwayat tukar jadwal shift.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
