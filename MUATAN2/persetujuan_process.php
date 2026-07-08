<?php
session_start();
include 'config.php';



if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 2. Validasi Input (tetap sama)
    if (!isset($_POST['id_kegiatan']) || !isset($_POST['status_aksi'])) {
        error_log("Missing required fields");
        header("location: daftar-ajuan.php?error=missing_fields");
        exit;
    }

    // 3. Ambil Data dari Form
    // Cukup simpan nilai POST, Prepared Statement akan menangani escaping
    $id_kegiatan = $_POST['id_kegiatan'];
    $status_aksi = $_POST['status_aksi'];
    $nama_guru = $_SESSION['nama'] ?? 'Guru Tidak Dikenal';

    error_log("Processing: ID=$id_kegiatan, Status=$status_aksi, Guru=$nama_guru");

    // Persiapan variabel umum
    $log_aksi = "Diproses oleh $nama_guru pada " . date('Y-m-d H:i:s');
    $query = "UPDATE kegiatan SET status_persetujuan = ?, log_persetujuan = ?, alasan_penolakan = ? WHERE id_kegiatan = ?";
    
    // Inisialisasi alasan penolakan default (NULL)
    $alasan_penolakan = null; // Akan diubah jika statusnya rejected

    // 4. Proses Berdasarkan Status
    if ($status_aksi == 'approved') {
        // APPROVE
        $status_final = 'approved';

        $alasan_penolakan = null;

    } elseif ($status_aksi == 'rejected') {
        // REJECT
        if (!isset($_POST['alasan_penolakan']) || empty($_POST['alasan_penolakan'])) {
            header("location: persetujuan.php?id=$id_kegiatan&error=reason_required");
            exit;
        }

        $alasan_penolakan = $_POST['alasan_penolakan'];
        $status_final = 'rejected';
        
    } else {
        // Status tidak valid
        error_log("Invalid status: $status_aksi");
        header("location: daftar-ajuan.php?error=invalid_status");
        exit;
    }

    
    if ($stmt = mysqli_prepare($koneksi, $query)) {
        
        // Cek apakah alasan_penolakan adalah NULL (untuk Approved)
        if ($alasan_penolakan === null) {
            // Bind parameter: sssi (status, log, alasan (dianggap string 'NULL'), id)
            // Catatan: Jika kolom alasan_penolakan di database Anda benar-benar NULL, 
            // Anda harus menggunakan teknik binding yang lebih kompleks (atau default ke string kosong)
            // Untuk kesederhanaan, kita bind sebagai string kosong ""
            $empty_alasan = "";
            mysqli_stmt_bind_param($stmt, "ssss", 
                $status_final, 
                $log_aksi, 
                $empty_alasan, // Menggunakan string kosong jika Approved
                $id_kegiatan
            );
        } else {
            // Bind parameter: ssss (status, log, alasan, id)
            mysqli_stmt_bind_param($stmt, "ssss", 
                $status_final, 
                $log_aksi, 
                $alasan_penolakan, // Alasan penolakan di-bind dengan aman
                $id_kegiatan
            );
        }

        if (mysqli_stmt_execute($stmt)) {
            error_log("Update successful");
            header("location: daftar-ajuan.php?success=status_updated&action=$status_aksi");
        } else {
            $error_msg = mysqli_error($koneksi);
            error_log("Update failed: $error_msg");
            header("location: persetujuan.php?id=$id_kegiatan&error=update_failed");
        }
        
        mysqli_stmt_close($stmt);

    } else {
        $error_msg = mysqli_error($koneksi);
        error_log("Query preparation failed: $error_msg");
        header("location: persetujuan.php?id=$id_kegiatan&error=update_failed");
    }

} else {
    // Jika diakses tanpa metode POST
    error_log("Accessed without POST method");
    header("location: daftar-ajuan.php");
    exit;
}
?>