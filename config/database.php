<?php
// ============================================
// KONFIGURASI DATABASE
// File: /config/database.php
//
// Mendukung environment variable Railway
// Jika tidak ada env var, pakai nilai default (lokal)
// ============================================

define('DB_HOST', getenv('MYSQLHOST')     ?: getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('MYSQLPORT')     ?: getenv('DB_PORT') ?: '3306');
define('DB_USER', getenv('MYSQLUSER')     ?: getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'umkm_keuangan');

/**
 * Fungsi untuk mendapatkan koneksi PDO ke database.
 * Menggunakan pola "singleton" agar koneksi hanya dibuat sekali.
 */
function getDB(): PDO {
    static $pdo = null; // $pdo disimpan selama satu request

    if ($pdo === null) {
        $dsn = sprintf(
            "mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
            DB_HOST, DB_PORT, DB_NAME
        );

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            // Kirim error sebagai JSON, lalu hentikan eksekusi
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Koneksi database gagal: ' . $e->getMessage(),
            ]);
            exit;
        }
    }

    return $pdo;
}
