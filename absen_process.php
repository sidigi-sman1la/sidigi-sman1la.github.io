<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'connection.php';

// ================== PROSES ABSENSI ==================
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    date_default_timezone_set('Asia/Jakarta');

    $user_id = $_SESSION['user_id'];
    $tanggal = date('Y-m-d');
    $jam_sekarang = date('H:i:s');

    // Ambil status absensi
    $status_input = $_POST['status'] ?? 'hadir';

    // Default status
    $status = '';

    // Lokasi
    $latitude = $_POST['latitude'] ?? null;
    $longitude = $_POST['longitude'] ?? null;
    $jarak_server = null;

    // Upload surat
    $surat_name = null;

    // Fungsi hitung jarak
    function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $R = 6371000;

        $dLat = ($lat2 - $lat1) * M_PI / 180;
        $dLon = ($lon2 - $lon1) * M_PI / 180;

        $a =
            sin($dLat / 2) * sin($dLat / 2) +
            cos($lat1 * M_PI / 180) *
            cos($lat2 * M_PI / 180) *
            sin($dLon / 2) *
            sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $R * $c;
    }

    // ================== CEK SUDAH ABSEN ==================
    if (empty($error)) {

        $check_query = "SELECT * FROM absensi 
                        WHERE user_id = '$user_id' 
                        AND tanggal = '$tanggal'";

        $check_result = mysqli_query($conn, $check_query);

        if (mysqli_num_rows($check_result) > 0) {

            $error = "Anda sudah melakukan absensi hari ini!";
        }
    }

    // ================== UPLOAD SURAT ==================
    if (empty($error) && isset($_FILES['surat']) && $_FILES['surat']['error'] !== UPLOAD_ERR_NO_FILE) {

        if ($_FILES['surat']['error'] === UPLOAD_ERR_OK) {
        $allowed_extensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif'];
            $file_extension = strtolower(pathinfo($_FILES['surat']['name'], PATHINFO_EXTENSION));

            if (!in_array($file_extension, $allowed_extensions)) {
                $error = "Format file surat tidak valid. Gunakan PDF, DOC, DOCX, atau gambar.";
            } else {
                $surat_name = time() . '_' . basename($_FILES['surat']['name']);

                if (!is_dir('uploads')) {
                    mkdir('uploads', 0755, true);
                }

                if (!move_uploaded_file($_FILES['surat']['tmp_name'], 'uploads/' . $surat_name)) {
                    $error = "Gagal mengunggah surat.";
                }
            }

        } else {

            $error = "Gagal mengunggah surat.";
        }
    }

    if (empty($error) && in_array($status_input, ['izin', 'sakit']) && $surat_name === null) {
        $error = "Surat wajib diunggah untuk status izin atau sakit.";
    }

    // ================== ABSENSI HADIR / TERLAMBAT ==================
    if (empty($error) && $status_input === 'hadir') {

        // Validasi lokasi
        if (
            empty($latitude) ||
            empty($longitude) ||
            !is_numeric($latitude) ||
            !is_numeric($longitude)
        ) {

            $error = "Gagal mendapatkan lokasi! Pastikan GPS aktif.";

        } else {

            // Koordinat sekolah
            $school_lat = -6.830273;
            $school_lng = 108.621136;

            $max_distance = 100;

            $jarak_server = round(
                calculateDistance(
                    (float)$latitude,
                    (float)$longitude,
                    $school_lat,
                    $school_lng
                )
            );
            // Validasi jarak
            if ($jarak_server > $max_distance) {

                $error = "Anda berada $jarak_server meter dari sekolah!";

            } else {

                // Validasi jam
                if ($jam_sekarang < '05:00:00') {

                    $error = "Absensi belum dibuka!";

                } else {
                    if ($status_input === 'hadir') {
                        if ($jam_sekarang <= '06:15:00') {
                            $status = 'hadir';
                        } else {
                            $status = 'terlambat';
                            if ($surat_name === null) {
                            }
                        }
                    } else {
                        $status = 'terlambat';
                    }
                }
            }
        }
    }

    if (empty($error) && in_array($status_input, ['izin', 'sakit'])) {
        $status = $status_input;
        $latitude = null;
        $longitude = null;
        $jarak_server = null;
    }

    // ================== SIMPAN DATABASE ==================
    if (empty($error)) {

        $query = "INSERT INTO absensi 
        (
            user_id,
            tanggal,
            jam_masuk,
            latitude,
            longitude,
            jarak,
            `status`,
            surat
        )
        VALUES
        (
            '$user_id',
            '$tanggal',
            '$jam_sekarang',
            '$latitude',
            '$longitude',
            '$jarak_server',
            '$status',
            '$surat_name'
        )";

        if (mysqli_query($conn, $query)) {

            $success = "✅ Absensi berhasil! Status: " . strtoupper($status);

        } else {

            $error = "Gagal menyimpan absensi: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Absensi Digital</title>
    <link rel="icon" href="Assets/smansalaLogo.png">
    <link rel="stylesheet" href="Style/absenStyle.css">
</head>

<body>

<div class="container">

    <h2>Form Absensi</h2>

    <?php if ($success): ?>

        <div class="alert-success">
            <?= $success ?>
        </div>

    <?php endif; ?>

    <?php if ($error): ?>

        <div class="alert-error">
            <?= $error ?>
        </div>

    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

        <div class="form-group">
            <label>Pilih Status Absensi</label>
            <label><input type="radio" name="status" value="hadir" checked> Hadir</label>
            <label><input type="radio" name="status" value="izin"> Izin/Sakit</label>
        </div>

        <div id="suratContainer" style="display:none; margin-bottom: 1rem;">
            <label for="surat">Unggah surat izin/sakit (PDF, DOC, DOCX, foto)</label>
            <input type="file" name="surat" id="surat" accept=".pdf,.doc,.docx,image/*">
        </div>

        <input type="hidden" name="latitude" id="latitude">
        <input type="hidden" name="longitude" id="longitude">

        
        <button type="submit"
                id="btnAbsen"
                class="btn">
            Absen Sekarang
        </button>

    </form>
    <a href="dashboard.php" class="btn-back">← Kembali ke Dashboard</a>
</div>

<script>
    // ================== GPS ==================

    const SCHOOL_LAT = -6.830273;
    const SCHOOL_LNG = 108.621136;
    
    let lokasiTerkunci = false; // Flag untuk memastikan lokasi sudah didapat

    function calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371000;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a =
            Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(lat1 * Math.PI / 180) *
            Math.cos(lat2 * Math.PI / 180) *
            Math.sin(dLon / 2) *
            Math.sin(dLon / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c;
    }

    function getLocation() {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject("Browser tidak mendukung GPS");
                return;
            }

            // Opsi GPS dengan timeout lebih lama
            const options = {
                enableHighAccuracy: true,
                timeout: 10000,  // Tunggu 10 detik
                maximumAge: 0
            };

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    document.getElementById('latitude').value = position.coords.latitude;
                    document.getElementById('longitude').value = position.coords.longitude;
                    lokasiTerkunci = true;
                    
                    // Hitung jarak untuk feedback
                    const jarak = calculateDistance(
                        position.coords.latitude,
                        position.coords.longitude,
                        SCHOOL_LAT,
                        SCHOOL_LNG
                    );
                    console.log(`Lokasi didapat! Jarak ke sekolah: ${Math.round(jarak)} meter`);
                    
                    resolve(position);
                },
                function(error) {
                    let pesanError = "";
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            pesanError = "Izin lokasi ditolak. Izinkan akses lokasi di browser.";
                            break;
                        case error.POSITION_UNAVAILABLE:
                            pesanError = "Lokasi tidak tersedia. Pastikan GPS aktif.";
                            break;
                        case error.TIMEOUT:
                            pesanError = "Waktu habis. Coba refresh halaman.";
                            break;
                    }
                    reject(pesanError);
                },
                options
            );
        });
    }

    function toggleSurat() {
        const status = document.querySelector('input[name="status"]:checked').value;
        const suratContainer = document.getElementById('suratContainer');
        
        // Tampilkan surat untuk izin/sakit saja
        if (status === 'izin' || status === 'sakit') {
            suratContainer.style.display = 'block';
        } else {
            suratContainer.style.display = 'none';
        }
    }

    // Cegah submit sebelum lokasi didapat
    document.querySelector('form').addEventListener('submit', async function(e) {
        const status = document.querySelector('input[name="status"]:checked').value;
        
        // Jika status hadir, harus ada lokasi
        if (status === 'hadir') {
            // Cek apakah lokasi sudah ada
            let lat = document.getElementById('latitude').value;
            let lng = document.getElementById('longitude').value;
            
            if (!lat || !lng) {
                e.preventDefault(); // Batalkan submit
                
                // Tampilkan pesan loading
                const btn = document.querySelector('#btnAbsen');
                const btnText = btn.innerHTML;
                btn.innerHTML = '⏳ Mendapatkan lokasi...';
                btn.disabled = true;
                
                try {
                    await getLocation();
                    // Setelah lokasi dapat, submit ulang form
                    btn.innerHTML = btnText;
                    btn.disabled = false;
                    e.target.submit();
                } catch(err) {
                    alert("❌ " + err);
                    btn.innerHTML = btnText;
                    btn.disabled = false;
                }
            }
        }
    });

    window.onload = function() {
        toggleSurat();
        
        // Ambil lokasi di background (tidak wajib selesai)
        getLocation().catch(err => {
            console.warn("Lokasi belum didapat:", err);
            // Tidak perlu alert, nanti saat submit akan cek lagi
        });
        
        // Event listener radio button
        document.querySelectorAll('input[name="status"]').forEach(function(element) {
            element.addEventListener('change', toggleSurat);
        });
    };
</script>

</body>
</html>