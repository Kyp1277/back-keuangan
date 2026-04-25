<?php
session_start();
require_once '../config/koneksi.php';
require_once '../includes/layout.php';
cekLogin();

$errors = [];
$input  = ['tanggal'=>date('Y-m-d'),'jenis'=>'pemasukan','kategori_id'=>'','jumlah'=>'','keterangan'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input['tanggal']     = trim($_POST['tanggal']     ?? '');
    $input['jenis']       = trim($_POST['jenis']       ?? '');
    $input['kategori_id'] = trim($_POST['kategori_id'] ?? '');
    $input['jumlah']      = trim($_POST['jumlah']      ?? '');
    $input['keterangan']  = trim($_POST['keterangan']  ?? '');

    // Validasi
    if (empty($input['tanggal']))                  $errors[] = 'Tanggal wajib diisi.';
    if (!in_array($input['jenis'], ['pemasukan','pengeluaran'])) $errors[] = 'Jenis tidak valid.';
    if (empty($input['kategori_id']))              $errors[] = 'Kategori wajib dipilih.';
    $jumlahClean = (float) str_replace(['.', ','], ['', '.'], $input['jumlah']);
    if ($jumlahClean <= 0)                         $errors[] = 'Jumlah harus lebih dari 0.';

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO transaksi (tanggal,jenis,kategori_id,jumlah,keterangan) VALUES (?,?,?,?,?)");
        $stmt->execute([$input['tanggal'], $input['jenis'], $input['kategori_id'], $jumlahClean, $input['keterangan']]);
        setFlash('success', 'Transaksi berhasil ditambahkan!');
        redirect('/umkm-keuangan/transaksi/index.php');
    }
}

// Ambil kategori sesuai jenis
$stmt = $pdo->query("SELECT * FROM kategori ORDER BY jenis, nama_kategori");
$semuaKategori = $stmt->fetchAll();

