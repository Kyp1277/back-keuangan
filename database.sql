-- ============================================
-- SISTEM KEUANGAN UMKM
-- Database: umkm_keuangan
-- ============================================


-- Tabel Users
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Kategori
CREATE TABLE kategori (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_kategori VARCHAR(100) NOT NULL,
    jenis ENUM('pemasukan', 'pengeluaran') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Transaksi
CREATE TABLE transaksi (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tanggal DATE NOT NULL,
    jenis ENUM('pemasukan', 'pengeluaran') NOT NULL,
    kategori_id INT NOT NULL,
    jumlah DECIMAL(15,2) NOT NULL,
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kategori_id) REFERENCES kategori(id) ON DELETE RESTRICT
);

-- ============================================
-- DATA AWAL (DUMMY DATA)
-- ============================================

-- Admin default: username=admin, password=admin123
INSERT INTO users (username, password, nama_lengkap) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin UMKM');

-- Kategori Pemasukan
INSERT INTO kategori (nama_kategori, jenis) VALUES
('Penjualan Produk', 'pemasukan'),
('Jasa Layanan', 'pemasukan'),
('Investasi', 'pemasukan'),
('Pendapatan Lain-lain', 'pemasukan');

-- Kategori Pengeluaran
INSERT INTO kategori (nama_kategori, jenis) VALUES
('Bahan Baku', 'pengeluaran'),
('Gaji Karyawan', 'pengeluaran'),
('Sewa Tempat', 'pengeluaran'),
('Listrik & Air', 'pengeluaran'),
('Transportasi', 'pengeluaran'),
('Peralatan', 'pengeluaran'),
('Marketing', 'pengeluaran'),
('Biaya Operasional', 'pengeluaran');

-- Dummy Transaksi (3 bulan terakhir)
INSERT INTO transaksi (tanggal, jenis, kategori_id, jumlah, keterangan) VALUES
('2026-02-01', 'pemasukan', 1, 5500000, 'Penjualan produk minggu pertama'),
('2026-02-03', 'pengeluaran', 5, 1200000, 'Pembelian bahan baku tepung & gula'),
('2026-02-05', 'pemasukan', 1, 3200000, 'Penjualan produk'),
('2026-02-07', 'pengeluaran', 8, 350000, 'Pembelian alat kebersihan'),
('2026-02-10', 'pemasukan', 2, 1500000, 'Jasa dekorasi acara'),
('2026-02-12', 'pengeluaran', 9, 250000, 'Ongkos kirim barang'),
('2026-02-14', 'pemasukan', 1, 4800000, 'Penjualan Valentine special'),
('2026-02-15', 'pengeluaran', 6, 2000000, 'Gaji karyawan paruh bulan'),
('2026-02-18', 'pengeluaran', 7, 500000, 'Iklan sosial media'),
('2026-02-20', 'pemasukan', 1, 2900000, 'Penjualan produk'),
('2026-02-22', 'pengeluaran', 5, 900000, 'Bahan baku tambahan'),
('2026-02-25', 'pemasukan', 1, 3600000, 'Penjualan produk akhir bulan'),
('2026-02-28', 'pengeluaran', 6, 2000000, 'Gaji karyawan akhir bulan'),
('2026-03-01', 'pengeluaran', 8, 1500000, 'Sewa tempat bulanan - SALAH KATEGORI'),
('2026-03-01', 'pengeluaran', 7, 150000, 'Bayar listrik'),
('2026-03-03', 'pemasukan', 1, 4200000, 'Penjualan produk awal bulan'),
('2026-03-05', 'pengeluaran', 5, 1100000, 'Bahan baku bulanan'),
('2026-03-08', 'pemasukan', 2, 2000000, 'Jasa catering acara pernikahan'),
('2026-03-10', 'pengeluaran', 9, 180000, 'Bensin & parkir'),
('2026-03-12', 'pemasukan', 1, 5100000, 'Penjualan produk'),
('2026-03-15', 'pengeluaran', 6, 2000000, 'Gaji karyawan'),
('2026-03-18', 'pemasukan', 4, 300000, 'Jual kardus bekas'),
('2026-03-20', 'pengeluaran', 7, 600000, 'Cetak brosur & banner'),
('2026-03-22', 'pemasukan', 1, 3800000, 'Penjualan produk'),
('2026-03-25', 'pengeluaran', 5, 800000, 'Bahan baku tambahan'),
('2026-03-28', 'pemasukan', 1, 2700000, 'Penjualan akhir bulan'),
('2026-03-31', 'pengeluaran', 6, 2000000, 'Gaji karyawan akhir bulan'),
('2026-04-01', 'pengeluaran', 7, 1500000, 'Sewa tempat April'),
('2026-04-01', 'pengeluaran', 8, 145000, 'Bayar listrik April'),
('2026-04-03', 'pemasukan', 1, 6200000, 'Penjualan produk awal April'),
('2026-04-05', 'pengeluaran', 5, 1300000, 'Bahan baku bulan April'),
('2026-04-08', 'pemasukan', 2, 2500000, 'Jasa layanan konsultasi'),
('2026-04-10', 'pengeluaran', 9, 220000, 'Transportasi pengiriman'),
('2026-04-12', 'pemasukan', 1, 4500000, 'Penjualan produk'),
('2026-04-15', 'pengeluaran', 6, 2000000, 'Gaji karyawan tengah bulan'),
('2026-04-17', 'pemasukan', 1, 3900000, 'Penjualan produk'),
('2026-04-20', 'pengeluaran', 10, 750000, 'Beli peralatan masak baru'),
('2026-04-22', 'pemasukan', 1, 5300000, 'Penjualan produk minggu ini');
