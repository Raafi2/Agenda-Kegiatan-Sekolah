<?php
session_start();
include 'config.php';

// Cek autentikasi dan otorisasi
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'panitia'){
    header("location: panitia.php");
    exit;
}

// Ambil data session dengan pengecekan aman
$nama_panitia = $_SESSION['nama'] ?? '';
$user_id = $_SESSION['id'] ?? null;

// Jika id tidak tersedia di session, coba cari berdasarkan nama (fallback)
if (!$user_id && $nama_panitia) {
    $safe_nama = mysqli_real_escape_string($koneksi, $nama_panitia);
    $tmp_q = mysqli_query($koneksi, "SELECT id FROM users WHERE nama='$safe_nama' AND role='panitia' LIMIT 1");
    if ($tmp_q && mysqli_num_rows($tmp_q) > 0) {
        $tmp_row = mysqli_fetch_assoc($tmp_q);
        $user_id = $tmp_row['id'];
    }
}

// Ambil data profil panitia
if ($user_id) {
    $profil_query = mysqli_query($koneksi, "SELECT * FROM users WHERE id='$user_id' AND role='panitia' LIMIT 1");
} else {
    $safe_nama = mysqli_real_escape_string($koneksi, $nama_panitia);
    $profil_query = mysqli_query($koneksi, "SELECT * FROM users WHERE nama='$safe_nama' AND role='panitia' LIMIT 1");
}

$profil = ($profil_query && mysqli_num_rows($profil_query) > 0) ? mysqli_fetch_assoc($profil_query) : null;
$foto_profil = $profil['foto_profil'] ?? '';
$nip_panitia = $profil['nip'] ?? '';

// Statistik Kegiatan
$safe_pengaju = mysqli_real_escape_string($koneksi, $nama_panitia);
$count_pending = (int) mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM kegiatan WHERE diajukan_oleh='$safe_pengaju' AND status_persetujuan='pending'"))['total'];
$count_approved = (int) mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM kegiatan WHERE diajukan_oleh='$safe_pengaju' AND status_persetujuan='approved'"))['total'];
$count_rejected = (int) mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM kegiatan WHERE diajukan_oleh='$safe_pengaju' AND status_persetujuan='rejected'"))['total'];
$count_total = $count_pending + $count_approved + $count_rejected;

