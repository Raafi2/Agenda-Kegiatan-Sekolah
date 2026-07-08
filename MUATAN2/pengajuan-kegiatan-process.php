<?php
session_start();
include 'config.php';

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'panitia'){
    header("location: panitia.php");
    exit;
}


function resizeAndCompressImage($source, $destination, $max_width, $max_height, $quality = 85) {
    // Cek GD extension
    if (!extension_loaded('gd') || !function_exists('gd_info')) {
        error_log("GD extension not loaded");
        return false;
    }
    
    // Validasi file source
    if (!file_exists($source) || !is_readable($source)) {
        error_log("Source file doesn't exist or not readable: " . $source);
        return false;
    }
    
    // Cek ukuran file
    if (filesize($source) === 0) {
        error_log("Source file is empty");
        return false;
    }
    
    // Dapatkan info gambar dengan error handling
    $image_info = @getimagesize($source);
    if (!$image_info) {
        error_log("Cannot get image info for: " . $source);
        return false;
    }
    
    list($orig_width, $orig_height, $image_type) = $image_info;
    
    // Validasi tipe gambar yang didukung
    $supported_types = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF];
    if (defined('IMAGETYPE_WEBP')) {
        $supported_types[] = IMAGETYPE_WEBP;
    }
    
    if (!in_array($image_type, $supported_types)) {
        error_log("Unsupported image type: " . $image_type);
        return false;
    }
    
    // Buat resource gambar
    $image = false;
    switch ($image_type) {
        case IMAGETYPE_JPEG:
            $image = @imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $image = @imagecreatefrompng($source);
            // Preserve transparency untuk PNG
            if ($image) {
                imagealphablending($image, false);
                imagesavealpha($image, true);
            }
            break;
        case IMAGETYPE_GIF:
            $image = @imagecreatefromgif($source);
            break;
        case IMAGETYPE_WEBP:
            $image = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($source) : false;
            break;
    }
    
    if (!$image) {
        error_log("Failed to create image resource from source");
        return false;
    }
    
    // Hitung dimensi baru
    if ($orig_width > $max_width || $orig_height > $max_height) {
        $ratio = min($max_width / $orig_width, $max_height / $orig_height);
        $new_width = intval($orig_width * $ratio);
        $new_height = intval($orig_height * $ratio);
    } else {
        // Jika gambar lebih kecil dari maksimal, gunakan ukuran asli
        $new_width = $orig_width;
        $new_height = $orig_height;
    }
    
    // Buat canvas baru
    $new_image = imagecreatetruecolor($new_width, $new_height);
    
    if (!$new_image) {
        error_log("Failed to create new image canvas");
        imagedestroy($image);
        return false;
    }
    
    // Handle transparency untuk PNG dan GIF
    if ($image_type == IMAGETYPE_PNG || $image_type == IMAGETYPE_GIF) {
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
        $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
        imagefill($new_image, 0, 0, $transparent);
    }
    
    // Resize image
    $resize_result = imagecopyresampled(
        $new_image, $image, 
        0, 0, 0, 0, 
        $new_width, $new_height, 
        $orig_width, $orig_height
    );
    
    if (!$resize_result) {
        error_log("Failed to resize image");
        imagedestroy($image);
        imagedestroy($new_image);
        return false;
    }
    
    // Tentukan ekstensi output
    $path_info = pathinfo($destination);
    $dir = $path_info['dirname'];
    $filename = $path_info['filename'];
    
    // Coba simpan sebagai WebP dulu jika didukung (ukuran lebih kecil)
    if (function_exists('imagewebp')) {
        $webp_path = $dir . '/' . $filename . '.webp';
        $save_result = imagewebp($new_image, $webp_path, $quality);
        $final_filename = $filename . '.webp';
    } 
    // Fallback ke format asli
    else {
        switch ($image_type) {
            case IMAGETYPE_JPEG:
                $jpeg_path = $dir . '/' . $filename . '.jpg';
                $save_result = imagejpeg($new_image, $jpeg_path, $quality);
                $final_filename = $filename . '.jpg';
                break;
            case IMAGETYPE_PNG:
                $png_path = $dir . '/' . $filename . '.png';
                $png_quality = 9 - round(($quality / 100) * 9);
                $save_result = imagepng($new_image, $png_path, $png_quality);
                $final_filename = $filename . '.png';
                break;
            case IMAGETYPE_GIF:
                $gif_path = $dir . '/' . $filename . '.gif';
                $save_result = imagegif($new_image, $gif_path);
                $final_filename = $filename . '.gif';
                break;
            default:
                $save_result = false;
        }
    }
    
    // Bersihkan memory
    imagedestroy($image);
    imagedestroy($new_image);
    
    if (!$save_result) {
        error_log("Failed to save processed image");
        return false;
    }
    
    // Verifikasi file berhasil dibuat
    $final_path = $dir . '/' . $final_filename;
    if (!file_exists($final_path) || filesize($final_path) === 0) {
        error_log("Processed image file not created or is empty");
        return false;
    }
    
    return $final_filename;
}

