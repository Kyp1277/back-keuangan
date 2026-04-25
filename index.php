<?php
// ============================================
// Entry point utama
// Redirect ke dokumentasi API
// ============================================

header("Content-Type: application/json");

echo json_encode([
    'status'  => 'success',
    'message' => 'UMKM Keuangan REST API berjalan!',
    'version' => '2.0.0',
    'endpoints' => [
        'POST /api/login.php'         => 'Login user',
        'POST /api/register.php'      => 'Registrasi user baru',
        'GET  /api/transaksi.php'     => 'Ambil semua transaksi (perlu token)',
        'POST /api/transaksi.php'     => 'Tambah transaksi (perlu token)',
        'PUT  /api/transaksi.php'     => 'Update transaksi (perlu token)',
        'DELETE /api/transaksi.php'   => 'Hapus transaksi (perlu token)',
        'GET  /api/kategori.php'      => 'Ambil semua kategori (perlu token)',
        'POST /api/kategori.php'      => 'Tambah kategori (perlu token)',
        'GET  /api/laporan.php'       => 'Ringkasan laporan (perlu token)',
    ],
]);
