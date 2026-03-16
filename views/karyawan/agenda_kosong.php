<?php
require_once '../../config/config.php';
if (!isKaryawan()) {
    redirect('../../login.php');
}

$user_id = $_SESSION['user_id'];

// Proses Set Agenda Kosong
if (isset($_POST['set_agenda'])) {
    $hari = $_POST['hari'];
    $jam_mulai = $_POST['jam_mulai'];
    $jam_selesai = $_POST['jam_selesai'];
    $keterangan = $_POST['keterangan'];

    // Handle File Upload
    $bukti_kuliah = "";
    if (isset($_FILES['bukti_kuliah']) && $_FILES['bukti_kuliah']['error'] == 0) {
        $allowed = array('jpg', 'jpeg', 'png', 'pdf');
        $filename = $_FILES['bukti_kuliah']['name'];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if (in_array(strtolower($ext), $allowed)) {
            $newName = "bukti_".$user_id."_".time().".".$ext;
            $destination = "../../uploads/bukti_kuliah/".$newName;
            if (move_uploaded_file($_FILES['bukti_kuliah']['tmp_name'], $destination)) {
                $bukti_kuliah = $newName;
            } else {
                $error = "Gagal mengunggah file bukti kuliah.";
            }
        } else {
            $error = "Format file tidak didukung. Harap upload JPG/PNG/PDF.";
        }
    }

    if (!isset($error)) {
        try {
            mysqli_execute_query($koneksi, "INSERT INTO ketersediaan_karyawan (user_id, hari, jam_mulai, jam_selesai, bukti_kuliah, keterangan) VALUES (?, ?, ?, ?, ?, ?)", [$user_id, $hari, $jam_mulai, $jam_selesai, $bukti_kuliah, $keterangan]);
            
            // Post/Redirect/Get (PRG) Pattern dipertahankan
            redirect('agenda_kosong.php?msg=success');
            exit;
            
        } catch(Exception $e) {
            $error = "Gagal menyimpan jadwal kosong.";
        }
    }
}

// Proses Hapus Agenda Kosong
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    // Cek kepemilikan
    $stmt_cek = mysqli_execute_query($koneksi, "SELECT * FROM ketersediaan_karyawan WHERE id=? AND user_id=?", [$id, $user_id]);
    $data_hapus = mysqli_fetch_assoc($stmt_cek);
    if ($data_hapus) {
        if (!empty($data_hapus['bukti_kuliah'])) {
            @unlink("../../uploads/bukti_kuliah/".$data_hapus['bukti_kuliah']);
        }
        mysqli_execute_query($koneksi, "DELETE FROM ketersediaan_karyawan WHERE id=?", [$id]);
        redirect('agenda_kosong.php?msg=deleted');
        exit;
    }
}

// Ambil riwayat input jadwal kosong
$stmt_agenda = mysqli_execute_query($koneksi, "SELECT * FROM ketersediaan_karyawan WHERE user_id=? ORDER BY id DESC", [$user_id]);
$agenda = mysqli_fetch_all($stmt_agenda, MYSQLI_ASSOC);

include '../layouts/header.php';
include '../layouts/sidebar_karyawan.php';
?>

