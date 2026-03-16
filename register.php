<?php
require_once 'config/config.php';

if (isLogged()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = $_POST['nama'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $nomor_hp = $_POST['nomor_hp'] ?? '';

    // Validasi username tidak boleh duplikat
    $stmt = mysqli_prepare($koneksi, "SELECT id FROM users WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    
    if (mysqli_stmt_num_rows($stmt) > 0) {
        $error = "Identitas ditolak. Username tersebut sudah terdaftar di sistem.";
    } else {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt_insert = mysqli_prepare($koneksi, "INSERT INTO users (role, nama, username, password, nomor_hp) VALUES ('karyawan', ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt_insert, "ssss", $nama, $username, $hashed_password, $nomor_hp);
            mysqli_stmt_execute($stmt_insert);
            
            // LOGIKA YANG BENAR: Jangan biarkan form di state POST.
            // Lempar pengguna kembali ke halaman login dengan pesan sukses.
            redirect('login.php?msg=registered');
            exit;
            
        } catch(Exception $e) {
            $error = "Terjadi kegagalan sistem saat merekam data. Silakan hubungi administrator.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Akun - HRIS Bakso Mas Yanto</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Catatan Kritis: Fakta bahwa blok CSS ini masih di-copy-paste di sini membuktikan 
           Anda belum memisahkan file CSS Anda menjadi global. Ini adalah utang teknis. */
        :root {
            --brand-orange: #F99451;
            --brand-orange-dark: #e07b38;
            --brand-black: #111827;
            --brand-gray: #f3f4f6;
            --brand-text-muted: #6b7280;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #E5E7EB; 
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            margin: 0;
        }

        .auth-container {
            width: 100%;
            max-width: 1100px;
        }

        .modern-card {
            display: flex;
            background: #ffffff;
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            min-height: 650px;
        }

        /* --- LEFT PANEL: SOLID BRANDING --- */
        .auth-brand {
            width: 45%;
            background: var(--brand-orange);
            color: #ffffff;
            padding: 4rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }

        .auth-brand::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: radial-gradient(circle at 2px 2px, rgba(255,255,255,0.15) 1px, transparent 0);
            background-size: 24px 24px;
            pointer-events: none;
        }

        .logo-wrapper {
            width: 120px;
            height: 120px;
            background: #ffffff;
            border-radius: 50%;
            padding: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            margin-bottom: 2.5rem;
            z-index: 1;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .brand-heading {
            font-weight: 900;
            font-size: 2.5rem;
            line-height: 1.1;
            margin-bottom: 1rem;
            letter-spacing: -0.03em;
            z-index: 1;
        }

        .brand-tagline {
            font-size: 1.1rem;
            font-weight: 500;
            opacity: 0.9;
            line-height: 1.6;
            z-index: 1;
        }

        /* --- RIGHT PANEL: CLEAN FORM --- */
        .auth-form-area {
            width: 55%;
            padding: 4rem 4rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: #ffffff;
        }

        .form-title {
            font-weight: 800;
            color: var(--brand-black);
            font-size: 2rem;
            margin-bottom: 0.5rem;
            letter-spacing: -0.02em;
        }

        .form-subtitle {
            color: var(--brand-text-muted);
            margin-bottom: 2rem;
            font-size: 1rem;
        }

        .modern-input-group {
            position: relative;
            margin-bottom: 1.75rem;
        }

        .modern-input {
            width: 100%;
            border: none;
            border-bottom: 2px solid #D1D5DB;
            padding: 1rem 0 0.5rem 0;
            font-size: 1.05rem;
            color: var(--brand-black);
            background: transparent;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .modern-input:focus {
            outline: none;
            border-bottom-color: var(--brand-orange);
        }

        .modern-label {
            position: absolute;
            left: 0;
            top: 1.2rem;
            color: var(--brand-text-muted);
            font-size: 1.05rem;
            font-weight: 500;
            transition: 0.3s ease all;
            pointer-events: none;
        }

        .modern-input:focus ~ .modern-label,
        .modern-input:not(:placeholder-shown) ~ .modern-label {
            top: -10px;
            font-size: 0.8rem;
            color: var(--brand-orange);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .btn-daftar {
            background-color: var(--brand-black);
            color: #ffffff;
            border: none;
            border-radius: 16px;
            padding: 1.25rem;
            font-size: 1.1rem;
            font-weight: 700;
            width: 100%;
            transition: all 0.3s ease;
            margin-top: 1rem;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-daftar:hover {
            background-color: var(--brand-orange);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(249, 148, 81, 0.3);
        }

        .link-masuk {
            color: var(--brand-orange);
            font-weight: 700;
            text-decoration: none;
            transition: color 0.2s;
        }

        .link-masuk:hover {
            color: var(--brand-black);
        }

        .alert-box {
            border-radius: 12px;
            padding: 1rem 1.25rem;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border: none;
        }
        
        .alert-error {
            background-color: #FEF2F2;
            color: #991B1B;
            border-left: 4px solid #EF4444;
        }

        /* Responsivitas */
        @media (max-width: 991px) {
            .modern-card {
                flex-direction: column;
            }
            .auth-brand {
                width: 100%;
                padding: 3rem 2rem;
                align-items: center;
                text-align: center;
            }
            .auth-form-area {
                width: 100%;
                padding: 3rem 2rem;
            }
            .brand-heading { font-size: 2rem; }
        }
    </style>
</head>
<body>

<div class="auth-container">
    <div class="modern-card">
        
        <!-- PANEL KIRI: BRANDING -->
        <div class="auth-brand">
            <div class="logo-wrapper">
                <img src="assets/img/logo.png" alt="Logo" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjRjk5NDUxIiBzdHJva2Utd2lkdGg9IjIiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCI+PHBhdGggZD0iTTEyIDJhMTAgMTAgMCAxIDAgMTAgMTAgMTAgMTAgMCAwIDAtMTAtMTB6bTAgMTRhNCA0IDAgMSAxIDQtNCA0IDQgMCAwIDEtNCA0eiIvPjwvc3ZnPg=='">
            </div>
            <h1 class="brand-heading">Bakso<br>Mas Yanto.</h1>
            <p class="brand-tagline">Portal pendaftaran pegawai baru. Pastikan data yang Anda masukkan valid.</p>
        </div>

        <!-- PANEL KANAN: FORM REGISTRASI -->
        <div class="auth-form-area">
            <div>
                <h2 class="form-title">Buat Akun Baru</h2>
                <p class="form-subtitle">Lengkapi identitas operasional Anda di bawah ini.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert-box alert-error">
                    <i class="fas fa-exclamation-triangle fs-5"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                
                <div class="row gx-4">
                    <div class="col-md-6">
                        <div class="modern-input-group">
                            <input type="text" class="modern-input" id="nama" name="nama" placeholder=" " required autocomplete="off">
                            <label class="modern-label" for="nama">Nama Lengkap</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="modern-input-group">
                            <input type="text" class="modern-input" id="username" name="username" placeholder=" " required autocomplete="off">
                            <label class="modern-label" for="username">Username</label>
                        </div>
                    </div>
                </div>

                <div class="modern-input-group">
                    <input type="text" class="modern-input" id="nomor_hp" name="nomor_hp" placeholder=" " required autocomplete="off">
                    <label class="modern-label" for="nomor_hp">Nomor WhatsApp Aktif</label>
                </div>
                
                <div class="modern-input-group mb-4">
                    <input type="password" class="modern-input" id="password" name="password" placeholder=" " required>
                    <label class="modern-label" for="password">Kata Sandi Sistem</label>
                </div>
                
                <button type="submit" class="btn-daftar">
                    Kirim Data Registrasi <i class="fas fa-arrow-right ms-2"></i>
                </button>
                
                <div class="text-center mt-4">
                    <p class="text-muted fw-medium mb-0">
                        Sudah terdaftar di sistem? <a href="login.php" class="link-masuk">Akses masuk</a>
                    </p>
                </div>
            </form>
        </div>

    </div>
</div>

</body>
</html>