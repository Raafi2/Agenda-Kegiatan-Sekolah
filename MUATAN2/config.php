<?php 
// config.php


$server = "localhost";
$user = "root";
$password = "";
$database = "db_agenda_sekolah";

$koneksi = mysqli_connect($server, $user, $password, $database);


if (mysqli_connect_error()){
	
	die("Koneksi database gagal : " . mysqli_connect_error()); 
}
?>