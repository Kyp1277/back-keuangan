<?php
// ============================================
// KONFIGURASI DATABASE
// File: /config/database.php
//
// Konfigurasi database untuk Render / local development.
// Di production, isi env var di Render dashboard:
// DB_HOST, DB_PORT, DB_USER, DB_PASS, DB_NAME
// ============================================

$databaseUrl = getenv('DATABASE_URL') ?: getenv('MYSQL_URL') ?: '';
$urlConfig = [];

if ($databaseUrl) {
    $parsedUrl = parse_url($databaseUrl);
    $urlConfig = [
        'host' => $parsedUrl['host'] ?? null,
        'port' => $parsedUrl['port'] ?? null,
        'user' => isset($parsedUrl['user']) ? urldecode($parsedUrl['user']) : null,
        'pass' => isset($parsedUrl['pass']) ? urldecode($parsedUrl['pass']) : null,
        'name' => isset($parsedUrl['path']) ? ltrim($parsedUrl['path'], '/') : null,
    ];
}

define('DB_HOST', getenv('MYSQLHOST')     ?: getenv('DB_HOST') ?: ($urlConfig['host'] ?? 'localhost'));
define('DB_PORT', getenv('MYSQLPORT')     ?: getenv('DB_PORT') ?: ($urlConfig['port'] ?? '3306'));
define('DB_USER', getenv('MYSQLUSER')     ?: getenv('DB_USER') ?: ($urlConfig['user'] ?? 'root'));
define('DB_PASS', getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: ($urlConfig['pass'] ?? ''));
define('DB_NAME', getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: ($urlConfig['name'] ?? 'umkm_keuangan'));

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
