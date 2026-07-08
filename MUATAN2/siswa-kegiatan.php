<?php
// Baris-baris ini tidak berubah karena sudah aman

session_start(); 
// Pastikan file config.php ada dan berisi koneksi $koneksi
include 'config.php';

// Cek sesi login
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'siswa'){
    header("location: siswa.php");
    exit;
}

if (!isset($_SESSION['id_user'])) {
    header("location: siswa.php?error=no_id");
    exit;
}

// 1. Ambil dan sanitasi ID Siswa (dijamin integer)
$id_siswa = intval($_SESSION['id_user']);

// Query data siswa
$query_siswa = mysqli_query($koneksi, "SELECT * FROM users WHERE id='$id_siswa' AND role='siswa'");

if (!$query_siswa || mysqli_num_rows($query_siswa) == 0) {
    // Fallback data jika query gagal atau user tidak ditemukan
    $data_siswa = array(
        'nama' => (isset($_SESSION['nama']) ? $_SESSION['nama'] : '-'),
        'nis' => (isset($_SESSION['nis']) ? $_SESSION['nis'] : '-'),
        'kelas' => (isset($_SESSION['kelas_lengkap']) ? $_SESSION['kelas_lengkap'] : '-'),
        'foto_profil' => ''
    );
} else {
    $data_siswa = mysqli_fetch_assoc($query_siswa);
}

// Data Siswa (menggunakan data database, fallback ke sesi)
$nama_siswa = '-'; 
if (isset($data_siswa['nama'])) { $nama_siswa = $data_siswa['nama']; } 
elseif (isset($_SESSION['nama'])) { $nama_siswa = $_SESSION['nama']; }

$nis = '-';
if (isset($data_siswa['nis'])) { $nis = $data_siswa['nis']; } 
elseif (isset($_SESSION['nis'])) { $nis = $_SESSION['nis']; }

$kelas = '-';
if (isset($data_siswa['kelas'])) { $kelas = $data_siswa['kelas']; } 
elseif (isset($_SESSION['kelas_lengkap'])) { $kelas = $_SESSION['kelas_lengkap']; }

$foto_profil = isset($data_siswa['foto_profil']) ? $data_siswa['foto_profil'] : '';


// --- Logika Filter Kegiatan ---
$filter = isset($_GET['filter']) ? trim($_GET['filter']) : ''; 
$allowed_filters = array('wajib', 'opsional');

if (!in_array($filter, $allowed_filters)) {
    $filter = ''; 
}

$query = "SELECT * FROM kegiatan WHERE status_persetujuan='approved'";

if ($filter) {
    $query .= " AND tipe_kegiatan='" . mysqli_real_escape_string($koneksi, $filter) . "'";
}

$query .= " ORDER BY jadwal DESC";

