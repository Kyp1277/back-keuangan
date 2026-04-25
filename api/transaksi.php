<?php
// ============================================
// ENDPOINT: /api/transaksi.php
// ============================================
// GET    /api/transaksi.php           → ambil semua transaksi (+ filter)
// POST   /api/transaksi.php           → tambah transaksi baru
// PUT    /api/transaksi.php           → update transaksi (id di body)
// DELETE /api/transaksi.php?id=...    → hapus transaksi
//
// Semua endpoint ini butuh token (Authorization: Bearer <token>)
// ============================================

require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth.php';
require_once __DIR__ . '/../config/database.php';

// 1. Set header CORS
setCorsHeaders();

// 2. Validasi token → hentikan jika tidak valid
$currentUser = validateToken();

// 3. Routing berdasarkan HTTP method
$bodyForMethodOverride = getRequestBody();
$method = strtoupper($bodyForMethodOverride['_method'] ?? $_GET['_method'] ?? $_SERVER['REQUEST_METHOD']);

switch ($method) {
    case 'GET':
        handleGet();
        break;
    case 'POST':
        handlePost();
        break;
    case 'PUT':
        handlePut();
        break;
    case 'DELETE':
        handleDelete();
        break;
    default:
        sendError('Method tidak diizinkan.', 405);
}


