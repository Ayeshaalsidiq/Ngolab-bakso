<?php
require_once '../../config/config.php';
if (!isKaryawan()) {
    redirect('../../login.php');
}

$user_id = $_SESSION['user_id'];
$hari_ini = date('Y-m-d');

// 1. Proses Mengajukan Tukar Jadwal
if (isset($_POST['ajukan_tukar'])) {
    $jadwal_saya_id = $_POST['jadwal_saya'];
    $jadwal_teman_id = $_POST['jadwal_teman'];
    $alasan = $_POST['alasan'];

    if ($jadwal_saya_id == $jadwal_teman_id || empty($jadwal_teman_id)) {
        $error = "Pilih jadwal teman yang valid.";
    } else {
        // Cari penerima_id dari jadwal teman
        $stmt_teman = mysqli_execute_query($koneksi, "SELECT user_id FROM jadwal_shift WHERE id=?", [$jadwal_teman_id]);
        $teman = mysqli_fetch_assoc($stmt_teman);

        if ($teman) {
            try {
                // Cek apakah sudah ada pengajuan pending
                $cek = mysqli_execute_query($koneksi, "SELECT id FROM tukar_jadwal WHERE (jadwal_pengaju_id=? OR jadwal_penerima_id=?) AND status IN ('pending_karyawan','pending_admin')", [$jadwal_saya_id, $jadwal_saya_id]);
                if (mysqli_num_rows($cek) > 0) {
                    $error = "Jadwal ini sedang dalam proses tukar yang belum selesai.";
                } else {
                    mysqli_execute_query($koneksi, "INSERT INTO tukar_jadwal (pengaju_id, penerima_id, jadwal_pengaju_id, jadwal_penerima_id, alasan) VALUES (?, ?, ?, ?, ?)", [$user_id, $teman['user_id'], $jadwal_saya_id, $jadwal_teman_id, $alasan]);
                    
                    // PERBAIKAN LOGIKA: Terapkan Pola PRG untuk mencegah resubmit saat Refresh
                    redirect("tukar_jadwal.php?msg=sukses_ajukan");
                    exit;
                }
            } catch(Exception $e) {
                $error = "Gagal memproses pengajuan. ".$e->getMessage();
            }
        }
    }
}

// 2. Proses Konfirmasi Teman
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $action = $_GET['action'];
    
    // Pastikan ini adalah penerima
    $cek_milik = mysqli_execute_query($koneksi, "SELECT * FROM tukar_jadwal WHERE id=? AND penerima_id=? AND status='pending_karyawan'", [$id, $user_id]);
    if (mysqli_num_rows($cek_milik) > 0) {
        if ($action == 'terima') {
            mysqli_execute_query($koneksi, "UPDATE tukar_jadwal SET status='pending_admin' WHERE id=?", [$id]);
            redirect("tukar_jadwal.php?msg=terima");
            exit;
        } elseif ($action == 'tolak') {
            mysqli_execute_query($koneksi, "UPDATE tukar_jadwal SET status='ditolak' WHERE id=?", [$id]);
            redirect("tukar_jadwal.php?msg=tolak");
            exit;
        }
    }
}

// Data form dropdown
$besok = date('Y-m-d', strtotime('+1 day'));
$stmt_saya = mysqli_execute_query($koneksi, "SELECT * FROM jadwal_shift WHERE user_id=? AND tanggal >= ? AND status='aktif' ORDER BY tanggal ASC", [$user_id, $besok]);
$jadwal_saya = mysqli_fetch_all($stmt_saya, MYSQLI_ASSOC);

$stmt_lain = mysqli_execute_query($koneksi, "SELECT j.*, u.nama FROM jadwal_shift j JOIN users u ON j.user_id=u.id WHERE j.user_id!=? AND j.tanggal >= ? AND j.status='aktif' ORDER BY j.tanggal ASC, u.nama ASC", [$user_id, $besok]);
$jadwal_lain = mysqli_fetch_all($stmt_lain, MYSQLI_ASSOC);

