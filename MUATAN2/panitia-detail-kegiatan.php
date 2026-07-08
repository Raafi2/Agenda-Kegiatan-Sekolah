<?php
session_start(); 
include 'config.php';

// Cek otorisasi
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'panitia'){
    header("location: panitia.php"); 
    exit;
}

$nama_panitia = $_SESSION['nama'];

// Ambil ID kegiatan
$id_kegiatan = isset($_GET['id']) ? mysqli_real_escape_string($koneksi, $_GET['id']) : die("ID Kegiatan tidak ditemukan.");

// Ambil detail kegiatan
$detail_query = mysqli_query($koneksi, "SELECT * FROM kegiatan WHERE id_kegiatan='$id_kegiatan'");
$keg = mysqli_fetch_assoc($detail_query);

if (!$keg) {
    header("location: status-persetujuan.php");
    exit;
}

// Cek apakah kegiatan ini milik panitia yang login
$is_owner = ($keg['diajukan_oleh'] == $nama_panitia);

// Cek apakah kegiatan opsional
$is_optional = ($keg['tipe_kegiatan'] == 'opsional');

// Status mapping
function getStatusInfo($status) {
    switch ($status) {
        case 'approved':
            return [
                'text' => 'DISETUJUI',
                'color' => '#4CAF50',
                'bg' => 'linear-gradient(135deg, #4CAF50, #388E3C)',
                'icon' => 'fa-check-circle'
            ];
        case 'pending':
            return [
                'text' => 'MENUNGGU PERSETUJUAN',
                'color' => '#FF9800',
                'bg' => 'linear-gradient(135deg, #FF9800, #F57C00)',
                'icon' => 'fa-clock'
            ];
        case 'rejected':
            return [
                'text' => 'DITOLAK',
                'color' => '#F44336',
                'bg' => 'linear-gradient(135deg, #F44336, #D32F2F)',
                'icon' => 'fa-times-circle'
            ];
        default:
            return [
                'text' => 'TIDAK DIKETAHUI',
                'color' => '#757575',
                'bg' => '#757575',
                'icon' => 'fa-question-circle'
            ];
    }
}

