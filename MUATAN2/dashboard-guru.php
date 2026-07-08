<?php
session_start(); 
include 'config.php';

// Cek autentikasi dan otorisasi
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'guru'){
    header("location: guru.php"); 
    exit;
}

$nama_guru = $_SESSION['nama'];
$user_id = isset($_SESSION['id_user']) ? $_SESSION['id_user'] : 0;

// Ambil data profil untuk NIP
$profil_query = mysqli_query($koneksi, "SELECT * FROM users WHERE id='$user_id'");
$profil = mysqli_fetch_assoc($profil_query);
$nip_guru = isset($profil['nip']) ? $profil['nip'] : '-';

// Statistik
$count_pending = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM kegiatan WHERE status_persetujuan='pending'"))['total'];
$count_approved = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM kegiatan WHERE status_persetujuan='approved'"))['total'];
$count_rejected = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM kegiatan WHERE status_persetujuan='rejected'"))['total'];
$count_total = $count_pending + $count_approved + $count_rejected;

// Ambil kegiatan yang akan berlangsung (approved dan jadwal >= hari ini)
$today = date('Y-m-d H:i:s');
$upcoming_query = mysqli_query($koneksi, "SELECT * FROM kegiatan WHERE status_persetujuan='approved' AND jadwal >= '$today' ORDER BY jadwal ASC LIMIT 5");

