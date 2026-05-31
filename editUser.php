<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: dashboard.php");
    exit;
}

include 'connection.php';

$error = '';
$success = '';

// Ambil ID user dari parameter URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Jika tidak ada ID, redirect
if ($id == 0) {
    header("Location: dashboard.php");
    exit;
}

// Ambil data user berdasarkan ID
$query = "SELECT * FROM users WHERE id = $id";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

// Jika user tidak ditemukan
if (!$user) {
    header("Location: dashboard.php");
    exit;
}

// Proses update data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $password = $_POST['password'];
    
    // Validasi
    if (empty($nama) || empty($email) || empty($role)) {
        $error = "Semua field harus diisi!";
    } else {
        // Cek email duplikat (kecuali email milik sendiri)
        $cek_email = "SELECT * FROM users WHERE email = '$email' AND id != $id";
        $cek_result = mysqli_query($conn, $cek_email);
        
        if (mysqli_num_rows($cek_result) > 0) {
            $error = "Email sudah digunakan oleh user lain!";
        } else {
            // Update password jika diisi
            if (!empty($password)) {
                $hashed_password = md5($password); // Ganti dengan password_hash() jika perlu
                $update_query = "UPDATE users SET nama='$nama', email='$email', password='$hashed_password', role='$role' WHERE id=$id";
            } else {
                $update_query = "UPDATE users SET nama='$nama', email='$email', role='$role' WHERE id=$id";
            }
            
            if (mysqli_query($conn, $update_query)) {
                $success = "User berhasil diupdate!";
                // Refresh data user
                $query = "SELECT * FROM users WHERE id = $id";
                $result = mysqli_query($conn, $query);
                $user = mysqli_fetch_assoc($result);
            } else {
                $error = "Gagal mengupdate: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit User - Absensi Digital</title>
    <link rel="stylesheet" href="Style/dashboard_style.css">
    <link rel="icon" href="Assets/smansalaLogo.ico">
    <style>
        .form-container {
            max-width: 500px;
            margin: 50px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            box-sizing: border-box;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #3498db;
        }
        .btn-save {
            background: #27ae60;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        .btn-save:hover {
            background: #219a52;
        }
        .btn-cancel {
            background: #95a5a6;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            margin-top: 10px;
            width: 100%;
        }
        .btn-cancel:hover {
            background: #7f8c8d;
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
        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        .info-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>

<div class="form-container">
    <h2>✏️ Edit User</h2>
    
    <?php if ($error): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success); ?>
            <br><br>
            <a href="dashboard.php" style="color: #155724; font-weight: bold;">← Kembali ke Dashboard</a>
        </div>
    <?php endif; ?>
    
    <form action="" method="POST">
        <div class="form-group">
            <label>Nama Lengkap</label>
            <input type="text" name="nama" value="<?php echo htmlspecialchars($user['nama']); ?>" required>
        </div>
        
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
        </div>
        
        <div class="form-group">
            <label>Password Baru (Kosongkan jika tidak ingin mengubah)</label>
            <input type="password" name="password" placeholder="Masukkan password baru">
            <div class="info-text">* Biarkan kosong jika tidak ingin mengubah password</div>
        </div>
        
        <div class="form-group">
            <label>Role / Hak Akses</label>
            <select name="role" required>
                <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>👤 User</option>
                <option value="manager" <?php echo $user['role'] == 'manager' ? 'selected' : ''; ?>>📊 Manager</option>
                <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>👑 Admin</option>
            </select>
        </div>
        
        <button type="submit" class="btn-save">💾 Simpan Perubahan</button>
        <a href="dashboard.php" class="btn-cancel">← Batal</a>
    </form>
</div>

</body>
</html>