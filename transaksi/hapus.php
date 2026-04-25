<?php
session_start();
require_once '../config/koneksi.php';
cekLogin();

$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $stmt = $pdo->prepare("DELETE FROM transaksi WHERE id = ?");
    $stmt->execute([$id]);
    if ($stmt->rowCount()) {
        setFlash('success', 'Transaksi berhasil dihapus.');
    } else {
        setFlash('error', 'Transaksi tidak ditemukan.');
    }
} else {
    setFlash('error', 'ID tidak valid.');
}

redirect('/umkm-keuangan/transaksi/index.php');
