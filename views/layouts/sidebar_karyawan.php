<!-- Sidebar Karyawan Modern -->
<div id="sidebar-wrapper" style="background-color: #ffffff; border-right: 1px solid #f1f5f9; box-shadow: 2px 0 15px rgba(0,0,0,0.02);">
    <!-- Sidebar Header -->
    <div class="sidebar-heading py-4 ps-4 border-bottom" style="border-color: #f1f5f9 !important;">
        <div class="d-flex align-items-center">
            <div class="shadow-sm" style="background-color: #000; color: #F99451; border-radius: 12px; width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; margin-right: 14px; font-size: 1.4rem; font-weight: 800;">K</div>
            <div>
                <span class="d-block" style="font-size: 1.15rem; font-weight: 900; letter-spacing: -0.5px; color: #000; line-height: 1;">Karyawan Hub</span>
                <span style="font-size: 0.75rem; color: #94a3b8; font-weight: 600; text-transform: uppercase;">Portal Pegawai</span>
            </div>
        </div>
    </div>

    <!-- Navigation List -->
    <div class="list-group list-group-flush mt-4 px-3" style="gap: 5px;">
        
        <!-- Section: Dashboard Utama -->
        <small class="text-uppercase fw-bold ps-3 mb-2 mt-2 sidebar-section-title" style="font-size: 0.75rem; letter-spacing: 1.5px; color: #000;">Dashboard Utama</small>
        
        <?php 
        $current_page = basename($_SERVER['PHP_SELF']); 
        $is_beranda = ($current_page == 'dashboard.php') ? 'active-nav-item' : '';
        $is_profil = ($current_page == 'profil.php') ? 'active-nav-item' : '';
        ?>

        <a href="dashboard.php" class="nav-item <?= $is_beranda ?>">
            <div class="nav-icon"><i class="fas fa-home"></i></div>
            <span class="nav-text">Beranda Utama</span>
        </a>
        <a href="profil.php" class="nav-item <?= $is_profil ?>">
            <div class="nav-icon"><i class="fas fa-id-card"></i></div>
            <span class="nav-text">Kelola Profil</span>
        </a>
        
        <!-- Section: Waktu & Kehadiran -->
        <small class="text-uppercase fw-bold ps-3 mt-4 mb-2 sidebar-section-title" style="font-size: 0.75rem; letter-spacing: 1.5px; color: #000;">Waktu & Kehadiran</small>
        
        <a href="agenda_kosong.php" class="nav-item <?= ($current_page == 'agenda_kosong.php') ? 'active-nav-item' : '' ?>">
            <div class="nav-icon"><i class="fas fa-calendar-plus"></i></div>
            <span class="nav-text">Set Jadwal Kosong</span>
        </a>
        <a href="shift.php" class="nav-item <?= ($current_page == 'shift.php') ? 'active-nav-item' : '' ?>">
            <div class="nav-icon"><i class="fas fa-calendar-check"></i></div>
            <span class="nav-text">Shift Jaga Saya</span>
        </a>
        <a href="absensi.php" class="nav-item <?= ($current_page == 'absensi.php') ? 'active-nav-item' : '' ?>">
            <div class="nav-icon"><i class="fas fa-fingerprint"></i></div>
            <span class="nav-text">Check-In / Izin</span>
        </a>
        <a href="tukar_jadwal.php" class="nav-item <?= ($current_page == 'tukar_jadwal.php') ? 'active-nav-item' : '' ?>">
            <div class="nav-icon"><i class="fas fa-exchange-alt"></i></div>
            <span class="nav-text">Tukar Jadwal Shift</span>
        </a>
        
        <!-- Section: Laporan Personal -->
        <small class="text-uppercase fw-bold ps-3 mt-4 mb-2 sidebar-section-title" style="font-size: 0.75rem; letter-spacing: 1.5px; color: #000;">Laporan Personal</small>
        
        <a href="performa.php" class="nav-item <?= ($current_page == 'performa.php') ? 'active-nav-item' : '' ?>">
            <div class="nav-icon"><i class="fas fa-chart-line"></i></div>
            <span class="nav-text">Nilai Kinerja</span>
        </a>
        <a href="gaji.php" class="nav-item <?= ($current_page == 'gaji.php') ? 'active-nav-item' : '' ?>">
            <div class="nav-icon"><i class="fas fa-file-invoice-dollar"></i></div>
            <span class="nav-text">Slip & Gaji</span>
        </a>
        
        <!-- Logout -->
        <div class="mt-4 pt-4 border-top">
            <a href="../../logout.php" class="nav-item nav-logout" style="color: #dc2626;">
                <div class="nav-icon text-danger bg-danger bg-opacity-10"><i class="fas fa-power-off"></i></div>
                <span class="nav-text fw-bold">Logout Sistem</span>
            </a>
        </div>
    </div>
</div>

<style>
/* Modern Sidebar Styling */
.nav-item {
    display: flex;
    align-items: center;
    padding: 0.85rem 1rem;
    border-radius: 12px;
    margin-bottom: 0.25rem;
    text-decoration: none;
    color: #475569;
    transition: all 0.2s ease;
    border: 1px solid transparent;
}
.nav-icon {
    width: 35px;
    height: 35px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 12px;
    font-size: 1.1rem;
    background-color: #f8fafc;
    color: #94a3b8;
    transition: all 0.2s ease;
}
.nav-text {
    font-weight: 600;
    font-size: 0.95rem;
    letter-spacing: 0.3px;
}

/* Hover State */
.nav-item:hover {
    background-color: #f8fafc;
    color: #0f172a;
}
.nav-item:hover .nav-icon {
    background-color: rgba(249, 148, 81, 0.1);
    color: #F99451;
}

/* Active State */
.active-nav-item {
    background-color: #000000 !important;
    color: #ffffff !important;
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}
.active-nav-item .nav-icon {
    background-color: rgba(249, 148, 81, 0.2);
    color: #F99451;
}
.active-nav-item .nav-text {
    color: #ffffff;
}

/* Logout Item */
.nav-logout:hover {
    background-color: #fef2f2;
    border-color: #fee2e2;
}
</style>
