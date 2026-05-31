<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'connection.php';

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Ambil data user saat ini
$query = "SELECT * FROM users WHERE id = $user_id";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

// Proses update profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    if (empty($nama) || empty($email)) {
        $error = "Nama dan email harus diisi!";
    } else {
        // Cek email duplikat (kecuali email sendiri)
        $cek_email = "SELECT * FROM users WHERE email = '$email' AND id != $user_id";
        $cek_result = mysqli_query($conn, $cek_email);
        
        if (mysqli_num_rows($cek_result) > 0) {
            $error = "Email sudah digunakan oleh user lain!";
        } else {
            $update_query = "UPDATE users SET nama='$nama', email='$email' WHERE id=$user_id";
            
            if (mysqli_query($conn, $update_query)) {
                $_SESSION['nama'] = $nama;
                $success = "Profil berhasil diupdate!";
                // Refresh data user
                $query = "SELECT * FROM users WHERE id = $user_id";
                $result = mysqli_query($conn, $query);
                $user = mysqli_fetch_assoc($result);
            } else {
                $error = "Gagal mengupdate profil: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Profil | Absensi Digital</title>
    <link rel="icon" href="Assets/smansalaLogo.png">
    <style>
        .profile-container {
            max-width: 600px;
            margin: 50px auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 50%;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .profile-header h2 {
            margin: 10px 0 5px;
        }
        .profile-header p {
            opacity: 0.9;
        }
        .profile-body {
            padding: 30px;
        }
        .info-group {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .info-label {
            font-weight: bold;
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .info-value {
            font-size: 16px;
            color: #333;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            box-sizing: border-box;
        }
        input:focus {
            outline: none;
            border-color: #667eea;
        }
        .btn-save {
            background: #667eea;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        .btn-save:hover {
            background: #5a67d8;
        }
        .btn-back {
            display: inline-block;
            background: #95a5a6;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 8px;
            margin-top: 10px;
            text-align: center;
        }
        .btn-back:hover {
            background: #7f8c8d;
        }
        .alert {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .role-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .role-admin { background: #e74c3c; color: white; }
        .role-manager { background: #3498db; color: white; }
        .role-user { background: #27ae60; color: white; }
    </style>
</head>
<body>

<div class="profile-container">
    <div class="profile-header">
        <div class="profile-avatar">
            <?php 
                if ($user['role'] == 'admin') echo "👑";
                elseif ($user['role'] == 'manager') echo "📊";
                else echo "👤";
            ?>
        </div>
        <h2><?php echo htmlspecialchars($user['nama']); ?></h2>
        <p>
            <span class="role-badge role-<?php echo $user['role']; ?>">
                <?php echo ucfirst($user['role']); ?>
            </span>
        </p>
    </div>
    
    <div class="profile-body">
        <?php if ($success): ?>
            <div class="alert alert-success">✅ <?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">❌ <?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="info-group">
            <div class="info-label">📧 Email</div>
            <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
        </div>
                
        <div class="info-group">
            <div class="info-label">📅 Terdaftar Sejak</div>
            <div class="info-value"><?php echo date('d F Y', strtotime($user['created_at'] ?? date('Y-m-d'))); ?></div>
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label>✏️ Edit Nama Lengkap</label>
                <input type="text" name="nama" value="<?php echo htmlspecialchars($user['nama']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>✏️ Edit Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            
            <button type="submit" class="btn-save">💾 Simpan Perubahan</button>
        </form>
        
        <a href="dashboard.php" class="btn-back">← Kembali ke Dashboard</a>
    </div>
</div>

</body>
</html>