$stmt_pengaju = mysqli_execute_query($koneksi, "
    SELECT t.*, u.nama as nama_penerima, j1.tanggal as tgl_saya, j1.jam_mulai as jam_saya, j2.tanggal as tgl_teman, j2.jam_mulai as jam_teman 
    FROM tukar_jadwal t 
    JOIN users u ON t.penerima_id = u.id 
    JOIN jadwal_shift j1 ON t.jadwal_pengaju_id = j1.id
    JOIN jadwal_shift j2 ON t.jadwal_penerima_id = j2.id
    WHERE t.pengaju_id=? ORDER BY t.id DESC
", [$user_id]);
$riwayat_pengaju = mysqli_fetch_all($stmt_pengaju, MYSQLI_ASSOC);

$stmt_penerima = mysqli_execute_query($koneksi, "
    SELECT t.*, u.nama as nama_pengaju, j1.tanggal as tgl_teman, j1.jam_mulai as jam_teman, j2.tanggal as tgl_saya, j2.jam_mulai as jam_saya
    FROM tukar_jadwal t 
    JOIN users u ON t.pengaju_id = u.id 
    JOIN jadwal_shift j1 ON t.jadwal_pengaju_id = j1.id
    JOIN jadwal_shift j2 ON t.jadwal_penerima_id = j2.id
    WHERE t.penerima_id=? ORDER BY t.id DESC
", [$user_id]);
$riwayat_penerima = mysqli_fetch_all($stmt_penerima, MYSQLI_ASSOC);

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

    .dash-header { margin-bottom: 2rem; }
    
    .dash-title {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--dash-text-dark);
        margin-bottom: 0.25rem;
        letter-spacing: -0.02em;
    }

    .dash-subtitle { color: var(--dash-text-muted); font-size: 0.95rem; margin: 0; }

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

    /* Alerts */
    .dash-alert {
        border-radius: 12px;
        border: none;
        padding: 1rem 1.25rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 1.5rem;
    }
    .alert-success { background-color: var(--dash-accent-light); color: var(--dash-accent); border: 1px solid rgba(249, 148, 81, 0.2); }
    .alert-danger { background-color: var(--dash-danger-light); color: var(--dash-danger); border: 1px solid rgba(239, 68, 68, 0.2); }
    .alert-warning { background-color: var(--dash-warning-light); color: var(--dash-warning); border: 1px solid rgba(245, 158, 11, 0.2); }

    /* Pertukaran Shift UI (Exchange Tickets) */
    .exchange-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .exchange-ticket {
        border: 1px solid var(--dash-border);
        border-radius: 16px;
        background: var(--dash-surface);
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
        transition: box-shadow 0.2s;
    }

    .exchange-ticket:hover {
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
    }

    .ticket-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        border-bottom: 1px dashed var(--dash-border);
        padding-bottom: 1rem;
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: var(--dash-bg);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        color: var(--dash-text-dark);
    }

    .user-meta h6 { margin: 0; font-weight: 800; font-size: 1rem; color: var(--dash-text-dark); }
    .user-meta span { font-size: 0.8rem; color: var(--dash-text-muted); }

    .ticket-body {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #F9FAFB;
        border-radius: 12px;
        padding: 1rem 1.5rem;
    }

    .shift-block {
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
    }

    .shift-block .label { font-size: 0.7rem; text-transform: uppercase; font-weight: 700; color: var(--dash-text-muted); letter-spacing: 0.05em; }
    .shift-block .date { font-size: 1rem; font-weight: 800; color: var(--dash-text-dark); }
    .shift-block .time { font-family: monospace; font-size: 0.85rem; color: var(--dash-accent); font-weight: 700; }

    .exchange-arrow {
        color: var(--dash-border);
        font-size: 1.5rem;
    }

    .ticket-reason {
        font-size: 0.95rem;
        color: var(--dash-text-muted);
        margin: 0;
        background: var(--dash-bg);
        padding: 0.75rem 1rem;
        border-radius: 8px;
        border-left: 3px solid var(--dash-accent);
    }

    .ticket-footer {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 0.75rem;
        margin-top: 0.5rem;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.4rem 1rem;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .badge-pending { background: #F3F4F6; color: var(--dash-text-muted); }
    .badge-process { background: var(--dash-warning-light); color: var(--dash-warning); border: 1px solid rgba(245, 158, 11, 0.2); }
    .badge-success { background: var(--dash-success-light); color: var(--dash-success); border: 1px solid rgba(16, 185, 129, 0.2); }
    .badge-danger { background: var(--dash-danger-light); color: var(--dash-danger); border: 1px solid rgba(239, 68, 68, 0.2); }

    .btn-action {
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 700;
        text-decoration: none;
        transition: all 0.2s;
        border: none;
        cursor: pointer;
    }
    
    .btn-accept { background: var(--dash-text-dark); color: var(--dash-surface); }
    .btn-accept:hover { background: var(--dash-accent); color: var(--dash-surface); }
    
    .btn-reject { background: var(--dash-danger-light); color: var(--dash-danger); }
    .btn-reject:hover { background: #FEE2E2; color: #DC2626; }

    @media (max-width: 768px) {
        .ticket-body {
            flex-direction: column;
            gap: 1rem;
            align-items: flex-start;
        }
        .exchange-arrow { transform: rotate(90deg); margin-left: 1rem; }
    }
</style>

<div id="page-content-wrapper" class="pb-5 pt-4">
    <div class="container-fluid px-xl-5">
        
        <!-- Header -->
        <div class="dash-header">
            <h1 class="dash-title">Pertukaran Jadwal</h1>
            <p class="dash-subtitle">Ajukan, terima, atau tolak pertukaran shift kerja dengan rekan operasional Anda.</p>
        </div>

        <!-- System Alerts -->
        <?php if(isset($_GET['msg'])): ?>
            <?php 
                $msg_type = 'alert-success';
                $msg_icon = 'fa-check-circle';
                $msg_text = 'Operasi berhasil diselesaikan.';
                
                if($_GET['msg'] == 'sukses_ajukan') $msg_text = 'Pengajuan berhasil dikirim ke rekan kerja. Menunggu konfirmasi mereka.';
                if($_GET['msg'] == 'terima') $msg_text = 'Anda telah menyetujui pertukaran. Menunggu validasi akhir dari Administrator.';
                if($_GET['msg'] == 'tolak') {
                    $msg_type = 'alert-warning';
                    $msg_icon = 'fa-info-circle';
                    $msg_text = 'Anda telah menolak permintaan tukar jadwal tersebut.';
                }
            ?>
            <div class="alert dash-alert <?= $msg_type ?> shadow-sm">
                <i class="fas <?= $msg_icon ?> fs-5"></i>
                <span><?= $msg_text ?></span>
            </div>
        <?php endif; ?>
        
        <?php if(isset($error)): ?>
            <div class="alert dash-alert alert-danger shadow-sm">
                <i class="fas fa-exclamation-triangle fs-5"></i>
                <span><?= $error ?></span>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            
            <!-- KOLOM KIRI: FORM PENGAJUAN -->
            <div class="col-lg-4">
                <div class="dash-card sticky-top" style="top: 2rem; z-index: 1;">
                    <h2 class="dash-card-title">
                        <span style="color: var(--dash-accent);">●</span> Form Pengajuan
                    </h2>
                    
                    <form method="POST">
                        <div class="mb-4">
                            <label class="dash-label">Pilih Jadwal Anda</label>
                            <select name="jadwal_saya" class="dash-input" required>
                                <option value="" disabled selected>-- Pilih Shift --</option>
                                <?php foreach($jadwal_saya as $s): ?>
                                    <option value="<?= $s['id'] ?>">
                                        <?= date('d M Y', strtotime($s['tanggal'])) ?> (<?= substr($s['jam_mulai'],0,5) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="d-flex align-items-center justify-content-center mb-4">
                            <div style="background: var(--dash-border); height: 1px; width: 40%;"></div>
                            <div class="px-3 text-muted"><i class="fas fa-exchange-alt" style="transform: rotate(90deg);"></i></div>
                            <div style="background: var(--dash-border); height: 1px; width: 40%;"></div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="dash-label">Target Shift Rekan</label>
                            <select name="jadwal_teman" class="dash-input" required>
                                <option value="" disabled selected>-- Pilih Jadwal Rekan --</option>
                                <?php foreach($jadwal_lain as $l): ?>
                                    <option value="<?= $l['id'] ?>">
                                        <?= htmlspecialchars($l['nama']) ?> - <?= date('d M', strtotime($l['tanggal'])) ?> (<?= substr($l['jam_mulai'],0,5) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-5">
                            <label class="dash-label">Alasan Pertukaran</label>
                            <textarea name="alasan" class="dash-input" rows="3" required placeholder="Sertakan alasan yang spesifik agar rekan dan admin memahami urgensinya..." style="resize: none;"></textarea>
                        </div>
                        
                        <button type="submit" name="ajukan_tukar" class="dash-btn-primary">
                            Kirim Permintaan <i class="fas fa-paper-plane ms-2"></i>
                        </button>
                    </form>
                </div>
            </div>

            <!-- KOLOM KANAN: KOTAK MASUK & KELUAR -->
            <div class="col-lg-8">
                
                <!-- KOTAK MASUK (INBOX) -->
                <div class="dash-card mb-4">
                    <h2 class="dash-card-title">
                        <span style="color: var(--dash-warning);">●</span> Permintaan Masuk
                    </h2>
                    
                    <div class="exchange-list">
                        <?php if(count($riwayat_penerima) > 0): ?>
                            <?php foreach($riwayat_penerima as $r): ?>
                                <div class="exchange-ticket">
                                    <div class="ticket-header">
                                        <div class="user-info">
                                            <div class="user-avatar" style="background: var(--dash-accent-light); color: var(--dash-accent);">
                                                <?= strtoupper(substr($r['nama_pengaju'], 0, 1)) ?>
                                            </div>
                                            <div class="user-meta">
                                                <h6><?= htmlspecialchars($r['nama_pengaju']) ?></h6>
                                                <span>Mengajukan pertukaran dengan Anda</span>
                                            </div>
                                        </div>
                                        
                                        <?php if($r['status'] == 'pending_karyawan'): ?>
                                            <span class="status-badge badge-process"><i class="fas fa-exclamation-circle"></i> Butuh Respon</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="ticket-body">
                                        <div class="shift-block">
                                            <span class="label">Shift Rekan (Tawaran)</span>
                                            <span class="date"><?= date('d M Y', strtotime($r['tgl_teman'])) ?></span>
                                            <span class="time"><i class="far fa-clock"></i> <?= substr($r['jam_teman'],0,5) ?> WIB</span>
                                        </div>
                                        <i class="fas fa-arrow-right exchange-arrow"></i>
                                        <div class="shift-block text-lg-end">
                                            <span class="label">Shift Anda (Target)</span>
                                            <span class="date"><?= date('d M Y', strtotime($r['tgl_saya'])) ?></span>
                                            <span class="time"><i class="far fa-clock"></i> <?= substr($r['jam_saya'],0,5) ?> WIB</span>
                                        </div>
                                    </div>
                                    
                                    <p class="ticket-reason">
                                        <i class="fas fa-quote-left text-muted me-2" style="opacity:0.5;"></i>
                                        <?= htmlspecialchars($r['alasan']) ?>
                                    </p>
                                    
                                    <div class="ticket-footer">
                                        <?php if($r['status'] == 'pending_karyawan'): ?>
                                            <a href="tukar_jadwal.php?action=tolak&id=<?= $r['id'] ?>" class="btn-action btn-reject" onclick="return confirm('Tolak permintaan tukar ini?');">Tolak</a>
                                            <a href="tukar_jadwal.php?action=terima&id=<?= $r['id'] ?>" class="btn-action btn-accept" onclick="return confirm('Setujui dan teruskan ke Admin?');">Setujui Permintaan</a>
                                        <?php else: ?>
                                            <!-- Status Pasca-Konfirmasi -->
                                            <?php 
                                                $st = $r['status'];
                                                if ($st == 'pending_admin') echo '<span class="status-badge badge-process"><i class="fas fa-user-shield"></i> Validasi Admin</span>';
                                                else if ($st == 'disetujui') echo '<span class="status-badge badge-success"><i class="fas fa-check"></i> Disetujui Admin</span>';
                                                else echo '<span class="status-badge badge-danger"><i class="fas fa-times"></i> Ditolak</span>';
                                            ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4 rounded-4" style="background-color: var(--dash-bg); border: 1px dashed var(--dash-border);">
                                <i class="fas fa-inbox mb-2" style="font-size: 2rem; color: #D1D5DB;"></i>
                                <h6 class="fw-bold text-dark m-0">Inbox Bersih</h6>
                                <p class="text-muted small m-0">Tidak ada pengajuan masuk dari rekan Anda.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- KOTAK KELUAR (OUTBOX) -->
                <div class="dash-card">
                    <h2 class="dash-card-title">
                        <span style="color: var(--dash-text-muted);">●</span> Riwayat Pengajuan Anda
                    </h2>
                    
                    <div class="exchange-list">
                        <?php if(count($riwayat_pengaju) > 0): ?>
                            <?php foreach($riwayat_pengaju as $rp): ?>
                                <div class="exchange-ticket" style="padding: 1rem 1.25rem;">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                                        
                                        <div class="d-flex flex-column gap-2">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="dash-label m-0" style="font-size: 0.7rem;">Target Rekan:</span>
                                                <span class="fw-bold text-dark" style="font-size: 0.95rem;"><?= htmlspecialchars($rp['nama_penerima']) ?></span>
                                            </div>
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="shift-block">
                                                    <span class="date" style="font-size: 0.9rem;"><?= date('d M', strtotime($rp['tgl_saya'])) ?></span>
                                                </div>
                                                <i class="fas fa-arrow-right text-muted" style="font-size: 0.8rem;"></i>
                                                <div class="shift-block">
                                                    <span class="date" style="font-size: 0.9rem;"><?= date('d M', strtotime($rp['tgl_teman'])) ?></span>
                                                </div>
                                            </div>
                                        </div>

                                        <div>
                                            <?php 
                                                $st = $rp['status'];
                                                if ($st == 'pending_karyawan') echo '<span class="status-badge badge-pending"><i class="fas fa-clock"></i> Menunggu Rekan</span>';
                                                else if ($st == 'pending_admin') echo '<span class="status-badge badge-process"><i class="fas fa-user-shield"></i> Validasi Admin</span>';
                                                else if ($st == 'disetujui') echo '<span class="status-badge badge-success"><i class="fas fa-check"></i> Disetujui</span>';
                                                else echo '<span class="status-badge badge-danger"><i class="fas fa-times"></i> Ditolak</span>';
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4 rounded-4" style="border: 1px dashed var(--dash-border);">
                                <p class="text-muted small m-0">Anda belum pernah mengajukan pertukaran shift.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>