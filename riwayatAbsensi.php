<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'connection.php';

$user_id = $_SESSION['user_id'];
$nama = $_SESSION['nama'];

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filter bulan dan tahun
$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : date('m');
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');

// Query untuk mengambil data absensi dengan filter
$where = "user_id = $user_id";
if ($bulan && $tahun) {
    $where .= " AND MONTH(tanggal) = $bulan AND YEAR(tanggal) = $tahun";
}

$query = "SELECT * FROM absensi WHERE $where ORDER BY tanggal DESC, jam_masuk DESC LIMIT $offset, $limit";
$result = mysqli_query($conn, $query);

// Hitung total data untuk pagination
$total_query = "SELECT COUNT(*) as total FROM absensi WHERE $where";
$total_result = mysqli_query($conn, $total_query);
$total_row = mysqli_fetch_assoc($total_result);
$total_data = $total_row['total'];
$total_pages = ceil($total_data / $limit);

// ========== QUERY STATISTIK YANG DIPERBAIKI ==========
// TERLAMBAT tetap dihitung sebagai HADIR
$stat_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'hadir' OR status = 'terlambat' THEN 1 ELSE 0 END) as hadir,
    SUM(CASE WHEN status = 'terlambat' THEN 1 ELSE 0 END) as terlambat,
    SUM(CASE WHEN status = 'izin' THEN 1 ELSE 0 END) as izin
FROM absensi 
WHERE user_id = $user_id AND MONTH(tanggal) = $bulan AND YEAR(tanggal) = $tahun";
$stat_result = mysqli_query($conn, $stat_query);
$stats = mysqli_fetch_assoc($stat_result);

