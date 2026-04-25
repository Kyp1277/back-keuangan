<?php
// ============================================
// AUTH HELPER / TOKEN MIDDLEWARE
// File: /utils/auth.php
//
// Sistem token sederhana tanpa library JWT.
// Token dibuat dari data user + secret key,
// lalu di-hash dengan HMAC SHA-256.
// ============================================

// Ganti SECRET_KEY dengan string acak yang panjang di production!
define('SECRET_KEY', getenv('JWT_SECRET') ?: 'umkm-keuangan-secret-key-2026-ganti-ini');

/**
 * Membuat token untuk user yang berhasil login.
 *
 * Format token: base64(payload) . "." . HMAC_signature
 *
 * @param  int    $userId
 * @param  string $username
 * @return string $token
 */
function generateToken(int $userId, string $username): string {
    $payload = base64_encode(json_encode([
        'user_id'  => $userId,
        'username' => $username,
        'exp'      => time() + (60 * 60 * 24), // Token kadaluarsa 24 jam
    ]));

    $signature = hash_hmac('sha256', $payload, SECRET_KEY);

    return $payload . '.' . $signature;
}

/**
 * Memvalidasi token dari request header.
 *
 * Cara pakai di frontend:
 *   Authorization: Bearer <token>
 *
 * @return array Data user (user_id, username) jika token valid
 */
function validateToken(): array {
    // Ambil header Authorization
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

    // Cek format "Bearer <token>"
    if (!str_starts_with($authHeader, 'Bearer ')) {
        http_response_code(401);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Token tidak ditemukan. Harap login terlebih dahulu.',
        ]);
        exit;
    }

    $token = substr($authHeader, 7); // Hapus "Bearer " di depan
    $parts = explode('.', $token);

    // Token harus punya 2 bagian: payload & signature
    if (count($parts) !== 2) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Format token tidak valid.']);
        exit;
    }

    [$payload, $signature] = $parts;

    // Verifikasi signature
    $expectedSignature = hash_hmac('sha256', $payload, SECRET_KEY);
    if (!hash_equals($expectedSignature, $signature)) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Token tidak valid atau sudah dimanipulasi.']);
        exit;
    }

    // Decode payload
    $data = json_decode(base64_decode($payload), true);

    // Cek apakah token sudah kadaluarsa
    if (!isset($data['exp']) || $data['exp'] < time()) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Token sudah kadaluarsa. Silakan login ulang.']);
        exit;
    }

    return $data; // Kembalikan data user (user_id, username)
}
