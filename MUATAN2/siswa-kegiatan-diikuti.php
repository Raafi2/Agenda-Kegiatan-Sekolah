<?php
session_start(); 
include 'config.php';

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'siswa'){
    header("location: siswa.php");
    exit;
}

if (!isset($_SESSION['id_user'])) {
    header("location: siswa.php?error=no_id");
    exit;
}

$id_siswa = intval($_SESSION['id_user']);

// Ambil data siswa
$query_siswa = mysqli_query($koneksi, "SELECT * FROM users WHERE id='$id_siswa' AND role='siswa'");
if (!$query_siswa || mysqli_num_rows($query_siswa) == 0) {
    $data_siswa = [
        'nama' => '-',
        'nis' => '-',
        'kelas' => '-',
        'foto_profil' => ''
    ];
} else {
    $data_siswa = mysqli_fetch_assoc($query_siswa);
}

$nama_siswa = $data_siswa['nama'] ?? $_SESSION['nama'] ?? '-';
$nis = $data_siswa['nip'] ?? $_SESSION['nis'] ?? '-';
$kelas = $data_siswa['kelas'] ?? $_SESSION['kelas_lengkap'] ?? '-';
$foto_profil = $data_siswa['foto_profil'] ?? '';

// Ambil kegiatan yang diikuti (hanya opsional)
$// Ambil kegiatan yang diikuti (hanya opsional)
// PERBAIKAN: Menghapus ", pk.id as id_peserta" karena kolom id tidak ada di tabel peserta_kegiatan
$query = mysqli_query($koneksi, 
    "SELECT k.* FROM kegiatan k 
     INNER JOIN peserta_kegiatan pk ON k.id_kegiatan = pk.id_kegiatan 
     WHERE pk.id_siswa='$id_siswa' 
     AND k.tipe_kegiatan='opsional'
     AND k.status_persetujuan='approved'
     ORDER BY k.jadwal DESC"
);
);