renderHead('Tambah Transaksi');
?>
<div class="flex min-h-screen">
    <?php renderSidebar('transaksi'); ?>
    <div class="flex-1 flex flex-col min-w-0">
        <?php renderTopbar('Tambah Transaksi'); ?>
        <main class="flex-1 p-6 overflow-auto fade-in">
            <div class="max-w-2xl mx-auto">
                <!-- Breadcrumb -->
                <div class="flex items-center gap-2 text-sm text-slate-500 mb-6">
                    <a href="/umkm-keuangan/transaksi/index.php" class="hover:text-blue-600">Transaksi</a>
                    <span>›</span>
                    <span class="text-slate-700 font-medium">Tambah Transaksi</span>
                </div>

                <?php if (!empty($errors)): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-5">
                    <p class="font-semibold text-sm mb-1">Terdapat kesalahan:</p>
                    <ul class="list-disc list-inside text-sm space-y-0.5">
                        <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <div class="bg-white rounded-2xl card-shadow border border-slate-100 p-6">
                    <form method="POST" id="formTambah">
                        <!-- Jenis toggle -->
                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Jenis Transaksi</label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="jenis-btn cursor-pointer">
                                    <input type="radio" name="jenis" value="pemasukan" class="sr-only" <?= $input['jenis']==='pemasukan'?'checked':'' ?> onchange="updateKategori()">
                                    <div class="jenis-card border-2 rounded-xl p-4 flex items-center gap-3 transition-all <?= $input['jenis']==='pemasukan'?'border-emerald-500 bg-emerald-50':'border-slate-200' ?>">
                                        <div class="w-10 h-10 rounded-xl <?= $input['jenis']==='pemasukan'?'bg-emerald-500':'bg-slate-100' ?> flex items-center justify-center">
                                            <svg class="w-5 h-5 <?= $input['jenis']==='pemasukan'?'text-white':'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="font-semibold text-sm text-slate-700">Pemasukan</div>
                                            <div class="text-xs text-slate-400">Uang masuk</div>
                                        </div>
                                    </div>
                                </label>
                                <label class="jenis-btn cursor-pointer">
                                    <input type="radio" name="jenis" value="pengeluaran" class="sr-only" <?= $input['jenis']==='pengeluaran'?'checked':'' ?> onchange="updateKategori()">
                                    <div class="jenis-card border-2 rounded-xl p-4 flex items-center gap-3 transition-all <?= $input['jenis']==='pengeluaran'?'border-red-500 bg-red-50':'border-slate-200' ?>">
                                        <div class="w-10 h-10 rounded-xl <?= $input['jenis']==='pengeluaran'?'bg-red-500':'bg-slate-100' ?> flex items-center justify-center">
                                            <svg class="w-5 h-5 <?= $input['jenis']==='pengeluaran'?'text-white':'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="font-semibold text-sm text-slate-700">Pengeluaran</div>
                                            <div class="text-xs text-slate-400">Uang keluar</div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                            <!-- Tanggal -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Tanggal <span class="text-red-500">*</span></label>
                                <input type="date" name="tanggal" value="<?= htmlspecialchars($input['tanggal']) ?>"
                                    class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400" required>
                            </div>

                            <!-- Kategori -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Kategori <span class="text-red-500">*</span></label>
                                <select name="kategori_id" id="selectKategori"
                                    class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    <?php foreach ($semuaKategori as $kat): ?>
                                    <option value="<?= $kat['id'] ?>"
                                        data-jenis="<?= $kat['jenis'] ?>"
                                        <?= $input['kategori_id'] == $kat['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($kat['nama_kategori']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Jumlah -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Jumlah <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                    <span class="text-slate-500 text-sm font-medium">Rp</span>
                                </div>
                                <input type="text" name="jumlah" id="jumlahInput" value="<?= htmlspecialchars($input['jumlah']) ?>"
                                    placeholder="0"
                                    class="w-full border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400"
                                    oninput="formatJumlah(this)" required>
                            </div>
                        </div>

                        <!-- Keterangan -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Keterangan</label>
                            <textarea name="keterangan" rows="3" placeholder="Deskripsi singkat transaksi..."
                                class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 resize-none"><?= htmlspecialchars($input['keterangan']) ?></textarea>
                        </div>

                        <div class="flex items-center gap-3">
                            <button type="submit"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-xl text-sm font-semibold transition-colors flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Simpan Transaksi
                            </button>
                            <a href="/umkm-keuangan/transaksi/index.php"
                                class="bg-slate-100 hover:bg-slate-200 text-slate-600 px-6 py-2.5 rounded-xl text-sm font-semibold transition-colors">
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
// Store all kategori data
const semuaKategori = <?= json_encode($semuaKategori) ?>;

function updateKategori() {
    const jenis = document.querySelector('input[name="jenis"]:checked')?.value;
    const select = document.getElementById('selectKategori');
    const current = select.value;
    select.innerHTML = '<option value="">-- Pilih Kategori --</option>';
    semuaKategori.filter(k => k.jenis === jenis).forEach(k => {
        const opt = document.createElement('option');
        opt.value = k.id;
        opt.textContent = k.nama_kategori;
        if (k.id == current) opt.selected = true;
        select.appendChild(opt);
    });

    // Update visual
    document.querySelectorAll('.jenis-card').forEach(card => {
        const radio = card.closest('label').querySelector('input');
        const isSelected = radio.value === jenis;
        if (radio.value === 'pemasukan') {
            card.classList.toggle('border-emerald-500', isSelected);
            card.classList.toggle('bg-emerald-50', isSelected);
            card.classList.toggle('border-slate-200', !isSelected);
            card.querySelector('div>div').classList.toggle('bg-emerald-500', isSelected);
            card.querySelector('div>div').classList.toggle('bg-slate-100', !isSelected);
            card.querySelector('svg').classList.toggle('text-white', isSelected);
            card.querySelector('svg').classList.toggle('text-slate-400', !isSelected);
        } else {
            card.classList.toggle('border-red-500', isSelected);
            card.classList.toggle('bg-red-50', isSelected);
            card.classList.toggle('border-slate-200', !isSelected);
            card.querySelector('div>div').classList.toggle('bg-red-500', isSelected);
            card.querySelector('div>div').classList.toggle('bg-slate-100', !isSelected);
            card.querySelector('svg').classList.toggle('text-white', isSelected);
            card.querySelector('svg').classList.toggle('text-slate-400', !isSelected);
        }
    });
}

function formatJumlah(input) {
    let raw = input.value.replace(/[^\d]/g, '');
    if (raw) input.value = parseInt(raw).toLocaleString('id-ID');
    else input.value = '';
}

// Initial filter
updateKategori();
</script>
</body></html>
