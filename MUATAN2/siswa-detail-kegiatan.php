<?php
// Tautan ke file proses untuk keluar
// Tautan ke file konfigurasi database
session_start(); 
include 'config.php';

// --- Validasi Sesi dan ID Siswa ---
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'siswa'){
    header("location: siswa.php"); 
    exit;
}

if (!isset($_SESSION['id_user'])) {
    header("location: siswa.php?error=no_id");
    exit;
}
$id_siswa = intval($_SESSION['id_user']); 

// --- Ambil ID Kegiatan dan Validasi ---
// Gunakan operator Null Coalescing PHP 7+ jika tersedia, atau ternary untuk kompatibilitas lebih luas
// Karena Anda sudah menggunakan sintaks alternatif di HTML (PHP 5.4+), kita asumsikan PHP 7+ untuk Null Coalescing Operator (??)
// Tapi karena die() di dalam string, kita tetap pakai ternary tradisional.
$id_kegiatan = isset($_GET['id']) ? mysqli_real_escape_string($koneksi, $_GET['id']) : die("ID Kegiatan tidak ditemukan.");

// --- Query Detail Kegiatan ---
$detail_query = mysqli_query($koneksi, "SELECT * FROM kegiatan WHERE id_kegiatan='$id_kegiatan' AND status_persetujuan='approved'");
$keg = mysqli_fetch_assoc($detail_query);

if (!$keg) {
    // Redirect jika kegiatan tidak ditemukan atau belum di-approve
    header("location: siswa-kegiatan.php?info=not_found");
    exit;
}

// --- Pengolahan Data Waktu dan Gambar ---
$timestamp = strtotime($keg['jadwal']);
$formatted_date = date("d F Y", $timestamp);
$formatted_time = date("H:i", $timestamp);
$day_name = date("l", $timestamp);
$days = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
$hari = $days[$day_name];
// Cek file gambar fisik, gunakan logo default jika tidak ada
$gambar_path = (isset($keg['gambar']) && $keg['gambar']) ? 'uploads/' . htmlspecialchars($keg['gambar']) : '';
$gambar_src = (file_exists($gambar_path) && $keg['gambar']) ? $gambar_path : 'assets/images/logo.jpg'; 


// --- Logika Pendaftaran dan Kuota ---
// Menggunakan Null Coalescing Operator (??) yang lebih modern untuk nilai default
$tipe_kegiatan = $keg['tipe_kegiatan'] ?? 'wajib';
$maks_peserta = $keg['maks_peserta'];
$is_registered = false;
$current_participants = 0;
$is_full = false;

if ($tipe_kegiatan == 'opsional') {
    // Hitung peserta saat ini
    $count_q = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM peserta_kegiatan WHERE id_kegiatan='$id_kegiatan'");
    if ($count_q) {
        $current_participants = mysqli_fetch_assoc($count_q)['total'];
    }
    
    // Cek apakah kuota penuh (hanya jika maks_peserta tidak NULL)
    if ($maks_peserta !== NULL && $current_participants >= $maks_peserta) {
        $is_full = true;
    }
    
    // Cek apakah siswa sudah terdaftar
    $cek_daftar_q = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM peserta_kegiatan WHERE id_kegiatan='$id_kegiatan' AND id_siswa='$id_siswa'");
    if ($cek_daftar_q && mysqli_fetch_assoc($cek_daftar_q)['total'] > 0) {
        $is_registered = true;
    }
}

