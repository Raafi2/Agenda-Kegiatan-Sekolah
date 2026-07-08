<?php
session_start();
include 'config.php';

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'siswa' || !isset($_SESSION['id_user'])) {
    header("location: siswa.php");
    exit;
}

$id_siswa = intval($_SESSION['id_user']); 
$id_kegiatan = isset($_GET['id']) ? mysqli_real_escape_string($koneksi, $_GET['id']) : null;

if (!$id_kegiatan) {
    echo "<script>alert('ID Kegiatan tidak valid!'); window.location='siswa-kegiatan.php';</script>";
    exit;
}

$keg_q = mysqli_query($koneksi, "SELECT tipe_kegiatan, maks_peserta FROM kegiatan WHERE id_kegiatan='$id_kegiatan' AND status_persetujuan='approved' AND tipe_kegiatan='opsional'");
$keg = mysqli_fetch_assoc($keg_q);

if (!$keg) {
    echo "<script>alert('Kegiatan tidak ditemukan, belum disetujui, atau bukan opsional.'); window.location='siswa-kegiatan.php';</script>";
    exit;
}

$maks_peserta = $keg['maks_peserta'];

$cek_daftar_q = mysqli_query($koneksi, "SELECT * FROM peserta_kegiatan WHERE id_kegiatan='$id_kegiatan' AND id_siswa='$id_siswa'");
if (mysqli_num_rows($cek_daftar_q) > 0) {
    echo "<script>alert('Anda sudah terdaftar di kegiatan ini!'); window.location='siswa-kegiatan.php';</script>";
    exit;
}

if ($maks_peserta !== NULL && $maks_peserta > 0) {
    $count_q = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM peserta_kegiatan WHERE id_kegiatan='$id_kegiatan'");
    $current_participants = mysqli_fetch_assoc($count_q)['total'];

    if ($current_participants >= $maks_peserta) {
        echo "<script>alert('Maaf, kuota peserta kegiatan sudah penuh!'); window.location='siswa-kegiatan.php';</script>";
        exit;
    }
}

$insert_q = "INSERT INTO peserta_kegiatan (id_kegiatan, id_siswa) VALUES ('$id_kegiatan', '$id_siswa')";

if (mysqli_query($koneksi, $insert_q)) {
    echo "<script>alert('Pendaftaran kegiatan berhasil! Selamat mengikuti.'); window.location='siswa-kegiatan-diikuti.php';</script>";
} else {
    echo "<script>alert('Gagal mendaftar. Silakan coba lagi.'); window.location='siswa-kegiatan.php';</script>";
}
?>