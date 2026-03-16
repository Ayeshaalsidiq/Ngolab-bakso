<?php
require_once '../../config/config.php';
if (!isKaryawan()) {
    redirect('../../login.php');
}

$user_id = $_SESSION['user_id'];

// Proses Update Profil
if (isset($_POST['update_profil'])) {
    $nama = $_POST['nama'];
    $username = $_POST['username'];
    $nomor_hp = $_POST['nomor_hp'];
    $foto_profil = null;

    // Handle File Upload
    if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['foto_profil']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $new_filename = 'profil_' . $user_id . '_' . time() . '.' . $ext;
            $upload_path = '../../uploads/profil/' . $new_filename;
            
            if (move_uploaded_file($_FILES['foto_profil']['tmp_name'], $upload_path)) {
                $foto_profil = $new_filename;
                $_SESSION['foto_profil'] = $foto_profil;
            }
        }
    }

    try {
        if (!empty($_POST['password']) && $foto_profil) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            mysqli_execute_query($koneksi, "UPDATE users SET nama=?, username=?, password=?, nomor_hp=?, foto_profil=? WHERE id=?", [$nama, $username, $password, $nomor_hp, $foto_profil, $user_id]);
        } elseif (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            mysqli_execute_query($koneksi, "UPDATE users SET nama=?, username=?, password=?, nomor_hp=? WHERE id=?", [$nama, $username, $password, $nomor_hp, $user_id]);
        } elseif ($foto_profil) {
            mysqli_execute_query($koneksi, "UPDATE users SET nama=?, username=?, nomor_hp=?, foto_profil=? WHERE id=?", [$nama, $username, $nomor_hp, $foto_profil, $user_id]);
        } else {
            mysqli_execute_query($koneksi, "UPDATE users SET nama=?, username=?, nomor_hp=? WHERE id=?", [$nama, $username, $nomor_hp, $user_id]);
        }
        
        // Update session nama
        $_SESSION['nama'] = $nama;
        $sukses = "Profil berhasil diperbarui!";
    } catch(Exception $e) {
        $error = "Gagal mengupdate profil. Username mungkin sudah dipakai.";
    }
}

// Ambil data user
$stmt = mysqli_execute_query($koneksi, "SELECT * FROM users WHERE id=?", [$user_id]);
$user = mysqli_fetch_assoc($stmt);

include '../layouts/header.php';
include '../layouts/sidebar_karyawan.php';
?>

<div id="page-content-wrapper" class="bg-light pb-5">
    
    <!-- Top Navbar modern -->
    <nav class="mb-4 pb-3 pt-2">
        <h4 class="mb-0 fw-bold" style="color: #000; letter-spacing: -0.5px;">Profil Saya</h4>
        <p class="text-muted small mt-1 mb-0">Kelola informasi data diri dan akun Anda di sini.</p>
    </nav>

    <div class="container-fluid px-0">
        <?php if(isset($sukses)): ?>
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" style="border-radius: 12px; background-color: rgba(16, 185, 129, 0.1); color: #059669;" role="alert">
                <i class="fas fa-check-circle me-2"></i> <strong>Berhasil!</strong> <?= $sukses ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if(isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" style="border-radius: 12px;" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> <strong>Gagal!</strong> <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Profile Identity Card -->
            <div class="col-lg-4">
                <div class="card border-0 h-100" style="border-radius: 12px; background: #fff; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);">
                    <div class="card-body py-5 text-center">
                        <div class="mb-4">
                            <?php if(!empty($user['foto_profil'])): ?>
                                <img src="../../uploads/profil/<?= htmlspecialchars($user['foto_profil']) ?>" class="rounded-circle shadow-sm" style="width: 130px; height: 130px; object-fit: cover; border: 3px solid #ea580c; padding: 3px;">
                            <?php else: ?>
                                <div class="rounded-circle d-flex align-items-center justify-content-center shadow-sm mx-auto" style="width: 130px; height: 130px; background-color: #000; border: 3px solid #ea580c; padding: 3px;">
                                    <i class="fas fa-user text-white" style="font-size: 3.5rem;"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <h4 class="fw-bold mb-1" style="color: #000; letter-spacing: -0.5px; font-size: 1.4rem;"><?= htmlspecialchars($user['nama']) ?></h4>
                        <p class="text-muted fw-bold mb-4 pb-2" style="font-size: 0.75rem; letter-spacing: 1px;">KARYAWAN</p>
                        
                        <div class="d-inline-flex align-items-center justify-content-center w-100 px-3 py-2" style="background-color: #f8fafc; border-radius: 6px;">
                            <i class="fas fa-phone-alt me-2 text-muted" style="font-size: 0.85rem;"></i>
                            <span class="fw-medium text-dark" style="font-size: 0.9rem;"><?= htmlspecialchars($user['nomor_hp']) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Form Card -->
            <div class="col-lg-8">
                <div class="card border-0" style="border-radius: 12px; background: #fff; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);">
                    <div class="card-body p-4 p-xl-5">
                        <div class="mb-4 pb-3 border-bottom border-light">
                            <h5 class="fw-bolder mb-0" style="color: #000; letter-spacing: -0.5px; font-size: 1.15rem;">Informasi Pribadi</h5>
                        </div>
                        
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-muted small text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">Nama Lengkap</label>
                                    <input type="text" class="form-control border-0" name="nama" value="<?= htmlspecialchars($user['nama']) ?>" required style="background-color: #f8fafc; border-radius: 8px; padding: 0.75rem 1rem; color: #475569;">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-muted small text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">Username Login</label>
                                    <input type="text" class="form-control border-0" name="username" value="<?= htmlspecialchars($user['username']) ?>" required readonly style="background-color: #f8fafc; border-radius: 8px; padding: 0.75rem 1rem; color: #94a3b8; cursor: not-allowed;">
                                </div>
                            </div>
                            
                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-uppercase" style="color: #475569; font-size: 0.75rem; letter-spacing: 0.5px;">Nomor HP</label>
                                    <input type="text" class="form-control bg-transparent" name="nomor_hp" value="<?= htmlspecialchars($user['nomor_hp']) ?>" required style="border: 1px solid #fed7aa; border-radius: 8px; padding: 0.75rem 1rem; color: #475569; background-color: #fffaf5 !important;">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-uppercase" style="color: #dc2626; font-size: 0.75rem; letter-spacing: 0.5px;">Ganti Password Baru</label>
                                    <input type="password" class="form-control border-0" name="password" placeholder="Biarkan kosong jika tetap" style="background-color: #f8fafc; border-radius: 8px; padding: 0.75rem 1rem; color: #475569;">
                                </div>
                            </div>

                            <div class="mb-5">
                                <label class="form-label fw-bold text-muted small text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">Upload Foto Profil</label>
                                <input type="file" class="form-control border-0" name="foto_profil" accept="image/*" style="background-color: #f8fafc; border-radius: 8px; padding: 0.5rem 1rem;">
                            </div>
                            
                            <div class="text-end pt-3 text-md-end text-center flex-md-row flex-column d-flex justify-content-end gap-2">
                                <button type="submit" name="update_profil" class="btn text-white fw-bold shadow-sm" style="background-color: #000; border-radius: 8px; padding: 0.75rem 2rem; font-size: 0.95rem; letter-spacing: 0.2px;">
                                    Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
