<?php
require_once '../../config/config.php';
if (!isKaryawan()) {
    redirect('../../login.php');
}

$user_id = $_SESSION['user_id'];

// Proses Pengajuan Izin
if (isset($_POST['ajukan_izin'])) {
    $tanggal_mulai = $_POST['tanggal_mulai'];
    $tanggal_selesai = $_POST['tanggal_selesai'];
    $alasan = $_POST['alasan'];

    // Handle Upload Bukti Izin (Surat Dokter / Foto dll)
    $bukti_izin = "";
    if (isset($_FILES['bukti_izin']) && $_FILES['bukti_izin']['error'] == 0) {
        $ext = pathinfo($_FILES['bukti_izin']['name'], PATHINFO_EXTENSION);
        if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'pdf'])) {
            $newName = "izin_".$user_id."_".time().".".$ext;
            if (move_uploaded_file($_FILES['bukti_izin']['tmp_name'], "../../uploads/izin/".$newName)) {
                $bukti_izin = $newName;
            }
        } else {
            $error = "Format file tidak didukung. Harap upload JPG/PNG/PDF.";
        }
    }

    if (!isset($error)) {
        try {
            mysqli_execute_query($koneksi, "INSERT INTO izin_karyawan (user_id, tanggal_mulai, tanggal_selesai, alasan, bukti_izin, status) 
                                   VALUES (?, ?, ?, ?, ?, 'pending')", [$user_id, $tanggal_mulai, $tanggal_selesai, $alasan, $bukti_izin]);
            
            // Post/Redirect/Get Pattern. 
            header("Location: " . $_SERVER['PHP_SELF'] . "?msg=success");
            exit;
            
        } catch(Exception $e) {
            $error = "Terjadi kesalahan sistem saat mengajukan izin.";
        }
    }
}

// Ambil Riwayat Izin
$stmt_izin = mysqli_execute_query($koneksi, "SELECT * FROM izin_karyawan WHERE user_id = ? ORDER BY id DESC", [$user_id]);
$data_izin = mysqli_fetch_all($stmt_izin, MYSQLI_ASSOC);

// Hitung Statistik
$stat_pending = 0;
$stat_approved = 0;
$stat_rejected = 0;

