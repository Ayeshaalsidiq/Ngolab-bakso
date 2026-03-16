<?php
require_once '../../config/config.php';
if (!isKaryawan()) {
    redirect('../../login.php');
}

$user_id = $_SESSION['user_id'];
$filter_bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m');

// Ambil data shift berdasarkan bulan filter untuk SEMUA karyawan
$stmt_shift = mysqli_execute_query($koneksi, "
    SELECT j.*, u.nama 
    FROM jadwal_shift j 
    JOIN users u ON j.user_id = u.id 
    WHERE DATE_FORMAT(j.tanggal, '%Y-%m') = ? 
    ORDER BY j.tanggal ASC, j.jam_mulai ASC
", [$filter_bulan]);
$jadwal_shift = mysqli_fetch_all($stmt_shift, MYSQLI_ASSOC);

include '../layouts/header.php';
include '../layouts/sidebar_karyawan.php';
?>

<style>
    /* PERINGATAN: Pindahkan blok CSS ini ke file global (misal: app.css) 
    dan panggil di header.php. Berhenti menduplikasi ini di setiap file.
    */
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

    .dash-subtitle { color: var(--dash-text-muted); font-size: 0.95rem; }

    .dash-card {
        background: var(--dash-surface);
        border: 1px solid var(--dash-border);
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
        margin-bottom: 1.5rem;
    }

    .dash-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .dash-card-title {
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--dash-text-dark);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    /* Form Filter */
    .filter-group {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .dash-label {
        font-size: 0.8rem;
        font-weight: 700;
        color: var(--dash-text-dark);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin: 0;
    }

    .dash-input-inline {
        background: #F9FAFB;
        border: 1px solid var(--dash-border);
        border-radius: 12px;
        padding: 0.65rem 1rem;
        font-size: 0.95rem;
        color: var(--dash-text-dark);
        font-weight: 600;
        transition: all 0.2s ease;
    }

    .dash-input-inline:focus {
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
        padding: 0.75rem 1.5rem;
        font-weight: 700;
        font-size: 0.95rem;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .dash-btn-primary:hover {
        background-color: var(--dash-accent);
        color: var(--dash-surface);
        transform: translateY(-2px);
    }

    /* Modern Table/List Design */
    .shift-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .shift-item {
        display: grid;
        grid-template-columns: 50px 150px 1fr 200px 120px;
        align-items: center;
        padding: 1rem 1.25rem;
        border: 1px solid var(--dash-border);
        border-radius: 16px;
        background: var(--dash-surface);
        transition: all 0.2s ease;
        gap: 1rem;
    }

    .shift-item:hover {
        border-color: #D1D5DB;
        background: #F9FAFB;
    }

    /* Highlight untuk hari ini dan milik user */
    .shift-item.is-today {
        border-color: var(--dash-accent);
        background: var(--dash-accent-light);
    }
    
    .shift-item.is-mine {
        border-left: 4px solid var(--dash-text-dark);
    }

    .col-no {
        color: var(--dash-text-muted);
        font-weight: 600;
        font-size: 0.9rem;
    }

    .col-date {
        font-weight: 800;
        color: var(--dash-text-dark);
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
    }

    .badge-today {
        background-color: var(--dash-text-dark);
        color: var(--dash-surface);
        font-size: 0.65rem;
        padding: 0.2rem 0.5rem;
        border-radius: 50px;
        font-weight: 800;
        letter-spacing: 0.05em;
        display: inline-block;
        width: max-content;
        animation: pulse 2s infinite;
    }

    .col-employee {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .avatar {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 0.95rem;
    }

    .avatar.mine { background: var(--dash-accent); color: var(--dash-text-dark); }
    .avatar.other { background: #E5E7EB; color: #4B5563; }

    .employee-name {
        font-weight: 700;
        margin: 0;
        font-size: 0.95rem;
    }
    .employee-name.mine { color: var(--dash-accent); }
    .employee-name.other { color: var(--dash-text-dark); }

    .employee-meta {
        font-size: 0.75rem;
        color: var(--dash-text-muted);
        margin: 0;
    }

    .col-time {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: var(--dash-surface);
        border: 1px solid var(--dash-border);
        color: var(--dash-text-dark);
        padding: 0.4rem 0.85rem;
        border-radius: 10px;
        font-family: monospace;
        font-weight: 700;
        font-size: 0.9rem;
    }
    
    .shift-item.is-today .col-time {
        border-color: rgba(249, 148, 81, 0.3);
    }

    .col-status {
        text-align: right;
    }

    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.4rem 0.85rem;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .status-aktif { background: var(--dash-success-light); color: var(--dash-success); }
    .status-tukar { background: var(--dash-warning-light); color: var(--dash-warning); }
    .status-default { background: #F3F4F6; color: var(--dash-text-muted); }

    .action-absen {
        display: block;
        margin-top: 0.5rem;
        font-size: 0.8rem;
        font-weight: 700;
        color: var(--dash-accent);
        text-decoration: none;
        transition: color 0.2s;
    }
    .action-absen:hover { color: var(--dash-text-dark); }

    /* Responsive Grid for mobile */
    @media (max-width: 991px) {
        .shift-item {
            grid-template-columns: 1fr;
            gap: 0.75rem;
            position: relative;
        }
        .col-no { position: absolute; top: 1rem; right: 1.25rem; }
        .col-status { text-align: left; margin-top: 0.5rem; border-top: 1px dashed var(--dash-border); padding-top: 0.75rem;}
        .col-time { width: max-content; }
    }

    @keyframes pulse {
        0% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.05); opacity: 0.8; }
        100% { transform: scale(1); opacity: 1; }
    }
</style>

<div id="page-content-wrapper" class="pb-5 pt-4">
    <div class="container-fluid px-xl-5">
        
        <!-- Header -->
        <div class="dash-header">
            <h1 class="dash-title">Jadwal Shift Pegawai</h1>
            <p class="dash-subtitle">Pantau alokasi jadwal shift bulanan seluruh divisi operasional.</p>
        </div>

        <!-- Filter Card -->
        <div class="dash-card">
            <form method="GET" class="filter-group">
                <label for="bulan" class="dash-label">Filter Bulan</label>
                <input type="month" id="bulan" name="bulan" class="dash-input-inline" value="<?= htmlspecialchars($filter_bulan) ?>" required>
                <button type="submit" class="dash-btn-primary">
                    <i class="fas fa-filter"></i> Terapkan
                </button>
            </form>
        </div>

        <!-- Data Card -->
        <div class="dash-card">
            <div class="dash-card-header">
                <h2 class="dash-card-title">
                    <span style="color: var(--dash-accent);">●</span> Daftar Shift (<?= date('F Y', strtotime($filter_bulan . '-01')) ?>)
                </h2>
            </div>
            
            <div class="shift-list">
                <?php if(count($jadwal_shift) > 0): ?>
                    <?php 
                    $no = 1; 
                    foreach($jadwal_shift as $s): 
                        $is_today = ($s['tanggal'] == date('Y-m-d'));
                        $is_mine = ($s['user_id'] == $user_id);
                        $st = strtolower($s['status']);
                        
                        $item_class = '';
                        if ($is_today) $item_class .= ' is-today';
                        if ($is_mine) $item_class .= ' is-mine';

                        $status_class = 'status-default';
                        if ($st == 'aktif') $status_class = 'status-aktif';
                        else if ($st == 'tukar') $status_class = 'status-tukar';
                    ?>
                    <div class="shift-item <?= $item_class ?>">
                        <div class="col-no">#<?= sprintf('%02d', $no++) ?></div>
                        
                        <div class="col-date">
                            <?= tgl_indo($s['tanggal']) ?>
                            <?php if($is_today): ?>
                                <span class="badge-today">HARI INI</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-employee">
                            <div class="avatar <?= $is_mine ? 'mine' : 'other' ?>">
                                <?= strtoupper(substr($s['nama'], 0, 1)) ?>
                            </div>
                            <div>
                                <p class="employee-name <?= $is_mine ? 'mine' : 'other' ?>">
                                    <?= htmlspecialchars($s['nama']) ?>
                                </p>
                                <?php if($is_mine): ?>
                                    <p class="employee-meta">Akun Anda</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div>
                            <div class="col-time">
                                <i class="far fa-clock" style="color: var(--dash-accent);"></i>
                                <?= substr($s['jam_mulai'],0,5) ?> - <?= substr($s['jam_selesai'],0,5) ?>
                            </div>
                        </div>
                        
                        <div class="col-status">
                            <span class="status-pill <?= $status_class ?>">
                                <i class="fas fa-circle" style="font-size: 0.4rem;"></i> <?= strtoupper($st) ?>
                            </span>
                            
                            <?php if($is_today && $st == 'aktif' && $is_mine): ?>
                                <a href="absensi.php" class="action-absen">
                                    <i class="fas fa-fingerprint me-1"></i> Check In Sekarang
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Empty State -->
                    <div class="text-center py-5 rounded-4" style="border: 1px dashed var(--dash-border); background: var(--dash-bg);">
                        <div class="mb-3">
                            <i class="fas fa-calendar-times" style="font-size: 3rem; color: #D1D5DB;"></i>
                        </div>
                        <h5 class="fw-bold" style="color: var(--dash-text-dark);">Tidak Ada Data</h5>
                        <p class="mb-0" style="color: var(--dash-text-muted); font-size: 0.95rem;">Tidak ditemukan jadwal operasional pada bulan terpilih.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>