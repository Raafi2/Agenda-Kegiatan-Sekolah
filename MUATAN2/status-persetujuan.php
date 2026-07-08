<?php
session_start();
include 'config.php';

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'panitia'){
    header("location: panitia.php");
    exit;
}

$nama_panitia = $_SESSION['nama'];
$safe_nama = mysqli_real_escape_string($koneksi, $nama_panitia);

// Ambil semua kegiatan yang diajukan oleh panitia ini
$kegiatan_query = mysqli_query($koneksi, 
    "SELECT * FROM kegiatan 
     WHERE diajukan_oleh='$safe_nama' 
     ORDER BY tanggal_pengajuan DESC"
);

function getStatusBadge($status) {
    switch ($status) {
        case 'approved':
            return '<span class="badge badge-approved"><i class="fas fa-check-circle"></i> Disetujui</span>';
        case 'pending':
            return '<span class="badge badge-pending"><i class="fas fa-clock"></i> Menunggu</span>';
        case 'rejected':
            return '<span class="badge badge-rejected"><i class="fas fa-times-circle"></i> Ditolak</span>';
        default:
            return '<span class="badge badge-unknown">Tidak Diketahui</span>';
    }
}

// =================================================================
// PERBAIKAN DI SINI: MENAMBAHKAN KASUS 'kegiatan_submitted'
// =================================================================
$success_message = '';
if(isset($_GET['success'])){
    switch ($_GET['success']) {
        case 'kegiatan_submitted':
            $success_message = '✅ Pengajuan kegiatan **BERHASIL** dikirim! Menunggu persetujuan.';
            break;
        case 'deleted':
            $success_message = '✅ Kegiatan berhasil dihapus!';
            break;
        case 'updated':
            $success_message = '✅ Kegiatan berhasil diperbarui!';
            break;
    }
}
// =================================================================
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Persetujuan Kegiatan - Panitia</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --primary-cyan: #00BCD4;
            --primary-cyan-dark: #0097A7;
            --primary-blue: #799DE3;
            --secondary-blue: #4267B2;
            --success-green: #4CAF50;
            --pending-orange: #FF9800;
            --rejected-red: #F44336;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f7f9;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .page-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid var(--primary-cyan);
        }

        .page-header h1 {
            font-size: 2.2em;
            color: #333;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .page-header p {
            color: #666;
            margin-top: 0;
        }

        .filter-tabs {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            gap: 10px;
        }

        .filter-tab {
            padding: 10px 20px;
            border: 2px solid var(--primary-cyan);
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            color: var(--primary-cyan);
            background-color: white;
            user-select: none;
        }

        .filter-tab:hover {
            background-color: var(--primary-cyan-light);
        }

        .filter-tab.active {
            background-color: var(--primary-cyan);
            color: white;
            box-shadow: 0 4px 10px rgba(0, 188, 212, 0.4);
        }

        .kegiatan-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            padding: 0;
            list-style: none;
        }

        .kegiatan-card {
            background-color: #fcfcfc;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s, box-shadow 0.3s;
            border: 1px solid #eee;
        }

        .kegiatan-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.15);
        }

        .card-image {
            height: 200px;
            overflow: hidden;
            background-color: #eee;
            position: relative;
        }

        .card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .card-status {
            position: absolute;
            top: 15px;
            right: 15px;
            font-weight: 700;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.85em;
            color: white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        
        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: 600;
        }

        .badge i {
            margin-right: 5px;
        }

        .badge-approved {
            background-color: var(--success-green);
            color: white;
        }

        .badge-pending {
            background-color: var(--pending-orange);
            color: white;
        }

        .badge-rejected {
            background-color: var(--rejected-red);
            color: white;
        }

        .card-content {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .card-content h3 {
            margin-top: 0;
            font-size: 1.4em;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }

        .card-meta {
            margin-bottom: 15px;
            font-size: 0.9em;
            color: #666;
        }

        .meta-item {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }

        .meta-item i {
            color: var(--primary-cyan);
            margin-right: 8px;
            width: 20px;
            text-align: center;
        }

        .card-actions {
            margin-top: auto; /* Push to the bottom */
            padding-top: 15px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
        }

        .btn-detail {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--primary-cyan);
            color: white;
            text-decoration: none;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 600;
            transition: background-color 0.3s, transform 0.2s;
            box-shadow: 0 4px 10px rgba(0, 188, 212, 0.3);
        }

        .btn-detail:hover {
            background-color: var(--primary-cyan-dark);
            transform: translateY(-2px);
        }

        .btn-detail i {
            margin-right: 5px;
        }

        .no-data {
            text-align: center;
            padding: 50px 20px;
            color: #999;
        }

        .no-data i {
            font-size: 4em;
            margin-bottom: 20px;
            color: #ccc;
        }

        .no-data h3 {
            font-weight: 600;
            color: #666;
        }
        
        .back-container {
            text-align: center;
            margin-top: 40px;
        }

        .btn-back {
            display: inline-block;
            padding: 12px 25px;
            background-color: var(--secondary-blue);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            transition: background-color 0.3s;
        }

        .btn-back:hover {
            background-color: #385898;
        }
        
        .alert {
            padding: 15px 25px;
            margin-bottom: 25px;
            border-radius: 10px;
            font-size: 1em;
            font-weight: 500;
            border: 1px solid transparent;
            word-wrap: break-word;
        }

        .success-alert {
            background-color: #e6ffed; /* Light Green */
            color: #155724; /* Dark Green */
            border-color: #c3e6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1>STATUS PENGAJUAN KEGIATAN</h1>
            <p>Halo, **<?php echo htmlspecialchars($nama_panitia); ?>**. Berikut adalah status kegiatan yang Anda ajukan.</p>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert success-alert">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <div class="filter-tabs">
            <div class="filter-tab active" onclick="filterStatus('all')">Semua</div>
            <div class="filter-tab" onclick="filterStatus('pending')">Menunggu</div>
            <div class="filter-tab" onclick="filterStatus('approved')">Disetujui</div>
            <div class="filter-tab" onclick="filterStatus('rejected')">Ditolak</div>
        </div>
        
        <div class="kegiatan-list">
            <?php if (mysqli_num_rows($kegiatan_query) > 0): ?>
                <?php while($keg = mysqli_fetch_assoc($kegiatan_query)): 
                    $image_src = !empty($keg['gambar']) ? 'uploads/' . htmlspecialchars($keg['gambar']) : 'path/to/default-image.jpg';
                    $jadwal_indo = date('d F Y H:i', strtotime($keg['jadwal']));
                ?>
                    <div class="kegiatan-card" data-status="<?php echo htmlspecialchars($keg['status_persetujuan']); ?>">
                        <div class="card-image">
                            <img src="<?php echo $image_src; ?>" alt="Gambar Kegiatan">
                            <div class="card-status">
                                <?php echo getStatusBadge($keg['status_persetujuan']); ?>
                            </div>
                        </div>

                        <div class="card-content">
                            <h3><?php echo htmlspecialchars($keg['nama_kegiatan']); ?></h3>
                            
                            <div class="card-meta">
                                <div class="meta-item">
                                    <i class="fas fa-clock"></i>
                                    <span><?php echo $jadwal_indo; ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo htmlspecialchars($keg['lokasi']); ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-tag"></i>
                                    <span>Tipe: <?php echo ucfirst(htmlspecialchars($keg['tipe_kegiatan'])); ?></span>
                                </div>
                            </div>

                            <div class="card-actions">
                                <a href="panitia-detail-kegiatan.php?id=<?php echo $keg['id_kegiatan']; ?>" class="btn-detail">
                                    <i class="fas fa-eye"></i> Lihat Detail
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-inbox"></i>
                    <h3>Belum ada kegiatan yang diajukan</h3>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="back-container">
            <a href="dashboard-panitia.php" class="btn-back">
                <i class="fas fa-arrow-left"></i>
                Kembali ke Dashboard
            </a>
        </div>
    </div>
    
    <script>
        function filterStatus(status) {
            const cards = document.querySelectorAll('.kegiatan-card');
            const tabs = document.querySelectorAll('.filter-tab');
            
            // Update active tab
            tabs.forEach(tab => tab.classList.remove('active'));
            event.target.classList.add('active');
            
            // Filter cards
            cards.forEach(card => {
                if (status === 'all' || card.dataset.status === status) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>