foreach ($data_izin as $izin) {
    if ($izin['status'] == 'pending') $stat_pending++;
    if ($izin['status'] == 'disetujui') $stat_approved++;
    if ($izin['status'] == 'ditolak') $stat_rejected++;
}

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
        --dash-success: #10B981;
        --dash-success-light: #ECFDF5;
        --dash-warning: #F59E0B;
        --dash-warning-light: #FFFBEB;
    }

    body {
        background-color: var(--dash-bg);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    }

    .dash-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .dash-title {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--dash-text-dark);
        margin-bottom: 0.25rem;
        letter-spacing: -0.02em;
    }

    .dash-subtitle { color: var(--dash-text-muted); font-size: 0.95rem; margin: 0; }

    /* Statistik Row */
    .stat-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: var(--dash-surface);
        border: 1px solid var(--dash-border);
        border-radius: 20px;
        padding: 1.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
        display: flex;
        align-items: center;
        gap: 1.25rem;
    }

    .stat-icon {
        width: 54px;
        height: 54px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .stat-info h3 {
        font-size: 1.75rem;
        font-weight: 900;
        color: var(--dash-text-dark);
        margin: 0;
        line-height: 1;
    }

    .stat-info p {
        margin: 0;
        color: var(--dash-text-muted);
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-top: 0.4rem;
    }

    /* Cards */
    .dash-card {
        background: var(--dash-surface);
        border: 1px solid var(--dash-border);
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
        margin-bottom: 1.5rem;
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

    .dash-btn-outline {
        background-color: var(--dash-surface);
        color: var(--dash-text-dark);
        border: 1px solid var(--dash-border);
        border-radius: 12px;
        padding: 0.6rem 1.25rem;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.2s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .dash-btn-outline:hover {
        background-color: #F9FAFB;
        border-color: #D1D5DB;
        color: var(--dash-text-dark);
    }

    /* Ticket Layout untuk Riwayat */
    .ticket-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .ticket-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1.25rem 1.5rem;
        border: 1px solid var(--dash-border);
        border-radius: 16px;
        background: var(--dash-surface);
        transition: all 0.2s;
        border-left-width: 6px; /* Thick border untuk status */
    }

    .ticket-item:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        transform: translateX(4px);
    }

    .ticket-status-pending { border-left-color: var(--dash-warning); }
    .ticket-status-disetujui { border-left-color: var(--dash-success); }
    .ticket-status-ditolak { border-left-color: var(--dash-danger); }

    .ticket-body {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .ticket-date {
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--dash-text-dark);
    }

    .ticket-reason {
        color: var(--dash-text-muted);
        font-size: 0.95rem;
        font-weight: 500;
        margin: 0;
    }

    .ticket-actions {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.75rem;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.4rem 1rem;
        border-radius: 8px;
        font-size: 0.8rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .badge-pending { background: var(--dash-warning-light); color: var(--dash-warning); }
    .badge-disetujui { background: var(--dash-success-light); color: var(--dash-success); }
    .badge-ditolak { background: var(--dash-danger-light); color: var(--dash-danger); }

    .file-link {
        font-size: 0.85rem;
        font-weight: 700;
        color: var(--dash-accent);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        transition: color 0.2s;
    }
    .file-link:hover { color: var(--dash-text-dark); }

    @media (max-width: 768px) {
        .ticket-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 1.25rem;
        }
        .ticket-actions {
            align-items: flex-start;
            width: 100%;
            flex-direction: row;
            justify-content: space-between;
            border-top: 1px dashed var(--dash-border);
            padding-top: 1rem;
        }
    }
</style>

<div id="page-content-wrapper" class="pb-5 pt-4">
    <div class="container-fluid px-xl-5">
        
        <!-- Header -->
        <div class="dash-header">
            <div>
                <h1 class="dash-title">Pengajuan Izin & Cuti</h1>
                <p class="dash-subtitle">Kirim form ketidakhadiran dan pantau persetujuan manajemen.</p>
            </div>
            <a href="absensi.php" class="dash-btn-outline">
                <i class="fas fa-arrow-left"></i> Kembali ke Absensi
            </a>
        </div>

        <!-- Alerts -->
        <?php if(isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
            <div class="alert alert-success dash-alert shadow-sm border-0 d-flex align-items-center mb-4" style="border-radius: 12px; padding: 1rem 1.25rem;">
                <i class="fas fa-check-circle me-2" style="font-size: 1.2rem; color: var(--dash-success);"></i> 
                <span class="fw-bold" style="color: var(--dash-success);">Pengajuan izin berhasil direkam. Menunggu validasi Admin.</span>
            </div>
        <?php endif; ?>
        <?php if(isset($error)): ?>
            <div class="alert alert-danger dash-alert shadow-sm border-0 d-flex align-items-center mb-4" style="border-radius: 12px; padding: 1rem 1.25rem;">
                <i class="fas fa-exclamation-circle me-2" style="font-size: 1.2rem; color: var(--dash-danger);"></i> 
                <span class="fw-bold" style="color: var(--dash-danger);"><?= $error ?></span>
            </div>
        <?php endif; ?>

        <!-- Panel Statistik -->
        <div class="stat-row">
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--dash-warning-light); color: var(--dash-warning);">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $stat_pending ?></h3>
                    <p>Menunggu Proses</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--dash-success-light); color: var(--dash-success);">
                    <i class="fas fa-check-double"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $stat_approved ?></h3>
                    <p>Total Disetujui</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--dash-danger-light); color: var(--dash-danger);">
                    <i class="fas fa-times"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $stat_rejected ?></h3>
                    <p>Total Ditolak</p>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Form Card -->
            <div class="col-lg-4">
                <div class="dash-card sticky-top" style="top: 2rem; z-index: 1;">
                    <h2 class="dash-card-title">
                        <span style="color: var(--dash-accent);">●</span> Form Permintaan
                    </h2>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-4">
                            <label class="dash-label">Mulai Tanggal</label>
                            <input type="date" name="tanggal_mulai" class="dash-input" required min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="mb-4">
                            <label class="dash-label">Sampai Tanggal</label>
                            <input type="date" name="tanggal_selesai" class="dash-input" required min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="mb-4">
                            <label class="dash-label">Alasan & Konteks</label>
                            <textarea name="alasan" class="dash-input" rows="3" required placeholder="Jelaskan alasan pengajuan secara spesifik..." style="resize: none;"></textarea>
                        </div>
                        <div class="mb-5">
                            <label class="dash-label">Bukti Pendukung <span class="text-muted fw-normal">(Bila ada)</span></label>
                            <input type="file" name="bukti_izin" class="dash-input" accept=".jpg,.jpeg,.png,.pdf" style="padding: 0.6rem 1rem;">
                        </div>
                        
                        <button type="submit" name="ajukan_izin" class="dash-btn-primary">
                            Kirim Permintaan
                        </button>
                    </form>
                </div>
            </div>

            <!-- List Card -->
            <div class="col-lg-8">
                <div class="dash-card">
                    <h2 class="dash-card-title">
                        <span style="color: var(--dash-text-muted);">●</span> Tiket Pengajuan Anda
                    </h2>
                    
                    <div class="ticket-list">
                        <?php if(count($data_izin) > 0): ?>
                            <?php foreach($data_izin as $i): 
                                $status = strtolower($i['status']);
                                $ticket_class = 'ticket-status-pending';
                                $badge_class = 'badge-pending';
                                $icon = 'fa-clock';
                                
                                if ($status == 'disetujui') {
                                    $ticket_class = 'ticket-status-disetujui';
                                    $badge_class = 'badge-disetujui';
                                    $icon = 'fa-check-circle';
                                } else if ($status == 'ditolak') {
                                    $ticket_class = 'ticket-status-ditolak';
                                    $badge_class = 'badge-ditolak';
                                    $icon = 'fa-times-circle';
                                }
                            ?>
                            <div class="ticket-item <?= $ticket_class ?>">
                                <div class="ticket-body">
                                    <div class="ticket-date">
                                        <?= date('d M Y', strtotime($i['tanggal_mulai'])) ?>
                                        <?php if($i['tanggal_mulai'] != $i['tanggal_selesai']): ?>
                                            <span style="color: var(--dash-text-muted); font-weight: 500;"> &mdash; <?= date('d M Y', strtotime($i['tanggal_selesai'])) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="ticket-reason">
                                        "<?= htmlspecialchars($i['alasan']) ?>"
                                    </p>
                                </div>
                                
                                <div class="ticket-actions">
                                    <span class="status-badge <?= $badge_class ?>">
                                        <i class="fas <?= $icon ?>"></i> <?= strtoupper($status) ?>
                                    </span>
                                    <?php if(!empty($i['bukti_izin'])): ?>
                                        <a href="../../uploads/izin/<?= htmlspecialchars($i['bukti_izin']) ?>" target="_blank" class="file-link">
                                            <i class="fas fa-paperclip"></i> Buka Bukti
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- Empty State -->
                            <div class="text-center py-5 rounded-4" style="background-color: var(--dash-bg); border: 1px dashed var(--dash-border);">
                                <div class="mb-3">
                                    <i class="fas fa-file-invoice" style="font-size: 3rem; color: #D1D5DB;"></i>
                                </div>
                                <h5 class="fw-bold" style="color: var(--dash-text-dark);">Belum Ada Tiket Izin</h5>
                                <p class="mb-0" style="color: var(--dash-text-muted); font-size: 0.95rem;">Riwayat pengajuan cuti atau izin Anda masih kosong.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>