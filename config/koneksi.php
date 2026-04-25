<?php
// ============================================
// KONFIGURASI DATABASE
// ============================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'umkm_keuangan');
define('APP_NAME', 'KeuanganKu UMKM');

// Koneksi PDO
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die(json_encode(['error' => 'Koneksi database gagal: ' . $e->getMessage()]));
}

// Helper: Format Rupiah
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Helper: Flash message
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Helper: Redirect
function redirect($url) {
    header("Location: $url");
    exit;
}

// Helper: Cek login
function cekLogin() {
    if (!isset($_SESSION['user_id'])) {
        redirect('/umkm-keuangan/auth/login.php');
    }
}
