<?php
session_start();
include 'connection.php';

$error = '';
$success = '';

// Proses tambah user jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi input tidak kosong
    if (empty($_POST['nama']) || empty($_POST['email']) || empty($_POST['password'])) {
        $error = "Semua field harus diisi!";
    } else {
        // Sanitasi input
        $nama = mysqli_real_escape_string($conn, $_POST['nama']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $password = md5($_POST['password']); // Catatan: MD5 tidak aman, gunakan password_hash()
        
        // Cek email sudah ada atau belum
        $cek = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
        
        if (!$cek) {
            $error = "Error database: " . mysqli_error($conn);
        } elseif (mysqli_num_rows($cek) > 0) {
            $error = "Email sudah digunakan! Silakan gunakan email lain.";
        } else {
            // Simpan ke database
            $query = "INSERT INTO users (nama, email, password) VALUES ('$nama', '$email', '$password')";
            
            if (mysqli_query($conn, $query)) {
                $success = "User berhasil ditambahkan!";
                // Optional: reset form setelah sukses
                // echo "<script>setTimeout(function(){ window.location.href='index.php'; }, 2000);</script>";
            } else {
                $error = "Gagal menyimpan: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tambah User - Absensi</title>
    <link rel="icon" href="Assets/smansalaLogo.png">
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 500px;
            margin: 50px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2 {
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: bold;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            box-sizing: border-box;
        }
        input:focus {
            outline: none;
            border-color: #4CAF50;
        }
        button {
            width: 100%;
            padding: 10px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover {
            background: #45a049;
        }
        .btn-back {
            display: inline-block;
            margin-top: 15px;
            text-align: center;
            width: 100%;
            text-decoration: none;
            color: #666;
        }
        .btn-back:hover {
            color: #333;
        }
        .alert {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Tambah Pengguna Baru</h2>
    
    <!-- Tampilkan pesan error jika ada -->
    <?php if ($error): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <!-- Tampilkan pesan sukses jika ada -->
    <?php if ($success): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success); ?>
            <br><br>
            <a href="index.php" style="color: #155724; font-weight: bold;">Kembali ke Dashboard</a>
        </div>
    <?php endif; ?>
    
    <!-- Form hanya ditampilkan jika belum sukses -->
    <?php if (!$success): ?>
        <form action="" method="POST">
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="nama" placeholder="Masukkan nama lengkap" required autofocus>
            </div>

            <div class="form-group">
                <label for="kelas">Kelas</label>
                <input type="text" name="kelas" placeholder="Masukkan kelas" required>
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="Masukkan email" required>
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Masukkan password" required>
            </div>
            
            <button type="submit">Simpan User</button>
        </form>
        
        <a href="index.php" class="btn-back">← Kembali ke Dashboard</a>
    <?php endif; ?>
</div>

</body>
</html>