// Jika tidak ada data, set default 0
if (!$stats) {
    $stats = ['total' => 0, 'hadir' => 0, 'terlambat' => 0, 'izin' => 0];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Riwayat | Absensi Digital</title>
    <link rel="icon" href="Assets/smansalaLogo.png">
    <style>
        /* ========================================
           RESET & BASE STYLES
           ======================================== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            min-height: 100vh;
            padding: 20px;
        }

        /* ========================================
           RIWAYAT CONTAINER
           ======================================== */
        .riwayat-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-title {
            text-align: center;
            margin-bottom: 30px;
            color: #2c3e50;
        }

        .page-title h1 {
            font-size: 28px;
            margin-bottom: 8px;
        }

        .page-title p {
            color: #666;
            font-size: 14px;
        }

        /* ========================================
           STATISTIK CARD
           ======================================== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card h3 {
            font-size: 28px;
            margin-bottom: 8px;
        }

        .stat-card p {
            color: #666;
            font-size: 14px;
        }

        .stat-total { border-bottom: 4px solid #3498db; }
        .stat-hadir { border-bottom: 4px solid #27ae60; }
        .stat-terlambat { border-bottom: 4px solid #f39c12; }
        .stat-alpha { border-bottom: 4px solid #e74c3c; }

        /* ========================================
           FILTER BOX
           ======================================== */
        .filter-box {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .filter-form {
            display: flex;
            gap: 15px;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .filter-group {
            flex: 1;
            min-width: 150px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
            font-size: 14px;
        }

        .filter-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            background: white;
            cursor: pointer;
        }

        .filter-group select:focus {
            outline: none;
            border-color: #3498db;
        }

        .btn-filter {
            background: #3498db;
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }

        .btn-filter:hover {
            background: #2980b9;
        }

        /* ========================================
           TABLE STYLES
           ======================================== */
        .table-container {
            background: white;
            border-radius: 12px;
            overflow-x: auto;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f8f9fa;
            font-weight: bold;
            color: #555;
            font-size: 14px;
        }

        td {
            font-size: 14px;
            color: #333;
        }

        tr:hover {
            background: #f8f9fa;
        }

        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-hadir { background: #d4edda; color: #155724; }
        .status-terlambat { background: #fff3cd; color: #856404; }
        .status-izin { background: #d1ecf1; color: #0c5460; }

        /* No Data */
        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .no-data p {
            margin-bottom: 10px;
        }

        /* ========================================
           PAGINATION
           ======================================== */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 25px;
            flex-wrap: wrap;
        }

        .pagination a, .pagination span {
            padding: 8px 15px;
            background: white;
            border-radius: 8px;
            text-decoration: none;
            color: #3498db;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }

        .pagination .active {
            background: #3498db;
            color: white;
        }

        .pagination a:hover {
            background: #2980b9;
            color: white;
        }

        /* Button Back */
        .btn-back {
            display: inline-block;
            margin-top: 25px;
            background: #95a5a6;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 8px;
            transition: background 0.3s;
        }

        .btn-back:hover {
            background: #7f8c8d;
        }

        /* ========================================
           MOBILE VIEW (max-width: 768px)
           ======================================== */
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }

            .page-title h1 {
                font-size: 22px;
            }

            .page-title p {
                font-size: 12px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
                margin-bottom: 20px;
            }

            .stat-card {
                padding: 15px;
            }

            .stat-card h3 {
                font-size: 22px;
            }

            .stat-card p {
                font-size: 11px;
            }

            .filter-box {
                padding: 15px;
            }

            .filter-form {
                flex-direction: column;
                gap: 10px;
            }

            .filter-group {
                min-width: 100%;
            }

            .filter-group label {
                font-size: 12px;
                margin-bottom: 5px;
            }

            .filter-group select {
                padding: 8px;
                font-size: 13px;
            }

            .btn-filter {
                width: 100%;
                padding: 10px;
            }

            th, td {
                padding: 10px 8px;
                font-size: 12px;
            }

            .status-badge {
                padding: 3px 8px;
                font-size: 10px;
            }

            .no-data {
                padding: 40px 15px;
            }

            .no-data p {
                font-size: 13px;
            }

            .pagination a, .pagination span {
                padding: 6px 12px;
                font-size: 12px;
            }

            .btn-back {
                width: 100%;
                text-align: center;
                margin-top: 20px;
            }
        }

        /* ========================================
           SMALL MOBILE (max-width: 480px)
           ======================================== */
        @media (max-width: 480px) {
            body {
                padding: 10px;
            }

            .page-title h1 {
                font-size: 18px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 8px;
            }

            .stat-card {
                padding: 10px;
            }

            .stat-card h3 {
                font-size: 18px;
            }

            .stat-card p {
                font-size: 10px;
            }

            th, td {
                padding: 8px 6px;
                font-size: 11px;
            }

            .status-badge {
                padding: 2px 6px;
                font-size: 9px;
            }

            .pagination a, .pagination span {
                padding: 5px 10px;
                font-size: 11px;
            }
        }

        /* ========================================
           TABLET VIEW (769px - 1024px)
           ======================================== */
        @media (min-width: 769px) and (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(4, 1fr);
            }

            .filter-form {
                flex-wrap: wrap;
            }

            .filter-group {
                min-width: 200px;
            }
        }

        /* ========================================
           LANDSCAPE MODE (max-height: 500px)
           ======================================== */
        @media (max-height: 500px) and (orientation: landscape) {
            body {
                padding: 10px;
            }

            .stats-grid {
                grid-template-columns: repeat(4, 1fr);
                gap: 10px;
                margin-bottom: 15px;
            }

            .stat-card {
                padding: 8px;
            }

            .stat-card h3 {
                font-size: 16px;
            }

            .filter-box {
                padding: 10px;
                margin-bottom: 15px;
            }

            .table-container {
                max-height: 300px;
                overflow-y: auto;
            }
        }

    </style>
</head>
<body>

<div class="riwayat-container">
    <div class="page-title">
        <h1>Riwayat Absensi</h1>
        <p>SMA Negeri 1 Lemahabang Kabupaten Cirebon</p>
    </div>
    
    <!-- Statistik -->
    <div class="stats-grid">
        <div class="stat-card stat-total">
            <h3><?php echo $stats['total'] ?? 0; ?></h3>
            <p>Total Absensi</p>
        </div>
        <div class="stat-card stat-hadir">
            <h3 style="color: #27ae60;"><?php echo $stats['hadir'] ?? 0; ?></h3>
            <p>✅ Hadir</p>
        </div>
        <div class="stat-card stat-terlambat">
            <h3 style="color: #f39c12;"><?php echo $stats['terlambat'] ?? 0; ?></h3>
            <p>⏰ Terlambat</p>
        </div>
        <div class="stat-card stat-alpha">
            <h3 style="color: #e74c3c;"><?php echo $stats['izin'] ?? 0; ?></h3>
            <p>📝Izin/Sakit</p>
        </div>
    </div>
    
    <!-- Filter -->
    <div class="filter-box">
        <form method="GET" class="filter-form">
            <div class="filter-group">
                <label>📅 Bulan</label>
                <select name="bulan">
                    <option value="1" <?php echo $bulan == 1 ? 'selected' : ''; ?>>Januari</option>
                    <option value="2" <?php echo $bulan == 2 ? 'selected' : ''; ?>>Februari</option>
                    <option value="3" <?php echo $bulan == 3 ? 'selected' : ''; ?>>Maret</option>
                    <option value="4" <?php echo $bulan == 4 ? 'selected' : ''; ?>>April</option>
                    <option value="5" <?php echo $bulan == 5 ? 'selected' : ''; ?>>Mei</option>
                    <option value="6" <?php echo $bulan == 6 ? 'selected' : ''; ?>>Juni</option>
                    <option value="7" <?php echo $bulan == 7 ? 'selected' : ''; ?>>Juli</option>
                    <option value="8" <?php echo $bulan == 8 ? 'selected' : ''; ?>>Agustus</option>
                    <option value="9" <?php echo $bulan == 9 ? 'selected' : ''; ?>>September</option>
                    <option value="10" <?php echo $bulan == 10 ? 'selected' : ''; ?>>Oktober</option>
                    <option value="11" <?php echo $bulan == 11 ? 'selected' : ''; ?>>November</option>
                    <option value="12" <?php echo $bulan == 12 ? 'selected' : ''; ?>>Desember</option>
                </select>
            </div>
            <div class="filter-group">
                <label>📆 Tahun</label>
                <select name="tahun">
                    <?php for($y = 2023; $y <= date('Y'); $y++): ?>
                        <option value="<?php echo $y; ?>" <?php echo $tahun == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <button type="submit" class="btn-filter">🔍 Tampilkan</button>
        </form>
    </div>
    
    <!-- Tabel Riwayat -->
    <div class="table-container">
        <?php if (mysqli_num_rows($result) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Jam Masuk</th>
                        <th>Jam Pulang</th>
                        <th>Status</th>
                        <th>Lokasi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = $offset + 1;
                    while($row = mysqli_fetch_assoc($result)): 
                        $status_class = '';
                        $status_text = '';
                        $status_icon = '';
                        
                        switch(strtolower($row['status'])) {
                            case 'hadir':
                                $status_class = 'status-hadir';
                                $status_text = 'Hadir';
                                $status_icon = '✅';
                                break;
                            case 'terlambat':
                                $status_class = 'status-terlambat';
                                $status_text = 'Terlambat';
                                $status_icon = '⏰';
                                break;
                            case 'izin':
                                $status_class = 'status-izin';
                                $status_text = 'Izin';
                                $status_icon = '📝';
                                break;
                        }
                    ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><?php echo date('d F Y', strtotime($row['tanggal'])); ?></td>
                        <td><?php echo $row['jam_masuk'] ?? '-'; ?></td>
                        <td><?php echo $row['jam_pulang'] ?? '-'; ?></td>
                        <td>
                            <span class="status-badge <?php echo $status_class; ?>">
                                <?php echo $status_icon . ' ' . $status_text; ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                            if (isset($row['jarak']) && $row['jarak'] > 0) {
                                echo $row['jarak'] . ' m dari sekolah';
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-data">
                <p>📭 Belum ada data absensi untuk periode ini.</p>
                <p>Silakan lakukan absensi terlebih dahulu.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page-1; ?>&bulan=<?php echo $bulan; ?>&tahun=<?php echo $tahun; ?>">« Prev</a>
        <?php endif; ?>
        
        <?php for($i = 1; $i <= $total_pages; $i++): ?>
            <?php if ($i == $page): ?>
                <span class="active"><?php echo $i; ?></span>
            <?php else: ?>
                <a href="?page=<?php echo $i; ?>&bulan=<?php echo $bulan; ?>&tahun=<?php echo $tahun; ?>"><?php echo $i; ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        
        <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $page+1; ?>&bulan=<?php echo $bulan; ?>&tahun=<?php echo $tahun; ?>">Next »</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <a href="dashboard.php" class="btn-back">← Kembali ke Dashboard</a>
</div>

</body>
</html>