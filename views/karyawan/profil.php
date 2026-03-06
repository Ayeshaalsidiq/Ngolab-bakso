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
            $stmt = $pdo->prepare("UPDATE users SET nama=?, username=?, password=?, nomor_hp=?, foto_profil=? WHERE id=?");
            $stmt->execute([$nama, $username, $password, $nomor_hp, $foto_profil, $user_id]);
        } elseif (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET nama=?, username=?, password=?, nomor_hp=? WHERE id=?");
            $stmt->execute([$nama, $username, $password, $nomor_hp, $user_id]);
        } elseif ($foto_profil) {
            $stmt = $pdo->prepare("UPDATE users SET nama=?, username=?, nomor_hp=?, foto_profil=? WHERE id=?");
            $stmt->execute([$nama, $username, $nomor_hp, $foto_profil, $user_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET nama=?, username=?, nomor_hp=? WHERE id=?");
            $stmt->execute([$nama, $username, $nomor_hp, $user_id]);
        }
        
        // Update session nama
        $_SESSION['nama'] = $nama;
        $sukses = "Profil berhasil diperbarui!";
    } catch(PDOException $e) {
        $error = "Gagal mengupdate profil. Username mungkin sudah dipakai.";
    }
}

// Ambil data user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

include '../layouts/header.php';
include '../layouts/sidebar_karyawan.php';
?>

<div id="page-content-wrapper">
    <nav class="top-navbar">
        <h4 class="mb-0 fw-bold">Kelola Profil</h4>
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

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        Detail Profil Saya
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label fw-medium">Nama Lengkap</label>
                                <input type="text" class="form-control" name="nama" value="<?= htmlspecialchars($user['nama']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-medium">Username</label>
                                <input type="text" class="form-control" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-medium">Nomor HP</label>
                                <input type="text" class="form-control" name="nomor_hp" value="<?= htmlspecialchars($user['nomor_hp']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-medium text-danger">Ganti Password (Opsional)</label>
                                <input type="password" class="form-control" name="password" placeholder="Biarkan kosong jika tidak ingin mengubah password">
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-medium">Upload Foto Profil Terbaru</label>
                                <input type="file" class="form-control" name="foto_profil" accept="image/*">
                            </div>
                            
                            <button type="submit" name="update_profil" class="btn btn-primary px-4">
                                <i class="fas fa-save me-1"></i> Simpan Perubahan
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body py-5">
                        <div class="mb-3">
                            <?php if(!empty($user['foto_profil'])): ?>
                                <img src="../../uploads/profil/<?= htmlspecialchars($user['foto_profil']) ?>" class="rounded-circle shadow-sm" style="width: 120px; height: 120px; object-fit: cover; border: 3px solid var(--primary-color);">
                            <?php else: ?>
                                <i class="fas fa-user-circle fa-5x text-secondary"></i>
                            <?php endif; ?>
                        </div>
                        <h4 class="fw-bold"><?= htmlspecialchars($user['nama']) ?></h4>
                        <p class="text-muted mb-0">Role: <?= ucfirst($user['role']) ?></p>
                        <p class="text-muted"><i class="fas fa-phone-alt me-1"></i> <?= htmlspecialchars($user['nomor_hp']) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
