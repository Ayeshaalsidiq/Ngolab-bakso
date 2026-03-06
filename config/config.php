<?php
session_start();

$host = 'localhost';
$username = 'root';
$password = ''; 
$database = 'db_masyanto';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Atur timezone
    date_default_timezone_set('Asia/Jakarta');
} catch(PDOException $e) {
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
	
	// variabel pecahkan 0 = tahun
	// variabel pecahkan 1 = bulan
	// variabel pecahkan 2 = tanggal
 
	return $pecahkan[2] . ' ' . $bulan[ (int)$pecahkan[1] ] . ' ' . $pecahkan[0];
}
?>
