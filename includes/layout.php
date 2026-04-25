<?php
// includes/layout.php - Shared layout functions
function renderHead($title) {
    global $appName;
    echo '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . ' — ' . APP_NAME . '</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: \'Plus Jakarta Sans\', sans-serif; }
        .sidebar-link.active { background: linear-gradient(90deg, rgba(59,130,246,0.2), transparent); border-left: 3px solid #3b82f6; }
        .sidebar-link:not(.active):hover { background: rgba(255,255,255,0.05); }
        .card-shadow { box-shadow: 0 4px 24px rgba(0,0,0,0.06); }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        .fade-in { animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
        #sidebar { transition: transform 0.3s ease; }
        @media (max-width: 768px) {
            #sidebar { position: fixed; z-index: 50; transform: translateX(-100%); }
            #sidebar.open { transform: translateX(0); }
        }
    </style>
</head>
<body class="bg-slate-50 min-h-screen">';
}

function renderSidebar($activePage) {
    $user = $_SESSION['nama_lengkap'] ?? 'Admin';
    $menu = [
        ['href' => '/umkm-keuangan/dashboard/index.php',  'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'label' => 'Dashboard', 'key' => 'dashboard'],
        ['href' => '/umkm-keuangan/transaksi/index.php',  'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 'label' => 'Transaksi', 'key' => 'transaksi'],
        ['href' => '/umkm-keuangan/kategori/index.php',   'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z', 'label' => 'Kategori', 'key' => 'kategori'],
        ['href' => '/umkm-keuangan/laporan/index.php',    'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'label' => 'Laporan', 'key' => 'laporan'],
    ];

    echo '<div id="sidebar" class="w-64 min-h-screen bg-slate-900 flex flex-col">
        <!-- Logo -->
        <div class="px-6 py-6 border-b border-slate-700/50">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <div class="text-white font-bold text-sm leading-tight">KeuanganKu</div>
                    <div class="text-slate-400 text-xs">UMKM</div>
                </div>
            </div>
        </div>

        <!-- Nav -->
        <nav class="flex-1 px-3 py-4 space-y-1">
            <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider px-3 mb-3">Menu Utama</p>';

    foreach ($menu as $item) {
        $isActive = $activePage === $item['key'] ? 'active' : '';
        $textColor = $activePage === $item['key'] ? 'text-blue-400' : 'text-slate-400';
        $labelColor = $activePage === $item['key'] ? 'text-white font-semibold' : 'text-slate-300';
        echo '<a href="' . $item['href'] . '" class="sidebar-link ' . $isActive . ' flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all">
            <svg class="w-5 h-5 ' . $textColor . ' flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="' . $item['icon'] . '"/>
            </svg>
            <span class="text-sm ' . $labelColor . '">' . $item['label'] . '</span>
        </a>';
    }

    echo '</nav>

        <!-- User info -->
        <div class="px-4 py-4 border-t border-slate-700/50">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center flex-shrink-0">
                    <span class="text-white text-xs font-bold">' . strtoupper(substr($user, 0, 1)) . '</span>
                </div>
                <div class="min-w-0">
                    <div class="text-white text-sm font-medium truncate">' . htmlspecialchars($user) . '</div>
                    <div class="text-slate-400 text-xs">Administrator</div>
                </div>
            </div>
            <a href="/umkm-keuangan/auth/logout.php" class="flex items-center gap-2 text-slate-400 hover:text-red-400 transition-colors text-sm px-1 py-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                Keluar
            </a>
        </div>
    </div>';
}

function renderTopbar($pageTitle) {
    echo '<div class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 sticky top-0 z-30">
        <div class="flex items-center gap-4">
            <button onclick="document.getElementById(\'sidebar\').classList.toggle(\'open\')" class="md:hidden text-slate-500 hover:text-slate-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <h1 class="text-slate-800 font-bold text-lg">' . htmlspecialchars($pageTitle) . '</h1>
        </div>
        <div class="text-slate-500 text-sm">' . date('l, d F Y') . '</div>
    </div>';
}

function renderFlash() {
    $flash = getFlash();
    if ($flash) {
        $bg = $flash['type'] === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800';
        $icon = $flash['type'] === 'success'
            ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>'
            : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>';
        echo '<div id="flashMsg" class="' . $bg . ' border rounded-xl px-4 py-3 mb-6 flex items-center gap-2 fade-in">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path ' . $icon . '/></svg>
            <span class="text-sm font-medium">' . htmlspecialchars($flash['message']) . '</span>
            <button onclick="this.parentElement.remove()" class="ml-auto opacity-60 hover:opacity-100">✕</button>
        </div>
        <script>setTimeout(()=>{const el=document.getElementById(\'flashMsg\');if(el)el.remove();},4000);</script>';
    }
}
