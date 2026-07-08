<?php
session_start(); 
include 'config.php'; 


error_log("=== PERSETUJUAN.PHP DEBUG ===");
error_log("Session Role: " . ($_SESSION['role'] ?? 'NOT SET'));
error_log("GET ID: " . ($_GET['id'] ?? 'NOT SET'));

// 1. Cek Otorisasi: Hanya Guru yang Boleh Mengakses
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'guru'){
    error_log("Redirecting to guru.php - Role not authorized");
    header("location: guru.php"); 
    exit;
}

// 2. Validasi ID dari URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    error_log("Redirecting to daftar-ajuan.php - No ID provided");
    header("location: daftar-ajuan.php");
    exit;
}

$id_kegiatan = mysqli_real_escape_string($koneksi, $_GET['id']);
error_log("Processing ID: " . $id_kegiatan);

// 3. Ambil detail kegiatan
// Menggunakan prepared statement lebih aman, tapi untuk saat ini, kita gunakan kode aslinya
$detail_query = mysqli_query($koneksi, "SELECT * FROM kegiatan WHERE id_kegiatan='$id_kegiatan'");
if (!$detail_query) {
    error_log("Database query failed: " . mysqli_error($koneksi));
    header("location: daftar-ajuan.php?error=query_failed");
    exit;
}

$keg = mysqli_fetch_assoc($detail_query);

// 4. Cek jika kegiatan tidak ada
if (!$keg) {
    error_log("Activity not found for ID: " . $id_kegiatan);
    header("location: daftar-ajuan.php?error=activity_not_found");
    exit;
}

error_log("Activity found: " . $keg['nama_kegiatan']);

// Cek apakah kegiatan ini opsional (untuk menampilkan daftar peserta)
$is_optional = ($keg['tipe_kegiatan'] == 'opsional');

// Fungsi Terjemahan Status
function terjemahkanStatus($status_eng) {
    switch ($status_eng) {
        case 'approved': return 'DISETUJUI';
        case 'pending': return 'MENUNGGU PERSETUJUAN';
        case 'rejected': return 'DITOLAK';
        default: return strtoupper($status_eng);
    }
}
$status_text = terjemahkanStatus($keg['status_persetujuan']);
$status = $keg['status_persetujuan'];