$statusInfo = getStatusInfo($keg['status_persetujuan']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail: <?php echo htmlspecialchars($keg['nama_kegiatan']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #E0F7FA 0%, #B2EBF2 50%, #80DEEA 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .page-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .page-header h1 {
            color: white;
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 20px;
            text-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            line-height: 1.3;
        }

        .status-badge-header {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 14px 32px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 15px;
            color: white;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            background: <?php echo $statusInfo['bg']; ?>;
        }
        
        .status-badge-header i {
            font-size: 20px;
        }

        .detail-card {
            background: white;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 188, 212, 0.2);
        }

        .image-section {
            width: 100%;
            height: 400px;
            background: linear-gradient(135deg, #00BCD4, #0097A7);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            font-weight: 600;
            text-align: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        .image-section img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
            top: 0;
            left: 0;
        }

        .image-section .no-image-text {
            position: relative;
            z-index: 1;
        }
        
        .image-section .no-image-text i {
            font-size: 80px;
            opacity: 0.7;
            margin-bottom: 15px;
        }

        .metadata-bar {
            background: linear-gradient(135deg, #F5FDFF 0%, #E0F7FA 100%);
            padding: 30px 40px;
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 25px;
            border-bottom: 3px solid #B2EBF2;
        }

        .metadata-item {
            text-align: center;
        }

        .metadata-label {
            font-size: 12px;
            color: #00BCD4;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .metadata-value {
            font-size: 16px;
            color: #263238;
            font-weight: 700;
        }
        
        .metadata-value i {
            color: #00BCD4;
            margin-right: 6px;
        }

        .detail-content {
            padding: 40px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 35px;
        }

        .info-item {
            background: #F5FDFF;
            padding: 24px;
            border-radius: 16px;
            border-left: 5px solid #00BCD4;
            transition: all 0.3s;
        }

        .info-item:hover {
            transform: translateX(8px);
            box-shadow: 0 8px 20px rgba(0, 188, 212, 0.15);
        }

        .info-label {
            font-size: 13px;
            font-weight: 700;
            color: #00BCD4;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .info-label i {
            font-size: 16px;
        }

        .info-value {
            font-size: 17px;
            color: #263238;
            font-weight: 600;
            line-height: 1.5;
        }

        .section-divider {
            height: 2px;
            background: linear-gradient(90deg, transparent, #B2EBF2, transparent);
            margin: 40px 0;
        }

        .purpose-section {
            margin-top: 30px;
        }

        .section-title {
            font-size: 22px;
            font-weight: 800;
            color: #263238;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-title i {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #00BCD4, #0097A7);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }

        .purpose-content {
            background: #F5FDFF;
            padding: 30px;
            border-radius: 16px;
            line-height: 1.9;
            font-size: 16px;
            color: #37474F;
            border: 2px solid #E0F7FA;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        /* Peserta Section */
        .peserta-info-box {
            background: linear-gradient(135deg, #E0F7FA, #B2EBF2);
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 2px solid #00BCD4;
        }

        .peserta-info-box strong {
            color: #0097A7;
            font-size: 1.1em;
        }

        .peserta-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 0.95em;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .peserta-table th, .peserta-table td {
            padding: 15px;
            text-align: left;
        }
        
        .peserta-table thead tr {
            background: linear-gradient(135deg, #00BCD4, #0097A7);
            color: white;
            font-weight: 700;
        }
        
        .peserta-table tbody tr {
            border-bottom: 1px solid #f0f2f5;
            transition: background 0.2s;
        }

        .peserta-table tbody tr:hover {
            background-color: #E0F7FA;
        }
        
        .peserta-table tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .peserta-table tbody tr:nth-child(even):hover {
            background-color: #E0F7FA;
        }

        .empty-peserta {
            text-align: center;
            padding: 30px;
            color: #607D8B;
            background: #f9fafb;
            border-radius: 10px;
            margin-top: 15px;
        }

        .empty-peserta i {
            font-size: 3em;
            color: #B2EBF2;
            margin-bottom: 10px;
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 40px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 16px 40px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            font-size: 16px;
            transition: all 0.3s;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            border: none;
            cursor: pointer;
        }

        .btn-back {
            background: linear-gradient(135deg, #00BCD4, #0097A7);
            color: white;
        }

        .btn-back:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(0, 188, 212, 0.3);
        }

        .btn-edit {
            background: linear-gradient(135deg, #FF9800, #F57C00);
            color: white;
        }

        .btn-edit:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(255, 152, 0, 0.3);
        }

        .btn-delete {
            background: linear-gradient(135deg, #F44336, #D32F2F);
            color: white;
        }

        .btn-delete:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(244, 67, 54, 0.3);
        }

        .alert-info {
            background: #E3F2FD;
            border-left: 4px solid #2196F3;
            padding: 15px 20px;
            border-radius: 8px;
            margin-top: 20px;
            color: #1565C0;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-info i {
            font-size: 20px;
        }

        /* Alasan Penolakan */
        .rejection-reason {
            background: #FFEBEE;
            border-left: 4px solid #F44336;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            color: #C62828;
        }

        .rejection-reason h4 {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .rejection-reason p {
            margin: 0;
            line-height: 1.6;
        }

        @media (max-width: 768px) {
            body {
                padding: 20px 10px;
            }

            .page-header h1 {
                font-size: 24px;
            }

            .image-section {
                height: 250px;
            }

            .detail-content {
                padding: 25px;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .metadata-bar {
                padding: 20px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1><?php echo htmlspecialchars($keg['nama_kegiatan']); ?></h1>
            <div class="status-badge-header">
                <i class="fas <?php echo $statusInfo['icon']; ?>"></i>
                <span><?php echo $statusInfo['text']; ?></span>
            </div>
        </div>
        
        <div class="detail-card">
            <div class="image-section">
                <?php if ($keg['gambar']): ?>
                    <img src="uploads/<?php echo htmlspecialchars($keg['gambar']); ?>" alt="<?php echo htmlspecialchars($keg['nama_kegiatan']); ?>">
                <?php else: ?>
                    <div class="no-image-text">
                        <i class="fas fa-image"></i>
                        <p>Tidak Ada Gambar</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="metadata-bar">
                <div class="metadata-item">
                    <div class="metadata-label"><i class="fas fa-user"></i> Diajukan Oleh</div>
                    <div class="metadata-value"><?php echo htmlspecialchars($keg['diajukan_oleh']); ?></div>
                </div>
                <div class="metadata-item">
                    <div class="metadata-label"><i class="fas fa-calendar"></i> Tanggal Pengajuan</div>
                    <div class="metadata-value"><?php echo date("d F Y", strtotime($keg['tanggal_pengajuan'])); ?></div>
                </div>
                <div class="metadata-item">
                    <div class="metadata-label"><i class="fas fa-clock"></i> Waktu Pengajuan</div>
                    <div class="metadata-value"><?php echo date("H:i", strtotime($keg['tanggal_pengajuan'])); ?> WIB</div>
                </div>
            </div>
            
            <div class="detail-content">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-calendar-check"></i>
                            <span>Jadwal Pelaksanaan</span>
                        </div>
                        <div class="info-value"><?php echo date("d F Y, H:i", strtotime($keg['jadwal'])); ?> WIB</div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Lokasi Kegiatan</span>
                        </div>
                        <div class="info-value"><?php echo htmlspecialchars($keg['lokasi']); ?></div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-tag"></i>
                            <span>Tipe Kegiatan</span>
                        </div>
                        <div class="info-value">
                            <?php 
                                if ($keg['tipe_kegiatan'] == 'wajib') {
                                    echo '<span style="color: #4CAF50; font-weight: 700;">Wajib</span>';
                                } else {
                                    echo '<span style="color: #FF9800; font-weight: 700;">Opsional</span>';
                                }
                            ?>
                        </div>
                    </div>

                    <?php if ($keg['tipe_kegiatan'] == 'opsional' && $keg['maks_peserta'] !== NULL): ?>
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-users"></i>
                            <span>Kuota Maksimal</span>
                        </div>
                        <div class="info-value"><?php echo $keg['maks_peserta']; ?> Peserta</div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Tampilkan Alasan Penolakan jika status rejected -->
                <?php if ($keg['status_persetujuan'] == 'rejected' && !empty($keg['alasan_penolakan'])): ?>
                <div class="rejection-reason">
                    <h4><i class="fas fa-exclamation-triangle"></i> Alasan Penolakan</h4>
                    <p><?php echo nl2br(htmlspecialchars($keg['alasan_penolakan'])); ?></p>
                </div>
                <?php endif; ?>

                <div class="section-divider"></div>

                <div class="purpose-section">
                    <h2 class="section-title">
                        <i class="fas fa-bullseye"></i>
                        Tujuan Kegiatan
                    </h2>
                    <div class="purpose-content"><?php echo nl2br(htmlspecialchars($keg['tujuan'])); ?></div>
                </div>

                <?php if ($is_optional && $keg['status_persetujuan'] == 'approved'): ?>
                    <?php
                    // Query untuk mengambil data peserta kegiatan opsional
                    $peserta_q = mysqli_query($koneksi, "
                        SELECT u.nama, u.nip, u.kelas 
                        FROM peserta_kegiatan pk
                        JOIN users u ON pk.id_siswa = u.id
                        WHERE pk.id_kegiatan='$id_kegiatan'
                        ORDER BY u.kelas, u.nama
                    ");
                    $total_peserta = mysqli_num_rows($peserta_q);
                    $maks_peserta = $keg['maks_peserta'];
                    ?>
                    
                    <div class="section-divider"></div>
                    
                    <div class="purpose-section">
                        <h2 class="section-title">
                            <i class="fas fa-users"></i>
                            Daftar Peserta (Kegiatan Opsional)
                        </h2>
                        
                        <div class="peserta-info-box">
                            <span>Total Peserta Terdaftar: <strong><?php echo $total_peserta; ?></strong></span>
                            <?php if ($maks_peserta !== NULL): ?>
                                <span>Kuota Maksimal: <strong><?php echo $maks_peserta; ?></strong></span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($total_peserta > 0): ?>
                            <table class="peserta-table">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>Nama Siswa</th>
                                        <th>NIS</th>
                                        <th>Kelas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; while ($peserta = mysqli_fetch_assoc($peserta_q)): ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($peserta['nama']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($peserta['nip']); ?></td>
                                        <td><?php echo htmlspecialchars($peserta['kelas']); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="empty-peserta">
                                <i class="fas fa-user-slash"></i>
                                <p><strong>Belum ada siswa yang mendaftar untuk kegiatan ini.</strong></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($is_owner && $keg['status_persetujuan'] == 'approved'): ?>
                    <div class="alert-info">
                        <i class="fas fa-info-circle"></i>
                        <span><strong>Informasi:</strong> Jika Anda mengedit kegiatan yang sudah disetujui, status akan berubah menjadi "Menunggu Persetujuan" dan memerlukan persetujuan ulang dari guru.</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="action-buttons">
            <a href="status-persetujuan.php" class="btn btn-back">
                <i class="fas fa-arrow-left"></i>
                Kembali ke Status Persetujuan
            </a>

            <?php if ($is_owner): ?>
                <?php if ($keg['status_persetujuan'] == 'approved'): ?>
                    <!-- Approved: Bisa edit saja -->
                    <a href="edit-kegiatan-panitia.php?id=<?php echo $id_kegiatan; ?>" class="btn btn-edit">
                        <i class="fas fa-edit"></i>
                        Edit Kegiatan
                    </a>
                <?php elseif ($keg['status_persetujuan'] == 'pending'): ?>
                    <!-- Pending: Bisa edit dan hapus -->
                    <a href="edit-kegiatan-panitia.php?id=<?php echo $id_kegiatan; ?>" class="btn btn-edit">
                        <i class="fas fa-edit"></i>
                        Edit Kegiatan
                    </a>
                    <button onclick="confirmDelete('<?php echo $id_kegiatan; ?>')" class="btn btn-delete">
                        <i class="fas fa-trash"></i>
                        Hapus Kegiatan
                    </button>
                <?php elseif ($keg['status_persetujuan'] == 'rejected'): ?>
                    <!-- Rejected: Bisa hapus saja -->
                    <button onclick="confirmDelete('<?php echo $id_kegiatan; ?>')" class="btn btn-delete">
                        <i class="fas fa-trash"></i>
                        Hapus Kegiatan
                    </button>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function confirmDelete(id) {
            if (confirm('Apakah Anda yakin ingin menghapus kegiatan ini? Tindakan ini tidak dapat dibatalkan.')) {
                window.location.href = 'hapus-kegiatan.php?id=' + id;
            }
        }
    </script>
</body>
</html>