<?php
session_start();
require_once '../config/koneksi.php';
require_once '../includes/layout.php';
cekLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { setFlash('error','ID tidak valid.'); redirect('/umkm-keuangan/transaksi/index.php'); }

$stmt = $pdo->prepare("SELECT * FROM transaksi WHERE id = ?");
$stmt->execute([$id]);
$trx = $stmt->fetch();
if (!$trx) { setFlash('error','Transaksi tidak ditemukan.'); redirect('/umkm-keuangan/transaksi/index.php'); }

$errors = [];
$input = $trx;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input['tanggal']     = trim($_POST['tanggal']     ?? '');
    $input['jenis']       = trim($_POST['jenis']       ?? '');
    $input['kategori_id'] = trim($_POST['kategori_id'] ?? '');
    $input['jumlah']      = trim($_POST['jumlah']      ?? '');
    $input['keterangan']  = trim($_POST['keterangan']  ?? '');

    if (empty($input['tanggal']))                   $errors[] = 'Tanggal wajib diisi.';
    if (!in_array($input['jenis'],['pemasukan','pengeluaran'])) $errors[] = 'Jenis tidak valid.';
    if (empty($input['kategori_id']))               $errors[] = 'Kategori wajib dipilih.';
    $jumlahClean = (float) str_replace(['.', ','], ['', '.'], $input['jumlah']);
    if ($jumlahClean <= 0)                          $errors[] = 'Jumlah harus lebih dari 0.';

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE transaksi SET tanggal=?,jenis=?,kategori_id=?,jumlah=?,keterangan=? WHERE id=?");
        $stmt->execute([$input['tanggal'],$input['jenis'],$input['kategori_id'],$jumlahClean,$input['keterangan'],$id]);
        setFlash('success','Transaksi berhasil diperbarui!');
        redirect('/umkm-keuangan/transaksi/index.php');
    }
}

$stmt = $pdo->query("SELECT * FROM kategori ORDER BY jenis, nama_kategori");
$semuaKategori = $stmt->fetchAll();

renderHead('Edit Transaksi');
?>
<div class="flex min-h-screen">
    <?php renderSidebar('transaksi'); ?>
    <div class="flex-1 flex flex-col min-w-0">
        <?php renderTopbar('Edit Transaksi'); ?>
        <main class="flex-1 p-6 overflow-auto fade-in">
            <div class="max-w-2xl mx-auto">
                <div class="flex items-center gap-2 text-sm text-slate-500 mb-6">
                    <a href="/umkm-keuangan/transaksi/index.php" class="hover:text-blue-600">Transaksi</a>
                    <span>›</span>
                    <span class="text-slate-700 font-medium">Edit Transaksi #<?= $id ?></span>
                </div>

                <?php if (!empty($errors)): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-5">
                    <ul class="list-disc list-inside text-sm space-y-0.5">
                        <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <div class="bg-white rounded-2xl card-shadow border border-slate-100 p-6">
                    <form method="POST">
                        <!-- Jenis toggle -->
                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Jenis Transaksi</label>
                            <div class="grid grid-cols-2 gap-3">
                                <?php foreach (['pemasukan','pengeluaran'] as $jenisOpt): 
                                    $isSelected = $input['jenis'] === $jenisOpt;
                                    $color = $jenisOpt==='pemasukan' ? 'emerald' : 'red';
                                    $icon = $jenisOpt==='pemasukan'
                                        ? 'M7 11l5-5m0 0l5 5m-5-5v12'
                                        : 'M17 13l-5 5m0 0l-5-5m5 5V6';
                                    $label = $jenisOpt==='pemasukan' ? 'Pemasukan' : 'Pengeluaran';
                                    $sublabel = $jenisOpt==='pemasukan' ? 'Uang masuk' : 'Uang keluar';
                                ?>
                                <label class="cursor-pointer">
                                    <input type="radio" name="jenis" value="<?= $jenisOpt ?>" class="sr-only" <?= $isSelected?'checked':'' ?> onchange="updateKategori()">
                                    <div class="jenis-card-<?= $jenisOpt ?> border-2 rounded-xl p-4 flex items-center gap-3 transition-all <?= $isSelected ? "border-{$color}-500 bg-{$color}-50" : 'border-slate-200' ?>">
                                        <div class="w-10 h-10 rounded-xl <?= $isSelected ? "bg-{$color}-500" : 'bg-slate-100' ?> flex items-center justify-center">
                                            <svg class="w-5 h-5 <?= $isSelected?'text-white':'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $icon ?>"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="font-semibold text-sm text-slate-700"><?= $label ?></div>
                                            <div class="text-xs text-slate-400"><?= $sublabel ?></div>
                                        </div>
                                    </div>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Tanggal <span class="text-red-500">*</span></label>
                                <input type="date" name="tanggal" value="<?= htmlspecialchars($input['tanggal']) ?>"
                                    class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Kategori <span class="text-red-500">*</span></label>
                                <select name="kategori_id" id="selectKategori"
                                    class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400" required>
                                    <?php foreach ($semuaKategori as $kat): ?>
                                    <option value="<?= $kat['id'] ?>" data-jenis="<?= $kat['jenis'] ?>"
                                        <?= $input['kategori_id'] == $kat['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($kat['nama_kategori']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Jumlah <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                    <span class="text-slate-500 text-sm font-medium">Rp</span>
                                </div>
                                <input type="text" name="jumlah" id="jumlahInput"
                                    value="<?= number_format((float)$input['jumlah'], 0, ',', '.') ?>"
                                    class="w-full border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400"
                                    oninput="formatJumlah(this)" required>
                            </div>
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Keterangan</label>
                            <textarea name="keterangan" rows="3"
                                class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 resize-none"><?= htmlspecialchars($input['keterangan']) ?></textarea>
                        </div>

                        <div class="flex items-center gap-3">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-xl text-sm font-semibold transition-colors">
                                Perbarui Transaksi
                            </button>
                            <a href="/umkm-keuangan/transaksi/index.php" class="bg-slate-100 hover:bg-slate-200 text-slate-600 px-6 py-2.5 rounded-xl text-sm font-semibold transition-colors">
                                Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>
<script>
const semuaKategori = <?= json_encode($semuaKategori) ?>;
function updateKategori() {
    const jenis = document.querySelector('input[name="jenis"]:checked')?.value;
    const select = document.getElementById('selectKategori');
    const current = select.value;
    select.innerHTML = '<option value="">-- Pilih Kategori --</option>';
    semuaKategori.filter(k => k.jenis === jenis).forEach(k => {
        const opt = document.createElement('option');
        opt.value = k.id; opt.textContent = k.nama_kategori;
        if (k.id == current) opt.selected = true;
        select.appendChild(opt);
    });
}
function formatJumlah(input) {
    let raw = input.value.replace(/[^\d]/g, '');
    if (raw) input.value = parseInt(raw).toLocaleString('id-ID');
    else input.value = '';
}
updateKategori();
</script>
</body></html>
