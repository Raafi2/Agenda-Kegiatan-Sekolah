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
$id_kegiatan = isset($_GET['id']) ? mysqli_real_escape_string($koneksi, $_GET['id']) : '';

if(empty($id_kegiatan)){
    header("location: status-persetujuan.php");
    exit;
}

// Ambil data kegiatan
$query = mysqli_query($koneksi, "SELECT * FROM kegiatan WHERE id_kegiatan='$id_kegiatan' AND diajukan_oleh='$nama_panitia'");
$kegiatan = mysqli_fetch_assoc($query);

if(!$kegiatan){
    header("location: status-persetujuan.php");
    exit;
}

// Jika kegiatan sudah ditolak, tidak bisa diedit
if($kegiatan['status_persetujuan'] == 'rejected'){
    header("location: panitia-detail-kegiatan.php?id=$id_kegiatan");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Kegiatan - <?php echo htmlspecialchars($kegiatan['nama_kegiatan']); ?></title>
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
            max-width: 800px;
            margin: 0 auto;
        }

        .page-header {
            background: white;
            border-radius: 20px;
            padding: 35px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 188, 212, 0.15);
            text-align: center;
        }
        
        .page-header h1 {
            font-size: 32px;
            color: #263238;
            font-weight: 800;
            margin-bottom: 10px;
        }
        
        .page-header p {
            color: #607D8B;
            font-size: 15px;
        }

        .form-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 188, 212, 0.15);
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            font-weight: 700;
            color: #263238;
            margin-bottom: 10px;
            font-size: 15px;
        }

        .form-label i {
            color: #00BCD4;
            margin-right: 8px;
        }

        .form-control {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #E0F7FA;
            border-radius: 12px;
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s;
            background: #F5FDFF;
        }

        .form-control:focus {
            outline: none;
            border-color: #00BCD4;
            background: white;
            box-shadow: 0 0 0 4px rgba(0, 188, 212, 0.1);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }

        .form-control::placeholder {
            color: #B0BEC5;
        }

        .image-preview {
            margin-top: 15px;
            text-align: center;
        }

        .image-preview img {
            max-width: 100%;
            max-height: 300px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            object-fit: cover;
        }

        .alert-warning {
            background: #FFF3E0;
            border-left: 4px solid #FF9800;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            color: #E65100;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-warning i {
            font-size: 20px;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
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

        .btn-primary {
            background: linear-gradient(135deg, #00BCD4, #0097A7);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(0, 188, 212, 0.3);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #607D8B, #455A64);
            color: white;
        }

        .btn-secondary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(96, 125, 139, 0.3);
        }

        @media (max-width: 768px) {
            .form-card {
                padding: 25px;
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
            <h1><i class="fas fa-edit"></i> Edit Kegiatan</h1>
            <p>Ubah detail kegiatan Anda</p>
        </div>

        <?php if($kegiatan['status_persetujuan'] == 'approved'): ?>
        <div class="alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <span><strong>Perhatian!</strong> Kegiatan ini sudah disetujui. Jika Anda mengedit, status akan berubah menjadi "Menunggu Persetujuan" dan memerlukan persetujuan ulang dari guru.</span>
        </div>
        <?php endif; ?>

        <div class="form-card">
            <form action="proses-edit-kegiatan.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id_kegiatan" value="<?php echo $id_kegiatan; ?>">
                <input type="hidden" name="gambar_lama" value="<?php echo htmlspecialchars($kegiatan['gambar']); ?>">
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-heading"></i>
                        Nama Kegiatan
                    </label>
                    <input type="text" name="nama_kegiatan" class="form-control" 
                           value="<?php echo htmlspecialchars($kegiatan['nama_kegiatan']); ?>" 
                           placeholder="Masukkan nama kegiatan" required>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-calendar"></i>
                        Jadwal Pelaksanaan
                    </label>
                    <input type="datetime-local" name="jadwal" class="form-control" 
                           value="<?php echo date('Y-m-d\TH:i', strtotime($kegiatan['jadwal'])); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-map-marker-alt"></i>
                        Lokasi Kegiatan
                    </label>
                    <input type="text" name="lokasi" class="form-control" 
                           value="<?php echo htmlspecialchars($kegiatan['lokasi']); ?>" 
                           placeholder="Masukkan lokasi kegiatan" required>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-bullseye"></i>
                        Tujuan Kegiatan
                    </label>
                    <textarea name="tujuan" class="form-control" 
                              placeholder="Jelaskan tujuan dari kegiatan ini" required><?php echo htmlspecialchars($kegiatan['tujuan']); ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-image"></i>
                        Gambar Kegiatan (Opsional - Kosongkan jika tidak ingin mengubah)
                    </label>
                    <input type="file" name="gambar" class="form-control" accept="image/*" onchange="previewImage(this)">
                    
                    <?php if($kegiatan['gambar']): ?>
                    <div class="image-preview" id="currentImage">
                        <p style="color: #607D8B; margin-top: 10px; font-size: 14px;">Gambar Saat Ini:</p>
                        <img src="uploads/<?php echo htmlspecialchars($kegiatan['gambar']); ?>" alt="Current Image">
                    </div>
                    <?php endif; ?>
                    
                    <div class="image-preview" id="newImagePreview" style="display: none;">
                        <p style="color: #607D8B; margin-top: 10px; font-size: 14px;">Preview Gambar Baru:</p>
                        <img id="previewImg" src="" alt="Preview">
                    </div>
                </div>

                <div class="action-buttons">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Simpan Perubahan
                    </button>
                    <a href="panitia-detail-kegiatan.php?id=<?php echo $id_kegiatan; ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                
                reader.onload = function(e) {
                    document.getElementById('currentImage').style.display = 'none';
                    document.getElementById('newImagePreview').style.display = 'block';
                    document.getElementById('previewImg').src = e.target.result;
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>