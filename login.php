<?php
require_once 'config/config.php';

if (isLogged()) {
    redirect('index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['foto_profil'] = $user['foto_profil'];
        
        redirect('index.php');
    } else {
        $error = "Username atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - HRIS Bakso Mas Yanto</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #FF7F11; /* Orange */
            --secondary: #FFB703; /* Yellow-Orange */
            --dark: #121212; /* Black */
            --light: #FFFFFF;
            --gray-bg: #F8F9FA;
        }
        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--gray-bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .auth-wrapper {
            width: 100%;
            padding: 2rem 0;
        }
        .auth-card {
            border: none;
            border-radius: 1.5rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.08);
            background: var(--light);
            overflow: hidden;
        }
        .auth-sidebar {
            background: linear-gradient(135deg, var(--dark) 0%, #2A2A2A 100%);
            color: var(--light);
            padding: 4rem 2rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
        }
        .auth-sidebar::after {
            content: '';
            position: absolute;
            bottom: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: var(--primary);
            border-top-left-radius: 100%;
            opacity: 0.1;
        }
        .auth-sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 150px;
            height: 150px;
            background: var(--secondary);
            border-bottom-right-radius: 100%;
            opacity: 0.1;
        }
        .brand-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: 800;
            color: var(--light);
            margin-bottom: 2rem;
            box-shadow: 0 10px 25px rgba(255, 127, 17, 0.3);
            position: relative;
            z-index: 1;
        }
        .auth-form-container {
            padding: 4rem 3rem;
        }
        .form-control {
            padding: 0.8rem 1.2rem;
            border-radius: 0.75rem;
            border: 1px solid #E0E0E0;
            font-weight: 500;
            background-color: #FDFDFD;
        }
        .form-control:focus {
            box-shadow: 0 0 0 4px rgba(255, 127, 17, 0.15);
            border-color: var(--primary);
            background-color: var(--light);
        }
        .form-label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }
        .btn-auth {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            border: none;
            color: var(--light);
            padding: 0.9rem;
            border-radius: 0.75rem;
            font-weight: 700;
            font-size: 1.1rem;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 15px rgba(255, 127, 17, 0.25);
        }
        .btn-auth:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 20px rgba(255, 127, 17, 0.35);
            color: var(--light);
        }
        .auth-link {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s;
        }
        .auth-link:hover {
            color: var(--secondary);
            text-decoration: underline;
        }
        @media (max-width: 767.98px) {
            .auth-sidebar { display: none; }
            .auth-form-container { padding: 2.5rem 2rem; }
        }
    </style>
</head>
<body>

<div class="auth-wrapper">
    <div class="container border-0">
        <div class="row justify-content-center">
            <div class="col-xl-9 col-lg-10">
                <div class="card auth-card">
                    <div class="row g-0">
                        <div class="col-md-5 auth-sidebar">
                            <div class="brand-icon">BM</div>
                            <h2 class="fw-bold mb-3">Bakso Mas Yanto</h2>
                            <p class="mb-0 opacity-75">Sistem Manajemen SDM Cerdas & Terintegrasi</p>
                        </div>
                        <div class="col-md-7">
                            <div class="auth-form-container">
                                <div class="mb-5 text-center text-md-start">
                                    <h3 class="fw-bold text-dark mb-1">Selamat Datang!</h3>
                                    <p class="text-muted">Login ke akun Anda untuk melanjutkan</p>
                                </div>

                                <?php if (isset($_GET['msg']) && $_GET['msg'] == 'logout'): ?>
                                    <div class="alert alert-success border-0 shadow-sm rounded-3 d-flex align-items-center" role="alert">
                                        <div>Anda berhasil logout.</div>
                                    </div>
                                <?php endif; ?>

                                <?php if ($error): ?>
                                    <div class="alert alert-danger border-0 shadow-sm rounded-3 d-flex align-items-center" role="alert">
                                        <div><?= htmlspecialchars($error) ?></div>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" action="">
                                    <div class="mb-4">
                                        <label for="username" class="form-label">Username</label>
                                        <input type="text" class="form-control" id="username" name="username" required autofocus placeholder="Masukkan username Anda">
                                    </div>
                                    <div class="mb-5">
                                        <label for="password" class="form-label d-flex justify-content-between">
                                            <span>Password</span>
                                        </label>
                                        <input type="password" class="form-control" id="password" name="password" required placeholder="Masukkan password Anda">
                                    </div>
                                    
                                    <div class="d-grid mb-4">
                                        <button type="submit" class="btn btn-auth">Masuk ke Sistem</button>
                                    </div>
                                    
                                    <div class="text-center mt-4">
                                        <p class="text-muted fw-medium mb-0">Karyawan baru? <a href="register.php" class="auth-link">Daftar Akun</a></p>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
