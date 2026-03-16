<?php
require_once '../../config/config.php';
if (!isAdmin()) {
    redirect('../../login.php');
}

// Proses Tambah Shift
if (isset($_POST['tambah_shift'])) {
    $user_id = $_POST['user_id'];
    $tanggal = $_POST['tanggal'];
    $jam_mulai = $_POST['jam_mulai'];
    $jam_selesai = $_POST['jam_selesai'];

    // Validasi apakah karyawan sudah ada shift di tanggal tersebut
    $cek_result = mysqli_execute_query($koneksi, "SELECT id FROM jadwal_shift WHERE user_id=? AND tanggal=?", [$user_id, $tanggal]);
    if (mysqli_num_rows($cek_result) > 0) {
        $error = "Karyawan tersebut sudah memiliki shift pada tanggal $tanggal.";
    } else {
        try {
            mysqli_execute_query($koneksi, "INSERT INTO jadwal_shift (user_id, tanggal, jam_mulai, jam_selesai, status) VALUES (?, ?, ?, ?, 'aktif')", [$user_id, $tanggal, $jam_mulai, $jam_selesai]);
            $sukses = "Jadwal shift berhasil ditambahkan!";
        } catch(Exception $e) {
            $error = "Gagal menambah jadwal shift.";
        }
    }
}

// Proses Hapus Shift
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_execute_query($koneksi, "DELETE FROM jadwal_shift WHERE id=?", [$id]);
    redirect('jadwal.php?msg=deleted');
}

// Filter Bulan / Tahun (Default bulan ini)
$filter_bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m');

// Ambil data shift berdasarkan bulan filter
$result_shift = mysqli_execute_query($koneksi, "
    SELECT j.*, u.nama 
    FROM jadwal_shift j 
    JOIN users u ON j.user_id = u.id 
    WHERE DATE_FORMAT(j.tanggal, '%Y-%m') = ? 
    ORDER BY j.tanggal ASC, j.jam_mulai ASC
", [$filter_bulan]);
$jadwal_shift = mysqli_fetch_all($result_shift, MYSQLI_ASSOC);

// Ambil list karyawan untuk dropdown
$res_kar = mysqli_query($koneksi, "SELECT id, nama FROM users WHERE role='karyawan' ORDER BY nama ASC");
$karyawan = mysqli_fetch_all($res_kar, MYSQLI_ASSOC);

include '../layouts/header.php';
include '../layouts/sidebar_admin.php';
?>

<div id="page-content-wrapper">
    <nav class="top-navbar">
        <h4 class="mb-0 fw-bold">Manajemen Jadwal Shift</h4>
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
                Jadwal shift berhasil dihapus.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
        <div class="row">
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header border-bottom-0 pb-0 pt-4 bg-white">
                        <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-calendar-plus me-2 text-primary"></i>Buat Shift Baru</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">KARYAWAN</label>
                                <select name="user_id" class="form-select" required>
                                    <option value="">-- Pilih Karyawan --</option>
                                    <?php foreach($karyawan as $k): ?>
                                        <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">TANGGAL SHIFT</label>
                                <input type="date" name="tanggal" class="form-control" required min="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="row mb-3">
                                <div class="col-6">
                                    <label class="form-label text-muted small fw-bold">JAM MULAI</label>
                                    <input type="time" name="jam_mulai" class="form-control" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label text-muted small fw-bold">JAM SELESAI</label>
                                    <input type="time" name="jam_selesai" class="form-control" required>
                                </div>
                            </div>
                            <button type="submit" name="tambah_shift" class="btn btn-primary w-100 mt-2">
                                Simpan Jadwal
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                        <span class="fw-bold text-dark"><i class="fas fa-calendar-alt me-2 text-primary"></i>Daftar Shift (<?= date('F Y', strtotime($filter_bulan . '-01')) ?>)</span>
                        <form method="GET" class="d-flex align-items-center gap-2">
                            <input type="month" name="bulan" class="form-control form-control-sm" value="<?= $filter_bulan ?>" required style="max-width: 150px;">
                            <button type="submit" class="btn btn-sm btn-outline-secondary">Filter</button>
                        </form>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">TANGGAL</th>
                                        <th>KARYAWAN</th>
                                        <th>WAKTU SHIFT</th>
                                        <th>STATUS</th>
                                        <th class="pe-4 text-center">AKSI</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($jadwal_shift) > 0): ?>
                                        <?php foreach($jadwal_shift as $s): ?>
                                        <tr>
                                            <td class="ps-4 fw-medium text-dark">
                                                <?= tgl_indo($s['tanggal']) ?>
                                            </td>
                                            <td class="text-muted"><?= htmlspecialchars($s['nama']) ?></td>
                                            <td><span class="badge text-bg-light border text-muted fw-normal"><?= substr($s['jam_mulai'],0,5) ?> - <?= substr($s['jam_selesai'],0,5) ?></span></td>
                                            <td>
                                                <?php 
                                                    $bg = ($s['status'] == 'aktif') ? 'success' : (($s['status'] == 'tukar') ? 'warning' : 'secondary');
                                                ?>
                                                <span class="badge bg-<?= $bg ?>"><?= ucfirst($s['status']) ?></span>
                                            </td>
                                            <td class="pe-4 text-center">
                                                <a href="jadwal.php?hapus=<?= $s['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus shift untuk <?= htmlspecialchars($s['nama']) ?>?');" title="Hapus Shift">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-5 text-muted">
                                                Belum ada jadwal shift untuk bulan terpilih.
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
