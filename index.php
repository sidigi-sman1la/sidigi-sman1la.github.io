<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: pages/auth/login.php");
    exit;
}

include 'config/connection.php';

// Ambil role dari session
$role = $_SESSION['role'];
$nama = $_SESSION['nama'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard | Absensi Digital</title>
    <link rel="stylesheet" href="assets/style/dashboardStyle.css">
    <link rel="icon" href="assets/image/smansalaLogo.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
</head>
<body>

<!--========== Navbar ==========-->
<div class="navbar">
    <h1>Absensi Digital SMA Negeri 1 Lemahabang Kabupaten Cirebon</h1>
    <div class="right-section">
        <div class="user-info">
            <span>
                <?php
                    if ($role == 'admin');
                    else echo htmlspecialchars($nama); ?></span>
            <span class="role-badge">
                <?php 
                    if ($role == 'admin') echo "Administrator";
                    elseif ($role == 'manager') echo "Manager";
                    else echo "Pengguna";
                ?>
            </span>
            <a href="pages/auth/logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
</div>

<div class="container">
    <!--====== Welcome Card + Real Time Clock ======-->
    <div class="welcome-card">
        <div class="welcome-text">
            <h2>Selamat Datang di Dashboard Absensi Digital</h2>
            <p>SMA Negeri 1 Lemahabang Kabupaten Cirebon</p>
        </div>
        <div class="welcome-clock">
            <div class="clock" id="realTimeClock">
                <div id="time">--:--:--</div>
                <div id="date" class="date">---, -- --- ----</div>
            </div>
        </div>
    </div>

    <?php if ($role == 'admin'): ?>
        
        <!-- ============ TAMPILAN ADMIN ============ -->
        <div class="admin-panel">
            <div class="admin-header">
                <h2>Panel Admin</h2>
                <a href="pages/userProcess/addUser.php" class="btn-primary">+ Tambah User Baru</a>
            </div>
            
            <h3>Daftar Seluruh Pengguna</h3>
            <?php
            $query = "SELECT * FROM users ORDER BY id DESC";
            $result = mysqli_query($conn, $query);
            
            if (mysqli_num_rows($result) > 0):
            ?>
            <div style="overflow-x: auto;">
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($user = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['nama']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="role-badge-small role-<?php echo $user['role']; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td class="action-buttons">
                                <a href="pages/userProcess/editUser.php?id=<?php echo $user['id']; ?>" class="edit-btn">Edit</a>
                                <button onclick="deleteUser(<?php echo $user['id']; ?>)" class="delete-btn">Hapus</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <p>Belum ada pengguna terdaftar.</p>
            <?php endif; ?>
        </div>
        
        <div class="menu-grid">
            <a href="pages/absensiProcess/rekapAbsensi.php" class="menu-card">
                <div class="icon">📊</div>
                <div>
                    <h3>Rekap Absensi</h3>
                    <p>Lihat laporan kehadiran</p>
                </div>
            </a>
            <a href="pages/absensiProcess/report.php" class="menu-card">
                <div class="icon">📈</div>
                <div>
                    <h3>Laporan</h3>
                    <p>Export laporan ke Excel/PDF</p>
                </div>
            </a>
            <a href="#" class="menu-card">
                <div class="icon">⚙️</div>
                <div>
                    <h3>Pengaturan</h3>
                    <p>Konfigurasi sistem</p>
                </div>
            </a>
        </div>
        
    <?php elseif ($role == 'manager'): ?>
        
        <div class="admin-panel">
            <div class="admin-header">
                <h2>Panel Manager</h2>
                <a href="pages/userProcess/addUser.php" class="btn-primary">+ Tambah User</a>
            </div>
            
            <h3>Daftar User (Non-Admin)</h3>
            <?php
            $query = "SELECT * FROM users WHERE role != 'admin' ORDER BY id DESC";
            $result = mysqli_query($conn, $query);
            
            if (mysqli_num_rows($result) > 0):
            ?>
            <div style="overflow-x: auto;">
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($user = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['nama']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="role-badge-small role-<?php echo $user['role']; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <p>Belum ada user terdaftar selain admin.</p>
            <?php endif; ?>
        </div>
        
        <div class="menu-grid">
            <a href="pages/absensiProcess/rekapAbsensi.php" class="menu-card">
                <div class="icon">📊</div>
                <div>
                    <h3>Rekap Absensi</h3>
                    <p>Lihat laporan kehadiran</p>
                </div>
            </a>
            <a href="pages/absensiProcess/report.php" class="menu-card">
                <div class="icon">📈</div>
                <div>
                    <h3>Laporan</h3>
                    <p>Export laporan ke Excel/PDF</p>
                </div>
            </a>
        </div>
        
    <?php else: ?>
        
        <div class="menu-grid">
            <a href="pages/absensiProcess/absenProcess.php" class="menu-card">
                <div class="icon">📝</div>
                <div>
                    <h3>Absensi</h3>
                    <p>Lakukan absensi masuk/pulang</p>
                </div>
            </a>
            <a href="pages/absensiProcess/riwayatAbsensi.php" class="menu-card">
                <div class="icon">📜</div>
                <div>
                    <h3>Riwayat Absensi</h3>
                    <p>Lihat riwayat kehadiran Anda</p>
                </div>
            </a>
            <a href="pages/userProcess/profile.php" class="menu-card">
                <div class="icon">👤</div>
                <div>
                    <h3>Profil Saya</h3>
                    <p>Update data diri</p>
                </div>
            </a>
        </div>
        
        <div class="info-tambahan" style="background: #e3f2fd; padding: 20px; border-radius: 10px; margin-top: 30px; text-align: center;">
            <p style="color: #1976d2;">✅ Anda login sebagai <strong>Pengguna</strong>. Anda hanya bisa melakukan absensi dan melihat riwayat sendiri.</p>
        </div>
        
    <?php endif; ?>
</div>

<footer>
    <p>&copy; <?php echo date('Y'); ?> Absensi Digital SMA Negeri 1 Lemahabang</p>
</footer>

<script>
function updateClock() {
    const now = new Date();
    
    let hours = now.getHours().toString().padStart(2, '0');
    let minutes = now.getMinutes().toString().padStart(2, '0');
    let seconds = now.getSeconds().toString().padStart(2, '0');
    const timeString = `${hours}:${minutes}:${seconds}`;
    
    const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    
    const dayName = days[now.getDay()];
    const day = now.getDate();
    const month = months[now.getMonth()];
    const year = now.getFullYear();
    
    const dateString = `${dayName}, ${day} ${month} ${year}`;
    
    document.getElementById('time').textContent = timeString;
    document.getElementById('date').textContent = dateString;
}

setInterval(updateClock, 1000);
updateClock();

function deleteUser(id) {
    if (confirm('Apakah Anda yakin ingin menghapus user ini?')) {
        window.location.href = 'pages/userProcess/deleteUser.php?id=' + id;
    }
}
</script>

</body>
</html>