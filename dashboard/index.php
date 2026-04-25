<?php
session_start();
require_once '../config/koneksi.php';
require_once '../includes/layout.php';
cekLogin();

// ── Stats utama ──────────────────────────────────────
$stmt = $pdo->query("SELECT
    SUM(CASE WHEN jenis='pemasukan' THEN jumlah ELSE 0 END)  AS total_masuk,
    SUM(CASE WHEN jenis='pengeluaran' THEN jumlah ELSE 0 END) AS total_keluar,
    COUNT(*) AS total_transaksi
FROM transaksi");
$stats = $stmt->fetch();
$saldo = ($stats['total_masuk'] ?? 0) - ($stats['total_keluar'] ?? 0);

// ── Stats bulan ini ───────────────────────────────────
$bulanIni = date('Y-m');
$stmt = $pdo->prepare("SELECT
    SUM(CASE WHEN jenis='pemasukan' THEN jumlah ELSE 0 END)  AS masuk_bulan,
    SUM(CASE WHEN jenis='pengeluaran' THEN jumlah ELSE 0 END) AS keluar_bulan
FROM transaksi WHERE DATE_FORMAT(tanggal,'%Y-%m') = ?");
$stmt->execute([$bulanIni]);
$statsBulan = $stmt->fetch();

// ── Grafik 6 bulan terakhir ───────────────────────────
$stmt = $pdo->query("SELECT
    DATE_FORMAT(tanggal,'%Y-%m') AS bulan,
    SUM(CASE WHEN jenis='pemasukan'  THEN jumlah ELSE 0 END) AS pemasukan,
    SUM(CASE WHEN jenis='pengeluaran' THEN jumlah ELSE 0 END) AS pengeluaran
FROM transaksi
WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
GROUP BY bulan ORDER BY bulan");
$grafikData = $stmt->fetchAll();

$grafikLabels   = [];
$grafikMasuk    = [];
$grafikKeluar   = [];
foreach ($grafikData as $row) {
    $grafikLabels[] = date('M Y', strtotime($row['bulan'] . '-01'));
    $grafikMasuk[]  = (float)$row['pemasukan'];
    $grafikKeluar[] = (float)$row['pengeluaran'];
}

// ── Transaksi terbaru ──────────────────────────────────
$stmt = $pdo->query("SELECT t.*, k.nama_kategori FROM transaksi t
    LEFT JOIN kategori k ON t.kategori_id = k.id
    ORDER BY t.tanggal DESC, t.id DESC LIMIT 8");
$transaksiTerbaru = $stmt->fetchAll();

// ── Top kategori pengeluaran ──────────────────────────
$stmt = $pdo->query("SELECT k.nama_kategori, SUM(t.jumlah) AS total
    FROM transaksi t JOIN kategori k ON t.kategori_id = k.id
    WHERE t.jenis='pengeluaran'
    GROUP BY k.id ORDER BY total DESC LIMIT 5");
$topKategori = $stmt->fetchAll();

renderHead('Dashboard');
?>
<div class="flex min-h-screen">
    <?php renderSidebar('dashboard'); ?>

    <!-- Main -->
    <div class="flex-1 flex flex-col min-w-0">
        <?php renderTopbar('Dashboard'); ?>

        <main class="flex-1 p-6 overflow-auto fade-in">
            <?php renderFlash(); ?>

            <!-- Summary cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">
                <!-- Total Pemasukan -->
                <div class="bg-white rounded-2xl p-5 card-shadow border border-slate-100">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-slate-500 text-sm font-medium">Total Pemasukan</span>
                        <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/>
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-extrabold text-slate-800"><?= formatRupiah($stats['total_masuk'] ?? 0) ?></div>
                    <div class="text-xs text-emerald-600 mt-1 font-medium">Bulan ini: <?= formatRupiah($statsBulan['masuk_bulan'] ?? 0) ?></div>
                </div>

                <!-- Total Pengeluaran -->
                <div class="bg-white rounded-2xl p-5 card-shadow border border-slate-100">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-slate-500 text-sm font-medium">Total Pengeluaran</span>
                        <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"/>
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-extrabold text-slate-800"><?= formatRupiah($stats['total_keluar'] ?? 0) ?></div>
                    <div class="text-xs text-red-500 mt-1 font-medium">Bulan ini: <?= formatRupiah($statsBulan['keluar_bulan'] ?? 0) ?></div>
                </div>

                <!-- Saldo -->
                <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-2xl p-5 card-shadow">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-blue-200 text-sm font-medium">Saldo Akhir</span>
                        <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-extrabold text-white"><?= formatRupiah($saldo) ?></div>
                    <div class="text-xs text-blue-200 mt-1 font-medium"><?= $saldo >= 0 ? '✓ Kondisi Sehat' : '⚠ Perlu Perhatian' ?></div>
                </div>

                <!-- Total Transaksi -->
                <div class="bg-white rounded-2xl p-5 card-shadow border border-slate-100">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-slate-500 text-sm font-medium">Total Transaksi</span>
                        <div class="w-10 h-10 bg-violet-100 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-extrabold text-slate-800"><?= number_format($stats['total_transaksi']) ?></div>
                    <div class="text-xs text-violet-600 mt-1 font-medium">Data tercatat</div>
                </div>
            </div>

            <!-- Charts & Sidebar -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-5 mb-6">
                <!-- Chart -->
                <div class="xl:col-span-2 bg-white rounded-2xl p-6 card-shadow border border-slate-100">
                    <div class="flex items-center justify-between mb-5">
                        <div>
                            <h2 class="text-slate-800 font-bold text-base">Grafik Arus Keuangan</h2>
                            <p class="text-slate-400 text-xs mt-0.5">6 bulan terakhir</p>
                        </div>
                        <div class="flex items-center gap-4 text-xs">
                            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-emerald-500 inline-block"></span>Pemasukan</span>
                            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-red-500 inline-block"></span>Pengeluaran</span>
                        </div>
                    </div>
                    <canvas id="chartArus" height="100"></canvas>
                </div>

                <!-- Top Kategori -->
                <div class="bg-white rounded-2xl p-6 card-shadow border border-slate-100">
                    <h2 class="text-slate-800 font-bold text-base mb-5">Top Pengeluaran</h2>
                    <div class="space-y-3">
                        <?php
                        $maxVal = max(array_column($topKategori, 'total') ?: [1]);
                        foreach ($topKategori as $i => $kat):
                            $pct = round(($kat['total'] / $maxVal) * 100);
                            $colors = ['bg-blue-500','bg-violet-500','bg-amber-500','bg-emerald-500','bg-red-400'];
                        ?>
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-slate-600 font-medium truncate pr-2"><?= htmlspecialchars($kat['nama_kategori']) ?></span>
                                <span class="text-slate-500 text-xs flex-shrink-0"><?= formatRupiah($kat['total']) ?></span>
                            </div>
                            <div class="bg-slate-100 rounded-full h-2">
                                <div class="<?= $colors[$i] ?> h-2 rounded-full transition-all" style="width:<?= $pct ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Transaksi terbaru -->
            <div class="bg-white rounded-2xl card-shadow border border-slate-100">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-slate-800 font-bold text-base">Transaksi Terbaru</h2>
                    <a href="/umkm-keuangan/transaksi/index.php" class="text-blue-600 hover:text-blue-700 text-sm font-medium">Lihat semua →</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-slate-500 text-xs uppercase tracking-wider">
                                <th class="px-6 py-3 font-semibold">Tanggal</th>
                                <th class="px-6 py-3 font-semibold">Keterangan</th>
                                <th class="px-6 py-3 font-semibold">Kategori</th>
                                <th class="px-6 py-3 font-semibold">Jenis</th>
                                <th class="px-6 py-3 font-semibold text-right">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php foreach ($transaksiTerbaru as $trx): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-3 text-slate-500"><?= date('d M Y', strtotime($trx['tanggal'])) ?></td>
                                <td class="px-6 py-3 text-slate-700 max-w-xs truncate"><?= htmlspecialchars($trx['keterangan'] ?: '-') ?></td>
                                <td class="px-6 py-3 text-slate-500"><?= htmlspecialchars($trx['nama_kategori'] ?? '-') ?></td>
                                <td class="px-6 py-3">
                                    <?php if ($trx['jenis'] === 'pemasukan'): ?>
                                    <span class="bg-emerald-100 text-emerald-700 text-xs font-semibold px-2.5 py-1 rounded-full">Pemasukan</span>
                                    <?php else: ?>
                                    <span class="bg-red-100 text-red-600 text-xs font-semibold px-2.5 py-1 rounded-full">Pengeluaran</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-3 text-right font-bold <?= $trx['jenis'] === 'pemasukan' ? 'text-emerald-600' : 'text-red-500' ?>">
                                    <?= $trx['jenis'] === 'pemasukan' ? '+' : '-' ?><?= formatRupiah($trx['jumlah']) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
const ctx = document.getElementById('chartArus').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($grafikLabels) ?>,
        datasets: [
            {
                label: 'Pemasukan',
                data: <?= json_encode($grafikMasuk) ?>,
                backgroundColor: 'rgba(16, 185, 129, 0.85)',
                borderRadius: 6,
                borderSkipped: false,
            },
            {
                label: 'Pengeluaran',
                data: <?= json_encode($grafikKeluar) ?>,
                backgroundColor: 'rgba(239, 68, 68, 0.85)',
                borderRadius: 6,
                borderSkipped: false,
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => ' Rp ' + ctx.parsed.y.toLocaleString('id-ID')
                }
            }
        },
        scales: {
            x: { grid: { display: false }, border: { display: false } },
            y: {
                border: { display: false, dash: [4,4] },
                grid: { color: '#f1f5f9' },
                ticks: {
                    callback: v => 'Rp ' + (v/1000000).toFixed(1) + 'jt'
                }
            }
        }
    }
});
</script>
</body></html>
