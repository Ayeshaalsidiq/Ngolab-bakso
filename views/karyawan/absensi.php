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
$stmt_shift = mysqli_execute_query($koneksi, "SELECT * FROM jadwal_shift WHERE user_id = ? AND tanggal = ? AND status='aktif'", [$user_id, $hari_ini]);
$shift = mysqli_fetch_assoc($stmt_shift);

$absensi = null;
if ($shift) {
    // Cek record absensi
    $result_absen = mysqli_execute_query($koneksi, "SELECT * FROM absensi WHERE user_id = ? AND jadwal_id = ?", [$user_id, $shift['id']]);
    $absensi = mysqli_fetch_assoc($result_absen);
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
            mysqli_execute_query($koneksi, "INSERT INTO absensi (user_id, jadwal_id, waktu_check_in, status, bukti_foto) VALUES (?, ?, ?, ?, ?)", [$user_id, $shift['id'], $waktu_sekarang, $status_absen, $bukti_foto]);
            $sukses = "Berhasil Check-In pada pukul " . date('H:i');
            
            // Refresh record absensi
            $result_absen2 = mysqli_execute_query($koneksi, "SELECT * FROM absensi WHERE user_id = ? AND jadwal_id = ?", [$user_id, $shift['id']]);
            $absensi = mysqli_fetch_assoc($result_absen2);
        } catch(Exception $e) {
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
            mysqli_execute_query($koneksi, "UPDATE absensi SET waktu_check_out = ? WHERE id = ?", [$waktu_sekarang, $absensi['id']]);
            $sukses = "Berhasil Check-Out pada pukul " . date('H:i');
            
            // Set shift selesai
            mysqli_execute_query($koneksi, "UPDATE jadwal_shift SET status='selesai' WHERE id=?", [$shift['id']]);

            // Refresh record absensi
            $result_absen3 = mysqli_execute_query($koneksi, "SELECT * FROM absensi WHERE user_id = ? AND jadwal_id = ?", [$user_id, $shift['id']]);
            $absensi = mysqli_fetch_assoc($result_absen3);
            $shift['status'] = 'selesai';
        } catch(Exception $e) {
            $error = "Terjadi kesalahan sistem saat Check-Out.";
        }
    }
}

include '../layouts/header.php';
include '../layouts/sidebar_karyawan.php';
?>

