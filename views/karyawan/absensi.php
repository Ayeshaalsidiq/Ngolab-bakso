<?php
require_once '../../config/config.php';
if (!isKaryawan()) {
    redirect('../../login.php');
}

$user_id = $_SESSION['user_id'];
$hari_ini = date('Y-m-d');
$waktu_sekarang = date('Y-m-d H:i:s');
$jam_sekarang = date('H:i:s');

// 1. Cek apakah ada jadwal shift aktif hari ini
$stmt_shift = $pdo->prepare("SELECT * FROM jadwal_shift WHERE user_id = ? AND tanggal = ? AND status='aktif'");
$stmt_shift->execute([$user_id, $hari_ini]);
$shift = $stmt_shift->fetch(PDO::FETCH_ASSOC);

$absensi = null;
if ($shift) {
    // Cek record absensi
    $stmt_absen = $pdo->prepare("SELECT * FROM absensi WHERE user_id = ? AND jadwal_id = ?");
    $stmt_absen->execute([$user_id, $shift['id']]);
    $absensi = $stmt_absen->fetch(PDO::FETCH_ASSOC);
}

// Proses Check-In
if (isset($_POST['check_in'])) {
    if ($shift && !$absensi) {
        $bukti_foto = "";
        // Upload Bukti (opsional atau wajib tergantung bisnis, di sini kita buat opsional)
        if (isset($_FILES['bukti_foto']) && $_FILES['bukti_foto']['error'] == 0) {
            $ext = pathinfo($_FILES['bukti_foto']['name'], PATHINFO_EXTENSION);
            if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png'])) {
                $newName = "absenIn_".$user_id."_".time().".".$ext;
                if (move_uploaded_file($_FILES['bukti_foto']['tmp_name'], "../../uploads/absensi/".$newName)) {
                    $bukti_foto = $newName;
                }
            }
        }

        // Tentukan status telat
        // Misal toleransi telat 15 menit
        $batas_telat = date('H:i:s', strtotime($shift['jam_mulai'] . ' +15 minutes'));
        $status_absen = ($jam_sekarang > $batas_telat) ? 'telat' : 'hadir';

        try {
            $stmt = $pdo->prepare("INSERT INTO absensi (user_id, jadwal_id, waktu_check_in, status, bukti_foto) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $shift['id'], $waktu_sekarang, $status_absen, $bukti_foto]);
            $sukses = "Berhasil Check-In pada pukul " . date('H:i');
            
            // Refresh record absensi
            $stmt_absen->execute([$user_id, $shift['id']]);
            $absensi = $stmt_absen->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            $error = "Terjadi kesalahan sistem saat Check-In.";
        }
    } else {
        $error = "Gagal Check-In. Pastikan Anda memiliki shift aktif hari ini.";
    }
}

// Proses Check-Out
if (isset($_POST['check_out'])) {
    if ($shift && $absensi && empty($absensi['waktu_check_out'])) {
        try {
            $stmt = $pdo->prepare("UPDATE absensi SET waktu_check_out = ? WHERE id = ?");
            $stmt->execute([$waktu_sekarang, $absensi['id']]);
            $sukses = "Berhasil Check-Out pada pukul " . date('H:i');
            
            // Set shift selesai
            $pdo->prepare("UPDATE jadwal_shift SET status='selesai' WHERE id=?")->execute([$shift['id']]);

            // Refresh record absensi
            $stmt_absen->execute([$user_id, $shift['id']]);
            $absensi = $stmt_absen->fetch(PDO::FETCH_ASSOC);
            $shift['status'] = 'selesai';
        } catch(PDOException $e) {
            $error = "Terjadi kesalahan sistem saat Check-Out.";
        }
    }
}

include '../layouts/header.php';
include '../layouts/sidebar_karyawan.php';
?>

