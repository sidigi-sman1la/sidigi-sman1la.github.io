<?php
session_start();

include 'connection.php';

// ================== CEK LOGIN ==================
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// ================== CEK ROLE ==================
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager') {
    die("Akses ditolak.");
}

date_default_timezone_set('Asia/Jakarta');

// ================== FILTER ==================
$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : date('m');
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');
$kelas = isset($_GET['kelas']) ? mysqli_real_escape_string($conn, $_GET['kelas']) : '';

// ================== AMBIL DATA KELAS ==================
$kelas_query = mysqli_query($conn, "SELECT DISTINCT kelas FROM users WHERE role = 'user' ORDER BY kelas ASC");

// ================== QUERY REKAP (DIPERBAIKI) ==================
$query = "SELECT 
    users.id, 
    users.nama, 
    users.kelas,
    COUNT(CASE WHEN absensi.status = 'hadir' OR absensi.status = 'terlambat' THEN 1 END) AS hadir,
    COUNT(CASE WHEN absensi.status = 'terlambat' THEN 1 END) AS terlambat,
    COUNT(CASE WHEN absensi.status = 'izin' OR absensi.status = 'sakit' THEN 1 END) AS izin
FROM users 
LEFT JOIN absensi ON users.id = absensi.user_id 
    AND MONTH(absensi.tanggal) = $bulan 
    AND YEAR(absensi.tanggal) = $tahun 
WHERE users.role = 'user'";  // <-- DIUBAH dari 'siswa' ke 'user'

if (!empty($kelas)) {
    $query .= " AND users.kelas = '$kelas'";
}

$query .= " GROUP BY users.id ORDER BY users.kelas ASC, users.nama ASC";

// Debug: echo query untuk cek
// echo "<pre>$query</pre>";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query Error: " . mysqli_error($conn));
}

// Debug: cek jumlah data
// echo "<pre>Jumlah data: " . mysqli_num_rows($result) . "</pre>";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Rekap Absensi | Absensi Digital</title>
    <link rel="icon" href="Assets/smansalaLogo.png">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h2 {
            color: #2c3e50;
            margin-bottom: 10px;
            text-align: center;
        }
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 25px;
            font-size: 14px;
        }
        .filter-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        .filter-box select, .filter-box button {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        .filter-box button {
            background: #3498db;
            color: white;
            border: none;
            cursor: pointer;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: center;
        }
        th {
            background: #3498db;
            color: white;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        @media (max-width: 768px) {
            th, td {
                padding: 8px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <h2>📊 Rekapitulasi Absensi Siswa</h2>
    <div class="subtitle">SMA Negeri 1 Lemahabang Kabupaten Cirebon</div>

    <form method="GET" class="filter-box">
        <select name="bulan">
            <option value="1" <?= $bulan == 1 ? 'selected' : '' ?>>Januari</option>
            <option value="2" <?= $bulan == 2 ? 'selected' : '' ?>>Februari</option>
            <option value="3" <?= $bulan == 3 ? 'selected' : '' ?>>Maret</option>
            <option value="4" <?= $bulan == 4 ? 'selected' : '' ?>>April</option>
            <option value="5" <?= $bulan == 5 ? 'selected' : '' ?>>Mei</option>
            <option value="6" <?= $bulan == 6 ? 'selected' : '' ?>>Juni</option>
            <option value="7" <?= $bulan == 7 ? 'selected' : '' ?>>Juli</option>
            <option value="8" <?= $bulan == 8 ? 'selected' : '' ?>>Agustus</option>
            <option value="9" <?= $bulan == 9 ? 'selected' : '' ?>>September</option>
            <option value="10" <?= $bulan == 10 ? 'selected' : '' ?>>Oktober</option>
            <option value="11" <?= $bulan == 11 ? 'selected' : '' ?>>November</option>
            <option value="12" <?= $bulan == 12 ? 'selected' : '' ?>>Desember</option>
        </select>

        <select name="tahun">
            <?php for($i=date('Y'); $i>=2024; $i--): ?>
                <option value="<?= $i ?>" <?= $tahun == $i ? 'selected' : '' ?>><?= $i ?></option>
            <?php endfor; ?>
        </select>

        <select name="kelas">
            <option value="">Semua Kelas</option>
            <?php 
            // Ambil kelas unik dari database
            $kelas_unique = mysqli_query($conn, "SELECT DISTINCT kelas FROM users WHERE role = 'user' AND kelas IS NOT NULL AND kelas != '' ORDER BY kelas ASC");
            while($k = mysqli_fetch_assoc($kelas_unique)): 
            ?>
                <option value="<?= $k['kelas'] ?>" <?= $kelas == $k['kelas'] ? 'selected' : '' ?>>
                    <?= $k['kelas'] ?>
                </option>
            <?php endwhile; ?>
        </select>

        <button type="submit">Tampilkan</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama</th>
                <th>Kelas</th>
                <th>Hadir</th>
                <th>Terlambat</th>
                <th>Izin/Sakit</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; ?>
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td style="text-align: left;"><?= htmlspecialchars($row['nama']) ?></td>
                        <td><?= htmlspecialchars($row['kelas']) ?></td>
                        <td><?= $row['hadir'] ?></td>
                        <td><?= $row['terlambat'] ?></td>
                        <td><?= $row['izin'] ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="no-data">
                        Belum ada data absensi untuk periode ini.<br>
                        <small>Filter: Bulan <?= $bulan ?>, Tahun <?= $tahun ?>, Kelas: <?= $kelas ?: 'Semua' ?></small>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>