// Fungsi terjemahan bulan yang lebih akurat
function terjemahkanBulan($tanggal) {
    $bulan_indo = array(
        'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret', 
        'April' => 'April', 'May' => 'Mei', 'June' => 'Juni', 
        'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September', 
        'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'
    );
    $date_formatted = date('d F Y', strtotime($tanggal));
    return strtr($date_formatted, $bulan_indo);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kegiatan Sekolah - Portal Siswa</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* ... CSS di sini ... */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
    --primary-blue: #00bcd4;      /* Cyan utama */
    --primary-dark: #0097a7;      /* Cyan lebih gelap */
    --primary-light: #4dd0e1;     /* Cyan lebih terang */
    --secondary-blue: #80deea;    /* Cyan lembut */

    --gray-bg: #f5fafe;           /* Putih kebiruan */
    --white: #ffffff;
    --text-dark: #1e293b;
    --text-grey: #64748b;

    --green-wajib: #00bfa5;       /* Hijau toska */
    --green-dark: #008e76;

    --yellow-opsional: #ffd54f;
    --yellow-dark: #ffb300;

    --red-full: #ef5350;
    --blue-join: #26c6da;         /* Tombol join = cyan */

    --shadow-sm: 0 1px 3px 0 rgb(0 0 0 / 0.1);
    --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
    --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
    --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1);
}

        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #e0f7fa 0%, #ffffff 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .main-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Header Styles */
        .header {
            background: var(--white);
            padding: 20px 30px;
            border-radius: 20px;
            margin-bottom: 25px;
            box-shadow: var(--shadow-xl);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-blue), var(--primary-light), var(--secondary-blue));
        }

        .profile-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .profile-photo-wrapper {
            position: relative;
            cursor: pointer;
        }

        .profile-photo {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary-blue);
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
        }

        .profile-photo:hover {
            transform: scale(1.05);
            border-color: var(--primary-light);
        }

        .profile-photo-wrapper .upload-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: rgba(0, 188, 212, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
            cursor: pointer;
        }

        .profile-photo-wrapper:hover .upload-overlay {
            opacity: 1;
        }

        .upload-overlay i {
            color: white;
            font-size: 1.5em;
        }

        .profile-details h2 {
            font-size: 1.4em;
            color: var(--text-dark);
            margin-bottom: 5px;
            font-weight: 700;
        }

        .profile-details p {
            font-size: 0.9em;
            color: var(--text-grey);
            font-weight: 500;
        }

        .header-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .logout-btn {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: var(--white);
            padding: 10px 20px;
            border: none;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: var(--shadow-md);
            font-size: 0.9em;
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        /* Title Section */
        .title-section {
            background: var(--white);
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 25px;
            box-shadow: var(--shadow-xl);
            text-align: center;
        }

        .title-section h1 {
            color: var(--primary-blue);
            font-size: 2em;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .title-section p {
            color: var(--text-grey);
            font-size: 1em;
            font-weight: 500;
        }

        /* Filter Section */
        .filter-section {
            background: var(--white);
            padding: 25px;
            border-radius: 20px;
            margin-bottom: 25px;
            box-shadow: var(--shadow-xl);
        }

        .filter-label {
            font-size: 0.95em;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 15px;
            display: block;
        }

        .filter-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 12px 25px;
            border-radius: 30px;
            border: 2px solid #e2e8f0;
            background: var(--white);
            color: var(--text-grey);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-size: 0.95em;
        }

        .filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .filter-btn.active {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-light));
            color: var(--white);
            border-color: var(--primary-blue);
            box-shadow: var(--shadow-md);
        }

        .filter-btn.wajib-filter.active {
            background: linear-gradient(135deg, var(--green-wajib), var(--green-dark));
            border-color: var(--green-wajib);
        }

        .filter-btn.opsional-filter.active {
            background: linear-gradient(135deg, var(--yellow-opsional), var(--yellow-dark));
            border-color: var(--yellow-opsional);
        }

        /* Activity Grid */
        .activity-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 25px;
        }

        .activity-card {
            background: var(--white);
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .activity-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
        }

        .card-image-wrapper {
            position: relative;
            width: 100%;
            height: 240px;
            overflow: hidden;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-light) 100%);
        }

        .card-image {
            width: 100%;
            height: 100%;
            object-fit: contain;
            transition: transform 0.3s ease;
        }

        .activity-card:hover .card-image {
            transform: scale(1.05);
        }

        .card-content {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .card-title {
            font-size: 1.25em;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 15px;
            line-height: 1.3;
            min-height: 2.6em;
        }

        .card-meta {
            display: flex;
            flex-direction: column;
            gap: 10px;
            font-size: 0.875em;
            color: var(--text-grey);
            margin-bottom: 15px;
        }
        
        .card-meta-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-meta-item i {
            width: 20px;
            color: var(--primary-blue);
            font-size: 1.1em;
        }

        .tipe-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-weight: 700; 
            padding: 6px 14px; 
            border-radius: 20px; 
            font-size: 0.75em; 
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .tipe-badge.wajib {
            background: linear-gradient(135deg, var(--green-wajib), var(--green-dark));
        }

        .tipe-badge.opsional {
            background: linear-gradient(135deg, var(--yellow-opsional), var(--yellow-dark));
        }

        .kuota-info {
            font-weight: 600;
            padding: 6px 14px; 
            border-radius: 20px; 
            font-size: 0.85em; 
            color: var(--primary-blue);
            background: #dbeafe;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .card-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 2px solid #f1f5f9;
            padding-top: 15px;
            margin-top: auto;
            gap: 10px;
        }

        .btn-detail {
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.95em;
        }

        .btn-detail:hover {
            color: var(--primary-dark);
            gap: 8px;
        }
        
        .btn-join {
            background: linear-gradient(135deg, var(--blue-join), var(--primary-blue));
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            font-size: 0.875em;
            display: flex;
            align-items: center;
            gap: 5px;
            box-shadow: var(--shadow-sm);
        }

        .btn-join:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .btn-status-registered, .btn-status-full {
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.875em;
            color: white;
            text-align: center;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .btn-status-registered {
            background: linear-gradient(135deg, var(--green-wajib), var(--green-dark));
        }
        
        .btn-status-full {
            background: linear-gradient(135deg, var(--red-full), #dc2626);
        }

        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 40px;
            color: var(--text-grey);
            background: var(--white);
            border-radius: 20px;
            box-shadow: var(--shadow-xl);
        }

        .empty-state i {
            font-size: 4em;
            color: var(--primary-blue);
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 1.5em;
            color: var(--text-dark);
            margin-bottom: 10px;
            font-weight: 700;
        }

        .empty-state p {
            font-size: 1em;
            margin-bottom: 20px;
        }

        /* Upload Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.6);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: var(--white);
            margin: 10% auto;
            padding: 30px;
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            box-shadow: var(--shadow-xl);
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f5f9;
        }

        .modal-header h2 {
            font-size: 1.5em;
            color: var(--text-dark);
            font-weight: 700;
        }

        .close {
            color: var(--text-grey);
            font-size: 2em;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s;
            line-height: 1;
        }

        .close:hover {
            color: var(--text-dark);
        }

        .upload-preview {
            text-align: center;
            margin-bottom: 25px;
        }

        .preview-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 20px;
            display: block;
            border: 4px solid var(--primary-blue);
            box-shadow: var(--shadow-md);
        }

        .upload-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }

        .file-input-wrapper input[type=file] {
            position: absolute;
            left: -9999px;
        }

        .file-input-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 15px 25px;
            background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
            color: var(--text-dark);
            border-radius: 15px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            border: 2px dashed var(--text-grey);
        }

        .file-input-label:hover {
            background: linear-gradient(135deg, #e2e8f0, #cbd5e1);
            border-color: var(--primary-blue);
        }

        .btn-upload {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-light));
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 1em;
            box-shadow: var(--shadow-md);
        }

        .btn-upload:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .header {
                padding: 15px 20px;
            }

            .profile-details h2 {
                font-size: 1.1em;
            }

            .title-section h1 {
                font-size: 1.5em;
            }

            .activity-list {
                grid-template-columns: 1fr;
            }

            .filter-buttons {
                flex-direction: column;
            }

            .filter-btn {
                width: 100%;
                justify-content: center;
            }

            .header-actions {
                width: 100%;
                justify-content: space-between;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="header">
            <div class="profile-info">
                <div class="profile-photo-wrapper" onclick="openUploadModal()">
                    <?php if ($foto_profil && file_exists('uploads/' . $foto_profil)) { ?>
                        <img src="uploads/<?php echo htmlspecialchars($foto_profil); ?>" alt="Foto Profil" class="profile-photo" id="profileImage">
                    <?php } else { ?>
                        <div class="profile-photo" style="display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, var(--primary-blue), var(--primary-light));">
                            <i class="fas fa-user" style="font-size: 2em; color: white;"></i>
                        </div>
                    <?php } ?>
                    <div class="upload-overlay">
                        <i class="fas fa-camera"></i>
                    </div>
                </div>
                <div class="profile-details">
                    <h2><?php echo htmlspecialchars($nama_siswa); ?></h2>
                    <p><i class="fas fa-id-card"></i> NIS: <?php echo htmlspecialchars($nis); ?> | <i class="fas fa-graduation-cap"></i> Kelas: <?php echo htmlspecialchars($kelas); ?></p>
                </div>
            </div>
            <div class="header-actions">
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <div class="title-section">
            <h1><i class="fas fa-calendar-check"></i> Agenda Kegiatan Sekolah</h1>
            <p>Jelajahi dan ikuti berbagai kegiatan yang tersedia</p>
        </div>

        <div class="filter-section">
            <label class="filter-label"><i class="fas fa-filter"></i> Filter Kegiatan:</label>
            <div class="filter-buttons">
                <?php 
                    $class_all = '';
                    if ($filter == '') { $class_all = 'active'; }
                ?>
                <a href="siswa-kegiatan.php" class="filter-btn <?php echo $class_all; ?>">
                    <i class="fas fa-list"></i> Semua Kegiatan
                </a>
                
                <?php 
                    $class_wajib = '';
                    if ($filter == 'wajib') { $class_wajib = 'active'; }
                ?>
                <a href="siswa-kegiatan.php?filter=wajib" class="filter-btn wajib-filter <?php echo $class_wajib; ?>">
                    <i class="fas fa-star"></i> Kegiatan Wajib
                </a>

                <?php 
                    $class_opsional = '';
                    if ($filter == 'opsional') { $class_opsional = 'active'; }
                ?>
                <a href="siswa-kegiatan.php?filter=opsional" class="filter-btn opsional-filter <?php echo $class_opsional; ?>">
                    <i class="fas fa-check-circle"></i> Kegiatan Opsional
                </a>
            </div>
        </div>

        <div class="activity-list">
            <?php 
            $result = mysqli_query($koneksi, $query);
            
            if (!$result) {
                echo "<div class=\"empty-state\">";
                echo "<h3><i class=\"fas fa-exclamation-triangle\"></i> Error Database</h3>";
                echo "<p>Gagal memuat daftar kegiatan: " . mysqli_error($koneksi) . "</p>";
                echo "</div>";
            } elseif (mysqli_num_rows($result) > 0) { 
                while ($data = mysqli_fetch_assoc($result)) { 
                    $id_kegiatan_current = $data['id_kegiatan'];
                    $tipe_kegiatan = isset($data['tipe_kegiatan']) ? $data['tipe_kegiatan'] : 'wajib';
                    $maks_peserta = $data['maks_peserta'];

                    $is_registered = false;
                    $current_participants = 0;
                    $is_full = false;

                    if ($tipe_kegiatan == 'opsional') {
                        $count_q = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM peserta_kegiatan WHERE id_kegiatan='$id_kegiatan_current'");
                        if ($count_q) {
                             $current_participants = mysqli_fetch_assoc($count_q)['total'];
                        }
                        
                        if ($maks_peserta !== NULL && $current_participants >= $maks_peserta) {
                            $is_full = true;
                        }
                        
                        $cek_daftar_q = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM peserta_kegiatan WHERE id_kegiatan='$id_kegiatan_current' AND id_siswa='$id_siswa'");
                        if ($cek_daftar_q && mysqli_fetch_assoc($cek_daftar_q)['total'] > 0) {
                            $is_registered = true;
                        }
                    }
                
                    $gambar_path = (isset($data['gambar']) && $data['gambar']) ? 'uploads/' . htmlspecialchars($data['gambar']) : '';
                    $gambar_src = (file_exists($gambar_path) && $data['gambar']) ? $gambar_path : 'assets/images/logo.jpg'; 

            ?>
                <div class="activity-card">
                    <div class="card-image-wrapper">
                        <img src="<?php echo $gambar_src; ?>" alt="<?php echo htmlspecialchars($data['nama_kegiatan']); ?>" class="card-image">
                    </div>
                    <div class="card-content">
                        <h3 class="card-title"><?php echo htmlspecialchars($data['nama_kegiatan']); ?></h3>
                        
                        <div class="card-meta">
                            <div class="card-meta-item">
                                <span class="tipe-badge <?php echo $tipe_kegiatan; ?>">
                                    <?php 
                                    if ($tipe_kegiatan == 'wajib') { 
                                        echo '<i class="fas fa-star"></i> WAJIB'; 
                                    } else { 
                                        echo '<i class="fas fa-check-circle"></i> OPSIONAL'; 
                                    }
                                    ?>
                                </span>
                                <?php if ($tipe_kegiatan == 'opsional' && $maks_peserta !== NULL) { ?>
                                    <span class="kuota-info">
                                        <i class="fas fa-users"></i> <?php echo $current_participants . '/' . $maks_peserta; ?>
                                    </span>
                                <?php } ?>
                            </div>
                            <div class="card-meta-item">
                                <i class="fas fa-calendar-alt"></i>
                                <span><?php echo terjemahkanBulan($data['jadwal']); ?></span>
                            </div>
                            <div class="card-meta-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?php echo htmlspecialchars($data['lokasi']); ?></span>
                            </div>
                        </div>

                        <div class="card-actions">
                            <a href="siswa-detail-kegiatan.php?id=<?php echo $data['id_kegiatan']; ?>" class="btn-detail">
                                Lihat Detail <i class="fas fa-arrow-right"></i>
                            </a>
                            
                            <?php if ($tipe_kegiatan == 'opsional') { ?>
                                <?php if ($is_registered) { ?>
                                    <span class="btn-status-registered">
                                        <i class="fas fa-check-circle"></i> Terdaftar
                                    </span>
                                <?php } elseif ($is_full) { ?>
                                    <span class="btn-status-full">
                                        <i class="fas fa-times-circle"></i> Penuh
                                    </span>
                                <?php } else { ?>
                                    <a href="siswa-ikut-kegiatan-process.php?id=<?php echo $data['id_kegiatan']; ?>" 
                                        onclick="return confirm('Apakah Anda yakin ingin mendaftar kegiatan ini?')" 
                                        class="btn-join">
                                        <i class="fas fa-user-plus"></i> Daftar
                                    </a>
                                <?php } ?>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            <?php } // Penutup while ?>
            <?php } else { ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h3>Belum Ada Kegiatan</h3>
                    <p>Saat ini belum ada kegiatan yang disetujui. Silakan cek kembali nanti.</p>
                </div>
            <?php } // Penutup if/else besar ?>
        </div>
    </div>

    <div id="uploadModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-camera"></i> Upload Foto Profil</h2>
                <span class="close" onclick="closeUploadModal()">&times;</span>
            </div>
            <form action="siswa-upload-foto.php" method="POST" enctype="multipart/form-data" onsubmit="return validateUpload()">
                <div class="upload-preview">
                    <?php 
                    // BARIS INI AMAN. HANYA IF/ELSE TRADISIONAL
                    $preview_src = '';
                    $preview_style = 'display:none;';
                    if ($foto_profil) {
                        $preview_src = 'uploads/' . htmlspecialchars($foto_profil);
                        $preview_style = ''; // Tampilkan jika ada foto profil
                    }
                    ?>
                    <img id="previewImg" 
                        src="<?php echo $preview_src; ?>" 
                        alt="Preview" 
                        class="preview-image" 
                        style="<?php echo $preview_style; ?>">
                </div>
                <div class="upload-form">
                    <div class="file-input-wrapper">
                        <input type="file" name="foto_profil" id="foto_profil" accept="image/*" onchange="previewImage(this)" required>
                        <label for="foto_profil" class="file-input-label">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <span id="fileName">Pilih Foto (JPG, PNG, max 2MB)</span>
                        </label>
                    </div>
                    <button type="submit" class="btn-upload">
                        <i class="fas fa-upload"></i> Upload Foto
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openUploadModal() {
            document.getElementById('uploadModal').style.display = 'block';
        }

        function closeUploadModal() {
            document.getElementById('uploadModal').style.display = 'none';
        }

        window.onclick = function(event) {
            var modal = document.getElementById('uploadModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        function previewImage(input) {
            const file = input.files[0];
            const fileName = file ? file.name : 'Pilih Foto (JPG, PNG, max 2MB)';
            document.getElementById('fileName').textContent = fileName;

            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('previewImg');
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        }

        function validateUpload() {
            const fileInput = document.getElementById('foto_profil');
            const file = fileInput.files[0];

            if (!file) {
                alert('Pilih foto terlebih dahulu!');
                return false;
            }

            const maxSize = 2 * 1024 * 1024; // 2MB
            if (file.size > maxSize) {
                alert('Ukuran file terlalu besar! Maksimal 2MB.');
                return false;
            }

            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
            if (!allowedTypes.includes(file.type)) {
                alert('Format file tidak didukung! Gunakan JPG atau PNG.');
                return false;
            }

            return true;
        }
    </script>
</body>
</html>