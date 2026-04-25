<?php
session_start();
require_once '../config/koneksi.php';
require_once '../includes/layout.php';
cekLogin();

// Filter
$tglMulai = $_GET['tgl_mulai'] ?? date('Y-m-01');
$tglAkhir = $_GET['tgl_akhir'] ?? date('Y-m-d');
$filterJenis = $_GET['jenis'] ?? '';

$where = ['t.tanggal BETWEEN ? AND ?'];
$params = [$tglMulai, $tglAkhir];

if ($filterJenis) {
    $where[] = 't.jenis = ?';
    $params[] = $filterJenis;
}
$whereStr = implode(' AND ', $where);

// Transaksi
$stmt = $pdo->prepare("SELECT t.*, k.nama_kategori FROM transaksi t
    LEFT JOIN kategori k ON t.kategori_id = k.id
    WHERE $whereStr ORDER BY t.tanggal ASC, t.id ASC");
$stmt->execute($params);
$transaksi = $stmt->fetchAll();

// Stats
$stmtStat = $pdo->prepare("SELECT
    SUM(CASE WHEN t.jenis='pemasukan'  THEN t.jumlah ELSE 0 END) AS total_masuk,
    SUM(CASE WHEN t.jenis='pengeluaran' THEN t.jumlah ELSE 0 END) AS total_keluar,
    COUNT(*) AS total_transaksi
FROM transaksi t WHERE $whereStr");
$stmtStat->execute($params);
$stats = $stmtStat->fetch();

// Per kategori
$stmtKat = $pdo->prepare("SELECT k.nama_kategori, k.jenis, SUM(t.jumlah) AS total, COUNT(t.id) AS jumlah
    FROM transaksi t JOIN kategori k ON t.kategori_id=k.id
    WHERE $whereStr GROUP BY k.id ORDER BY k.jenis, total DESC");
$stmtKat->execute($params);
$perKategori = $stmtKat->fetchAll();

// Daily chart
$stmtDaily = $pdo->prepare("SELECT tanggal,
    SUM(CASE WHEN jenis='pemasukan' THEN jumlah ELSE 0 END) as masuk,
    SUM(CASE WHEN jenis='pengeluaran' THEN jumlah ELSE 0 END) as keluar
FROM transaksi WHERE tanggal BETWEEN ? AND ? GROUP BY tanggal ORDER BY tanggal");
$stmtDaily->execute([$tglMulai, $tglAkhir]);
$dailyData = $stmtDaily->fetchAll();

$saldo = ($stats['total_masuk'] ?? 0) - ($stats['total_keluar'] ?? 0);
$isPrint = isset($_GET['print']);

if ($isPrint):
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Keuangan — <?= APP_NAME ?></title>
    <style>
        * { font-family: 'Segoe UI', sans-serif; margin:0; padding:0; box-sizing:border-box; }
        body { font-size:12px; color:#1e293b; padding:20px; }
        .header { text-align:center; border-bottom:2px solid #1e3a5f; padding-bottom:15px; margin-bottom:20px; }
        .header h1 { font-size:20px; color:#1e3a5f; }
        .header p { color:#64748b; margin-top:4px; }
        .stats { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-bottom:20px; }
        .stat-box { border:1px solid #e2e8f0; border-radius:8px; padding:12px; text-align:center; }
        .stat-box .label { font-size:10px; color:#64748b; text-transform:uppercase; letter-spacing:.5px; }
        .stat-box .value { font-size:16px; font-weight:700; margin-top:4px; }
        .masuk { color:#059669; }
        .keluar { color:#dc2626; }
        .saldo { color:#1d4ed8; }
        table { width:100%; border-collapse:collapse; margin-bottom:20px; }
        th { background:#f1f5f9; padding:8px; text-align:left; font-size:10px; text-transform:uppercase; letter-spacing:.5px; color:#64748b; }
        td { padding:7px 8px; border-bottom:1px solid #f1f5f9; font-size:11px; }
        tr:nth-child(even) td { background:#fafbfc; }
        .badge-masuk { background:#d1fae5; color:#065f46; padding:2px 8px; border-radius:9999px; font-size:10px; font-weight:600; }
        .badge-keluar { background:#fee2e2; color:#991b1b; padding:2px 8px; border-radius:9999px; font-size:10px; font-weight:600; }
        .text-right { text-align:right; }
        .footer { text-align:center; color:#94a3b8; font-size:10px; margin-top:30px; border-top:1px solid #e2e8f0; padding-top:10px; }
        @media print { body { padding:10px; } }
    </style>
</head>
<body>
    <div class="header">
        <h1><?= APP_NAME ?></h1>
        <p>Laporan Keuangan Periode: <?= date('d F Y', strtotime($tglMulai)) ?> s/d <?= date('d F Y', strtotime($tglAkhir)) ?></p>
        <p style="color:#94a3b8;font-size:11px;">Dicetak: <?= date('d F Y H:i') ?></p>
    </div>

    <div class="stats">
        <div class="stat-box"><div class="label">Total Pemasukan</div><div class="value masuk"><?= formatRupiah($stats['total_masuk']??0) ?></div></div>
        <div class="stat-box"><div class="label">Total Pengeluaran</div><div class="value keluar"><?= formatRupiah($stats['total_keluar']??0) ?></div></div>
        <div class="stat-box"><div class="label">Saldo Bersih</div><div class="value saldo"><?= formatRupiah($saldo) ?></div></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th><th>Tanggal</th><th>Keterangan</th><th>Kategori</th><th>Jenis</th><th class="text-right">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transaksi as $i => $row): ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                <td><?= htmlspecialchars($row['keterangan'] ?: '-') ?></td>
                <td><?= htmlspecialchars($row['nama_kategori']??'-') ?></td>
                <td><span class="badge-<?= $row['jenis']==='pemasukan'?'masuk':'keluar' ?>"><?= ucfirst($row['jenis']) ?></span></td>
                <td class="text-right" style="font-weight:600;color:<?= $row['jenis']==='pemasukan'?'#059669':'#dc2626' ?>">
                    <?= $row['jenis']==='pemasukan'?'+':'-' ?><?= formatRupiah($row['jumlah']) ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="footer">
        Laporan ini dibuat secara otomatis oleh sistem <?= APP_NAME ?> • <?= date('d F Y H:i:s') ?>
    </div>

    <script>window.print(); window.onafterprint = () => window.close();</script>
</body>
</html>
<?php
else:
renderHead('Laporan');
?>
<div class="flex min-h-screen">
    <?php renderSidebar('laporan'); ?>
    <div class="flex-1 flex flex-col min-w-0">
        <?php renderTopbar('Laporan Keuangan'); ?>
        <main class="flex-1 p-6 overflow-auto fade-in">
            <?php renderFlash(); ?>

            <!-- Filter -->
            <div class="bg-white rounded-2xl card-shadow border border-slate-100 p-5 mb-6">
                <form method="GET" class="flex flex-wrap gap-3 items-end">
                    <div class="min-w-[160px]">
                        <label class="block text-xs font-medium text-slate-500 mb-1.5">Tanggal Mulai</label>
                        <input type="date" name="tgl_mulai" value="<?= htmlspecialchars($tglMulai) ?>"
                            class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400">
                    </div>
                    <div class="min-w-[160px]">
                        <label class="block text-xs font-medium text-slate-500 mb-1.5">Tanggal Akhir</label>
                        <input type="date" name="tgl_akhir" value="<?= htmlspecialchars($tglAkhir) ?>"
                            class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400">
                    </div>
                    <div class="min-w-[140px]">
                        <label class="block text-xs font-medium text-slate-500 mb-1.5">Jenis</label>
                        <select name="jenis" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400">
                            <option value="">Semua Jenis</option>
                            <option value="pemasukan"   <?= $filterJenis==='pemasukan'  ?'selected':'' ?>>Pemasukan</option>
                            <option value="pengeluaran" <?= $filterJenis==='pengeluaran'?'selected':'' ?>>Pengeluaran</option>
                        </select>
                    </div>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-sm font-medium transition-colors">Tampilkan</button>
                    <a href="/umkm-keuangan/laporan/index.php" class="bg-slate-100 hover:bg-slate-200 text-slate-600 px-4 py-2 rounded-xl text-sm font-medium transition-colors">Reset</a>

                    <!-- Print button -->
                    <a href="?tgl_mulai=<?= urlencode($tglMulai) ?>&tgl_akhir=<?= urlencode($tglAkhir) ?>&jenis=<?= urlencode($filterJenis) ?>&print=1"
                        target="_blank"
                        class="ml-auto bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 rounded-xl text-sm font-medium transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        Cetak / PDF
                    </a>
                </form>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <div class="bg-white rounded-2xl card-shadow border border-slate-100 p-5">
                    <div class="text-slate-500 text-xs font-medium mb-2">Total Pemasukan</div>
                    <div class="text-2xl font-extrabold text-emerald-600"><?= formatRupiah($stats['total_masuk'] ?? 0) ?></div>
                </div>
                <div class="bg-white rounded-2xl card-shadow border border-slate-100 p-5">
                    <div class="text-slate-500 text-xs font-medium mb-2">Total Pengeluaran</div>
                    <div class="text-2xl font-extrabold text-red-500"><?= formatRupiah($stats['total_keluar'] ?? 0) ?></div>
                </div>
                <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-2xl p-5">
                    <div class="text-blue-200 text-xs font-medium mb-2">Saldo Bersih</div>
                    <div class="text-2xl font-extrabold text-white"><?= formatRupiah($saldo) ?></div>
                </div>
            </div>

            <!-- Charts & Breakdown -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-5 mb-6">
                <!-- Daily chart -->
                <div class="xl:col-span-2 bg-white rounded-2xl card-shadow border border-slate-100 p-6">
                    <h3 class="font-bold text-slate-800 text-sm mb-4">Grafik Harian</h3>
                    <canvas id="chartHarian" height="120"></canvas>
                </div>

                <!-- Per kategori -->
                <div class="bg-white rounded-2xl card-shadow border border-slate-100 p-5">
                    <h3 class="font-bold text-slate-800 text-sm mb-4">Rincian per Kategori</h3>
                    <div class="space-y-3 max-h-64 overflow-y-auto">
                        <?php foreach ($perKategori as $kat): ?>
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full flex-shrink-0 <?= $kat['jenis']==='pemasukan'?'bg-emerald-500':'bg-red-500' ?>"></span>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-medium text-slate-700 truncate"><?= htmlspecialchars($kat['nama_kategori']) ?></div>
                                <div class="text-xs text-slate-400"><?= $kat['jumlah'] ?> transaksi</div>
                            </div>
                            <div class="text-xs font-bold <?= $kat['jenis']==='pemasukan'?'text-emerald-600':'text-red-500' ?> flex-shrink-0">
                                <?= formatRupiah($kat['total']) ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Tabel Transaksi -->
            <div class="bg-white rounded-2xl card-shadow border border-slate-100 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="font-bold text-slate-800 text-sm">Detail Transaksi</h3>
                    <span class="text-xs text-slate-400"><?= count($transaksi) ?> data</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 text-left text-slate-500 text-xs uppercase tracking-wider border-b border-slate-200">
                                <th class="px-5 py-3 font-semibold">No</th>
                                <th class="px-5 py-3 font-semibold">Tanggal</th>
                                <th class="px-5 py-3 font-semibold">Keterangan</th>
                                <th class="px-5 py-3 font-semibold">Kategori</th>
                                <th class="px-5 py-3 font-semibold">Jenis</th>
                                <th class="px-5 py-3 font-semibold text-right">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($transaksi)): ?>
                            <tr><td colspan="6" class="px-5 py-10 text-center text-slate-400">Tidak ada data pada periode ini</td></tr>
                            <?php else: ?>
                            <?php foreach ($transaksi as $i => $row): ?>
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-5 py-3 text-slate-400"><?= $i+1 ?></td>
                                <td class="px-5 py-3 text-slate-600"><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                                <td class="px-5 py-3 text-slate-500 max-w-xs truncate"><?= htmlspecialchars($row['keterangan'] ?: '-') ?></td>
                                <td class="px-5 py-3 text-slate-700"><?= htmlspecialchars($row['nama_kategori'] ?? '-') ?></td>
                                <td class="px-5 py-3">
                                    <?php if ($row['jenis']==='pemasukan'): ?>
                                    <span class="bg-emerald-100 text-emerald-700 text-xs font-semibold px-2 py-0.5 rounded-full">↑ Pemasukan</span>
                                    <?php else: ?>
                                    <span class="bg-red-100 text-red-600 text-xs font-semibold px-2 py-0.5 rounded-full">↓ Pengeluaran</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-3 text-right font-bold <?= $row['jenis']==='pemasukan'?'text-emerald-600':'text-red-500' ?>">
                                    <?= $row['jenis']==='pemasukan'?'+':'-' ?><?= formatRupiah($row['jumlah']) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <!-- Total row -->
                            <tr class="bg-slate-50 font-bold border-t-2 border-slate-200">
                                <td colspan="5" class="px-5 py-3 text-slate-700 text-right">Total Saldo Bersih:</td>
                                <td class="px-5 py-3 text-right text-lg <?= $saldo >= 0 ? 'text-emerald-600':'text-red-500' ?>">
                                    <?= formatRupiah($saldo) ?>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
const dailyLabels = <?= json_encode(array_map(fn($r) => date('d/m', strtotime($r['tanggal'])), $dailyData)) ?>;
const dailyMasuk  = <?= json_encode(array_map(fn($r) => (float)$r['masuk'],  $dailyData)) ?>;
const dailyKeluar = <?= json_encode(array_map(fn($r) => (float)$r['keluar'], $dailyData)) ?>;

new Chart(document.getElementById('chartHarian').getContext('2d'), {
    type: 'line',
    data: {
        labels: dailyLabels,
        datasets: [
            {
                label: 'Pemasukan',
                data: dailyMasuk,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16,185,129,0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 3,
            },
            {
                label: 'Pengeluaran',
                data: dailyKeluar,
                borderColor: '#ef4444',
                backgroundColor: 'rgba(239,68,68,0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 3,
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom', labels: { font: { size: 11 }, boxWidth: 12 } },
            tooltip: { callbacks: { label: ctx => ' Rp ' + ctx.parsed.y.toLocaleString('id-ID') } }
        },
        scales: {
            x: { grid: { display: false }, border: { display: false }, ticks: { font: { size: 10 } } },
            y: {
                border: { display: false, dash: [4,4] },
                grid: { color: '#f1f5f9' },
                ticks: { callback: v => 'Rp '+(v/1000000).toFixed(1)+'jt', font: { size: 10 } }
            }
        }
    }
});
</script>
</body></html>
<?php endif; ?>
