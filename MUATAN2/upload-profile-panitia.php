<?php
session_start();
include 'config.php';

// Cek autentikasi
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'panitia'){
    header("location: panitia.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['foto_profil'])) {
    $user_id = $_POST['user_id'] ?? $_SESSION['id'] ?? null;
    
    if (!$user_id) {
        header("Location: dashboard-panitia.php?error=user_not_found");
        exit;
    }
    
    $file = $_FILES['foto_profil'];
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    // Validasi tipe file
    if (!in_array($file['type'], $allowed_types)) {
        header("Location: dashboard-panitia.php?error=invalid_type");
        exit;
    }
    
    // Validasi ekstensi file
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, $allowed_extensions)) {
        header("Location: dashboard-panitia.php?error=invalid_extension");
        exit;
    }
    
    // Validasi ukuran file (max 5MB)
    if ($file['size'] > 5242880) {
        header("Location: dashboard-panitia.php?error=file_too_large");
        exit;
    }
    
    // Validasi error upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        header("Location: dashboard-panitia.php?error=upload_error");
        exit;
    }
    
    // Buat direktori uploads/profiles jika belum ada
    $upload_dir = 'uploads/profiles/';
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            header("Location: dashboard-panitia.php?error=directory_creation_failed");
            exit;
        }
    }
    
    // Generate nama file unik
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
    $upload_path = $upload_dir . $new_filename;
    
    // Hapus foto profil lama jika ada
    $old_photo_query = mysqli_query($koneksi, "SELECT foto_profil FROM users WHERE id='$user_id'");
    if ($old_photo_query && mysqli_num_rows($old_photo_query) > 0) {
        $old_data = mysqli_fetch_assoc($old_photo_query);
        if ($old_data['foto_profil'] && file_exists($upload_dir . $old_data['foto_profil'])) {
            unlink($upload_dir . $old_data['foto_profil']);
        }
    }
    
    // Cek apakah GD extension tersedia
    if (extension_loaded('gd') && function_exists('gd_info')) {
        // Gunakan GD untuk resize dan compress
        $upload_success = resizeAndCompressImage($file['tmp_name'], $upload_path, 400, 400, 85);
    } else {
        // Fallback: Upload sederhana tanpa resize
        $upload_success = move_uploaded_file($file['tmp_name'], $upload_path);
        
        // Verifikasi file berhasil diupload
        if ($upload_success && (!file_exists($upload_path) || filesize($upload_path) === 0)) {
            $upload_success = false;
        }
    }
    
    if ($upload_success) {
        // Update database
        $update_query = "UPDATE users SET foto_profil='$new_filename' WHERE id='$user_id'";
        if (mysqli_query($koneksi, $update_query)) {
            $_SESSION['foto_profil'] = $new_filename; // Update session
            header("Location: dashboard-panitia.php?success=profile_updated");
            exit;
        } else {
            // Hapus file jika update database gagal
            if (file_exists($upload_path)) {
                unlink($upload_path);
            }
            header("Location: dashboard-panitia.php?error=db_update_failed");
            exit;
        }
    } else {
        header("Location: dashboard-panitia.php?error=upload_failed");
        exit;
    }
} else {
    header("Location: dashboard-panitia.php");
    exit;
}

/**
 * Fungsi untuk resize dan compress gambar (HANYA jika GD tersedia)
 */
function resizeAndCompressImage($source, $destination, $max_width, $max_height, $quality = 85) {
    // Pastikan GD tersedia
    if (!extension_loaded('gd') || !function_exists('gd_info')) {
        return false;
    }
    
    // Dapatkan info gambar
    $image_info = @getimagesize($source);
    if (!$image_info) {
        return false;
    }
    
    list($orig_width, $orig_height, $image_type) = $image_info;
    
    // Buat resource gambar berdasarkan tipe
    $image = false;
    switch ($image_type) {
        case IMAGETYPE_JPEG:
            $image = @imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $image = @imagecreatefrompng($source);
            break;
        case IMAGETYPE_GIF:
            $image = @imagecreatefromgif($source);
            break;
        case IMAGETYPE_WEBP:
            $image = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($source) : false;
            break;
        default:
            return false;
    }
    
    if (!$image) {
        return false;
    }
    
    // Hitung dimensi baru dengan mempertahankan aspect ratio
    $ratio = min($max_width / $orig_width, $max_height / $orig_height);
    
    // Jika gambar sudah lebih kecil dari maksimal, gunakan ukuran asli
    if ($ratio >= 1) {
        $new_width = $orig_width;
        $new_height = $orig_height;
    } else {
        $new_width = intval($orig_width * $ratio);
        $new_height = intval($orig_height * $ratio);
    }
    
    // Buat gambar baru dengan ukuran yang sudah dihitung
    $new_image = imagecreatetruecolor($new_width, $new_height);
    
    if (!$new_image) {
        imagedestroy($image);
        return false;
    }
    
    // Preserve transparency untuk PNG dan GIF
    if ($image_type == IMAGETYPE_PNG || $image_type == IMAGETYPE_GIF) {
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
        $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
        imagefilledrectangle($new_image, 0, 0, $new_width, $new_height, $transparent);
    }
    
    // Copy dan resize
    $resize_result = imagecopyresampled($new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $orig_width, $orig_height);
    
    if (!$resize_result) {
        imagedestroy($image);
        imagedestroy($new_image);
        return false;
    }
    
    // Simpan gambar berdasarkan tipe
    $result = false;
    switch ($image_type) {
        case IMAGETYPE_JPEG:
            $result = imagejpeg($new_image, $destination, $quality);
            break;
        case IMAGETYPE_PNG:
            $png_quality = intval(9 - ($quality / 100 * 9));
            $result = imagepng($new_image, $destination, $png_quality);
            break;
        case IMAGETYPE_GIF:
            $result = imagegif($new_image, $destination);
            break;
        case IMAGETYPE_WEBP:
            $result = function_exists('imagewebp') ? imagewebp($new_image, $destination, $quality) : false;
            break;
    }
    
    // Bersihkan memory
    imagedestroy($image);
    imagedestroy($new_image);
    
    // Verifikasi file berhasil dibuat
    if ($result && (!file_exists($destination) || filesize($destination) === 0)) {
        return false;
    }
    
    return $result;
}
?>