// Ambil kegiatan yang akan berlangsung (approved & jadwal masih akan datang)
$now = date('Y-m-d H:i:s');
$kegiatan_mendatang_query = mysqli_query($koneksi, 
    "SELECT * FROM kegiatan 
     WHERE diajukan_oleh='$safe_pengaju' 
     AND status_persetujuan='approved' 
     AND jadwal >= '$now'
     ORDER BY jadwal ASC 
     LIMIT 5"
);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Panitia - Agenda Kegiatan Sekolah</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #E0F7FA 0%, #B2EBF2 50%, #80DEEA 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* Header Modern */
        .header-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0, 188, 212, 0.15);
            display: flex;
            align-items: center;
            gap: 25px;
        }
        
        .profile-section {
            position: relative;
        }
        
        .profile-photo {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #00BCD4;
            box-shadow: 0 5px 15px rgba(0, 188, 212, 0.3);
            background: #E0F7FA;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: #00BCD4;
            overflow: hidden;
        }
        
        .profile-photo img { 
            width: 100%; 
            height: 100%; 
            object-fit: cover; 
            border-radius: 50%; 
        }
        
        .edit-photo-btn {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 32px;
            height: 32px;
            background: #00BCD4;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 3px 10px rgba(0, 188, 212, 0.4);
            transition: all 0.3s;
        }
        
        .edit-photo-btn:hover { background: #0097A7; transform: scale(1.1); }
        .edit-photo-btn i { color: white; font-size: 14px; }
        
        .welcome-info h1 {
            font-size: 28px;
            color: #263238;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .welcome-info p {
            color: #00BCD4;
            font-size: 16px;
            font-weight: 600;
        }
        
        /* Stats Grid Modern */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0, 188, 212, 0.1);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
        }
        
        .stat-card.total::before { background: linear-gradient(90deg, #00BCD4, #0097A7); }
        .stat-card.pending::before { background: linear-gradient(90deg, #FF9800, #F57C00); }
        .stat-card.approved::before { background: linear-gradient(90deg, #4CAF50, #388E3C); }
        .stat-card.rejected::before { background: linear-gradient(90deg, #F44336, #D32F2F); }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 188, 212, 0.2);
        }
        
        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .stat-card.total .stat-icon { background: #E0F7FA; color: #00BCD4; }
        .stat-card.pending .stat-icon { background: #FFF3E0; color: #FF9800; }
        .stat-card.approved .stat-icon { background: #E8F5E9; color: #4CAF50; }
        .stat-card.rejected .stat-icon { background: #FFEBEE; color: #F44336; }
        
        .stat-value {
            font-size: 36px;
            font-weight: 800;
            color: #263238;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            color: #607D8B;
            font-weight: 500;
        }
        
        /* Kegiatan Mendatang Section */
        .upcoming-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0, 188, 212, 0.15);
        }
        
        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #E0F7FA;
        }
        
        .section-header h2 {
            font-size: 22px;
            color: #263238;
            font-weight: 700;
        }
        
        .section-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #00BCD4, #0097A7);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }
        
        .event-timeline {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .event-item {
            display: flex;
            gap: 20px;
            padding: 20px;
            background: #F5FDFF;
            border-radius: 12px;
            border-left: 4px solid #00BCD4;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .event-item:hover {
            background: #E0F7FA;
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0, 188, 212, 0.1);
        }
        
        .event-date-box {
            min-width: 80px;
            text-align: center;
            background: white;
            padding: 12px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .event-day {
            font-size: 28px;
            font-weight: 800;
            color: #00BCD4;
            line-height: 1;
        }
        
        .event-month {
            font-size: 12px;
            color: #607D8B;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .event-details {
            flex: 1;
        }
        
        .event-title {
            font-size: 18px;
            font-weight: 700;
            color: #263238;
            margin-bottom: 8px;
        }
        
        .event-meta {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            color: #607D8B;
        }
        
        .meta-item i { color: #00BCD4; }
        
        .event-badge {
            padding: 6px 14px;
            background: linear-gradient(135deg, #4CAF50, #388E3C);
            color: white;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .event-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-detail {
            padding: 8px 16px;
            background: linear-gradient(135deg, #00BCD4, #0097A7);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-detail:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 188, 212, 0.3);
        }
        
        .no-events {
            text-align: center;
            padding: 40px;
            color: #607D8B;
        }
        
        .no-events i {
            font-size: 60px;
            color: #B2EBF2;
            margin-bottom: 15px;
        }
        
        /* Menu Grid */
        .menu-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0, 188, 212, 0.15);
        }
        
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .menu-item {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 25px;
            background: linear-gradient(135deg, #00BCD4, #0097A7);
            border-radius: 16px;
            text-decoration: none;
            color: white;
            transition: all 0.3s;
            box-shadow: 0 5px 20px rgba(0, 188, 212, 0.3);
            position: relative;
        }
        
        .menu-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 188, 212, 0.4);
        }
        
        .menu-icon {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }
        
        .menu-content .title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .menu-content .desc {
            font-size: 13px;
            opacity: 0.9;
        }
        
        .notification-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #F44336;
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(244, 67, 54, 0.4);
        }
        
        /* Logout Button */
        .logout-container {
            text-align: center;
            margin-top: 30px;
        }
        
        .btn-logout {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 15px 40px;
            background: linear-gradient(135deg, #F44336, #D32F2F);
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 700;
            text-decoration: none;
            box-shadow: 0 5px 20px rgba(244, 67, 54, 0.3);
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .btn-logout:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(244, 67, 54, 0.4);
        }
        
        @media (max-width: 768px) {
            .header-card { flex-direction: column; text-align: center; }
            .stats-grid { grid-template-columns: 1fr; }
            .menu-grid { grid-template-columns: 1fr; }
            .event-item { flex-direction: column; }
            .event-actions { justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header Profile -->
        <div class="header-card">
            <div class="profile-section">
                <?php
                    $profile_img_path = $foto_profil ? "uploads/profiles/" . $foto_profil : '';
                    $has_image = ($profile_img_path && file_exists($profile_img_path));
                ?>
                <div class="profile-photo" id="profileImage">
                    <?php if ($has_image): ?>
                        <img src="<?php echo htmlspecialchars($profile_img_path); ?>" alt="Profile">
                    <?php else: ?>
                        <i class="fas fa-user"></i>
                    <?php endif; ?>
                </div>

                <form id="uploadProfileForm" action="upload-profile-panitia.php" method="POST" enctype="multipart/form-data" style="display:none;">
                    <?php if ($user_id): ?>
                        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>">
                    <?php endif; ?>
                    <input type="file" id="uploadProfileInput" name="foto_profil" accept="image/*">
                </form>

                <label for="uploadProfileInput" class="edit-photo-btn" title="Ganti foto profil">
                    <i class="fas fa-camera"></i>
                </label>
            </div>

            <div class="welcome-info">
                <h1>Selamat Datang, <?php echo htmlspecialchars($nama_panitia); ?>!</h1>
                <p><i class="fas fa-id-badge"></i> Panitia Kegiatan Sekolah</p>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?php echo $count_total; ?></div>
                        <div class="stat-label">Total Pengajuan</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card pending">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?php echo $count_pending; ?></div>
                        <div class="stat-label">Menunggu Persetujuan</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card approved">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?php echo $count_approved; ?></div>
                        <div class="stat-label">Kegiatan Disetujui</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card rejected">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?php echo $count_rejected; ?></div>
                        <div class="stat-label">Pengajuan Ditolak</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kegiatan Yang Akan Berlangsung -->
        <div class="upcoming-section">
            <div class="section-header">
                <div class="section-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <h2>Kegiatan Yang Akan Berlangsung</h2>
            </div>
            
            <?php if (mysqli_num_rows($kegiatan_mendatang_query) > 0): ?>
                <div class="event-timeline">
                    <?php while($event = mysqli_fetch_assoc($kegiatan_mendatang_query)): 
                        $jadwal_timestamp = strtotime($event['jadwal']);
                        $day = date('d', $jadwal_timestamp);
                        $month = date('M', $jadwal_timestamp);
                        $time = date('H:i', $jadwal_timestamp);
                    ?>
                    <div class="event-item">
                        <div class="event-date-box">
                            <div class="event-day"><?php echo $day; ?></div>
                            <div class="event-month"><?php echo $month; ?></div>
                        </div>
                        <div class="event-details">
                            <div class="event-title"><?php echo htmlspecialchars($event['nama_kegiatan']); ?></div>
                            <div class="event-meta">
                                <div class="meta-item">
                                    <i class="fas fa-clock"></i>
                                    <span><?php echo $time; ?> WIB</span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo htmlspecialchars($event['lokasi']); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="event-actions">
                            <div class="event-badge">
                                <i class="fas fa-check"></i> Disetujui
                            </div>
                            <a href="panitia-detail-kegiatan.php?id=<?php echo $event['id_kegiatan']; ?>" class="btn-detail">
                                <i class="fas fa-eye"></i> Lihat Detail
                            </a>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-events">
                    <i class="fas fa-calendar-times"></i>
                    <p>Belum ada kegiatan yang dijadwalkan</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Menu Section -->
        <div class="menu-section">
            <div class="section-header">
                <div class="section-icon">
                    <i class="fas fa-th-large"></i>
                </div>
                <h2>Menu Panitia</h2>
            </div>
            
            <div class="menu-grid">
                <a href="pengajuan-kegiatan.php" class="menu-item">
                    <div class="menu-icon">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <div class="menu-content">
                        <div class="title">Ajukan Kegiatan Baru</div>
                        <div class="desc">Buat dan kirim pengajuan kegiatan</div>
                    </div>
                </a>

                <a href="status-persetujuan.php" class="menu-item" style="position:relative;">
                    <div class="menu-icon">
                        <i class="fas fa-list-check"></i>
                    </div>
                    <div class="menu-content">
                        <div class="title">Status Pengajuan</div>
                        <div class="desc">Lihat status kegiatan yang diajukan</div>
                    </div>
                    <?php if ($count_pending > 0): ?>
                        <span class="notification-badge"><?php echo $count_pending; ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>

        <!-- Logout -->
        <div class="logout-container">
            <a href="logout.php" class="btn-logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <script>
        // Auto-submit profile photo upload
        var uploadInput = document.getElementById('uploadProfileInput');
        var profileImageContainer = document.getElementById('profileImage');

        uploadInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    profileImageContainer.innerHTML = '<img src="' + e.target.result + '" alt="Profile">';
                }
                reader.readAsDataURL(this.files[0]);
                document.getElementById('uploadProfileForm').submit();
            }
        });
    </script>
</body>
</html>