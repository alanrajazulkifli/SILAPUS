<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Riwayat Peminjaman - Perpustakaan Sekolah</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="<?= htmlspecialchars($publicBaseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/auth-guard.js"></script>
</head>
<body class="bg-gray-50 min-h-screen">

<div class="max-w-4xl mx-auto p-5">

    <!-- Navbar -->
    <div class="flex items-center justify-between bg-white border border-gray-100 rounded-xl px-5 py-3 mb-5">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-orange-200 flex items-center justify-center">📚</div>
            <div>
                <p class="font-medium text-sm">Perpustakaan Sekolah</p>
                <p class="text-xs text-gray-500">Sistem peminjaman buku</p>
            </div>
        </div>
        <div class="flex items-center gap-5 text-sm">
            <a href="?page=peminjaman_siswa" class="text-gray-500 hover:text-gray-800">Peminjaman</a>
            <span class="font-medium border-b-2 border-orange-500 pb-1">Riwayat</span>
            <button onclick="logout()" class="text-gray-500 hover:text-red-600">Keluar</button>
        </div>
    </div>

    <h1 class="text-xl font-medium mb-1">Riwayat peminjaman saya</h1>
    <p class="text-gray-500 text-sm mb-5">Pantau status buku yang sedang atau pernah kamu pinjam.</p>

    <!-- Stat ringkas -->
    <div class="grid grid-cols-3 gap-3 mb-5">
        <div class="bg-blue-50 rounded-xl p-4">
            <p class="text-xs text-blue-800 mb-1">Sedang dipinjam</p>
            <p id="stat-dipinjam" class="text-xl font-medium text-blue-900">-</p>
        </div>
        <div class="bg-amber-50 rounded-xl p-4">
            <p class="text-xs text-amber-800 mb-1">Jatuh tempo &lt; 2 hari</p>
            <p id="stat-jatuh-tempo" class="text-xl font-medium text-amber-900">-</p>
        </div>
        <div class="bg-pink-50 rounded-xl p-4">
            <p class="text-xs text-pink-800 mb-1">Pernah terlambat</p>
            <p id="stat-terlambat" class="text-xl font-medium text-pink-900">-</p>
        </div>
    </div>

    <!-- Filter tab -->
    <div class="flex gap-2 mb-4" id="tab-status">
        <button data-status="" class="tab-btn px-3 py-1.5 text-sm rounded-lg bg-orange-500 text-white">Semua</button>
        <button data-status="dipinjam" class="tab-btn px-3 py-1.5 text-sm rounded-lg border border-gray-300">Dipinjam</button>
        <button data-status="dikembalikan" class="tab-btn px-3 py-1.5 text-sm rounded-lg border border-gray-300">Dikembalikan</button>
        <button data-status="terlambat" class="tab-btn px-3 py-1.5 text-sm rounded-lg border border-gray-300">Terlambat</button>
    </div>

    <!-- List riwayat -->
    <div id="list-riwayat" class="space-y-2.5">
        <p class="text-gray-400 text-sm text-center py-6">Memuat riwayat...</p>
    </div>

</div>

<script>
requireRole('siswa');

let currentStatus = '';

async function loadRiwayat() {
    const params = new URLSearchParams({ mine: 1 });
    if (currentStatus) params.append('status', currentStatus);

    const res = await apiFetch('/loans.php?' + params.toString());
    if (!res) return;

    const loans = res.data || [];
    renderStats(loans);
    renderList(loans);
}

function renderStats(loans) {
    document.getElementById('stat-dipinjam').textContent = loans.filter(l => l.status === 'dipinjam').length;
    document.getElementById('stat-terlambat').textContent = loans.filter(l => l.status === 'terlambat').length;

    const twoDaysFromNow = new Date();
    twoDaysFromNow.setDate(twoDaysFromNow.getDate() + 2);
    const jatuhTempo = loans.filter(l =>
        l.status === 'dipinjam' && new Date(l.batas_kembali) <= twoDaysFromNow
    ).length;
    document.getElementById('stat-jatuh-tempo').textContent = jatuhTempo;
}

function renderList(loans) {
    const list = document.getElementById('list-riwayat');

    if (loans.length === 0) {
        list.innerHTML = '<p class="text-gray-400 text-sm text-center py-6">Belum ada riwayat peminjaman.</p>';
        return;
    }

    const badgeClass = {
        dipinjam: 'bg-amber-50 text-amber-800',
        dikembalikan: 'bg-blue-50 text-blue-800',
        terlambat: 'bg-red-50 text-red-800'
    };
    const badgeLabel = {
        dipinjam: 'Dipinjam',
        dikembalikan: 'Dikembalikan',
        terlambat: 'Terlambat'
    };

    list.innerHTML = loans.map(l => `
        <div class="bg-white border border-gray-100 rounded-xl p-3.5 flex items-center gap-3">
            <div class="w-11 h-11 rounded-lg bg-orange-200 flex items-center justify-center text-lg flex-shrink-0">📖</div>
            <div class="flex-1">
                <p class="font-medium text-sm">${escapeHtml(l.judul)}</p>
                <p class="text-xs text-gray-500">
                    Dipinjam ${formatTanggal(l.tanggal_pinjam)}
                    ${l.status === 'dikembalikan'
                        ? ' · Dikembalikan ' + formatTanggal(l.tanggal_kembali)
                        : ' · Batas kembali ' + formatTanggal(l.batas_kembali)
                    }
                </p>
            </div>
            <span class="text-xs px-2.5 py-1 rounded whitespace-nowrap ${badgeClass[l.status]}">${badgeLabel[l.status]}</span>
        </div>
    `).join('');
}

document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn').forEach(b => b.className = 'tab-btn px-3 py-1.5 text-sm rounded-lg border border-gray-300');
        btn.className = 'tab-btn px-3 py-1.5 text-sm rounded-lg bg-orange-500 text-white';
        currentStatus = btn.dataset.status;
        loadRiwayat();
    });
});

loadRiwayat();
</script>

</body>
</html>
