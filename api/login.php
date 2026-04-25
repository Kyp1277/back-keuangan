<?php
// ============================================
// ENDPOINT: POST /api/login.php
// ============================================
// Menerima: { "username", "password" }
// Mengembalikan: token + data user jika berhasil
// ============================================

require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth.php';
require_once __DIR__ . '/../config/database.php';

// 1. Set header CORS (wajib di awal)
setCorsHeaders();

// 2. Hanya terima method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method tidak diizinkan. Gunakan POST.', 405);
}

// 3. Ambil data dari body JSON
$body     = getRequestBody();
$username = trim($body['username'] ?? '');
$password = trim($body['password'] ?? '');

// 4. Validasi input
if (empty($username) || empty($password)) {
    sendError('Username dan password wajib diisi.');
}

// 5. Cari user di database
$db   = getDB();
$stmt = $db->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
$stmt->execute([$username]);
$user = $stmt->fetch();

// 6. Verifikasi password
// password_verify() aman terhadap timing attack
if (!$user || !password_verify($password, $user['password'])) {
    sendError('Username atau password salah.', 401);
}

// 7. Buat token autentikasi
$token = generateToken((int) $user['id'], $user['username']);

// 8. Kirim response sukses (jangan kirim password ke frontend!)
sendSuccess(
    data: [
        'token' => $token,
        'user'  => [
            'id'           => (int) $user['id'],
            'username'     => $user['username'],
            'nama_lengkap' => $user['nama_lengkap'],
        ],
    ],
    message: 'Login berhasil! Selamat datang, ' . $user['nama_lengkap'] . '.'
);