// Success message
$success_message = '';
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'approved') {
        $success_message = '✅ Kegiatan berhasil disetujui!';
    } else if ($_GET['success'] == 'rejected') {
        $success_message = '❌ Kegiatan berhasil ditolak!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Guru</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        /* DEFINISI VARIABEL WARNA */
        :root {
            --primary-cyan: #00BCD4;
            --primary-cyan-dark: #0097A7;
            --primary-cyan-light: #B2EBF2;
            --bg-gray: #f5f7fa;
            --text-dark: #111827;
            --text-gray: #6b7280;
            --white: #ffffff;
            
            /* Warna Statistik */
            --purple-card: #8b5cf6;
            --yellow-border: #f59e0b;
            --green-border: #10b981;
            --red-border: #ef4444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #E0F7FA 0%, #B2EBF2 50%, #80DEEA 100%);
            min-height: 100vh;
            padding: 2rem;
        }

        .dashboard-wrapper {
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* HEADER SECTION */
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary-cyan), var(--primary-cyan-dark));
            padding: 30px 40px;
            border-radius: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 188, 212, 0.3);
            color: white;
        }

        .welcome-section h1 {
            color: white;
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .welcome-section h1 i {
            font-size: 1.8rem;
        }

        .welcome-section p {
            color: rgba(255, 255, 255, 0.95);
            font-size: 1.1rem;
            font-weight: 400;
        }
        
        .welcome-section p strong {
            color: #fff;
            font-weight: 700;
        }

        .profile-card-mini {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 10px 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 15px;
            background-color: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
        }

        .profile-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #fff, #E0F7FA);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-cyan);
            font-size: 1.1rem;
            font-weight: 800;
            text-transform: uppercase;
            border: 3px solid white;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .profile-info {
            text-align: right;
        }

        .profile-info h3 {
            font-size: 1rem;
            font-weight: 700;
            color: white;
            margin-bottom: 2px;
        }

        .profile-info p {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.9);
            margin: 0;
        }

        /* STATS GRID */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            position: relative;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            height: 130px;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.12);
        }

        /* Card 1: Total (Cyan Gradient) */
        .stat-card.total {
            background: linear-gradient(135deg, #00BCD4, #0097A7);
            color: white;
            border: none;
        }
        
        .stat-card.total .stat-label { color: rgba(255,255,255,0.95); }
        .stat-card.total .stat-value { color: white; }
        .stat-card.total .stat-icon { 
            background: rgba(255,255,255,0.2); 
            padding: 10px;
            border-radius: 10px;
            font-size: 1.8rem;
        }

        /* Card Lain: Putih dengan Border Kiri */
        .stat-card.white-bg {
            background: white;
            border-left: 6px solid transparent;
            padding-left: 25px;
        }

        .stat-card.pending { border-left-color: var(--yellow-border); }
        .stat-card.approved { border-left-color: var(--green-border); }
        .stat-card.rejected { border-left-color: var(--red-border); }

        .stat-info {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .stat-label {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-gray);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .stat-label i { font-size: 1rem; }
        .stat-card.pending .stat-label i { color: var(--yellow-border); }
        .stat-card.approved .stat-label i { color: var(--green-border); }
        .stat-card.rejected .stat-label i { color: var(--red-border); }

        .stat-value {
            font-size: 2.8rem;
            font-weight: 800;
            line-height: 1;
            color: var(--text-dark);
        }

        .stat-icon-large {
            font-size: 3rem;
            opacity: 0.8;
        }
        
        .stat-card.pending .stat-icon-large { color: var(--yellow-border); }
        .stat-card.approved .stat-icon-large { color: var(--green-border); }
        .stat-card.rejected .stat-icon-large { color: var(--red-border); }

        /* SECTION CONTAINER */
        .section-container {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }

        .section-title {
            color: var(--primary-cyan);
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 15px;
            border-bottom: 3px solid var(--primary-cyan-light);
        }

        /* KEGIATAN AKAN BERLANGSUNG */
        .upcoming-grid {
            display: grid;
            gap: 20px;
        }

        .upcoming-card {
            background: linear-gradient(to right, #ffffff, #f8fdff);
            border: 2px solid var(--primary-cyan-light);
            border-radius: 15px;
            padding: 20px;
            display: flex;
            gap: 20px;
            align-items: center;
            transition: all 0.3s;
            box-shadow: 0 3px 10px rgba(0, 188, 212, 0.1);
        }

        .upcoming-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 20px rgba(0, 188, 212, 0.2);
            border-color: var(--primary-cyan);
        }

        .upcoming-date {
            background: linear-gradient(135deg, var(--primary-cyan), var(--primary-cyan-dark));
            color: white;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            min-width: 80px;
            box-shadow: 0 4px 10px rgba(0, 188, 212, 0.3);
        }

        .upcoming-day {
            font-size: 2rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 5px;
        }

        .upcoming-month {
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .upcoming-info {
            flex: 1;
        }

        .upcoming-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .tipe-badge {
            font-size: 0.7rem;
            padding: 3px 10px;
            border-radius: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .tipe-badge.wajib {
            background-color: var(--green-border);
            color: white;
        }

        .tipe-badge.opsional {
            background-color: var(--yellow-border);
            color: white;
        }

        .upcoming-details {
            display: flex;
            gap: 20px;
            font-size: 0.9rem;
            color: var(--text-gray);
        }

        .upcoming-details i {
            color: var(--primary-cyan);
            margin-right: 5px;
        }

        .upcoming-action {
            display: flex;
            align-items: center;
        }

        .btn-view {
            background: linear-gradient(135deg, var(--primary-cyan), var(--primary-cyan-dark));
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s;
            box-shadow: 0 4px 10px rgba(0, 188, 212, 0.3);
        }

        .btn-view:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 188, 212, 0.4);
        }

        .empty-upcoming {
            text-align: center;
            padding: 40px;
            color: var(--text-gray);
        }

        .empty-upcoming i {
            font-size: 4rem;
            color: var(--primary-cyan-light);
            margin-bottom: 15px;
        }

        /* MENU SECTION */
        .menu-grid {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 30px;
        }

        .menu-item {
            display: flex;
            align-items: center;
            padding: 22px 28px;
            background: linear-gradient(90deg, var(--primary-cyan), var(--primary-cyan-dark));
            border-radius: 15px;
            text-decoration: none;
            color: white;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(0, 188, 212, 0.3);
            position: relative;
            overflow: hidden;
        }

        .menu-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .menu-item:hover::before {
            left: 100%;
        }

        .menu-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 188, 212, 0.4);
        }

        .menu-icon-circle {
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.25);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            font-size: 1.3rem;
            flex-shrink: 0;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .menu-text {
            flex: 1;
        }

        .menu-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 3px;
            display: block;
        }

        .menu-desc {
            font-size: 0.9rem;
            font-weight: 400;
            opacity: 0.95;
            display: block;
        }

        .notification-badge {
            background: #dc3545;
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            font-weight: 700;
            position: absolute;
            right: 25px;
            top: 50%;
            transform: translateY(-50%);
            box-shadow: 0 3px 8px rgba(0,0,0,0.3);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: translateY(-50%) scale(1); }
            50% { transform: translateY(-50%) scale(1.1); }
        }

        /* BUTTONS */
        .btn-logout {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            padding: 14px 45px;
            font-size: 1.1rem;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 700;
            transition: all 0.3s;
            display: inline-block;
            border: none;
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }

        .btn-logout:hover { 
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(220, 53, 69, 0.4);
        }

        .btn-logout i { margin-right: 8px; }

        /* SUCCESS MESSAGE */
        .success-message {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
            padding: 18px 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            font-weight: 600;
            text-align: center;
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.2);
            border-left: 5px solid var(--green-border);
        }

        @media (max-width: 768px) {
            .dashboard-header { 
                flex-direction: column; 
                align-items: flex-start; 
                gap: 20px;
            }
            .profile-card-mini { 
                width: 100%; 
                justify-content: space-between; 
            }
            .stats-grid { 
                grid-template-columns: 1fr; 
            }
            .upcoming-card {
                flex-direction: column;
                text-align: center;
            }
            .upcoming-details {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        
        <?php if ($success_message): ?>
        <div class="success-message">
            <?php echo $success_message; ?>
        </div>
        <?php endif; ?>

        <div class="dashboard-header">
            <div class="welcome-section">
                <h1><i class="fas fa-university"></i> Dashboard Guru</h1>
                <p>Selamat datang, <strong><?php echo htmlspecialchars($nama_guru); ?></strong></p>
            </div>
            
            <div class="profile-card-mini">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($nama_guru, 0, 2)); ?>
                </div>
                <div class="profile-info">
                    <h3><?php echo htmlspecialchars($nama_guru); ?></h3>
                    <p>NIP: <?php echo htmlspecialchars($nip_guru); ?></p>
                </div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-info">
                    <div class="stat-label">Total Kegiatan</div>
                    <div class="stat-value"><?php echo $count_total; ?></div>
                </div>
                <div class="stat-icon"><i class="fas fa-chart-bar"></i></div>
            </div>

            <div class="stat-card white-bg pending">
                <div class="stat-info">
                    <div class="stat-label"><i class="fas fa-hourglass-half"></i> Menunggu</div>
                    <div class="stat-value"><?php echo $count_pending; ?></div>
                </div>
                <div class="stat-icon-large"><i class="fas fa-hourglass-end"></i></div>
            </div>

            <div class="stat-card white-bg approved">
                <div class="stat-info">
                    <div class="stat-label"><i class="fas fa-check-square"></i> Disetujui</div>
                    <div class="stat-value"><?php echo $count_approved; ?></div>
                </div>
                <div class="stat-icon-large"><i class="fas fa-check-square"></i></div>
            </div>

            <div class="stat-card white-bg rejected">
                <div class="stat-info">
                    <div class="stat-label"><i class="fas fa-times"></i> Ditolak</div>
                    <div class="stat-value"><?php echo $count_rejected; ?></div>
                </div>
                <div class="stat-icon-large"><i class="fas fa-times"></i></div>
            </div>
        </div>

        <!-- KEGIATAN YANG AKAN BERLANGSUNG -->
        <div class="section-container">
            <h2 class="section-title">
                <i class="fas fa-calendar-check"></i> Kegiatan Yang Akan Berlangsung
            </h2>
            
            <div class="upcoming-grid">
                <?php 
                if (mysqli_num_rows($upcoming_query) > 0):
                    while ($kegiatan = mysqli_fetch_assoc($upcoming_query)):
                        $tanggal = date('d', strtotime($kegiatan['jadwal']));
                        $bulan = date('M', strtotime($kegiatan['jadwal']));
                        $waktu = date('H:i', strtotime($kegiatan['jadwal']));
                        $tipe = $kegiatan['tipe_kegiatan'] ?? 'wajib';
                        
                        // Untuk kegiatan opsional, hitung jumlah peserta
                        $peserta_count = 0;
                        if ($tipe == 'opsional') {
                            $count_q = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM peserta_kegiatan WHERE id_kegiatan='{$kegiatan['id_kegiatan']}'");
                            $peserta_count = mysqli_fetch_assoc($count_q)['total'];
                        }
                ?>
                    <div class="upcoming-card">
                        <div class="upcoming-date">
                            <div class="upcoming-day"><?php echo $tanggal; ?></div>
                            <div class="upcoming-month"><?php echo $bulan; ?></div>
                        </div>
                        
                        <div class="upcoming-info">
                            <div class="upcoming-title">
                                <?php echo htmlspecialchars($kegiatan['nama_kegiatan']); ?>
                                <span class="tipe-badge <?php echo $tipe; ?>"><?php echo strtoupper($tipe); ?></span>
                            </div>
                            <div class="upcoming-details">
                                <span><i class="fas fa-clock"></i> <?php echo $waktu; ?> WIB</span>
                                <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($kegiatan['lokasi']); ?></span>
                                <?php if ($tipe == 'opsional'): ?>
                                    <span><i class="fas fa-users"></i> <?php echo $peserta_count; ?> Peserta</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="upcoming-action">
                            <a href="persetujuan.php?id=<?php echo $kegiatan['id_kegiatan']; ?>" class="btn-view">
                                <i class="fas fa-eye"></i> Lihat Detail
                            </a>
                        </div>
                    </div>
                <?php 
                    endwhile;
                else:
                ?>
                    <div class="empty-upcoming">
                        <i class="fas fa-calendar-times"></i>
                        <h3>Tidak Ada Kegiatan Mendatang</h3>
                        <p>Belum ada kegiatan yang dijadwalkan untuk periode mendatang.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- MENU GURU -->
        <div class="section-container">
            <h2 class="section-title"><i class="fas fa-list-alt"></i> Menu Guru</h2>
            
            <div class="menu-grid">
                <a href="daftar-ajuan.php" class="menu-item">
                    <div class="menu-icon-circle">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="menu-text">
                        <span class="menu-title">Proses Persetujuan Kegiatan</span>
                        <span class="menu-desc">Tinjau dan berikan keputusan untuk kegiatan yang diajukan panitia.</span>
                    </div>
                    <?php if ($count_pending > 0): ?>
                        <span class="notification-badge"><?php echo $count_pending; ?></span>
                    <?php endif; ?>
                </a>
            </div>

            <div style="text-align: center;">
                <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>
</body>
</html>