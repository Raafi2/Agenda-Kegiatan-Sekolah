<?php
include 'config.php';
session_start();

$nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
$password = $_POST['password']; // TANPA MD5

$query = mysqli_query($koneksi, "SELECT * FROM users WHERE nama='$nama' AND password='$password' AND role='panitia'");
$cek = mysqli_num_rows($query);

if($cek > 0){
    $data = mysqli_fetch_assoc($query);
    $_SESSION['id_user'] = $data['id'];
    $_SESSION['nama'] = $data['nama'];
    $_SESSION['role'] = "panitia";
    header("location: dashboard-panitia.php"); 
} else {
    header("location: panitia.php?pesan=gagal"); 
}
?>