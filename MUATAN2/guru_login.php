<?php
include 'config.php';
session_start();

$nip = mysqli_real_escape_string($koneksi, $_POST['nip']);
$nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
$password = $_POST['password']; // TANPA MD5

$query = mysqli_query($koneksi, "SELECT * FROM users WHERE nip='$nip' AND nama='$nama' AND password='$password' AND role='guru'");
$cek = mysqli_num_rows($query);

if($cek > 0){
    $data = mysqli_fetch_assoc($query);

    $_SESSION['id_user'] = $data['id'];
    $_SESSION['nama'] = $data['nama'];
    $_SESSION['role'] = "guru";

    header("location: dashboard-guru.php"); 
} else {
    header("location: guru.php?pesan=gagal"); 
}
?>