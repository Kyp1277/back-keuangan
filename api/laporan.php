<?php
// ============================================
// ENDPOINT: GET /api/laporan.php
// ============================================
// Mengembalikan ringkasan keuangan untuk dashboard:
//   - Ringkasan bulanan (total masuk, keluar, saldo)
//   - 5 transaksi terbaru
//   - Pengeluaran per kategori (untuk pie chart)
// ============================================

require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth.php';
require_once __DIR__ . '/../config/database.php';

setCorsHeaders();
$currentUser = validateToken();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Method tidak diizinkan. Gunakan GET.', 405);
}

$db    = getDB();
$bulan = $_GET['bulan'] ?? date('Y-m'); // Default: bulan ini

// --- 1. Ringkasan bulan ini ---
$stmtRing = $db->prepare("
    SELECT
        COALESCE(SUM(CASE WHEN jenis = 'pemasukan'   THEN jumlah ELSE 0 END), 0) AS total_masuk,
        COALESCE(SUM(CASE WHEN jenis = 'pengeluaran' THEN jumlah ELSE 0 END), 0) AS total_keluar,
        COUNT(*) AS jumlah_transaksi
    FROM transaksi
    WHERE DATE_FORMAT(tanggal, '%Y-%m') = ?
");
$stmtRing->execute([$bulan]);
$ringkasan = $stmtRing->fetch();

// --- 2. 5 transaksi terbaru ---
$stmtTerbaru = $db->query("
    SELECT t.id, t.tanggal, t.jenis, t.jumlah, t.keterangan, k.nama_kategori
    FROM transaksi t
    LEFT JOIN kategori k ON t.kategori_id = k.id
    ORDER BY t.tanggal DESC, t.id DESC
    LIMIT 5
");
$terbaru = $stmtTerbaru->fetchAll();
foreach ($terbaru as &$row) {
    $row['jumlah'] = (float) $row['jumlah'];
}
unset($row);

// --- 3. Pengeluaran per kategori bulan ini (untuk chart) ---
$stmtKat = $db->prepare("
    SELECT k.nama_kategori, SUM(t.jumlah) AS total
    FROM transaksi t
    LEFT JOIN kategori k ON t.kategori_id = k.id
    WHERE t.jenis = 'pengeluaran'
      AND DATE_FORMAT(t.tanggal, '%Y-%m') = ?
    GROUP BY k.id, k.nama_kategori
    ORDER BY total DESC
");
$stmtKat->execute([$bulan]);
$perKategori = $stmtKat->fetchAll();
foreach ($perKategori as &$row) {
    $row['total'] = (float) $row['total'];
}
unset($row);

sendSuccess([
    'bulan'        => $bulan,
    'ringkasan'    => [
        'total_masuk'       => (float) $ringkasan['total_masuk'],
        'total_keluar'      => (float) $ringkasan['total_keluar'],
        'saldo'             => (float) $ringkasan['total_masuk'] - (float) $ringkasan['total_keluar'],
        'jumlah_transaksi'  => (int)   $ringkasan['jumlah_transaksi'],
    ],
    'transaksi_terbaru' => $terbaru,
    'pengeluaran_per_kategori' => $perKategori,
]);