function terjemahkanBulan($tanggal) {
    $bulan_indo = [
        'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret', 
        'April' => 'April', 'May' => 'Mei', 'June' => 'Juni', 
        'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September', 
        'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'
    ];
    return strtr(date('d F Y', strtotime($tanggal)), $bulan_indo);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kegiatan Yang Diikuti - Siswa</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --primary-blue: #00BCD4;
            --primary-dark: #008ba3;
            --gray-bg: #f0f2f5;
            --white: #ffffff;
            --text-dark: #1f2937;
            --text-grey: #6b7280;
            --green-wajib: #28A745;
            --yellow-opsional: #FFC107;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #E0F7FA 0%, #B2EBF2 50%, #80DEEA 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .main-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background-color: var(--white);
            padding: 25px;
            border-radius: 20px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0, 188, 212, 0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .profile-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .profile-photo {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary-blue);
            box-shadow: 0 5px 15px rgba(0, 188, 212, 0.3);
        }

        .profile-details h2 {
            font-size: 1.4em;
            color: var(--text-dark);
            margin: 0 0 5px 0;
            font-weight: 700;
        }

        .profile-details p {
            font-size: 0.9em;
            color: var(--text-grey);
            margin: 0;
        }

        .header-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95em;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-dark));
            color: var(--white);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 188, 212, 0.3);
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
        }

        .page-title {
            background: white;
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0, 188, 212, 0.15);
            text-align: center;
        }

        .page-title h1 {
            color: var(--primary-blue);
            font-weight: 700;
            font-size: 2em;
            margin-bottom: 10px;
        }

        .page-title p {
            color: var(--text-grey);
            font-size: 1em;
        }

        .stats-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0, 188, 212, 0.1);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .stats-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-dark));
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
        }

        .stats-content h3 {
            font-size: 2em;
            color: var(--primary-blue);
            font-weight: 800;
            margin: 0;
        }

        .stats-content p {
            color: var(--text-grey);
            margin: 0;
            font-size: 0.95em;
        }

        .activity-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .activity-card {
            background-color: var(--white);
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(0, 188, 212, 0.15);
            overflow: hidden;
            transition: all 0.3s;
        }

        .activity-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(0, 188, 212, 0.25);
        }

        .card-image {
            width: 100%;
            height: 220px;
            object-fit: cover;
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-dark));
        }

        .card-content {
            padding: 20px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
            gap: 10px;
        }

        .card-title {
            font-size: 1.3em;
            font-weight: 700;
            color: var(--text-dark);
            line-height: 1.3;
            flex: 1;
        }

        .status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 700;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .badge-opsional {
            background: linear-gradient(135deg, var(--yellow-opsional), #F57F17);
            color: var(--text-dark);
        }

        .badge-terdaftar {
            background: linear-gradient(135deg, var(--green-wajib), #1B5E20);
            color: white;
        }

        .card-meta {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 15px;
            padding: 15px;
            background: #F5FDFF;
            border-radius: 12px;
            border-left: 4px solid var(--primary-blue);
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9em;
            color: var(--text-grey);
        }

        .meta-item i {
            color: var(--primary-blue);
            width: 20px;
            text-align: center;
        }

        .card-actions {
            display: flex;
            gap: 10px;
            padding-top: 15px;
            border-top: 2px solid #f0f2f5;
        }

        .btn-detail {
            flex: 1;
            text-align: center;
            padding: 12px;
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-dark));
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-detail:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0, 188, 212, 0.3);
        }

        .empty-state {
            background: white;
            padding: 80px 40px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 188, 212, 0.15);
        }

        .empty-state i {
            font-size: 100px;
            color: #B2EBF2;
            margin-bottom: 25px;
        }

        .empty-state h3 {
            font-size: 1.8em;
            color: var(--text-dark);
            margin-bottom: 15px;
            font-weight: 700;
        }

        .empty-state p {
            color: var(--text-grey);
            font-size: 1.1em;
            margin-bottom: 25px;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-info {
                flex-direction: column;
            }

            .activity-grid {
                grid-template-columns: 1fr;
            }

            .header-actions {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="header">
            <div class="profile-info">
                <?php if ($foto_profil && file_exists('uploads/' . $foto_profil)): ?>
                    <img src="uploads/<?php echo htmlspecialchars($foto_profil); ?>" alt="Foto Profil" class="profile-photo">
                <?php else: ?>
                    <div class="profile-photo" style="display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #00BCD4, #0097A7);">
                        <i class="fas fa-user" style="font-size: 2em; color: white;"></i>
                    </div>
                <?php endif; ?>
                <div class="profile-details">
                    <h2><?php echo htmlspecialchars($nama_siswa); ?></h2>
                    <p><i class="fas fa-id-card"></i> NIS: <?php echo htmlspecialchars($nis); ?> | Kelas: <?php echo htmlspecialchars($kelas); ?></p>
                </div>
            </div>
            <div class="header-actions">
                <a href="siswa-kegiatan.php" class="btn btn-secondary">
                    <i class="fas fa-calendar"></i> Semua Kegiatan
                </a>
                <a href="siswa.php" class="btn btn-primary">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </div>
        </div>

        <div class="page-title">
            <h1><i class="fas fa-clipboard-check"></i> Kegiatan Yang Diikuti</h1>
            <p>Daftar kegiatan opsional yang sudah Anda daftarkan</p>
        </div>

        <?php if (mysqli_num_rows($query) > 0): ?>
            <div class="stats-card">
                <div class="stats-icon">
                    <i class="fas fa-check-double"></i>
                </div>
                <div class="stats-content">
                    <h3><?php echo mysqli_num_rows($query); ?></h3>
                    <p>Kegiatan Terdaftar</p>
                </div>
            </div>

            <div class="activity-grid">
                <?php while ($data = mysqli_fetch_assoc($query)): 
                    $gambar_src = $data['gambar_kegiatan'] ? 'uploads/' . htmlspecialchars($data['gambar_kegiatan']) : 'logo.jpg';
                    $jadwal_timestamp = strtotime($data['jadwal']);
                    $is_past = $jadwal_timestamp < time();
                ?>
                    <div class="activity-card">
                        <img src="<?php echo $gambar_src; ?>" alt="<?php echo htmlspecialchars($data['nama_kegiatan']); ?>" class="card-image">
                        <div class="card-content">
                            <div class="card-header">
                                <h3 class="card-title"><?php echo htmlspecialchars($data['nama_kegiatan']); ?></h3>
                                <span class="status-badge badge-terdaftar">
                                    <i class="fas fa-check-circle"></i> Terdaftar
                                </span>
                            </div>

                            <div class="card-meta">
                                <div class="meta-item">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span><?php echo terjemahkanBulan($data['jadwal']); ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-clock"></i>
                                    <span><?php echo date('H:i', $jadwal_timestamp); ?> WIB</span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo htmlspecialchars($data['lokasi']); ?></span>
                                </div>
                                <?php if ($is_past): ?>
                                <div class="meta-item" style="color: #00BCD4; font-weight: 600;">
                                    <i class="fas fa-info-circle"></i>
                                    <span>Kegiatan Sudah Berlangsung</span>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="card-actions">
                                <a href="siswa-detail-kegiatan.php?id=<?php echo $data['id_kegiatan']; ?>" class="btn-detail">
                                    <i class="fas fa-eye"></i> Lihat Detail
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <h3>Belum Ada Kegiatan Yang Diikuti</h3>
                <p>Anda belum mendaftar kegiatan opsional apapun</p>
                <a href="siswa-kegiatan.php" class="btn btn-primary">
                    <i class="fas fa-search"></i> Cari Kegiatan
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>