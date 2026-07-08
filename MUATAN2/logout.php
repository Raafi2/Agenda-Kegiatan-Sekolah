<?php
session_start();

// Simpan pesan logout sebelum menghapus session
$logout_message = "Anda telah berhasil logout.";

// Hapus semua data session
$_SESSION = array();

// Hapus session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Hancurkan session
session_destroy();

// Redirect ke halaman login dengan pesan
header("Location: index.php?message=" . urlencode($logout_message));
exit;
?>