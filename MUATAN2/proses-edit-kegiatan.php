<?php
session_start();
include 'config.php';

// Cek otorisasi
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'panitia'){
    header("location: panitia.php");
    exit;
}

$nama_panitia = $_SESSION['nama'];

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $id_kegiatan = mysqli_real_escape_string($koneksi, $_POST['id_kegiatan']);
    $nama_kegiatan = mysqli_real_escape_string($koneksi, $_POST['nama_kegiatan']);
    $jadwal = mysqli_real_escape_string($koneksi, $_POST['jadwal']);
    $lokasi = mysqli_real_escape_string($koneksi, $_POST['lokasi']);
    $tujuan = mysqli_real_escape_string($koneksi, $_POST['tujuan']);
    $gambar_lama = mysqli_real_escape_string($koneksi, $_POST['gambar_lama']);
    
    // Cek apakah kegiatan milik panitia yang login
    $check_query = mysqli_query($koneksi, "SELECT status_persetujuan FROM kegiatan WHERE id_kegiatan='$id_kegiatan' AND diajukan_oleh='$nama_panitia'");
    
    if(mysqli_num_rows($check_query) == 0){
        header("location: status-persetujuan.php");
        exit;
    }
    
    $kegiatan_data = mysqli_fetch_assoc($check_query);
    $status_lama = $kegiatan_data['status_persetujuan'];
    
    // Handle upload gambar
    $gambar_final = $gambar_lama;
    
    if(isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0){
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        $file_name = $_FILES['gambar']['name'];
        $file_size = $_FILES['gambar']['size'];
        $file_tmp = $_FILES['gambar']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if(in_array($file_ext, $allowed_ext) && $file_size <= 5000000){
            $new_file_name = uniqid() . '_' . time() . '.' . $file_ext;
            $upload_path = 'uploads/' . $new_file_name;
            
            if(move_uploaded_file($file_tmp, $upload_path)){
                // Hapus gambar lama jika ada
                if(!empty($gambar_lama) && file_exists('uploads/' . $gambar_lama)){
                    unlink('uploads/' . $gambar_lama);
                }
                $gambar_final = $new_file_name;
            }
        }
    }
    
    // Jika kegiatan sudah disetujui, ubah status menjadi pending (butuh persetujuan ulang)
    $status_baru = ($status_lama == 'approved') ? 'pending' : $status_lama;
    
    // Update kegiatan
    $update_query = mysqli_query($koneksi, 
        "UPDATE kegiatan SET 
        nama_kegiatan='$nama_kegiatan',
        jadwal='$jadwal',
        lokasi='$lokasi',
        tujuan='$tujuan',
        gambar='$gambar_final',
        status_persetujuan='$status_baru'
        WHERE id_kegiatan='$id_kegiatan' AND diajukan_oleh='$nama_panitia'"
    );
    
    if($update_query){
        header("location: panitia-detail-kegiatan.php?id=$id_kegiatan&success=edited");
    } else {
        header("location: edit-kegiatan-panitia.php?id=$id_kegiatan&error=failed");
    }
} else {
    header("location: status-persetujuan.php");
}
?>