// ============================================
// GET - Ambil daftar transaksi (dengan filter opsional)
//
// Query params yang didukung:
//   ?jenis=pemasukan|pengeluaran
//   ?bulan=YYYY-MM
//   ?q=kata kunci keterangan/kategori
// ============================================
function handleGet(): void {
    $db = getDB();

    // Ambil parameter filter dari URL
    $filterJenis = $_GET['jenis'] ?? '';
    $filterBulan = $_GET['bulan'] ?? '';
    $search      = $_GET['q']     ?? '';

    // Bangun klausa WHERE secara dinamis
    $where  = ['1=1'];
    $params = [];

    if ($filterJenis) {
        $where[]  = 't.jenis = ?';
        $params[] = $filterJenis;
    }

    if ($filterBulan) {
        $where[]  = "DATE_FORMAT(t.tanggal, '%Y-%m') = ?";
        $params[] = $filterBulan;
    }

    if ($search) {
        $where[]  = "(t.keterangan LIKE ? OR k.nama_kategori LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $whereStr = implode(' AND ', $where);

    // Query utama: ambil transaksi + nama kategori
    $stmt = $db->prepare("
        SELECT
            t.id,
            t.tanggal,
            t.jenis,
            t.kategori_id,
            k.nama_kategori,
            t.jumlah,
            t.keterangan,
            t.created_at
        FROM transaksi t
        LEFT JOIN kategori k ON t.kategori_id = k.id
        WHERE $whereStr
        ORDER BY t.tanggal DESC, t.id DESC
    ");
    $stmt->execute($params);
    $transaksi = $stmt->fetchAll();

    // Konversi jumlah ke float (bukan string)
    foreach ($transaksi as &$row) {
        $row['jumlah'] = (float) $row['jumlah'];
    }
    unset($row);

    // Query statistik (total masuk, keluar, saldo)
    $stmtStat = $db->prepare("
        SELECT
            COALESCE(SUM(CASE WHEN t.jenis = 'pemasukan'   THEN t.jumlah ELSE 0 END), 0) AS total_masuk,
            COALESCE(SUM(CASE WHEN t.jenis = 'pengeluaran' THEN t.jumlah ELSE 0 END), 0) AS total_keluar
        FROM transaksi t
        LEFT JOIN kategori k ON t.kategori_id = k.id
        WHERE $whereStr
    ");
    $stmtStat->execute($params);
    $stat = $stmtStat->fetch();

    sendSuccess([
        'transaksi' => $transaksi,
        'statistik' => [
            'total_masuk'  => (float) $stat['total_masuk'],
            'total_keluar' => (float) $stat['total_keluar'],
            'saldo'        => (float) $stat['total_masuk'] - (float) $stat['total_keluar'],
        ],
        'total' => count($transaksi),
    ]);
}


// ============================================
// POST - Tambah transaksi baru
//
// Body JSON:
// {
//   "tanggal": "2026-04-25",
//   "jenis": "pemasukan",
//   "kategori_id": 1,
//   "jumlah": 500000,
//   "keterangan": "Penjualan produk"
// }
// ============================================
function handlePost(): void {
    $db   = getDB();
    $body = getRequestBody();

    // Ambil dan bersihkan input
    $tanggal    = trim($body['tanggal']     ?? '');
    $jenis      = trim($body['jenis']       ?? '');
    $kategoriId = (int) ($body['kategori_id'] ?? 0);
    $jumlah     = (float) ($body['jumlah']  ?? 0);
    $keterangan = trim($body['keterangan']  ?? '');

    // Validasi input wajib
    if (empty($tanggal)) {
        sendError('Tanggal transaksi wajib diisi.');
    }
    if (!in_array($jenis, ['pemasukan', 'pengeluaran'])) {
        sendError('Jenis transaksi harus "pemasukan" atau "pengeluaran".');
    }
    if ($kategoriId <= 0) {
        sendError('Kategori tidak valid.');
    }
    if ($jumlah <= 0) {
        sendError('Jumlah harus lebih dari 0.');
    }

    // Validasi format tanggal YYYY-MM-DD
    $dateObj = DateTime::createFromFormat('Y-m-d', $tanggal);
    if (!$dateObj || $dateObj->format('Y-m-d') !== $tanggal) {
        sendError('Format tanggal tidak valid. Gunakan YYYY-MM-DD.');
    }

    // Cek kategori ada di database
    $stmtKat = $db->prepare("SELECT id FROM kategori WHERE id = ? LIMIT 1");
    $stmtKat->execute([$kategoriId]);
    if (!$stmtKat->fetch()) {
        sendError('Kategori tidak ditemukan.');
    }

    // Simpan transaksi
    $stmt = $db->prepare("
        INSERT INTO transaksi (tanggal, jenis, kategori_id, jumlah, keterangan)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$tanggal, $jenis, $kategoriId, $jumlah, $keterangan]);
    $newId = (int) $db->lastInsertId();

    // Ambil data yang baru disimpan untuk dikembalikan
    $stmtNew = $db->prepare("
        SELECT t.*, k.nama_kategori
        FROM transaksi t
        LEFT JOIN kategori k ON t.kategori_id = k.id
        WHERE t.id = ?
    ");
    $stmtNew->execute([$newId]);
    $newData = $stmtNew->fetch();
    $newData['jumlah'] = (float) $newData['jumlah'];

    sendSuccess($newData, 'Transaksi berhasil ditambahkan.', 201);
}


// ============================================
// PUT - Update transaksi yang sudah ada
//
// Body JSON:
// {
//   "id": 5,
//   "tanggal": "2026-04-25",
//   "jenis": "pengeluaran",
//   "kategori_id": 2,
//   "jumlah": 300000,
//   "keterangan": "Update keterangan"
// }
// ============================================
function handlePut(): void {
    $db   = getDB();
    $body = getRequestBody();

    $id         = (int) ($body['id']          ?? 0);
    $tanggal    = trim($body['tanggal']        ?? '');
    $jenis      = trim($body['jenis']          ?? '');
    $kategoriId = (int) ($body['kategori_id']  ?? 0);
    $jumlah     = (float) ($body['jumlah']     ?? 0);
    $keterangan = trim($body['keterangan']     ?? '');

    if ($id <= 0) {
        sendError('ID transaksi tidak valid.');
    }
    if (empty($tanggal)) {
        sendError('Tanggal transaksi wajib diisi.');
    }
    if (!in_array($jenis, ['pemasukan', 'pengeluaran'])) {
        sendError('Jenis transaksi tidak valid.');
    }
    if ($kategoriId <= 0) {
        sendError('Kategori tidak valid.');
    }
    if ($jumlah <= 0) {
        sendError('Jumlah harus lebih dari 0.');
    }

    // Cek apakah transaksi ada
    $stmtCek = $db->prepare("SELECT id FROM transaksi WHERE id = ? LIMIT 1");
    $stmtCek->execute([$id]);
    if (!$stmtCek->fetch()) {
        sendError('Transaksi tidak ditemukan.', 404);
    }

    // Update data
    $stmt = $db->prepare("
        UPDATE transaksi
        SET tanggal = ?, jenis = ?, kategori_id = ?, jumlah = ?, keterangan = ?
        WHERE id = ?
    ");
    $stmt->execute([$tanggal, $jenis, $kategoriId, $jumlah, $keterangan, $id]);

    // Ambil data terbaru
    $stmtUpd = $db->prepare("
        SELECT t.*, k.nama_kategori
        FROM transaksi t
        LEFT JOIN kategori k ON t.kategori_id = k.id
        WHERE t.id = ?
    ");
    $stmtUpd->execute([$id]);
    $updated = $stmtUpd->fetch();
    $updated['jumlah'] = (float) $updated['jumlah'];

    sendSuccess($updated, 'Transaksi berhasil diupdate.');
}


// ============================================
// DELETE - Hapus transaksi
//
// Query param: ?id=5
// ============================================
function handleDelete(): void {
    $db = getDB();
    $id = (int) ($_GET['id'] ?? 0);

    if ($id <= 0) {
        sendError('ID transaksi tidak valid. Contoh: ?id=5');
    }

    // Cek apakah transaksi ada sebelum dihapus
    $stmtCek = $db->prepare("SELECT id FROM transaksi WHERE id = ? LIMIT 1");
    $stmtCek->execute([$id]);
    if (!$stmtCek->fetch()) {
        sendError('Transaksi tidak ditemukan.', 404);
    }

    $stmt = $db->prepare("DELETE FROM transaksi WHERE id = ?");
    $stmt->execute([$id]);

    sendSuccess(['id' => $id], 'Transaksi berhasil dihapus.');
}
