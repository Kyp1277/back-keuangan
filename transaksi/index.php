<?php
session_start();
require_once '../config/koneksi.php';
require_once '../includes/layout.php';
cekLogin();

// Filter
$filterJenis  = $_GET['jenis']  ?? '';
$filterBulan  = $_GET['bulan']  ?? '';
$search       = $_GET['q']      ?? '';

$where = ['1=1'];
$params = [];

if ($filterJenis) {
    $where[] = 't.jenis = ?';
    $params[] = $filterJenis;
}
if ($filterBulan) {
    $where[] = "DATE_FORMAT(t.tanggal,'%Y-%m') = ?";
    $params[] = $filterBulan;
}
if ($search) {
    $where[] = "(t.keterangan LIKE ? OR k.nama_kategori LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereStr = implode(' AND ', $where);

$stmt = $pdo->prepare("SELECT t.*, k.nama_kategori FROM transaksi t
    LEFT JOIN kategori k ON t.kategori_id = k.id
    WHERE $whereStr
    ORDER BY t.tanggal DESC, t.id DESC");
$stmt->execute($params);
$transaksi = $stmt->fetchAll();

// Stats filter
$stmtStat = $pdo->prepare("SELECT
    SUM(CASE WHEN t.jenis='pemasukan' THEN t.jumlah ELSE 0 END)  AS masuk,
    SUM(CASE WHEN t.jenis='pengeluaran' THEN t.jumlah ELSE 0 END) AS keluar
FROM transaksi t LEFT JOIN kategori k ON t.kategori_id=k.id WHERE $whereStr");
$stmtStat->execute($params);
$statFilter = $stmtStat->fetch();

renderHead('Transaksi');
?>
<div class="flex min-h-screen">
    <?php renderSidebar('transaksi'); ?>
    <div class="flex-1 flex flex-col min-w-0">
        <?php renderTopbar('Manajemen Transaksi'); ?>
        <main class="flex-1 p-6 overflow-auto fade-in">
            <?php renderFlash(); ?>

            <!-- Header actions -->
            <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
                <div class="flex gap-3 flex-wrap">
                    <div class="bg-white border border-slate-200 rounded-xl px-4 py-2 text-sm text-slate-600">
                        <span class="font-semibold text-emerald-600"><?= formatRupiah($statFilter['masuk'] ?? 0) ?></span> Masuk
                    </div>
                    <div class="bg-white border border-slate-200 rounded-xl px-4 py-2 text-sm text-slate-600">
                        <span class="font-semibold text-red-500"><?= formatRupiah($statFilter['keluar'] ?? 0) ?></span> Keluar
                    </div>
                    <div class="bg-white border border-slate-200 rounded-xl px-4 py-2 text-sm text-slate-600">
                        <span class="font-semibold text-blue-600"><?= formatRupiah(($statFilter['masuk'] ?? 0) - ($statFilter['keluar'] ?? 0)) ?></span> Saldo
                    </div>
                </div>
                <a href="/umkm-keuangan/transaksi/tambah.php"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Transaksi
                </a>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-2xl p-4 card-shadow border border-slate-100 mb-5">
                <form method="GET" class="flex flex-wrap gap-3 items-end">
                    <div class="flex-1 min-w-[180px]">
                        <label class="block text-xs font-medium text-slate-500 mb-1.5">Cari</label>
                        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                            placeholder="Cari keterangan / kategori..."
                            class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400">
                    </div>
                    <div class="min-w-[140px]">
                        <label class="block text-xs font-medium text-slate-500 mb-1.5">Jenis</label>
                        <select name="jenis" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400">
                            <option value="">Semua Jenis</option>
                            <option value="pemasukan"  <?= $filterJenis==='pemasukan'  ? 'selected':'' ?>>Pemasukan</option>
                            <option value="pengeluaran"<?= $filterJenis==='pengeluaran'? 'selected':'' ?>>Pengeluaran</option>
                        </select>
                    </div>
                    <div class="min-w-[160px]">
                        <label class="block text-xs font-medium text-slate-500 mb-1.5">Bulan</label>
                        <input type="month" name="bulan" value="<?= htmlspecialchars($filterBulan) ?>"
                            class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400">
                    </div>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-sm font-medium transition-colors">Filter</button>
                    <a href="/umkm-keuangan/transaksi/index.php" class="bg-slate-100 hover:bg-slate-200 text-slate-600 px-4 py-2 rounded-xl text-sm font-medium transition-colors">Reset</a>
                </form>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl card-shadow border border-slate-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 text-left text-slate-500 text-xs uppercase tracking-wider border-b border-slate-200">
                                <th class="px-5 py-3.5 font-semibold">No</th>
                                <th class="px-5 py-3.5 font-semibold">Tanggal</th>
                                <th class="px-5 py-3.5 font-semibold">Kategori</th>
                                <th class="px-5 py-3.5 font-semibold">Keterangan</th>
                                <th class="px-5 py-3.5 font-semibold">Jenis</th>
                                <th class="px-5 py-3.5 font-semibold text-right">Jumlah</th>
                                <th class="px-5 py-3.5 font-semibold text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($transaksi)): ?>
                            <tr>
                                <td colspan="7" class="px-5 py-12 text-center text-slate-400">
                                    <svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    <p class="font-medium">Tidak ada transaksi ditemukan</p>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($transaksi as $i => $row): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-5 py-3 text-slate-400"><?= $i + 1 ?></td>
                                <td class="px-5 py-3 text-slate-600"><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                                <td class="px-5 py-3 text-slate-700"><?= htmlspecialchars($row['nama_kategori'] ?? '-') ?></td>
                                <td class="px-5 py-3 text-slate-500 max-w-xs">
                                    <span class="truncate block" title="<?= htmlspecialchars($row['keterangan']) ?>">
                                        <?= htmlspecialchars($row['keterangan'] ?: '-') ?>
                                    </span>
                                </td>
                                <td class="px-5 py-3">
                                    <?php if ($row['jenis'] === 'pemasukan'): ?>
                                    <span class="bg-emerald-100 text-emerald-700 text-xs font-semibold px-2.5 py-1 rounded-full">↑ Pemasukan</span>
                                    <?php else: ?>
                                    <span class="bg-red-100 text-red-600 text-xs font-semibold px-2.5 py-1 rounded-full">↓ Pengeluaran</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-3 text-right font-bold <?= $row['jenis']==='pemasukan'?'text-emerald-600':'text-red-500' ?>">
                                    <?= $row['jenis']==='pemasukan'?'+':'-' ?><?= formatRupiah($row['jumlah']) ?>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="/umkm-keuangan/transaksi/edit.php?id=<?= $row['id'] ?>"
                                            class="w-8 h-8 bg-amber-100 hover:bg-amber-200 text-amber-700 rounded-lg flex items-center justify-center transition-colors" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>
                                        <a href="/umkm-keuangan/transaksi/hapus.php?id=<?= $row['id'] ?>"
                                            onclick="return confirm('Hapus transaksi ini?')"
                                            class="w-8 h-8 bg-red-100 hover:bg-red-200 text-red-600 rounded-lg flex items-center justify-center transition-colors" title="Hapus">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="px-5 py-3 border-t border-slate-100 text-xs text-slate-400">
                    Menampilkan <?= count($transaksi) ?> transaksi
                </div>
            </div>
        </main>
    </div>
</div>
</body></html>
