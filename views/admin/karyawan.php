<?php
require_once '../../config/config.php';
if (!isAdmin()) {
    redirect('../../login.php');
}

// Proses Tambah Karyawan
if (isset($_POST['tambah'])) {
    $nama = $_POST['nama'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $nomor_hp = $_POST['nomor_hp'];

    try {
        mysqli_execute_query($koneksi, "INSERT INTO users (role, nama, username, password, nomor_hp) VALUES ('karyawan', ?, ?, ?, ?)", [$nama, $username, $password, $nomor_hp]);
        $sukses = "Karyawan berhasil ditambahkan!";
    } catch(Exception $e) {
        $error = "Gagal menambah karyawan. Username mungkin sudah dipakai.";
    }
}

// Proses Edit Karyawan
if (isset($_POST['edit'])) {
    $id = $_POST['id'];
    $nama = $_POST['nama'];
    $username = $_POST['username'];
    $nomor_hp = $_POST['nomor_hp'];

    try {
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            mysqli_execute_query($koneksi, "UPDATE users SET nama=?, username=?, password=?, nomor_hp=? WHERE id=?", [$nama, $username, $password, $nomor_hp, $id]);
        } else {
            mysqli_execute_query($koneksi, "UPDATE users SET nama=?, username=?, nomor_hp=? WHERE id=?", [$nama, $username, $nomor_hp, $id]);
        }
        $sukses = "Data karyawan berhasil diupdate!";
    } catch(Exception $e) {
        $error = "Gagal mengupdate karyawan.";
    }
}

// Proses Hapus Karyawan
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_execute_query($koneksi, "DELETE FROM users WHERE id=?", [$id]);
    redirect('karyawan.php?msg=deleted');
}

// Ambil data karyawan
$result = mysqli_query($koneksi, "SELECT * FROM users WHERE role='karyawan' ORDER BY nama ASC");
$karyawan = mysqli_fetch_all($result, MYSQLI_ASSOC);

include '../layouts/header.php';
include '../layouts/sidebar_admin.php';
?>

<div id="page-content-wrapper">
    <nav class="top-navbar">
        <h4 class="mb-0 fw-bold">Manajemen Data Karyawan</h4>
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
                Karyawan berhasil dihapus.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Daftar Karyawan</span>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#tambahModal">
                    <i class="fas fa-plus me-1"></i> Tambah Karyawan
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Nama Lengkap</th>
                                <th>Username</th>
                                <th>No. HP</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no=1; foreach($karyawan as $k): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td class="fw-medium text-dark"><?= htmlspecialchars($k['nama']) ?></td>
                                <td class="text-muted"><?= htmlspecialchars($k['username']) ?></td>
                                <td><?= htmlspecialchars($k['nomor_hp']) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary mb-1 me-1" data-bs-toggle="modal" data-bs-target="#editModal<?= $k['id'] ?>" title="Edit">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <a href="karyawan.php?hapus=<?= $k['id'] ?>" class="btn btn-sm btn-outline-danger mb-1" onclick="return confirm('Yakin ingin menghapus data ini? Semua jadwal dan absensinya juga akan terhapus.');" title="Hapus">
                                        <i class="fas fa-trash"></i> Hapus
                                    </a>
                                </td>
                            </tr>

                            <!-- Modal Edit -->
                            <div class="modal fade" id="editModal<?= $k['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 shadow">
                                        <form method="POST" action="">
                                            <div class="modal-header border-bottom-0 pb-0">
                                                <h5 class="modal-title fw-bold">Edit Karyawan</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body pt-4">
                                                <input type="hidden" name="id" value="<?= $k['id'] ?>">
                                                <div class="mb-3">
                                                    <label class="form-label text-muted small fw-bold">NAMA LENGKAP</label>
                                                    <input type="text" class="form-control" name="nama" value="<?= htmlspecialchars($k['nama']) ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label text-muted small fw-bold">USERNAME</label>
                                                    <input type="text" class="form-control" name="username" value="<?= htmlspecialchars($k['username']) ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label text-muted small fw-bold">NOMOR HP</label>
                                                    <input type="text" class="form-control" name="nomor_hp" value="<?= htmlspecialchars($k['nomor_hp']) ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label text-muted small fw-bold">PASSWORD BARU</label>
                                                    <input type="password" class="form-control" name="password" placeholder="Kosongkan jika tidak diubah">
                                                </div>
                                            </div>
                                            <div class="modal-footer border-top-0 pt-0">
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" name="edit" class="btn btn-primary px-4">Simpan Perubahan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="tambahModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">Tambah Karyawan Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-4">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">NAMA LENGKAP</label>
                        <input type="text" class="form-control" name="nama" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">USERNAME</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">NOMOR HP</label>
                        <input type="text" class="form-control" name="nomor_hp" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">PASSWORD DEFAULT</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah" class="btn btn-primary px-4">Simpan Karyawan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