<div id="page-content-wrapper">
    <nav class="top-navbar">
        <h4 class="mb-0 fw-bold">Autentikasi Kehadiran</h4>
        <div class="h5 mb-0 font-monospace text-primary fw-bold" id="liveClock"><?= date('H:i:s') ?></div>
    </nav>

    <div class="container-fluid">
        <?php if(isset($sukses)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-1"></i> <?= $sukses ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if(isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <!-- Info Shift Hari Ini -->
                <div class="card shadow border-0">
                    <div class="card-body text-center p-5">
                        <h5 class="text-muted mb-4">
                            <?= tgl_indo($hari_ini) ?>
                        </h5>
                        
                        <?php if($shift): ?>
                            <div class="mb-5">
                                <h2 class="fw-bold mb-1">Shift Anda: <?= substr($shift['jam_mulai'],0,5) ?> - <?= substr($shift['jam_selesai'],0,5) ?></h2>
                                <p class="text-muted mb-0">Pastikan check-in sebelum pukul <?= date('H:i', strtotime($shift['jam_mulai'] . ' +15 minutes')) ?> agar tidak dihitung telat.</p>
                            </div>

                            <?php if(!$absensi): ?>
                                <!-- Belum Check In -->
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="mb-4 text-start">
                                        <label class="form-label fw-medium"><i class="fas fa-camera me-1"></i> Foto Selfie Kehadiran (Opsional)</label>
                                        <input type="file" name="bukti_foto" class="form-control" accept="image/*" capture="user">
                                    </div>
                                    <button type="submit" name="check_in" class="btn btn-success btn-lg w-100 rounded-pill py-3 fw-bold fs-5 shadow-sm">
                                        <i class="fas fa-fingerprint me-2"></i> CHECK IN SEKARANG
                                    </button>
                                </form>
                            <?php elseif(empty($absensi['waktu_check_out'])): ?>
                                <!-- Sudah Check In, Belum Check Out -->
                                <div class="alert alert-success mb-5">
                                    <strong>Hadir!</strong> Anda telah Check-In pada pukul <?= date('H:i', strtotime($absensi['waktu_check_in'])) ?>. Status: <?= ucfirst($absensi['status']) ?>.
                                    <?php if($absensi['status'] === 'telat'): ?>
                                    <br><small class="text-danger">Anda check-in melewati batas waktu toleransi.</small>
                                    <?php endif; ?>
                                </div>
                                <form method="POST">
                                    <button type="submit" name="check_out" class="btn btn-danger btn-lg w-100 rounded-pill py-3 fw-bold fs-5 shadow-sm">
                                        <i class="fas fa-sign-out-alt me-2"></i> CHECK OUT PULAANG
                                    </button>
                                </form>
                            <?php else: ?>
                                <!-- Sudah Check Out -->
                                <div class="alert alert-primary p-4 border-0">
                                    <i class="fas fa-check-circle fa-4x text-primary mb-3"></i>
                                    <h4>Anda Selesai Shift Hari Ini</h4>
                                    <p class="mb-1">Check-In: <?= date('H:i', strtotime($absensi['waktu_check_in'])) ?></p>
                                    <p class="mb-0">Check-Out: <?= date('H:i', strtotime($absensi['waktu_check_out'])) ?></p>
                                </div>
                            <?php endif; ?>

                        <?php else: ?>
                            <!-- Tidak ada shift aktif -->
                            <div class="py-4">
                                <i class="fas fa-calendar-times fa-5x text-secondary mb-4 opacity-50"></i>
                                <h3 class="fw-bold text-dark">Tidak Ada Shift Hari Ini</h3>
                                <p class="text-muted">Atau shift Anda mungkin sudah selesai.<br>Selamat beristirahat!</p>
                            </div>
                        <?php endif; ?>

                        <hr class="my-5">

                        <div class="text-start bg-light p-4 rounded-3 border">
                            <h6 class="fw-bold mb-3"><i class="fas fa-info-circle me-2 text-warning"></i>Kondisi Darurat / Tidak Bisa Hadir?</h6>
                            <p class="text-muted small mb-3">Jika Anda hari ini memiliki jadwal shift namun tidak dapat hadir dikarenakan sakit atau keperluan mendesak lainnya, silakan ajukan Izin resmi ke Admin.</p>
                            <a href="izin.php" class="btn btn-outline-warning w-100 fw-bold">
                                <i class="fas fa-envelope-open-text me-1"></i> Form Pengajuan Izin
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Jam Digital
function updateClock() {
    var now = new Date();
    var h = String(now.getHours()).padStart(2, '0');
    var m = String(now.getMinutes()).padStart(2, '0');
    var s = String(now.getSeconds()).padStart(2, '0');
    document.getElementById('liveClock').textContent = h + ":" + m + ":" + s;
}
setInterval(updateClock, 1000);
</script>

<?php include '../layouts/footer.php'; ?>
