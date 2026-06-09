<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../index.php");
    exit;
}

include '../../config/connection.php';

// Ambil ID user dari parameter URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Jika tidak ada ID atau ID adalah user yang sedang login
if ($id == 0 || $id == $_SESSION['user_id']) {
    $_SESSION['error'] = "Tidak bisa menghapus user sendiri!";
    header("Location: ../../index.php");
    exit;
}

// Cek apakah user ada
$check_query = "SELECT * FROM users WHERE id = $id";
$check_result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($check_result) == 0) {
    $_SESSION['error'] = "User tidak ditemukan!";
    header("Location: ../../index.php");
    exit;
}

// Proses hapus user
$delete_query = "DELETE FROM users WHERE id = $id";

if (mysqli_query($conn, $delete_query)) {
    $_SESSION['success'] = "User berhasil dihapus!";
} else {
    $_SESSION['error'] = "Gagal menghapus user: " . mysqli_error($conn);
}

header("Location: ../../index.php");
exit;
?>