// Pastikan path gambar benar. Jika kosong, pakai placeholder/logo.
$gambar_src = !empty($keg['gambar']) ? 'uploads/' . htmlspecialchars($keg['gambar']) : 'https://via.placeholder.com/800x400/00BCD4/FFFFFF?text=Gambar+Kegiatan';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Persetujuan Kegiatan: <?php echo htmlspecialchars($keg['nama_kegiatan']); ?></title>
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
            max-width: 1000px;
            margin: 0 auto;
        }

        .card-detail {
            background-color: var(--white);
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 25px;
        }

        .kegiatan-header {
            position: relative;
            height: 300px;
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: flex-end;
            padding: 30px;
            color: white;
            z-index: 1;
        }
        
        .kegiatan-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, rgba(0,0,0,0.3), rgba(0,0,0,0.7));
            z-index: 2;
        }
        
        .header-content {
            z-index: 3;
            width: 100%;
        }

        .header-content h1 {
            font-size: 2.3em;
            margin: 0 0 10px 0;
            font-weight: 800;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }

        .metadata-section {
            background: linear-gradient(135deg, var(--primary-cyan-light), #E0F7FA);
            padding: 20px 30px;
            display: flex;
            justify-content: space-around;
            align-items: center;
            border-bottom: 3px solid var(--primary-cyan);
        }

        .metadata-box {
            display: flex;
            flex-direction: column;
            text-align: center;
            padding: 10px 15px;
        }

        .metadata-label {
            font-size: 0.85em;
            color: var(--text-grey);
            font-weight: 600;
            margin-bottom: 5px;
        }

        .metadata-value {
            font-size: 1.1em;
            color: var(--text-dark);
            font-weight: 700;
        }

        .status-badge {
            font-size: 1.1em;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 800;
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            display: inline-block;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }

        .approved { background: linear-gradient(135deg, #10b981, #059669); }
        .pending { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .rejected { background: linear-gradient(135deg, #ef4444, #dc2626); }

        .detail-content {
            padding: 30px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-item {
            padding: 20px;
            border: 2px solid var(--primary-cyan-light);
            border-radius: 15px;
            background: linear-gradient(to bottom right, #ffffff, #f8fdff);
            transition: all 0.3s;
        }

        .info-item:hover {
            border-color: var(--primary-cyan);
            box-shadow: 0 5px 15px rgba(0, 188, 212, 0.2);
            transform: translateY(-2px);
        }

        .info-label {
            font-weight: 700;
            color: var(--primary-cyan);
            margin-bottom: 8px;
            font-size: 1.05em;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-label i {
            font-size: 1.2em;
        }

        .info-value {
            color: var(--text-dark);
            font-size: 1.05em;
            font-weight: 500;
        }

        .section-divider {
            border-top: 2px dashed var(--primary-cyan-light);
            margin: 30px 0;
        }

        .section-title {
            font-size: 1.4em;
            color: var(--primary-cyan);
            font-weight: 700;
            margin-bottom: 20px;
            border-bottom: 3px solid var(--primary-cyan-light);
            padding-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .purpose-content {
            color: var(--text-dark);
            line-height: 1.8;
            font-size: 1.05em;
            padding: 15px;
            background: #f8fdff;
            border-radius: 10px;
            border-left: 4px solid var(--primary-cyan);
        }

        /* Gaya untuk Daftar Peserta */
        .peserta-info-box {
            background: linear-gradient(135deg, var(--primary-cyan-light), #E0F7FA);
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 2px solid var(--primary-cyan);
        }

        .peserta-info-box strong {
            color: var(--primary-cyan-dark);
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
            background: linear-gradient(135deg, var(--primary-cyan), var(--primary-cyan-dark));
            color: white;
            font-weight: 700;
        }
        
        .peserta-table tbody tr {
            border-bottom: 1px solid #f0f2f5;
            transition: background 0.2s;
        }

        .peserta-table tbody tr:hover {
            background-color: var(--primary-cyan-light);
        }
        
        .peserta-table tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .peserta-table tbody tr:nth-child(even):hover {
            background-color: var(--primary-cyan-light);
        }

        .empty-peserta {
            text-align: center;
            padding: 30px;
            color: var(--text-grey);
            background: #f9fafb;
            border-radius: 10px;
            margin-top: 15px;
        }

        .empty-peserta i {
            font-size: 3em;
            color: var(--primary-cyan-light);
            margin-bottom: 10px;
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            border-top: 2px solid var(--primary-cyan-light);
            padding: 25px;
        }

        .btn {
            padding: 14px 35px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.05em;
            transition: all 0.3s;
            cursor: pointer;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn-success { 
            background: linear-gradient(135deg, #10b981, #059669);
            color: white; 
        }
        .btn-success:hover { 
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
        }
        
        .btn-danger { 
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white; 
        }
        .btn-danger:hover { 
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, var(--primary-cyan), var(--primary-cyan-dark));
            color: white;
        }
        .btn-secondary:hover { 
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 188, 212, 0.4);
        }

        /* Modal Style */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.6);
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 30px;
            border-radius: 20px;
            width: 90%;
            max-width: 550px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            animation: slideIn 0.3s;
        }

        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-content h3 {
            color: #dc2626;
            margin-bottom: 20px;
            font-size: 1.5em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-content textarea {
            width: 100%;
            padding: 15px;
            border-radius: 10px;
            border: 2px solid #e5e7eb;
            margin: 15px 0;
            font-family: 'Poppins', sans-serif;
            font-size: 1em;
            resize: vertical;
            transition: border-color 0.3s;
        }

        .modal-content textarea:focus {
            outline: none;
            border-color: var(--primary-cyan);
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 20px;
        }

        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
            .action-buttons {
                flex-direction: column;
            }
            .metadata-section {
                flex-direction: column;
                gap: 15px;
            }
            .kegiatan-header {
                height: 250px;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        
        <div class="card-detail">
            
            <div class="kegiatan-header" style="background-image: url('<?php echo $gambar_src; ?>');">
                <div class="header-content">
                    <span class="status-badge <?php echo $status; ?>" style="margin-bottom: 10px; display: inline-block;">
                        <?php echo $status_text; ?>
                    </span>
                    <h1><?php echo htmlspecialchars($keg['nama_kegiatan']); ?></h1>
                </div>
            </div>

            <div class="metadata-section">
                <div class="metadata-box">
                    <div class="metadata-label">Diajukan Oleh</div>
                    <div class="metadata-value"><?php echo htmlspecialchars($keg['diajukan_oleh']); ?></div>
                </div>
                <div class="metadata-box">
                    <div class="metadata-label">Tanggal Pengajuan</div>
                    <div class="metadata-value"><?php echo date("d F Y", strtotime($keg['tanggal_pengajuan'])); ?></div>
                </div>
                <div class="metadata-box">
                    <div class="metadata-label">Tipe Kegiatan</div>
                    <div class="metadata-value" style="color: <?php echo $keg['tipe_kegiatan'] == 'opsional' ? '#f59e0b' : '#10b981'; ?>;"><?php echo strtoupper($keg['tipe_kegiatan']); ?></div>
                </div>
            </div>
            
            <div class="detail-content">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Jadwal Pelaksanaan</span>
                        </div>
                        <div class="info-value"><?php echo date("l, d F Y H:i", strtotime($keg['jadwal'])); ?> WIB</div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Lokasi Kegiatan</span>
                        </div>
                        <div class="info-value"><?php echo htmlspecialchars($keg['lokasi']); ?></div>
                    </div>
                </div>

                <div class="section-divider"></div>

                <div class="purpose-section">
                    <h2 class="section-title">
                        <i class="fas fa-bullseye"></i> Tujuan Kegiatan
                    </h2>
                    <div class="purpose-content"><?php echo nl2br(htmlspecialchars($keg['tujuan'])); ?></div>
                </div>
                
                <?php if ($is_optional): ?>
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
                            <i class="fas fa-users"></i> Daftar Peserta (Kegiatan Opsional)
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
            </div>
            
            <?php if ($keg['status_persetujuan'] == 'pending'): ?>
<div class="action-buttons">
    <form id="approveForm" action="persetujuan_process.php" method="POST" style="display: inline;">
        <input type="hidden" name="id_kegiatan" value="<?php echo $keg['id_kegiatan']; ?>">
        <input type="hidden" name="status_aksi" value="approved">
        <button type="submit" onclick="return confirmApprove()" class="btn btn-success">
            <i class="fas fa-check-circle"></i> SETUJUI
        </button>
    </form>
    
    <button type="button" onclick="showRejectModal()" class="btn btn-danger">
        <i class="fas fa-times-circle"></i> TOLAK
    </button>
</div>
<?php endif; ?>
        </div>
        
        <div style="text-align: center; margin-top: 0;">
            <a href="daftar-ajuan.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>
    
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-exclamation-triangle"></i> Alasan Penolakan</h3>
            <p>Jelaskan secara singkat alasan kegiatan ini ditolak:</p>
            
            <form id="rejectForm" action="persetujuan_process.php" method="POST">
                <input type="hidden" name="id_kegiatan" value="<?php echo $keg['id_kegiatan']; ?>">
                <input type="hidden" name="status_aksi" value="rejected">
                <textarea name="alasan_penolakan" rows="5" placeholder="Masukkan alasan penolakan di sini..." required></textarea>
                <div class="modal-actions">
                    <button type="button" onclick="hideRejectModal()" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-danger" onclick="return validateRejectForm()">
                        <i class="fas fa-paper-plane"></i> Kirim Penolakan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // Fungsi Menampilkan Modal Tolak
    function showRejectModal() {
        document.getElementById('rejectModal').style.display = 'flex';
    }

    // Fungsi Menyembunyikan Modal Tolak
    function hideRejectModal() {
        document.getElementById('rejectModal').style.display = 'none';
        // Reset form reject
        document.getElementById('rejectForm').reset();
    }

    // Fungsi Konfirmasi Approve
    function confirmApprove() {
        return confirm('Apakah Anda yakin ingin menyetujui kegiatan ini?');
    }

    // Fungsi Validasi Form Reject
    function validateRejectForm() {
        const alasan = document.querySelector('textarea[name="alasan_penolakan"]').value.trim();
        if (alasan === '') {
            alert('Mohon isi alasan penolakan sebelum mengirim.');
            return false;
        }
        return confirm('Apakah Anda yakin ingin menolak kegiatan ini?');
    }

    // Menutup modal jika klik di area gelap (overlay)
    document.getElementById('rejectModal').addEventListener('click', function(e) {
        if (e.target === this) {
            hideRejectModal();
        }
    });

    // Debug: Log untuk memastikan JavaScript berjalan
    console.log('persetujuan.php loaded successfully');
    console.log('Activity ID:', '<?php echo $keg['id_kegiatan']; ?>');
</script>
</body>
</html>