<style>
    :root {
        --dash-bg: #F4F6F8;
        --dash-surface: #FFFFFF;
        --dash-text-dark: #000000;
        --dash-text-muted: #6B7280;
        --dash-accent: #F99451;
        --dash-accent-light: #FFF4ED;
        --dash-border: #E5E7EB;
        --dash-danger: #EF4444;
        --dash-danger-light: #FEF2F2;
    }

    body {
        background-color: var(--dash-bg);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    }

    /* Dashboard Header Style */
    .dash-header {
        margin-bottom: 2rem;
    }
    
    .dash-title {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--dash-text-dark);
        margin-bottom: 0.25rem;
        letter-spacing: -0.02em;
    }

    .dash-subtitle {
        color: var(--dash-text-muted);
        font-size: 0.95rem;
    }

    /* Container Cards */
    .dash-card {
        background: var(--dash-surface);
        border: 1px solid var(--dash-border);
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
    }

    .dash-card-title {
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--dash-text-dark);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    /* Forms */
    .dash-label {
        font-size: 0.8rem;
        font-weight: 700;
        color: var(--dash-text-dark);
        margin-bottom: 0.5rem;
        display: block;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .dash-input {
        width: 100%;
        background: #F9FAFB;
        border: 1px solid var(--dash-border);
        border-radius: 12px;
        padding: 0.85rem 1rem;
        font-size: 0.95rem;
        color: var(--dash-text-dark);
        transition: all 0.2s ease;
    }

    .dash-input:focus {
        outline: none;
        background: var(--dash-surface);
        border-color: var(--dash-accent);
        box-shadow: 0 0 0 4px var(--dash-accent-light);
    }

    /* Buttons */
    .dash-btn-primary {
        background-color: var(--dash-text-dark);
        color: var(--dash-surface);
        border: none;
        border-radius: 12px;
        padding: 1rem 1.5rem;
        font-weight: 700;
        font-size: 1rem;
        width: 100%;
        transition: all 0.2s ease;
    }

    .dash-btn-primary:hover {
        background-color: var(--dash-accent);
        color: var(--dash-surface);
        transform: translateY(-2px);
    }

    /* List Layout (Fixing the cutoff issue) */
    .list-module {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .list-item {
        display: flex;
        align-items: center;
        padding: 1.25rem;
        border: 1px solid var(--dash-border);
        border-radius: 16px;
        background: var(--dash-surface);
        transition: border-color 0.2s, box-shadow 0.2s;
        gap: 1.5rem;
        flex-wrap: wrap; /* CRITICAL FIX: Allows wrapping on smaller screens */
    }

    .list-item:hover {
        border-color: var(--dash-accent);
        box-shadow: 0 4px 12px rgba(249, 148, 81, 0.1);
    }

    .item-day {
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--dash-text-dark);
        text-transform: uppercase;
        min-width: 100px;
    }

    .item-details {
        flex: 1;
        min-width: 200px; /* Ensures text has room before wrapping */
    }

    .item-time-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        background: var(--dash-accent-light);
        color: var(--dash-accent);
        padding: 0.3rem 0.75rem;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.85rem;
        margin-bottom: 0.4rem;
    }

    .item-desc {
        color: var(--dash-text-muted);
        font-size: 0.95rem;
        margin: 0;
        line-height: 1.4;
    }

    .item-actions {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-shrink: 0;
    }

    .action-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.5rem 1rem;
        border-radius: 50px;
        font-size: 0.85rem;
        font-weight: 600;
        text-decoration: none;
        transition: background 0.2s, color 0.2s;
    }

    .pill-link {
        background: #F3F4F6;
        color: var(--dash-text-dark);
    }

    .pill-link:hover {
        background: #E5E7EB;
    }

    .pill-danger {
        background: var(--dash-danger-light);
        color: var(--dash-danger);
    }

    .pill-danger:hover {
        background: #FEE2E2;
        color: #DC2626;
    }

    /* Alerts */
    .dash-alert {
        border-radius: 12px;
        border: none;
        padding: 1rem 1.25rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    .alert-success { background-color: var(--dash-accent-light); color: var(--dash-accent); border: 1px solid rgba(249, 148, 81, 0.2); }
    .alert-danger { background-color: var(--dash-danger-light); color: var(--dash-danger); border: 1px solid rgba(239, 68, 68, 0.2); }
</style>

<div id="page-content-wrapper" class="pb-5 pt-4">
    <div class="container-fluid px-xl-5">
        
        <!-- Header Identik dengan Dashboard -->
        <div class="dash-header">
            <h1 class="dash-title">Jadwal Ketersediaan</h1>
            <p class="dash-subtitle">Pantau dan ajukan ketersediaan waktu luang Anda untuk penugasan shift.</p>
        </div>

        <!-- Notifikasi -->
        <?php if(isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
            <div class="alert dash-alert alert-success mb-4 shadow-sm">
                <i class="fas fa-check-circle" style="font-size: 1.2rem;"></i> 
                <span>Data jadwal berhasil dicatat. Menunggu verifikasi.</span>
            </div>
        <?php endif; ?>
        <?php if(isset($error)): ?>
            <div class="alert dash-alert alert-danger mb-4 shadow-sm">
                <i class="fas fa-exclamation-circle" style="font-size: 1.2rem;"></i> 
                <span><?= $error ?></span>
            </div>
        <?php endif; ?>
        <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
            <div class="alert dash-alert mb-4 shadow-sm" style="background-color: #F3F4F6; color: var(--dash-text-muted); border: 1px solid var(--dash-border);">
                <i class="fas fa-trash-alt" style="font-size: 1.2rem;"></i> 
                <span>Data jadwal telah ditarik dari sistem.</span>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Kolom Form -->
            <div class="col-lg-4">
                <div class="dash-card sticky-top" style="top: 2rem; z-index: 1;">
                    <h2 class="dash-card-title">
                        <span style="color: var(--dash-accent);">●</span> Input Jadwal Baru
                    </h2>
                    
                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="mb-4">
                            <label class="dash-label">Hari Kosong</label>
                            <select class="dash-input" name="hari" required>
                                <option value="" disabled selected>Pilih Hari</option>
                                <option value="Senin">Senin</option>
                                <option value="Selasa">Selasa</option>
                                <option value="Rabu">Rabu</option>
                                <option value="Kamis">Kamis</option>
                                <option value="Jumat">Jumat</option>
                                <option value="Sabtu">Sabtu</option>
                                <option value="Minggu">Minggu</option>
                            </select>
                        </div>
                        
                        <div class="row g-3 mb-4">
                            <div class="col-6">
                                <label class="dash-label">Jam Mulai</label>
                                <input type="time" class="dash-input" name="jam_mulai" required>
                            </div>
                            <div class="col-6">
                                <label class="dash-label">Jam Selesai</label>
                                <input type="time" class="dash-input" name="jam_selesai" required>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="dash-label">Keterangan / Alasan</label>
                            <input type="text" class="dash-input" name="keterangan" placeholder="Contoh: Selesai kelas kuliah..." required autocomplete="off">
                        </div>
                        
                        <div class="mb-5">
                            <label class="dash-label">Dokumen Bukti (Opsional)</label>
                            <input type="file" class="dash-input" name="bukti_kuliah" accept=".jpg,.jpeg,.png,.pdf" style="padding-top: 0.6rem; padding-bottom: 0.6rem;">
                        </div>
                        
                        <button type="submit" name="set_agenda" class="dash-btn-primary">
                            Ajukan Jadwal
                        </button>
                    </form>
                </div>
            </div>

            <!-- Kolom List Data -->
            <div class="col-lg-8">
                <div class="dash-card">
                    <h2 class="dash-card-title">
                        <span style="color: var(--dash-text-muted);">●</span> Riwayat Input Anda
                    </h2>
                    
                    <div class="list-module">
                        <?php if(count($agenda) > 0): ?>
                            <?php foreach($agenda as $a): ?>
                            <div class="list-item">
                                <!-- Hari -->
                                <div class="item-day">
                                    <?= htmlspecialchars($a['hari']) ?>
                                </div>
                                
                                <!-- Detail Jam & Keterangan -->
                                <div class="item-details">
                                    <div class="item-time-badge">
                                        <i class="far fa-clock"></i>
                                        <?= substr($a['jam_mulai'],0,5) ?> &mdash; <?= substr($a['jam_selesai'],0,5) ?>
                                    </div>
                                    <p class="item-desc">
                                        <?= htmlspecialchars($a['keterangan']) ?>
                                    </p>
                                </div>
                                
                                <!-- Aksi -->
                                <div class="item-actions">
                                    <?php if(!empty($a['bukti_kuliah'])): ?>
                                        <a href="../../uploads/bukti_kuliah/<?= htmlspecialchars($a['bukti_kuliah']) ?>" target="_blank" class="action-pill pill-link">
                                            <i class="fas fa-paperclip"></i> Lampiran
                                        </a>
                                    <?php endif; ?>
                                    <a href="agenda_kosong.php?hapus=<?= $a['id'] ?>" class="action-pill pill-danger" onclick="return confirm('Apakah Anda yakin ingin membatalkan jadwal ini?');">
                                        <i class="fas fa-times"></i> Cabut
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- Empty State selaras dengan tema -->
                            <div class="text-center py-5 rounded-4" style="background-color: var(--dash-bg); border: 1px dashed var(--dash-border);">
                                <div class="mb-3">
                                    <i class="fas fa-folder-open" style="font-size: 3rem; color: #D1D5DB;"></i>
                                </div>
                                <h5 class="fw-bold" style="color: var(--dash-text-dark);">Belum Ada Riwayat</h5>
                                <p class="mb-0" style="color: var(--dash-text-muted); font-size: 0.95rem;">Anda belum mendaftarkan waktu kosong untuk sistem penugasan.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>