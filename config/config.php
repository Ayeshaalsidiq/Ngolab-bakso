<?php
session_start();

$host = 'localhost';
$username = 'root';
$password = ''; 
$database = 'db_masyanto';

try {
    $koneksi = mysqli_connect($host, $username, $password, $database);
    if (!$koneksi) {
        die("Koneksi Database Gagal: " . mysqli_connect_error());
    }
    // Set charset
    mysqli_set_charset($koneksi, "utf8mb4");
    // Atur timezone
    date_default_timezone_set('Asia/Jakarta');
} catch(Exception $e) {
    die("Koneksi Database Gagal: " . $e->getMessage());
}

// Helper Functions
function isLogged() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isLogged() && $_SESSION['role'] === 'admin';
}

function isKaryawan() {
    return isLogged() && $_SESSION['role'] === 'karyawan';
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function tgl_indo($tanggal){
	$bulan = array (
		1 =>   'Januari',
		'Februari',
		'Maret',
		'April',
		'Mei',
		'Juni',
		'Juli',
		'Agustus',
		'September',
		'Oktober',
		'November',
		'Desember'
	);
	$pecahkan = explode('-', $tanggal);
	
 
	return $pecahkan[2] . ' ' . $bulan[ (int)$pecahkan[1] ] . ' ' . $pecahkan[0];
}
?>
