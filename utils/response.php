<?php
// ============================================
// CORS + RESPONSE HELPER
// File: /utils/response.php
//
// Fungsi-fungsi pendukung untuk semua endpoint API
// ============================================

/**
 * Set semua header CORS yang diperlukan.
 * Wajib dipanggil di AWAL setiap file API
 * agar frontend (Vercel) bisa mengakses backend (Railway).
 */
function setCorsHeaders(): void {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Content-Type: application/json; charset=UTF-8");

    // Browser mengirim request OPTIONS sebelum request asli (preflight).
    // Kita langsung balas 200 OK dan hentikan eksekusi.
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

/**
 * Kirim response sukses dalam format standar.
 *
 * @param mixed  $data    Data yang dikembalikan (array, string, null)
 * @param string $message Pesan sukses
 * @param int    $code    HTTP status code (default 200)
 */
function sendSuccess(mixed $data = null, string $message = 'Berhasil', int $code = 200): void {
    http_response_code($code);
    echo json_encode([
        'status'  => 'success',
        'message' => $message,
        'data'    => $data,
    ]);
    exit;
}

/**
 * Kirim response error dalam format standar.
 *
 * @param string $message Pesan error
 * @param int    $code    HTTP status code (default 400)
 */
function sendError(string $message, int $code = 400): void {
    http_response_code($code);
    echo json_encode([
        'status'  => 'error',
        'message' => $message,
    ]);
    exit;
}

/**
 * Ambil body JSON dari request (untuk POST/PUT).
 * Mengembalikan array kosong jika body tidak ada / bukan JSON.
 */
function getRequestBody(): array {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? [];
}
