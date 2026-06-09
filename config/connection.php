<?php
$conn = mysqli_connect("localhost", "root", "", "absensidigital");

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>