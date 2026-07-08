<?php
session_start(); 
include 'config.php';

// Cek autentikasi dan otorisasi
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'guru'){
    header("location: guru.php"); 
    exit;
}

$nama_guru = $_SESSION['nama'];

// Ambil data kegiatan yang pending
$query = mysqli_query($koneksi, "SELECT * FROM kegiatan WHERE status_persetujuan='pending' ORDER BY tanggal_pengajuan ASC");
$count_pending = mysqli_num_rows($query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Ajuan Kegiatan - Guru</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --primary-cyan: #00BCD4;
            --primary-cyan-dark: #0097A7;
            --primary-cyan-light: #B2EBF2;
            --gray-bg: #f5f7fa;
            --white: #ffffff;
            --text-dark: #1f2937;
            --text-grey: #6b7280;
            --green-wajib: #10b981;
            --yellow-opsional: #f59e0b;
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
            padding: 30px 20px;
        }

        .main-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary-cyan), var(--primary-cyan-dark));
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 188, 212, 0.3);
            color: white;
            text-align: center;
        }

        h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            font-weight: 800;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .subtitle {
            font-size: 1.1rem;
            font-weight: 500;
            opacity: 0.95;
        }

        .subtitle strong {
            font-weight: 800;
            color: #fff;
        }

        .activity-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .activity-card {
            background-color: var(--white);
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            border: 3px solid transparent;
        }

        .activity-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0, 188, 212, 0.2);
            border-color: var(--primary-cyan-light);
        }

        .card-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            position: relative;
        }

        .card-content {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .card-title {
            font-size: 1.3em;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 12px;
            line-height: 1.3;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .tipe-badge {
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.7em;
            color: white;
            text-transform: uppercase;
            white-space: nowrap;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .tipe-badge.wajib {
            background: linear-gradient(135deg, var(--green-wajib), #059669);
        }

        .tipe-badge.opsional {
            background: linear-gradient(135deg, var(--yellow-opsional), #d97706);
        }

        .card-meta {
            font-size: 0.85em;
            color: var(--text-grey);
            margin-bottom: 18px;
        }
        
        .card-meta span {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            padding: 5px 0;
        }
        
        .card-meta span i {
            margin-right: 8px;
            color: var(--primary-cyan);
            width: 18px;
            font-size: 1.1em;
        }

        .card-actions {
            display: flex;
            justify-content: center;
            align-items: center;
            border-top: 2px solid #f0f2f5;
            padding-top: 18px;
        }

        .btn-action {
            background: linear-gradient(135deg, var(--primary-cyan), var(--primary-cyan-dark));
            color: white;
            font-weight: 700;
            padding: 12px 28px;
            border-radius: 25px;
            text-decoration: none;
            font-size: 1em;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(0, 188, 212, 0.3);
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 188, 212, 0.4);
        }
        
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 40px;
            color: var(--text-grey);
            background-color: var(--white);
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .empty-state h2 {
            font-size: 4em;
            margin-bottom: 15px;
        }

        .empty-state h3 {
            color: var(--text-dark);
            font-size: 1.5em;
            margin-bottom: 10px;
        }
        
        .btn-back {
            display: inline-block;
            margin-top: 30px;
            padding: 14px 35px;
            background: linear-gradient(135deg, var(--primary-cyan), var(--primary-cyan-dark));
            color: white;
            text-decoration: none;
            border-radius: 30px;
            transition: all 0.3s;
            font-weight: 600;
            font-size: 1.05rem;
            box-shadow: 0 5px 15px rgba(0, 188, 212, 0.3);
        }

        .btn-back:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 188, 212, 0.4);
        }

        .btn-back i {
            margin-right: 8px;
        }

        @media (max-width: 768px) {
            .activity-list {
                grid-template-columns: 1fr;
            }
            h1 {
                font-size: 2em;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="page-header">
            <h1><i class="fas fa-clipboard-list"></i> Ajuan Kegiatan Menunggu Persetujuan</h1>
            <p class="subtitle">Halo, <strong><?php echo htmlspecialchars($nama_guru); ?></strong>. Ada <strong><?php echo $count_pending; ?></strong> kegiatan menunggu tinjauan Anda.</p>
        </div>

        <div class="activity-list">
            <?php 
            if (mysqli_num_rows($query) > 0): 
                while ($data = mysqli_fetch_assoc($query)): 
                    $tipe_kegiatan = $data['tipe_kegiatan'] ?? 'wajib';
                    $gambar_src = $data['gambar'] ? 'uploads/' . htmlspecialchars($data['gambar']) : 'logo.jpg';
            ?>
                    <div class="activity-card">
                        <img src="<?php echo $gambar_src; ?>" alt="<?php echo htmlspecialchars($data['nama_kegiatan']); ?>" class="card-image">
                        
                        <div class="card-content">
                            <div>
                                <h3 class="card-title">
                                    <span style="flex: 1;"><?php echo htmlspecialchars($data['nama_kegiatan']); ?></span>
                                    <span class="tipe-badge <?php echo $tipe_kegiatan; ?>">
                                        <?php echo strtoupper($tipe_kegiatan); ?>
                                    </span>
                                </h3>
                                
                                <div class="card-meta">
                                    <span><i class="fas fa-calendar-alt"></i> <?php echo date('d M Y H:i', strtotime($data['jadwal'])); ?></span>
                                    <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($data['lokasi']); ?></span>
                                    <span><i class="fas fa-user-plus"></i> Diajukan: <?php echo htmlspecialchars($data['diajukan_oleh']); ?></span>
                                </div>
                            </div>

                            <div class="card-actions">
                                <a href="persetujuan.php?id=<?php echo $data['id_kegiatan']; ?>" class="btn-action">
                                    <i class="fas fa-eye"></i> Tinjau Ajuan
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state"
                    <h3>Tidak ada ajuan kegiatan yang tertunda</h3>
                    <p>Semua kegiatan sudah ditinjau dan disetujui/ditolak.</p>
                </div>
            <?php endif; ?>
        </div>

        <div style="text-align: center;">
            <a href="dashboard-guru.php" class="btn-back"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a>
        </div>
    </div>
</body>
</html>