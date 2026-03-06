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
    $cek = $pdo->prepare("SELECT id FROM jadwal_shift WHERE user_id=? AND tanggal=?");
    $cek->execute([$user_id, $tanggal]);
    if ($cek->rowCount() > 0) {
        $error = "Karyawan tersebut sudah memiliki shift pada tanggal $tanggal.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO jadwal_shift (user_id, tanggal, jam_mulai, jam_selesai, status) VALUES (?, ?, ?, ?, 'aktif')");
            $stmt->execute([$user_id, $tanggal, $jam_mulai, $jam_selesai]);
            $sukses = "Jadwal shift berhasil ditambahkan!";
        } catch(PDOException $e) {
            $error = "Gagal menambah jadwal shift.";
        }
    }
}

// Proses Hapus Shift
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $pdo->prepare("DELETE FROM jadwal_shift WHERE id=?")->execute([$id]);
    redirect('jadwal.php?msg=deleted');
}

// Filter Bulan / Tahun (Default bulan ini)
$filter_bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m');

// Ambil data shift berdasarkan bulan filter
$stmt_shift = $pdo->prepare("
    SELECT j.*, u.nama 
    FROM jadwal_shift j 
    JOIN users u ON j.user_id = u.id 
    WHERE DATE_FORMAT(j.tanggal, '%Y-%m') = ? 
    ORDER BY j.tanggal ASC, j.jam_mulai ASC
");
$stmt_shift->execute([$filter_bulan]);
$jadwal_shift = $stmt_shift->fetchAll(PDO::FETCH_ASSOC);

// Ambil list karyawan untuk dropdown
$karyawan = $pdo->query("SELECT id, nama FROM users WHERE role='karyawan' ORDER BY nama ASC")->fetchAll(PDO::FETCH_ASSOC);

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
            <div class="col-md-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white border-0">
                        <i class="fas fa-calendar-plus me-1"></i> Buat Shift Baru
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label">Karyawan</label>
                                <select name="user_id" class="form-select" required>
                                    <option value="">-- Pilih Karyawan --</option>
                                    <?php foreach($karyawan as $k): ?>
                                        <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tanggal Shift</label>
                                <input type="date" name="tanggal" class="form-control" required min="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="row mb-3">
                                <div class="col-6">
                                    <label class="form-label">Jam Mulai</label>
                                    <input type="time" name="jam_mulai" class="form-control" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Jam Selesai</label>
                                    <input type="time" name="jam_selesai" class="form-control" required>
                                </div>
                            </div>
                            <button type="submit" name="tambah_shift" class="btn btn-primary w-100">
                                Simpan Jadwal
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-calendar-alt me-1"></i> Daftar Shift (<?= date('F Y', strtotime($filter_bulan . '-01')) ?>)</span>
                        <form method="GET" class="d-flex align-items-center gap-2">
                            <input type="month" name="bulan" class="form-control form-control-sm" value="<?= $filter_bulan ?>" required>
                            <button type="submit" class="btn btn-sm btn-outline-secondary">Filter</button>
                        </form>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Tanggal</th>
                                        <th>Karyawan</th>
                                        <th>Waktu Shift</th>
                                        <th>Status</th>
                                        <th class="pe-3">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($jadwal_shift) > 0): ?>
                                        <?php foreach($jadwal_shift as $s): ?>
                                        <tr>
                                            <td class="ps-3 fw-medium">
                                                <?= tgl_indo($s['tanggal']) ?>
                                            </td>
                                            <td><?= htmlspecialchars($s['nama']) ?></td>
                                            <td><span class="badge text-bg-light border"><?= substr($s['jam_mulai'],0,5) ?> - <?= substr($s['jam_selesai'],0,5) ?></span></td>
                                            <td>
                                                <?php 
                                                    $bg = ($s['status'] == 'aktif') ? 'success' : (($s['status'] == 'tukar') ? 'warning' : 'secondary');
                                                ?>
                                                <span class="badge bg-<?= $bg ?>"><?= ucfirst($s['status']) ?></span>
                                            </td>
                                            <td class="pe-3">
                                                <a href="jadwal.php?hapus=<?= $s['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus shift untuk <?= htmlspecialchars($s['nama']) ?>?');" title="Hapus Shift">
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
