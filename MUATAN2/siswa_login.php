<?php
include 'config.php';
session_start();

$input_nis = mysqli_real_escape_string($koneksi, trim($_POST['nis']));
$input_nama = mysqli_real_escape_string($koneksi, strtolower(trim($_POST['nama'])));
$password = $_POST['password']; // TANPA MD5

$query = mysqli_query($koneksi, "SELECT * FROM users WHERE LOWER(nama)='$input_nama' AND nip='$input_nis' AND password='$password' AND role='siswa'");
$cek = mysqli_num_rows($query);

if($cek > 0){
    $data = mysqli_fetch_assoc($query);
    
    $_SESSION['id_user'] = $data['id']; 
    $_SESSION['nama'] = $data['nama'];
    $_SESSION['nis'] = $data['nip'];
    $_SESSION['kelas_lengkap'] = $data['kelas']; 
    $_SESSION['role'] = "siswa";

    header("location: siswa-kegiatan.php"); 
} else {
    header("location: siswa.php?pesan=gagal"); 
}
?>