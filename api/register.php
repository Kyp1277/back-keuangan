<?php
// ============================================
// ENDPOINT: POST /api/register.php
// ============================================
// Menerima: { "username", "password", "nama_lengkap" }
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
$body = getRequestBody();
$username    = trim($body['username']    ?? '');
$password    = trim($body['password']    ?? '');
$namaLengkap = trim($body['nama_lengkap'] ?? '');

// 4. Validasi input wajib
if (empty($username) || empty($password) || empty($namaLengkap)) {
    sendError('Username, password, dan nama lengkap wajib diisi.');
}

if (strlen($password) < 6) {
    sendError('Password minimal 6 karakter.');
}

if (strlen($username) < 3 || strlen($username) > 50) {
    sendError('Username harus antara 3-50 karakter.');
}

// 5. Cek apakah username sudah dipakai
$db   = getDB();
$stmt = $db->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
$stmt->execute([$username]);

if ($stmt->fetch()) {
    sendError('Username sudah digunakan. Pilih username lain.', 409);
}

// 6. Hash password (jangan simpan plain text!)
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

// 7. Simpan user baru ke database
$stmt = $db->prepare(
    "INSERT INTO users (username, password, nama_lengkap) VALUES (?, ?, ?)"
);
$stmt->execute([$username, $hashedPassword, $namaLengkap]);
$userId = (int) $db->lastInsertId();

// 8. Buat token untuk langsung login setelah register
$token = generateToken($userId, $username);

// 9. Kirim response sukses
sendSuccess(
    data: [
        'token' => $token,
        'user'  => [
            'id'          => $userId,
            'username'    => $username,
            'nama_lengkap'=> $namaLengkap,
        ],
    ],
    message: 'Registrasi berhasil! Selamat datang, ' . $namaLengkap . '.',
    code: 201
);
