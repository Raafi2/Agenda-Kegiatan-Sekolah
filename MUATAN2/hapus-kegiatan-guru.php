<?php
session_start();
include 'config.php';

// Cek autentikasi dan otorisasi
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'guru'){
    header("location: guru.php");
    exit;
}

$id_kegiatan = isset($_GET['id']) ? mysqli_real_escape_string($koneksi, $_GET['id']) : '';

if(empty($id_kegiatan)){
    header("location: daftar-ajuan.php");
    exit;
}

// Ambil data kegiatan untuk cek status dan gambar
$query = mysqli_query($koneksi, "SELECT * FROM kegiatan WHERE id_kegiatan='$id_kegiatan'");
$kegiatan = mysqli_fetch_assoc($query);

if(!$kegiatan){
    header("location: daftar-ajuan.php?error=not_found");
    exit;
}

// PERBAIKAN: Boleh hapus jika status pending ATAU rejected
if($kegiatan['status_persetujuan'] == 'approved'){
    header("location: daftar-ajuan.php?error=cannot_delete_approved");
    exit;
}

// Hapus gambar jika ada
if(!empty($kegiatan['gambar']) && file_exists('uploads/' . $kegiatan['gambar'])){
    unlink('uploads/' . $kegiatan['gambar']);
}

// Hapus data peserta kegiatan jika ada
mysqli_query($koneksi, "DELETE FROM peserta_kegiatan WHERE id_kegiatan='$id_kegiatan'");

// Hapus kegiatan
$delete_query = mysqli_query($koneksi, "DELETE FROM kegiatan WHERE id_kegiatan='$id_kegiatan'");

if($delete_query){
    header("location: daftar-ajuan.php?success=deleted");
} else {
    header("location: daftar-ajuan.php?error=delete_failed");
}
?>