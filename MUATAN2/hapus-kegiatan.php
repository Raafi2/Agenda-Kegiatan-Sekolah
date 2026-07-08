<?php
session_start();
include 'config.php';

// Cek otorisasi
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'panitia'){
    header("location: panitia.php");
    exit;
}

$nama_panitia = $_SESSION['nama'];
$id_kegiatan = isset($_GET['id']) ? mysqli_real_escape_string($koneksi, $_GET['id']) : '';

if(empty($id_kegiatan)){
    header("location: status-persetujuan.php");
    exit;
}

// Ambil data kegiatan untuk cek status dan gambar
$query = mysqli_query($koneksi, "SELECT * FROM kegiatan WHERE id_kegiatan='$id_kegiatan' AND diajukan_oleh='$nama_panitia'");
$kegiatan = mysqli_fetch_assoc($query);

if(!$kegiatan){
    header("location: status-persetujuan.php");
    exit;
}

// PERBAIKAN: Boleh hapus jika status BUKAN approved (bisa pending atau rejected)
if($kegiatan['status_persetujuan'] == 'approved'){
    header("location: panitia-detail-kegiatan.php?id=$id_kegiatan&error=approved");
    exit;
}

// Hapus gambar jika ada
if(!empty($kegiatan['gambar']) && file_exists('uploads/' . $kegiatan['gambar'])){
    unlink('uploads/' . $kegiatan['gambar']);
}

// Hapus data peserta kegiatan jika ada
mysqli_query($koneksi, "DELETE FROM peserta_kegiatan WHERE id_kegiatan='$id_kegiatan'");

// Hapus kegiatan
$delete_query = mysqli_query($koneksi, "DELETE FROM kegiatan WHERE id_kegiatan='$id_kegiatan' AND diajukan_oleh='$nama_panitia'");

if($delete_query){
    header("location: status-persetujuan.php?success=deleted");
} else {
    header("location: panitia-detail-kegiatan.php?id=$id_kegiatan&error=delete_failed");
}
?>