<?php
session_start();
include 'config.php';

// --- Bagian 1: Validasi Sesi dan User ---
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'siswa') {
    header("location: siswa.php?error=unauthorized");
    exit;
}

if (!isset($_SESSION['id_user'])) {
    header("location: siswa.php?error=no_id");
    exit;
}

$id_siswa = intval($_SESSION['id_user']);

// Pastikan koneksi database berhasil
if (!$koneksi) {
    header("location: siswa-kegiatan.php?upload=error&msg=db_connect_failed");
    exit;
}

// --- Bagian 2: Proses Upload File ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['foto_profil'])) {
    $file = $_FILES['foto_profil'];
    
    // 2.1. Validasi error upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_msg = 'unknown_upload_error';
        if ($file['error'] == UPLOAD_ERR_INI_SIZE || $file['error'] == UPLOAD_ERR_FORM_SIZE) {
             $error_msg = 'file_too_large_php';
        }
        header("location: siswa-kegiatan.php?upload=error&msg=$error_msg");
        exit;
    }
    
    // 2.2. Validasi ukuran file (max 2MB)
    $maxSize = 2 * 1024 * 1024; // 2MB
    if ($file['size'] > $maxSize) {
        header("location: siswa-kegiatan.php?upload=error&msg=file_too_large");
        exit;
    }
    
    // 2.3. Validasi tipe file (Lebih aman menggunakan finfo jika tersedia)
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
    $fileType = $file['type']; // Ambil tipe dari browser dulu

    // Fallback: Lakukan pemeriksaan tipe MIME yang lebih ketat jika ekstensi fileinfo tersedia
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $fileType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
    } 
    // Jika mime_content_type tersedia, gunakan itu (Seperti di kode asli Anda)
    else if (function_exists('mime_content_type')) {
        $fileType = mime_content_type($file['tmp_name']);
    }

    if (!in_array($fileType, $allowedTypes)) {
        header("location: siswa-kegiatan.php?upload=error&msg=invalid_type");
        exit;
    }
    
    // 2.4. Buat nama file unik
    // Dapatkan ekstensi yang bersih dari fileType (misal: 'jpeg')
    $extension_map = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
    $fileExtension = $extension_map[$fileType] ?? pathinfo($file['name'], PATHINFO_EXTENSION);
    
    // Bersihkan fileExtension, hanya izinkan alfanumerik
    $fileExtension = preg_replace("/[^a-zA-Z0-9]/", "", strtolower($fileExtension));

    $newFileName = 'profile_' . $id_siswa . '_' . time() . '.' . $fileExtension;
    
    // 2.5. Persiapan direktori dan path
    $uploadDir = 'uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $uploadPath = $uploadDir . $newFileName;
    
    // 2.6. Ambil foto profil lama untuk dihapus
    $query_old = mysqli_query($koneksi, "SELECT foto_profil FROM users WHERE id='$id_siswa'");
    $old_data = mysqli_fetch_assoc($query_old);
    $old_photo = $old_data['foto_profil'] ?? ''; // Menggunakan Null Coalescing PHP 7+
    
    // 2.7. Upload file baru
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        // --- Bagian 3: Update Database dan Hapus File Lama ---
        
        // Sanitasi dan persiapkan query update
        $sanitized_newFileName = mysqli_real_escape_string($koneksi, $newFileName);
        $update_query = "UPDATE users SET foto_profil='$sanitized_newFileName' WHERE id='$id_siswa'";
        
        if (mysqli_query($koneksi, $update_query)) {
            // Hapus foto lama jika ada dan bukan file default
            $default_files = ['logo.jpg', 'default.png']; // Tambahkan file default Anda
            
            if ($old_photo && $old_photo !== $newFileName && !in_array($old_photo, $default_files) && file_exists($uploadDir . $old_photo)) {
                // Tambahkan pengecekan keamanan tambahan (misalnya, cek apakah $old_photo benar-benar file)
                if (is_file($uploadDir . $old_photo)) {
                    unlink($uploadDir . $old_photo);
                }
            }
            
            header("location: siswa-kegiatan.php?upload=success");
            exit;
        } else {
            // Rollback: Hapus file yang baru diupload jika gagal update database
            if (file_exists($uploadPath)) {
                unlink($uploadPath);
            }
            header("location: siswa-kegiatan.php?upload=error&msg=db_error");
            exit;
        }
    } else {
        header("location: siswa-kegiatan.php?upload=error&msg=move_failed");
        exit;
    }
} else {
    header("location: siswa-kegiatan.php?upload=error&msg=no_file_data");
    exit;
}
?>