function simpleImageUpload($file, $upload_dir) {
    // Generate nama file unik dengan ekstensi asli
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($file_extension, $allowed_extensions)) {
        error_log("Invalid file extension: " . $file_extension);
        return false;
    }
    
    $new_filename = 'kegiatan_' . time() . '_' . uniqid() . '.' . $file_extension;
    $destination = $upload_dir . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        // Verifikasi file berhasil diupload
        if (file_exists($destination) && filesize($destination) > 0) {
            return $new_filename;
        } else {
            error_log("Uploaded file is empty or doesn't exist");
            return false;
        }
    }
    
    error_log("Move uploaded file failed");
    return false;
}



if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_panitia = $_SESSION['nama'];
    
    // Ambil dan bersihkan data
    $kegiatan = trim($_POST['kegiatan'] ?? '');
    $jadwal = $_POST['jadwal'] ?? '';
    $lokasi = trim($_POST['lokasi'] ?? '');
    $tujuan = trim($_POST['tujuan'] ?? '');
    $tipe_kegiatan = $_POST['tipe_kegiatan'] ?? '';
    $maks_peserta_input = $_POST['maks_peserta'] ?? '';
    
    // Konversi maks_peserta
    $maks_peserta = null;
    if (!empty($maks_peserta_input) && is_numeric($maks_peserta_input) && $maks_peserta_input > 0) {
        $maks_peserta = intval($maks_peserta_input);
    }
    

    if (empty($kegiatan) || empty($jadwal) || empty($lokasi) || empty($tujuan) || empty($tipe_kegiatan)) {
        header("Location: pengajuan-kegiatan.php?error=data_required");
        exit;
    }


    $upload_dir = 'uploads/';
    $new_filename = null;

    // Validasi gambar WAJIB
    if (!isset($_FILES['gambar_kegiatan']) || $_FILES['gambar_kegiatan']['error'] == UPLOAD_ERR_NO_FILE) {
        header("Location: pengajuan-kegiatan.php?error=image_required");
        exit;
    }
    
    $file = $_FILES['gambar_kegiatan'];
    
    // Debug information
    error_log("=== IMAGE UPLOAD PROCESS START ===");
    error_log("File: " . $file['name']);
    error_log("Size: " . $file['size']);
    error_log("Type: " . $file['type']);
    error_log("Error: " . $file['error']);
    error_log("Temp: " . $file['tmp_name']);
    
    // Validasi error upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        error_log("Upload error: " . $file['error']);
        header("Location: pengajuan-kegiatan.php?error=image_processing_failed");
        exit;
    }
    
    $allowed_types = ['image/jpeg', 'image/pjpeg', 'image/png', 'image/gif', 'image/webp'];
    
    // Validasi tipe file
    if (!in_array($file['type'], $allowed_types)) {
        error_log("Invalid file type: " . $file['type']);
        header("Location: pengajuan-kegiatan.php?error=invalid_image_type");
        exit;
    }
    
    // Validasi ukuran file (max 10MB)
    if ($file['size'] > 10485760) {
        error_log("File too large: " . $file['size']);
        header("Location: pengajuan-kegiatan.php?error=image_too_large");
        exit;
    }
    
    // Validasi file sementara
    if (!file_exists($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        error_log("Temporary file not valid");
        header("Location: pengajuan-kegiatan.php?error=image_processing_failed");
        exit;
    }
    
    // Buat direktori uploads jika belum ada
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            error_log("Failed to create upload directory");
            header("Location: pengajuan-kegiatan.php?error=image_processing_failed");
            exit;
        }
    }
    
    // Cek permission direktori
    if (!is_writable($upload_dir)) {
        error_log("Upload directory not writable");
        header("Location: pengajuan-kegiatan.php?error=image_processing_failed");
        exit;
    }
    
    // Generate nama file unik
    $temp_filename = 'kegiatan_' . time() . '_' . uniqid();
    $temp_path = $upload_dir . $temp_filename;

    // Coba proses gambar dengan GD terlebih dahulu
    error_log("Attempting advanced image processing...");
    $new_filename = resizeAndCompressImage($file['tmp_name'], $temp_path, 1200, 800, 80);
    
    // Jika GD processing gagal, coba fallback ke upload sederhana
    if (!$new_filename) {
        error_log("Advanced processing failed, trying simple upload...");
        $new_filename = simpleImageUpload($file, $upload_dir);
        
        if (!$new_filename) {
            error_log("All image processing methods failed");
            header("Location: pengajuan-kegiatan.php?error=image_processing_failed");
            exit;
        }
    }
    
    error_log("Image processing successful: " . $new_filename);


    $execute_result = false;
    $stmt = null;

    try {
        $id_kegiatan = 'KEG-' . date('YmdHis') . '-' . substr(uniqid(), -6);
        
        // Double check untuk memastikan ID unik - FIXED
        $check_query = "SELECT COUNT(*) as count FROM kegiatan WHERE id_kegiatan = ?";
        $check_stmt = mysqli_prepare($koneksi, $check_query);
        mysqli_stmt_bind_param($check_stmt, "s", $id_kegiatan);
        mysqli_stmt_execute($check_stmt);
        
        
        $result = mysqli_stmt_get_result($check_stmt);
        $row = mysqli_fetch_assoc($result);
        
        
        if ($row['count'] > 0) {
            $id_kegiatan = 'KEG-' . date('YmdHis') . '-' . rand(1000, 9999);
        }
        mysqli_stmt_close($check_stmt);

        // Siapkan query berdasarkan apakah maks_peserta ada atau tidak
        if ($maks_peserta === null) {
            // Query TANPA maks_peserta - sekarang dengan id_kegiatan
            $insert_query = "INSERT INTO kegiatan (
                                id_kegiatan, nama_kegiatan, jadwal, lokasi, tujuan, tipe_kegiatan, 
                                gambar, diajukan_oleh, status_persetujuan, tanggal_pengajuan
                            ) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
            
            $stmt = mysqli_prepare($koneksi, $insert_query);
            if ($stmt) {
                // Binding parameter: 8 string parameters (termasuk id_kegiatan)
                mysqli_stmt_bind_param($stmt, "ssssssss", 
                    $id_kegiatan,    // s - id_kegiatan (PRIMARY KEY)
                    $kegiatan,       // s - nama_kegiatan
                    $jadwal,         // s - jadwal
                    $lokasi,         // s - lokasi
                    $tujuan,         // s - tujuan
                    $tipe_kegiatan,  // s - tipe_kegiatan
                    $new_filename,   // s - gambar
                    $nama_panitia    // s - diajukan_oleh
                );
                $execute_result = mysqli_stmt_execute($stmt);
            }
        } else {
            // Query DENGAN maks_peserta - sekarang dengan id_kegiatan
            $insert_query = "INSERT INTO kegiatan (
                                id_kegiatan, nama_kegiatan, jadwal, lokasi, tujuan, tipe_kegiatan, 
                                maks_peserta, gambar, diajukan_oleh, status_persetujuan, tanggal_pengajuan
                            ) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
            
            $stmt = mysqli_prepare($koneksi, $insert_query);
            if ($stmt) {
                // Binding parameter: 9 parameters total (8 string + 1 integer)
                mysqli_stmt_bind_param($stmt, "ssssssiss", 
                    $id_kegiatan,    // s - id_kegiatan (PRIMARY KEY)
                    $kegiatan,       // s - nama_kegiatan
                    $jadwal,         // s - jadwal
                    $lokasi,         // s - lokasi
                    $tujuan,         // s - tujuan
                    $tipe_kegiatan,  // s - tipe_kegiatan
                    $maks_peserta,   // i - maks_peserta (integer)
                    $new_filename,   // s - gambar
                    $nama_panitia    // s - diajukan_oleh
                );
                $execute_result = mysqli_stmt_execute($stmt);
            }
        }

        // Handle hasil execute
        if ($stmt && $execute_result) {
            error_log("Database insert successful. ID Kegiatan: " . $id_kegiatan);
            mysqli_stmt_close($stmt);
            header("Location: status-persetujuan.php?success=kegiatan_submitted");
            exit;
        } else {
            throw new Exception($stmt ? mysqli_stmt_error($stmt) : mysqli_error($koneksi));
        }

    } catch (mysqli_sql_exception $e) {
        // Tangani error database khusus
        error_log("MySQL Error: " . $e->getMessage());
        
        if ($stmt) {
            mysqli_stmt_close($stmt);
        }
        
        // Hapus file gambar yang sudah terupload
        $file_to_unlink = $upload_dir . $new_filename;
        if ($new_filename && file_exists($file_to_unlink)) {
            unlink($file_to_unlink);
        }
        
        // Redirect dengan pesan error yang spesifik
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            header("Location: pengajuan-kegiatan.php?error=duplicate_entry");
        } else {
            header("Location: pengajuan-kegiatan.php?error=db_insert_failed");
        }
        exit;
        
    } catch (Exception $e) {
        // Tangani error umum
        error_log("General Error: " . $e->getMessage());
        
        if ($stmt) {
            mysqli_stmt_close($stmt);
        }
        
        // Hapus file gambar yang sudah diupload
        $file_to_unlink = $upload_dir . $new_filename;
        if ($new_filename && file_exists($file_to_unlink)) {
            unlink($file_to_unlink);
        }
        
        header("Location: pengajuan-kegiatan.php?error=system_error");
        exit;
    }
    
} else {
    // Jika diakses tidak melalui metode POST
    header("Location: pengajuan-kegiatan.php");
    exit;
}
?>