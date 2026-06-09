<?php
session_start();
include '../../config/connection.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['email']) || empty($_POST['password'])) {
        $error = "Email dan password harus diisi!";
    } else {
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $password = md5($_POST['password']);

        $query = "SELECT * FROM users WHERE email='$email' AND password='$password'";
        $result = mysqli_query($conn, $query);
        
        if (!$result) {
            $error = "Error database: " . mysqli_error($conn);
        } else {
            $data = mysqli_fetch_assoc($result);
            
            if ($data) {
                $_SESSION['user_id'] = $data['id'];
                $_SESSION['nama'] = $data['nama'];
                $_SESSION['role'] = $data['role'];
                $_SESSION['username'] = $data['username'];
                $_SESSION['email'] = $data['email'];
                $_SESSION['kelas'] = $data['kelas'] ?? '';
                
                header("Location: ../../index.php");
                exit();
            } else {
                $error = "Login gagal! Email atau password salah.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Masuk | Sidigi Smansala</title>
    <link rel="icon" href="../../assets/image/smansalaLogo.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .main-container {
            max-width: 1200px;
            width: 100%;
            background: white;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            display: flex;
            flex-wrap: wrap;
        }

        /* LEFT SIDE - FORM LOGIN */
        .login-section {
            flex: 1;
            min-width: 300px;
            padding: 48px 40px;
            background: white;
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 40px;
        }

        .logo-area img {
            width: 50px;
            height: 50px;
            object-fit: contain;
        }

        .logo-area h3 {
            font-size: 18px;
            color: #1e293b;
            font-weight: 700;
        }

        .logo-area span {
            color: #3b82f6;
        }

        .welcome-text h1 {
            font-size: 28px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 12px;
        }

        .welcome-text p {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 32px;
        }

        .input-group {
            margin-bottom: 20px;
        }

        .input-group label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #334155;
            margin-bottom: 8px;
        }

        .input-group .input-icon {
            position: relative;
        }

        .input-group .input-icon i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 16px;
        }

        .input-group input {
            width: 100%;
            padding: 12px 16px 12px 44px;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s;
            background: #f8fafc;
        }

        .input-group input:focus {
            outline: none;
            border-color: #3b82f6;
            background: white;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            font-size: 13px;
        }

        .checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #64748b;
        }

        .checkbox input {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

        .forgot-password {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }

        .forgot-password:hover {
            text-decoration: underline;
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 20px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(59, 130, 246, 0.4);
        }

        .register-link {
            text-align: center;
            font-size: 14px;
            color: #64748b;
        }

        .register-link a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 600;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .error-message {
            background: #fef2f2;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 13px;
            margin-bottom: 20px;
            border-left: 4px solid #dc2626;
        }

        /* RIGHT SIDE - HERO SECTION */
        .hero-section {
            flex: 1;
            min-width: 300px;
            background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
            padding: 48px 40px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .hero-header .badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            padding: 6px 14px;
            border-radius: 40px;
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 24px;
        }

        .hero-header h2 {
            font-size: 32px;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 16px;
        }

        .hero-header p {
            font-size: 14px;
            opacity: 0.8;
            line-height: 1.5;
            margin-bottom: 40px;
        }

        .stats-grid {
            display: flex;
            gap: 30px;
            margin-top: auto;
        }

        .stat-item h3 {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .stat-item p {
            font-size: 13px;
            opacity: 0.7;
        }

        .hero-footer {
            margin-top: 40px;
            padding-top: 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            font-size: 12px;
            text-align: center;
            opacity: 0.7;
        }

        .hero-footer a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-weight: 500;
        }

        /* RESPONSIVE */
        @media (max-width: 900px) {
            .main-container {
                flex-direction: column;
                max-width: 500px;
            }
            
            .login-section {
                padding: 32px 24px;
            }
            
            .hero-section {
                padding: 32px 24px;
                text-align: center;
            }
            
            .stats-grid {
                justify-content: center;
            }
            
            .hero-header h2 {
                font-size: 24px;
            }
            
            .stat-item h3 {
                font-size: 24px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 12px;
            }
            
            .login-section {
                padding: 24px 20px;
            }
            
            .welcome-text h1 {
                font-size: 22px;
            }
            
            .stats-grid {
                gap: 20px;
            }
            
            .stat-item h3 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>

<div class="main-container">
    <!-- LEFT SIDE - FORM LOGIN -->
    <div class="login-section">
        <div class="logo-area">
            <img src="../../assets/image/smansalaLogo.png" alt="Logo SMAN LAA">
            <h3>Absensi Digital<br>SMA Negeri 1 Lemahabang</h3>
        </div>
        
        <div class="welcome-text">
            <h1>Masuk ke Akun</h1>
            <p>Masukkan email dan password Anda</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form action="" method="POST">
            <div class="input-group">
                <label>Email</label>
                <div class="input-icon">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" placeholder="Masukkan email Anda" required autofocus>
                </div>
            </div>
            
            <div class="input-group">
                <label>Password</label>
                <div class="input-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="Masukkan password Anda" required>
                </div>
            </div>
            
            <div class="options">
                <label class="checkbox">
                    <input type="checkbox" name="remember"> Ingat saya
                </label>
                <a href="forgot-password.php" class="forgot-password">Lupa password?</a>
            </div>
            
            <button type="submit" class="btn-login">
                <i class="fas fa-arrow-right-to-bracket" style="margin-right: 8px;"></i>
                Masuk ke Dashboard
            </button>
        </form>
        
        <div class="register-link">
            Belum punya akun? <a href="../userProcess/addUser.php">Daftar di sini</a>
        </div>
    </div>
    
    <!-- RIGHT SIDE - HERO SECTION -->
    <div class="hero-section">
        <div class="hero-header">
            <h2>SMA Negeri 1 Lemahabang<br>Cirebon</h2>
            <p>Sistem absensi digital siswa SMA Negeri Lemahabang Kabupaten Cirebon yang modern, aman, dan mudah digunakan.</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-item">
                <h3>847</h3>
                <p>Total Siswa</p>
            </div>
            <div class="stat-item">
                <h3>56</h3>
                <p>Total Guru</p>
            </div>
            <div class="stat-item">
                <h3>30</h3>
                <p>Total Kelas</p>
            </div>
        </div>
        
        <div class="hero-footer">
            <a href="https://sman1lacirebon.sch.id" target="_blank">Absensi Digital SMA Negeri 1 Lemahabang Kabupaten Cirebon</a>
        </div>
    </div>
</div>

</body>
</html>