// --- Fungsi Terjemahan Bulan ---
function terjemahkanBulan($tanggal) {
    $bulan_indo = [
        'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret', 
        'April' => 'April', 'May' => 'Mei', 'June' => 'Juni', 
        'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September', 
        'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'
    ];
    // Gunakan strtr untuk mengganti nama bulan dalam tanggal yang sudah diformat
    return strtr($tanggal, $bulan_indo);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail: <?php echo htmlspecialchars($keg['nama_kegiatan']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* CSS yang Anda berikan sudah sangat baik dan lengkap, tidak perlu diubah */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-blue: #00bcd4;
            --primary-dark: #0097a7;
            --primary-light: #4dd0e1;
            --secondary-blue: #80deea;
            --gray-bg: #f8fafc;
            --white: #ffffff;
            --text-dark: #1e293b;
            --text-grey: #64748b;
            --green-wajib: #00bfa5;
            --green-dark: #008e76;
            --yellow-opsional: #ffd54f;
            --yellow-dark: #ffb300;
            --red-full: #ef4444;
            --blue-join: #26c6da;
            --shadow-sm: 0 1px 3px 0 rgb(0 0 0 / 0.1);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #e0f7fa 0%, #ffffff 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .main-container {
            max-width: 900px;
            margin: 0 auto;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--white);
            color: var(--primary-blue);
            padding: 12px 25px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 20px;
            transition: all 0.3s;
            box-shadow: var(--shadow-md);
        }

        .back-button:hover {
            transform: translateX(-5px);
            box-shadow: var(--shadow-lg);
        }

        .activity-card {
            background: var(--white);
            border-radius: 25px;
            box-shadow: var(--shadow-xl);
            overflow: hidden;
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from {
                transform: translateY(30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .card-image-wrapper {
            width: 100%;
            height: 450px;
            overflow: hidden;
            position: relative;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-light) 100%);
        }

        .card-image {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .card-content {
            padding: 40px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            padding-bottom: 25px;
            border-bottom: 3px solid #f1f5f9;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .type-badge {
            font-size: 0.95em;
            font-weight: 800;
            padding: 10px 20px;
            border-radius: 30px;
            color: white;
            text-transform: uppercase;
            white-space: nowrap;
            letter-spacing: 0.5px;
            box-shadow: var(--shadow-md);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .wajib { 
            background: linear-gradient(135deg, var(--green-wajib), var(--green-dark)); 
        }
        .opsional { 
            background: linear-gradient(135deg, var(--yellow-opsional), var(--yellow-dark)); 
        }

        .card-title {
            font-size: 2.2em;
            font-weight: 800;
            color: var(--text-dark);
            margin: 0;
            line-height: 1.3;
            flex: 1;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 35px;
        }

        .info-box {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            padding: 25px;
            border-radius: 20px;
            border-left: 5px solid var(--primary-blue);
            transition: all 0.3s;
            box-shadow: var(--shadow-sm);
        }

        .info-box:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        .info-label {
            font-size: 0.85em;
            color: var(--text-grey);
            font-weight: 600;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-label i {
            color: var(--primary-blue);
            font-size: 1.3em;
        }

        .info-value {
            font-size: 1.15em;
            font-weight: 700;
            color: var(--text-dark);
            line-height: 1.4;
        }

        .section-title {
            font-size: 1.5em;
            color: var(--text-dark);
            font-weight: 800;
            margin-bottom: 20px;
            padding-top: 20px;
            border-top: 3px solid #f1f5f9;
            margin-top: 35px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-title i {
            color: var(--primary-blue);
            font-size: 1.1em;
        }

        .description-box {
            color: var(--text-dark);
            line-height: 1.9;
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            padding: 30px;
            border-radius: 20px;
            border: 2px solid #e2e8f0;
            font-size: 1.05em;
            box-shadow: var(--shadow-sm);
        }

        .action-buttons {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 3px solid #f1f5f9;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 16px 32px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: var(--shadow-md);
            font-size: 1em;
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6b7280, #4b5563);
            color: white;
        }
        .btn-secondary:hover { 
            background: linear-gradient(135deg, #4b5563, #374151);
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        .btn-join {
            background: linear-gradient(135deg, var(--blue-join), var(--primary-blue));
            color: white;
        }
        .btn-join:hover { 
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }
        
        .btn-status-registered, .btn-status-full {
            background: linear-gradient(135deg, var(--green-wajib), var(--green-dark));
            color: white;
            cursor: default;
        }

        .btn-status-full {
            background: linear-gradient(135deg, var(--red-full), #dc2626);
        }

        .kuota-alert {
            padding: 15px 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
        }

        .kuota-alert.success {
            background: #d1fae5;
            color: var(--green-dark);
            border: 2px solid var(--green-wajib);
        }

        .kuota-alert.warning {
            background: #fef3c7;
            color: #92400e;
            border: 2px solid var(--yellow-opsional);
        }

        .kuota-alert.danger {
            background: #fee2e2;
            color: #991b1b;
            border: 2px solid var(--red-full);
        }

        .kuota-alert i {
            font-size: 1.3em;
        }

        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .card-content {
                padding: 25px;
            }
            
            .card-header {
                flex-direction: column;
            }
            
            .card-title {
                font-size: 1.6em;
            }

            .card-image-wrapper {
                height: 300px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <a href="siswa-kegiatan.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Kembali ke Daftar Kegiatan
        </a>

        <div class="activity-card">
            <div class="card-image-wrapper">
                <img src="<?php echo $gambar_src; ?>" alt="<?php echo htmlspecialchars($keg['nama_kegiatan']); ?>" class="card-image">
            </div>
            
            <div class="card-content">
                <div class="card-header">
                    <h1 class="card-title"><?php echo htmlspecialchars($keg['nama_kegiatan']); ?></h1>
                    <span class="type-badge <?php echo htmlspecialchars($tipe_kegiatan); ?>">
                        <i class="fas fa-<?php echo ($tipe_kegiatan == 'wajib' ? 'star' : 'check-circle'); ?>"></i>
                        <?php echo strtoupper(htmlspecialchars($tipe_kegiatan)); ?>
                    </span>
                </div>

                <?php if ($tipe_kegiatan == 'opsional'): ?>
                    <?php if ($is_registered): ?>
                        <div class="kuota-alert success">
                            <i class="fas fa-check-circle"></i>
                            <span>Anda telah terdaftar dalam kegiatan ini!</span>
                        </div>
                    <?php elseif ($is_full): ?>
                        <div class="kuota-alert danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>Kuota peserta sudah penuh. Pendaftaran ditutup.</span>
                        </div>
                    <?php else: 
                        // Perhitungan sisa kuota untuk alert
                        $sisa = ($maks_peserta !== NULL) ? ($maks_peserta - $current_participants) : 0;
                        $alert_class = 'success';
                        $alert_message = 'Silakan daftar!';

                        if ($maks_peserta !== NULL) {
                            if ($sisa <= 5) { // Warning jika sisa tinggal 5 atau kurang
                                $alert_class = 'warning';
                                $alert_message = "Tersisa $sisa/$maks_peserta kuota pendaftar. Segera daftar!";
                            } else {
                                $alert_message = "Tersisa $sisa/$maks_peserta kuota pendaftar.";
                            }
                        } else {
                            $alert_message = "Kuota tidak terbatas - Silakan daftar!";
                        }
                    ?>
                        <div class="kuota-alert <?php echo $alert_class; ?>">
                            <i class="fas fa-info-circle"></i>
                            <span><?php echo $alert_message; ?></span>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="info-grid">
                    <div class="info-box">
                        <div class="info-label">
                            <i class="fas fa-calendar-day"></i>
                            Hari & Tanggal
                        </div>
                        <div class="info-value">
                            <?php echo $hari . ", " . terjemahkanBulan($formatted_date); ?>
                        </div>
                    </div>
                    
                    <div class="info-box">
                        <div class="info-label">
                            <i class="fas fa-clock"></i>
                            Waktu Pelaksanaan
                        </div>
                        <div class="info-value"><?php echo $formatted_time; ?> WIB</div>
                    </div>
                    
                    <div class="info-box">
                        <div class="info-label">
                            <i class="fas fa-map-marker-alt"></i>
                            Lokasi Kegiatan
                        </div>
                        <div class="info-value"><?php echo htmlspecialchars($keg['lokasi']); ?></div>
                    </div>
                    
                    <?php if ($tipe_kegiatan == 'opsional'): ?>
                    <div class="info-box" style="border-left-color: <?php echo $is_full ? 'var(--red-full)' : 'var(--green-wajib)'; ?>;">
                        <div class="info-label">
                            <i class="fas fa-users"></i>
                            Kuota Peserta
                        </div>
                        <div class="info-value">
                            <?php 
                                if ($maks_peserta === NULL) {
                                    echo "Tidak Terbatas";
                                } else {
                                    echo $current_participants . " / " . $maks_peserta . " Peserta";
                                }
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="section-title">
                    <i class="fas fa-bullseye"></i>
                    Tujuan Kegiatan
                </div>
                
                <div class="description-box">
                    <?php echo nl2br(htmlspecialchars($keg['tujuan'])); ?>
                </div>
                
                <div class="action-buttons">
                    <a href="siswa-kegiatan.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                    
                    <?php if ($tipe_kegiatan == 'opsional'): ?>
                        <?php if ($is_registered): ?>
                            <span class="btn btn-status-registered">
                                <i class="fas fa-check-circle"></i> Anda Sudah Terdaftar
                            </span>
                        <?php elseif ($is_full): ?>
                            <span class="btn btn-status-full">
                                <i class="fas fa-times-circle"></i> Kuota Penuh
                            </span>
                        <?php else: ?>
                            <a href="siswa-ikut-kegiatan-process.php?id=<?php echo htmlspecialchars($keg['id_kegiatan']); ?>" 
                               onclick="return confirm('Apakah Anda yakin ingin mendaftar kegiatan ini?')" 
                               class="btn btn-join">
                                <i class="fas fa-user-plus"></i> Daftar Sekarang
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>