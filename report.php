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

// ================== FILTER SAMA DENGAN REKAP ==================
$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : date('m');
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');
$kelas = isset($_GET['kelas']) ? mysqli_real_escape_string($conn, $_GET['kelas']) : '';

// ================== AMBIL DATA ==================
$query = "SELECT 
    users.id, 
    users.nama, 
    users.kelas,
    COUNT(CASE WHEN absensi.status = 'hadir' OR absensi.status = 'terlambat' THEN 1 END) AS hadir,
    COUNT(CASE WHEN absensi.status = 'terlambat' THEN 1 END) AS terlambat,
    COUNT(CASE WHEN absensi.status = 'izin' OR absensi.status = 'sakit' THEN 1 END) AS izin_sakit
FROM users 
LEFT JOIN absensi ON users.id = absensi.user_id 
    AND MONTH(absensi.tanggal) = $bulan 
    AND YEAR(absensi.tanggal) = $tahun 
WHERE users.role = 'user'";

if (!empty($kelas)) {
    $query .= " AND users.kelas = '$kelas'";
}

$query .= " GROUP BY users.id ORDER BY users.kelas ASC, users.nama ASC";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query Error: " . mysqli_error($conn));
}

// ================== SET HEADER EXPORT EXCEL ==================
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="rekap_absensi_' . $bulan . '_' . $tahun . '.csv"');

// Buat file output
$output = fopen('php://output', 'w');

// Tambahkan BOM untuk UTF-8 (agar Excel bisa baca)
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Header kolom
fputcsv($output, [
    'No',
    'Nama',
    'Kelas',
    'Hadir',
    'Terlambat',
    'Izin/Sakit'
]);

// Data
$no = 1;
while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, [
        $no++,
        $row['nama'],
        $row['kelas'],
        $row['hadir'],
        $row['terlambat'],
        $row['izin_sakit']
    ]);
}

fclose($output);
exit;
?>