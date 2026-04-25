<?php
session_start();
require_once '../config/koneksi.php';
require_once '../includes/layout.php';
cekLogin();

// Handle delete
if ($_GET['action'] ?? '' === 'hapus') {
    $id = (int)($_GET['id'] ?? 0);
    // Cek apakah kategori digunakan
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM transaksi WHERE kategori_id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        setFlash('error', 'Kategori tidak dapat dihapus karena masih digunakan oleh transaksi!');
    } else {
        $stmt = $pdo->prepare("DELETE FROM kategori WHERE id = ?");
        $stmt->execute([$id]);
        setFlash('success', 'Kategori berhasil dihapus.');
    }
    redirect('/umkm-keuangan/kategori/index.php');
}

// Handle add
$errorsAdd = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'tambah') {
    $nama  = trim($_POST['nama_kategori'] ?? '');
    $jenis = trim($_POST['jenis'] ?? '');
    if (empty($nama))  $errorsAdd[] = 'Nama kategori wajib diisi.';
    if (!in_array($jenis, ['pemasukan','pengeluaran'])) $errorsAdd[] = 'Jenis tidak valid.';
    if (empty($errorsAdd)) {
        $stmt = $pdo->prepare("INSERT INTO kategori (nama_kategori, jenis) VALUES (?,?)");
        $stmt->execute([$nama, $jenis]);
        setFlash('success', "Kategori \"$nama\" berhasil ditambahkan!");
        redirect('/umkm-keuangan/kategori/index.php');
    }
}

// Handle edit
$errorsEdit = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    $id    = (int)($_POST['id'] ?? 0);
    $nama  = trim($_POST['nama_kategori'] ?? '');
    $jenis = trim($_POST['jenis'] ?? '');
    if (empty($nama))  $errorsEdit[] = 'Nama kategori wajib diisi.';
    if (!in_array($jenis, ['pemasukan','pengeluaran'])) $errorsEdit[] = 'Jenis tidak valid.';
    if (empty($errorsEdit) && $id) {
        $stmt = $pdo->prepare("UPDATE kategori SET nama_kategori=?, jenis=? WHERE id=?");
        $stmt->execute([$nama, $jenis, $id]);
        setFlash('success', 'Kategori berhasil diperbarui!');
        redirect('/umkm-keuangan/kategori/index.php');
    }
}

$editId = (int)($_GET['edit'] ?? 0);
$editData = null;
if ($editId) {
    $stmt = $pdo->prepare("SELECT * FROM kategori WHERE id=?");
    $stmt->execute([$editId]);
    $editData = $stmt->fetch();
}

