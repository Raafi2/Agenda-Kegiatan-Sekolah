<?php
session_start(); 
include 'config.php';

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'panitia'){
    header("location: panitia.php");
    exit;
}

$nama_panitia = $_SESSION['nama'];

// Handle error messages
$error_message = '';
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'image_required':
            $error_message = 'Gambar kegiatan wajib diunggah!';
            break;
        case 'invalid_image_type':
            $error_message = 'Tipe file tidak valid! Hanya JPG, PNG, GIF, dan WEBP yang diperbolehkan.';
            break;
        case 'image_too_large':
            $error_message = 'Ukuran gambar terlalu besar! Maksimal 10MB.';
            break;
        case 'image_processing_failed':
            $error_message = 'Gagal memproses gambar!';
            break;
        case 'db_insert_failed':
            $error_message = 'Gagal menyimpan data ke database!';
            break;
        case 'data_required':
            $error_message = 'Mohon lengkapi semua data wajib sebelum mengirim!';
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengajuan Kegiatan - Agenda Sekolah</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }

        .main-container {
            width: 100%;
            max-width: 900px;
            background-color: #ffffff;
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 188, 212, 0.2);
        }

        .header {
            text-align: center;
            margin-bottom: 35px;
            padding-bottom: 25px;
            border-bottom: 3px solid #E0F7FA;
        }

        .header-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #00BCD4, #0097A7);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 32px;
            color: white;
            box-shadow: 0 8px 20px rgba(0, 188, 212, 0.3);
        }

        .header h1 {
            color: #263238;
            font-weight: 800;
            font-size: 28px;
            margin-bottom: 8px;
        }

        .header p {
            color: #607D8B;
            font-size: 15px;
        }
        
        .header p strong {
            color: #00BCD4;
            font-weight: 700;
        }

        .form-section {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .input-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        label {
            font-weight: 600;
            font-size: 14px;
            color: #37474F;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        label i {
            color: #00BCD4;
            font-size: 16px;
        }
        
        label .required {
            color: #F44336;
            font-size: 16px;
        }

        input[type="text"],
        input[type="datetime-local"],
        input[type="number"],
        textarea,
        select {
            padding: 14px 18px;
            border: 2px solid #E0E0E0;
            border-radius: 12px;
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            width: 100%;
            transition: all 0.3s;
            background: #FAFAFA;
        }

        input:focus,
        textarea:focus,
        select:focus {
            border-color: #00BCD4;
            outline: none;
            background: white;
            box-shadow: 0 0 0 4px rgba(0, 188, 212, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 120px;
            line-height: 1.6;
        }
        
        select {
            cursor: pointer;
        }
        
        small {
            font-size: 12px;
            color: #607D8B;
            margin-top: 4px;
        }

        /* Image Upload Styling */
        .file-upload-wrapper {
            position: relative;
        }
        
        .file-upload-container {
            border: 3px dashed #B2EBF2;
            border-radius: 16px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: #F5FDFF;
            position: relative;
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            overflow: hidden;
        }

        .file-upload-container:hover {
            border-color: #00BCD4;
            background: #E0F7FA;
            transform: translateY(-2px);
        }

        .upload-placeholder {
            color: #607D8B;
        }
        
        .upload-placeholder i {
            font-size: 48px;
            color: #80DEEA;
            margin-bottom: 15px;
        }
        
        .upload-placeholder p {
            font-size: 15px;
            font-weight: 500;
        }

        .image-preview {
            max-width: 100%;
            max-height: 100%;
            border-radius: 12px;
            object-fit: contain;
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .image-preview.show {
            opacity: 1;
        }
        
        .remove-image-btn {
            position: absolute;
            top: 12px;
            right: 12px;
            background: rgba(244, 67, 54, 0.95);
            color: white;
            border: none;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            cursor: pointer;
            font-size: 18px;
            line-height: 1;
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(244, 67, 54, 0.4);
        }
        
        .remove-image-btn:hover {
            background: #D32F2F;
            transform: scale(1.1);
        }

        .remove-image-btn.show {
            display: flex;
        }
        
        /* Submit Button */
        .btn-submit {
            background: linear-gradient(135deg, #00BCD4, #0097A7);
            color: white;
            padding: 16px 40px;
            border: none;
            border-radius: 50px;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            margin-top: 20px;
            box-shadow: 0 8px 20px rgba(0, 188, 212, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(0, 188, 212, 0.4);
        }
        
        .btn-submit:active {
            transform: translateY(-1px);
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
            text-decoration: none;
            color: #00BCD4;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s;
            padding: 10px 0;
        }

        .btn-back:hover {
            gap: 12px;
            color: #0097A7;
        }
        
        /* Info Box */
        .info-box {
            background: #E1F5FE;
            border-left: 4px solid #00BCD4;
            padding: 16px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
        }
        
        .info-box p {
            color: #01579B;
            font-size: 14px;
            line-height: 1.6;
            margin: 0;
        }
        
        .info-box i {
            color: #00BCD4;
            margin-right: 8px;
        }
        
        /* Error Alert */
        .error-alert {
            background: #FFEBEE;
            border-left: 4px solid #F44336;
            padding: 16px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideDown 0.3s ease;
        }
        
        .error-alert i {
            color: #F44336;
            font-size: 20px;
        }
        
        .error-alert p {
            color: #C62828;
            font-size: 14px;
            font-weight: 600;
            margin: 0;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 30px 20px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="header">
            <div class="header-icon">
                <i class="fas fa-calendar-plus"></i>
            </div>
            <h1>Form Pengajuan Kegiatan</h1>
            <p>Diajukan oleh: <strong><?php echo htmlspecialchars($nama_panitia); ?></strong></p>
        </div>
        
        <?php if ($error_message): ?>
            <div class="error-alert">
                <i class="fas fa-exclamation-triangle"></i>
                <p><?php echo $error_message; ?></p>
            </div>
        <?php endif; ?>
        
        <div class="info-box">
            <p><i class="fas fa-info-circle"></i> Silakan lengkapi form di bawah ini untuk mengajukan kegiatan baru. <strong>Gambar kegiatan wajib diunggah</strong> dan akan otomatis disesuaikan ukurannya.</p>
        </div>
        
        <form id="pengajuanForm" action="pengajuan-kegiatan-process.php" method="POST" enctype="multipart/form-data" class="form-section">
            
            <div class="input-group">
                <label for="kegiatan">
                    <i class="fas fa-file-signature"></i>
                    Nama Kegiatan
                    <span class="required">*</span>
                </label>
                <input type="text" id="kegiatan" name="kegiatan" placeholder="Contoh: Lomba Basket Antar Kelas" required>
            </div>
            
            <div class="form-row">
                <div class="input-group">
                    <label for="jadwal">
                        <i class="fas fa-calendar-alt"></i>
                        Jadwal Pelaksanaan
                        <span class="required">*</span>
                    </label>
                    <input type="datetime-local" id="jadwal" name="jadwal" required>
                </div>
                
                <div class="input-group">
                    <label for="lokasi">
                        <i class="fas fa-map-marker-alt"></i>
                        Lokasi Kegiatan
                        <span class="required">*</span>
                    </label>
                    <input type="text" id="lokasi" name="lokasi" placeholder="Contoh: Lapangan Utama Sekolah" required>
                </div>
            </div>
            
            <div class="input-group">
                <label for="tujuan">
                    <i class="fas fa-bullseye"></i>
                    Tujuan Kegiatan
                    <span class="required">*</span>
                </label>
                <textarea id="tujuan" name="tujuan" placeholder="Jelaskan tujuan dan manfaat kegiatan secara detail..." rows="5" required></textarea>
            </div>
            
            <div class="form-row">
                <div class="input-group">
                    <label for="tipe_kegiatan">
                        <i class="fas fa-tag"></i>
                        Tipe Kegiatan
                        <span class="required">*</span>
                    </label>
                    <select id="tipe_kegiatan" name="tipe_kegiatan" required onchange="toggleMaxPeserta()">
                        <option value="wajib">Wajib (Semua siswa)</option>
                        <option value="opsional">Opsional (Bisa diikuti siswa, ada kuota)</option>
                    </select>
                </div>

                <div class="input-group" id="maksPesertaContainer" style="display: none;">
                    <label for="maks_peserta">
                        <i class="fas fa-users"></i>
                        Maksimal Peserta
                    </label>
                    <input 
                        type="number" 
                        id="maks_peserta" 
                        name="maks_peserta" 
                        placeholder="Contoh: 50" 
                        min="1"
                        value=""
                    >
                    <small>Kosongkan jika tidak ada batasan</small>
                </div>
            </div>
            
            <div class="input-group">
                <label for="gambar_kegiatan">
                    <i class="fas fa-image"></i>
                    Gambar Kegiatan
                    <span class="required">*</span>
                </label>
                <input 
                    type="file" 
                    id="gambar_kegiatan_input" 
                    name="gambar_kegiatan" 
                    style="display: none;" 
                    accept="image/*"
                    required
                >
                <div class="file-upload-wrapper">
                    <div class="file-upload-container" id="imagePreviewContainer">
                        <button type="button" class="remove-image-btn" id="removeImageBtn">
                            <i class="fas fa-times"></i>
                        </button>
                        <img src="#" alt="Preview Gambar" class="image-preview" id="previewImage">
                        <div class="upload-placeholder" id="uploadPlaceholder">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p><strong>WAJIB:</strong> Klik untuk unggah gambar kegiatan</p>
                            <small style="margin-top: 8px; display: block;">Gambar akan otomatis disesuaikan ukurannya (Max 10MB)</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn-submit">
                <i class="fas fa-paper-plane"></i>
                AJUKAN KEGIATAN
            </button>
        </form>
        
        <a href="dashboard-panitia.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>
    </div>

    <script>
        const fileInput = document.getElementById('gambar_kegiatan_input');
        const imagePreviewContainer = document.getElementById('imagePreviewContainer');
        const previewImage = document.getElementById('previewImage');
        const uploadPlaceholder = document.getElementById('uploadPlaceholder');
        const removeImageBtn = document.getElementById('removeImageBtn');

        // Toggle Maksimal Peserta
        function toggleMaxPeserta() {
            const tipe = document.getElementById('tipe_kegiatan').value;
            const container = document.getElementById('maksPesertaContainer');
            const input = document.getElementById('maks_peserta');

            if (tipe === 'opsional') {
                container.style.display = 'flex';
                // Mengosongkan nilai input number (opsional)
                // input.value = ''; // Biarkan nilai yang diisi user jika ada
            } else {
                container.style.display = 'none';
                input.value = '';
            }
        }
        
        document.addEventListener('DOMContentLoaded', toggleMaxPeserta);

        // Image Upload Logic
        imagePreviewContainer.addEventListener('click', function(e) {
            // Mencegah klik container jika tombol hapus yang diklik
            if (e.target.id !== 'removeImageBtn' && !e.target.closest('#removeImageBtn')) {
                // Membuka dialog file input
                fileInput.click();
            }
        });

        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    previewImage.classList.add('show');
                    uploadPlaceholder.style.display = 'none';
                    removeImageBtn.classList.add('show');
                    // Menghilangkan required setelah ada file
                    fileInput.removeAttribute('required');
                };
                
                reader.readAsDataURL(file);
            }
        });

        removeImageBtn.addEventListener('click', function(e) {
            e.stopPropagation(); // Mencegah event klik menyebar ke container
            fileInput.value = ''; // Mengosongkan file input
            previewImage.src = '';
            previewImage.classList.remove('show');
            uploadPlaceholder.style.display = 'block';
            removeImageBtn.classList.remove('show');
            // Menambahkan kembali required setelah file dihapus
            fileInput.setAttribute('required', 'required');
        });

        // Form Validation
        document.getElementById('pengajuanForm').addEventListener('submit', function(e) {
            const kegiatan = document.getElementById('kegiatan').value.trim();
            const jadwal = document.getElementById('jadwal').value;
            const lokasi = document.getElementById('lokasi').value.trim();
            const tujuan = document.getElementById('tujuan').value.trim();
            
            if (!kegiatan || !jadwal || !lokasi || !tujuan) {
                e.preventDefault();
                alert('Mohon lengkapi semua field yang wajib diisi!');
                return false;
            }
            
            if (!fileInput.files || fileInput.files.length === 0) {
                e.preventDefault();
                alert('Gambar kegiatan WAJIB diunggah!');
                return false;
            }
            
            // Mengatur kembali required sebelum submit (jika sebelumnya dihapus)
            fileInput.setAttribute('required', 'required');
        });
    </script>
</body>
</html>