<div id="page-content-wrapper" class="bg-light pb-5">
    <nav class="top-navbar mb-4 border-0 shadow-sm" style="border-radius: 1rem; background: #fff;">
        <h4 class="mb-0 fw-bold" style="color: #000; letter-spacing: -0.5px;">Autentikasi Kehadiran</h4>
        <div class="h4 mb-0 font-monospace fw-bold" id="liveClock" style="color: #F99451; letter-spacing: 2px;"><?= date('H:i:s') ?></div>
    </nav>

    <div class="container-fluid px-0">
        <?php if(isset($sukses)): ?>
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" style="border-radius: 1rem; background-color: rgba(16, 185, 129, 0.1); color: #059669;" role="alert">
                <i class="fas fa-check-circle me-1"></i> <?= $sukses ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if(isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" style="border-radius: 1rem;" role="alert">
                <i class="fas fa-exclamation-circle me-1"></i> <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row justify-content-center g-4">
            <div class="col-md-10 col-lg-8">
                <!-- Info Shift Hari Ini -->
                <div class="card border-0 shadow-sm" style="border-radius: 1.5rem; overflow: hidden;">
                    <div class="card-body p-0">
                        <!-- Top Banner / Date -->
                        <div class="text-center py-4" style="background-color: #f8fafc; border-bottom: 1px solid #e5e7eb;">
                            <h5 class="fw-bold mb-0 text-uppercase" style="color: #64748b; letter-spacing: 1px; font-size: 0.9rem;">
                                <i class="far fa-calendar-alt me-2" style="color: #F99451;"></i>
                                <?= tgl_indo($hari_ini) ?>
                            </h5>
                        </div>

                        <div class="p-4 p-md-5 text-center">
                        <?php if($shift): ?>
                            <div class="mb-5 bg-light p-4 rounded-4 border" style="border-color: #e5e7eb; position: relative;">
                                <!-- Orange Accent -->
                                <div class="position-absolute top-0 start-50 translate-middle-x" style="width: 50px; height: 4px; background-color: #F99451; border-radius: 4px;"></div>
                                
                                <p class="text-muted text-uppercase fw-bold mb-2" style="font-size: 0.8rem; letter-spacing: 1px;">Jadwal Shift Anda Saat Ini</p>
                                <h2 class="fw-bold mb-3" style="color: #000;">
                                    <?= substr($shift['jam_mulai'],0,5) ?> - <?= substr($shift['jam_selesai'],0,5) ?> WIB
                                </h2>
                                <div class="d-inline-block px-3 py-2 rounded-3" style="background-color: #fff; border: 1px solid #fecaca; color: #b91c1c;">
                                    <i class="fas fa-info-circle me-1"></i> Batas toleransi check-in: <span class="fw-bold"><?= date('H:i', strtotime($shift['jam_mulai'] . ' +15 minutes')) ?></span>
                                </div>
                            </div>

                            <?php if(!$absensi): ?>
                                <!-- Belum Check In -->
                                <form method="POST" class="d-flex flex-column align-items-center" enctype="multipart/form-data">
                                    <div class="mb-4 text-start w-100" style="max-width: 400px;">
                                        <label class="form-label fw-bold text-dark small"><i class="fas fa-camera me-1" style="color: #F99451;"></i> Foto Selfie Kehadiran (Opsional)</label>
                                        <div class="position-relative">
                                            <input type="file" name="bukti_foto" class="form-control" accept="image/*" capture="user" style="padding: 0.6rem 1rem; border-radius: 0.75rem;">
                                        </div>
                                    </div>
                                    <button type="submit" name="check_in" class="btn text-white w-100 shadow fw-bold fs-5 text-uppercase" style="max-width: 400px; border-radius: 1rem; padding: 1rem; background-color: #000; border: 2px solid #000; transition: all 0.2s;">
                                        <i class="fas fa-fingerprint fs-4 me-2" style="color: #F99451;"></i> Check In Sekarang
                                    </button>
                                </form>
                            <?php elseif(empty($absensi['waktu_check_out'])): ?>
                                <!-- Sudah Check In, Belum Check Out -->
                                <div class="mb-5 p-4 rounded-4" style="background-color: rgba(16, 185, 129, 0.05); border: 1px solid rgba(16, 185, 129, 0.2);">
                                    <i class="fas fa-check-circle fill-current fa-3x mb-3" style="color: #10b981;"></i>
                                    <h4 class="fw-bold" style="color: #000;">Check-In Berhasil</h4>
                                    <p class="mb-1 text-muted">Anda tercatat hadir pada pukul <strong class="text-dark fs-5"><?= date('H:i', strtotime($absensi['waktu_check_in'])) ?></strong></p>
                                    
                                    <?php if($absensi['status'] === 'telat'): ?>
                                    <div class="mt-3 badge rounded-pill bg-danger-subtle text-danger px-3 py-2 border border-danger">
                                        <i class="fas fa-exclamation-triangle me-1"></i> Anda telah melewati batas waktu toleransi (Telat)
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <form method="POST" class="d-flex justify-content-center">
                                    <button type="submit" name="check_out" class="btn text-white shadow fw-bold fs-5 w-100 text-uppercase d-flex align-items-center justify-content-center" style="max-width: 400px; border-radius: 1rem; padding: 1rem; background-color: #000; transition: all 0.2s;">
                                        <i class="fas fa-sign-out-alt fs-4 me-3 text-danger"></i> Akhiri Sesi & Check Out
                                    </button>
                                </form>
                            <?php else: ?>
                                <!-- Sudah Check Out -->
                                <div class="py-4">
                                    <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center mb-4" style="width: 100px; height: 100px; background-color: rgba(249, 148, 81, 0.1);">
                                        <i class="fas fa-clipboard-check fa-3x" style="color: #F99451;"></i>
                                    </div>
                                    <h3 class="fw-bold mb-4" style="color: #000;">Shift Anda Selesai</h3>
                                    
                                    <div class="row justify-content-center g-3">
                                        <div class="col-sm-5">
                                            <div class="p-3 rounded-3" style="background-color: #f8fafc; border: 1px solid #e5e7eb;">
                                                <p class="text-muted text-uppercase small fw-bold mb-1">Check-in Pukul</p>
                                                <h4 class="fw-bold mb-0 text-dark"><?= date('H:i', strtotime($absensi['waktu_check_in'])) ?></h4>
                                            </div>
                                        </div>
                                        <div class="col-sm-5">
                                            <div class="p-3 rounded-3" style="background-color: #f8fafc; border: 1px solid #e5e7eb;">
                                                <p class="text-muted text-uppercase small fw-bold mb-1">Check-out Pukul</p>
                                                <h4 class="fw-bold mb-0 text-dark"><?= date('H:i', strtotime($absensi['waktu_check_out'])) ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                        <?php else: ?>
                            <!-- Tidak ada shift aktif -->
                            <div class="py-5 text-center">
                                <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center mb-4" style="width: 120px; height: 120px; background-color: #f8fafc;">
                                    <i class="fas fa-mug-hot fa-4x text-muted" style="opacity: 0.5;"></i>
                                </div>
                                <h3 class="fw-bold mb-2" style="color: #000;">Anda Sedang Libur</h3>
                                <p class="text-muted fs-5">Tidak ada shift yang aktif untuk Anda saat ini.<br>Selamat menikmati waktu luang!</p>
                            </div>
                        <?php endif; ?>

                        </div>
                    </div>
                </div>

                <!-- Guidance / Info Card -->
                <div class="card mt-4 border-0 shadow-sm" style="border-radius: 1.5rem; background-color: #111111; color: #fff;">
                    <div class="card-body p-4 d-flex align-items-center">
                        <div class="me-4 rounded-circle d-flex flex-shrink-0 align-items-center justify-content-center" style="width: 60px; height: 60px; background-color: rgba(249,148,81,0.2);">
                            <i class="fas fa-question-circle fa-2x" style="color: #F99451;"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="fw-bold mb-1">Berhalangan Hadir?</h5>
                            <p class="mb-0 text-white-50" style="font-size: 0.95rem;">Jika Anda sakit atau memiliki keperluan mendesak, segera laporkan ke Admin dengan mengirimkan form Izin resmi.</p>
                        </div>
                        <div class="ms-3">
                            <a href="izin.php" class="btn text-dark fw-bold px-4 py-2" style="background-color: #F99451; border-radius: 0.5rem; white-space: nowrap;">
                                Ajukan Izin
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
