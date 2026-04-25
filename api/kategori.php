<?php
// ============================================
// ENDPOINT: /api/kategori.php
// ============================================
// GET  /api/kategori.php          → ambil semua kategori
// GET  /api/kategori.php?jenis=.. → filter berdasarkan jenis
// POST /api/kategori.php          → tambah kategori baru
// ============================================

require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth.php';
require_once __DIR__ . '/../config/database.php';

setCorsHeaders();
$currentUser = validateToken();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $db          = getDB();
    $filterJenis = $_GET['jenis'] ?? '';

    if ($filterJenis && in_array($filterJenis, ['pemasukan', 'pengeluaran'])) {
        $stmt = $db->prepare("SELECT * FROM kategori WHERE jenis = ? ORDER BY nama_kategori");
        $stmt->execute([$filterJenis]);
    } else {
        $stmt = $db->query("SELECT * FROM kategori ORDER BY jenis, nama_kategori");
    }

    sendSuccess($stmt->fetchAll());

} elseif ($method === 'POST') {
    $db   = getDB();
    $body = getRequestBody();

    $namaKategori = trim($body['nama_kategori'] ?? '');
    $jenis        = trim($body['jenis']         ?? '');

    if (empty($namaKategori)) {
        sendError('Nama kategori wajib diisi.');
    }
    if (!in_array($jenis, ['pemasukan', 'pengeluaran'])) {
        sendError('Jenis harus "pemasukan" atau "pengeluaran".');
    }

    $stmt = $db->prepare("INSERT INTO kategori (nama_kategori, jenis) VALUES (?, ?)");
    $stmt->execute([$namaKategori, $jenis]);

    sendSuccess(
        ['id' => (int) $db->lastInsertId(), 'nama_kategori' => $namaKategori, 'jenis' => $jenis],
        'Kategori berhasil ditambahkan.',
        201
    );

} else {
    sendError('Method tidak diizinkan.', 405);
}