$stmt = $pdo->query("SELECT k.*, COUNT(t.id) AS jumlah_transaksi
    FROM kategori k LEFT JOIN transaksi t ON k.id = t.kategori_id
    GROUP BY k.id ORDER BY k.jenis, k.nama_kategori");
$kategori = $stmt->fetchAll();

renderHead('Kategori');
?>
<div class="flex min-h-screen">
    <?php renderSidebar('kategori'); ?>
    <div class="flex-1 flex flex-col min-w-0">
        <?php renderTopbar('Manajemen Kategori'); ?>
        <main class="flex-1 p-6 overflow-auto fade-in">
            <?php renderFlash(); ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Form -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-2xl card-shadow border border-slate-100 p-6 sticky top-6">
                        <h2 class="text-slate-800 font-bold text-base mb-5">
                            <?= $editData ? 'Edit Kategori' : 'Tambah Kategori' ?>
                        </h2>

                        <?php if (!empty($errorsAdd) || !empty($errorsEdit)): 
                            $errs = !empty($errorsAdd) ? $errorsAdd : $errorsEdit;
                        ?>
                        <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4">
                            <?php foreach ($errs as $e): ?>
                            <p class="text-sm"><?= htmlspecialchars($e) ?></p>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <form method="POST">
                            <?php if ($editData): ?>
                            <input type="hidden" name="id" value="<?= $editData['id'] ?>">
                            <input type="hidden" name="action" value="edit">
                            <?php else: ?>
                            <input type="hidden" name="action" value="tambah">
                            <?php endif; ?>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Nama Kategori <span class="text-red-500">*</span></label>
                                <input type="text" name="nama_kategori"
                                    value="<?= htmlspecialchars($editData['nama_kategori'] ?? $_POST['nama_kategori'] ?? '') ?>"
                                    placeholder="Contoh: Penjualan Produk"
                                    class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400"
                                    required>
                            </div>

                            <div class="mb-6">
                                <label class="block text-sm font-medium text-slate-700 mb-1.5">Jenis <span class="text-red-500">*</span></label>
                                <select name="jenis"
                                    class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400">
                                    <option value="pemasukan"  <?= ($editData['jenis']??$_POST['jenis']??'') === 'pemasukan'  ? 'selected' : '' ?>>Pemasukan</option>
                                    <option value="pengeluaran"<?= ($editData['jenis']??$_POST['jenis']??'') === 'pengeluaran'? 'selected' : '' ?>>Pengeluaran</option>
                                </select>
                            </div>

                            <div class="flex gap-2">
                                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2.5 rounded-xl text-sm font-semibold transition-colors">
                                    <?= $editData ? 'Perbarui' : 'Tambah' ?>
                                </button>
                                <?php if ($editData): ?>
                                <a href="/umkm-keuangan/kategori/index.php" class="flex-1 text-center bg-slate-100 hover:bg-slate-200 text-slate-600 py-2.5 rounded-xl text-sm font-semibold transition-colors">
                                    Batal
                                </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- List -->
                <div class="lg:col-span-2 space-y-5">
                    <!-- Pemasukan -->
                    <div class="bg-white rounded-2xl card-shadow border border-slate-100 overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                            <h3 class="font-bold text-slate-800 text-sm">Kategori Pemasukan</h3>
                            <span class="ml-auto bg-emerald-100 text-emerald-700 text-xs font-semibold px-2 py-0.5 rounded-full">
                                <?= count(array_filter($kategori, fn($k)=>$k['jenis']==='pemasukan')) ?>
                            </span>
                        </div>
                        <div class="divide-y divide-slate-100">
                            <?php $hasMasuk = false; ?>
                            <?php foreach ($kategori as $kat): if ($kat['jenis'] !== 'pemasukan') continue; $hasMasuk = true; ?>
                            <div class="flex items-center px-5 py-3 hover:bg-slate-50/50 group">
                                <div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center mr-3 flex-shrink-0">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <div class="text-sm font-medium text-slate-700"><?= htmlspecialchars($kat['nama_kategori']) ?></div>
                                    <div class="text-xs text-slate-400"><?= $kat['jumlah_transaksi'] ?> transaksi</div>
                                </div>
                                <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <a href="?edit=<?= $kat['id'] ?>" class="w-7 h-7 bg-amber-100 hover:bg-amber-200 text-amber-700 rounded-lg flex items-center justify-center transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <a href="?action=hapus&id=<?= $kat['id'] ?>" onclick="return confirm('Hapus kategori ini?')"
                                        class="w-7 h-7 bg-red-100 hover:bg-red-200 text-red-600 rounded-lg flex items-center justify-center transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php if (!$hasMasuk): ?>
                            <div class="px-5 py-8 text-center text-slate-400 text-sm">Belum ada kategori pemasukan</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Pengeluaran -->
                    <div class="bg-white rounded-2xl card-shadow border border-slate-100 overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-red-500"></div>
                            <h3 class="font-bold text-slate-800 text-sm">Kategori Pengeluaran</h3>
                            <span class="ml-auto bg-red-100 text-red-600 text-xs font-semibold px-2 py-0.5 rounded-full">
                                <?= count(array_filter($kategori, fn($k)=>$k['jenis']==='pengeluaran')) ?>
                            </span>
                        </div>
                        <div class="divide-y divide-slate-100">
                            <?php $hasKeluar = false; ?>
                            <?php foreach ($kategori as $kat): if ($kat['jenis'] !== 'pengeluaran') continue; $hasKeluar = true; ?>
                            <div class="flex items-center px-5 py-3 hover:bg-slate-50/50 group">
                                <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center mr-3 flex-shrink-0">
                                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <div class="text-sm font-medium text-slate-700"><?= htmlspecialchars($kat['nama_kategori']) ?></div>
                                    <div class="text-xs text-slate-400"><?= $kat['jumlah_transaksi'] ?> transaksi</div>
                                </div>
                                <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <a href="?edit=<?= $kat['id'] ?>" class="w-7 h-7 bg-amber-100 hover:bg-amber-200 text-amber-700 rounded-lg flex items-center justify-center transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <a href="?action=hapus&id=<?= $kat['id'] ?>" onclick="return confirm('Hapus kategori ini?')"
                                        class="w-7 h-7 bg-red-100 hover:bg-red-200 text-red-600 rounded-lg flex items-center justify-center transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php if (!$hasKeluar): ?>
                            <div class="px-5 py-8 text-center text-slate-400 text-sm">Belum ada kategori pengeluaran</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
</body></html>
