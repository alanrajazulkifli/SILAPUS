<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Peminjaman - Perpustakaan Sekolah</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="<?= htmlspecialchars($publicBaseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/auth-guard.js"></script>
</head>
<body class="bg-gray-50 min-h-screen">

<div class="max-w-6xl mx-auto p-5">

    <!-- Navbar -->
    <div class="flex items-center justify-between bg-white border border-gray-100 rounded-xl px-5 py-3 mb-5">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-orange-200 flex items-center justify-center">📚</div>
            <div>
                <p class="font-medium text-sm">Perpustakaan Sekolah</p>
                <p class="text-xs text-gray-500">Panel admin</p>
            </div>
        </div>
        <div class="flex items-center gap-5 text-sm">
            <a href="?page=dashboard_admin" class="text-gray-500 hover:text-gray-800">Dashboard</a>
            <a href="?page=data_buku" class="text-gray-500 hover:text-gray-800">Data buku</a>
            <span class="font-medium border-b-2 border-orange-500 pb-1">Peminjaman</span>
            <button onclick="logout()" class="text-gray-500 hover:text-red-600">Keluar</button>
        </div>
    </div>

    <h1 class="text-xl font-medium mb-1">Kelola peminjaman</h1>
    <p class="text-gray-500 text-sm mb-5">Pantau dan kelola semua transaksi peminjaman buku.</p>

    <!-- Filter tab + search -->
    <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
        <div class="flex gap-2" id="tab-status">
            <button data-status="" class="tab-btn px-3 py-1.5 text-sm rounded-lg bg-orange-500 text-white">Semua</button>
            <button data-status="dipinjam" class="tab-btn px-3 py-1.5 text-sm rounded-lg border border-gray-300">Dipinjam</button>
            <button data-status="dikembalikan" class="tab-btn px-3 py-1.5 text-sm rounded-lg border border-gray-300">Dikembalikan</button>
            <button data-status="terlambat" class="tab-btn px-3 py-1.5 text-sm rounded-lg border border-gray-300">Terlambat</button>
        </div>
        <input id="search" type="text" placeholder="Cari nama siswa atau judul..." class="border border-gray-300 rounded-lg px-3 py-2 text-sm min-w-[220px]">
    </div>

    <!-- Table -->
    <div class="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 text-gray-500 text-left">
                    <th class="px-4 py-3 font-medium">Siswa</th>
                    <th class="px-4 py-3 font-medium">Judul buku</th>
                    <th class="px-4 py-3 font-medium">Tgl pinjam</th>
                    <th class="px-4 py-3 font-medium">Batas kembali</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium text-right">Aksi</th>
                </tr>
            </thead>
            <tbody id="table-peminjaman" class="divide-y divide-gray-100">
                <tr><td colspan="6" class="px-4 py-6 text-center text-gray-400">Memuat data...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
requireRole('admin');

let currentStatus = '';
let allLoans = [];

async function loadLoans() {
    const params = new URLSearchParams();
    if (currentStatus) params.append('status', currentStatus);

    const res = await apiFetch('/loans.php?' + params.toString());
    if (!res) return;

    allLoans = res.data || [];
    renderLoans();
}

function renderLoans() {
    const search = document.getElementById('search').value.toLowerCase();
    const filtered = allLoans.filter(l =>
        l.nama_siswa.toLowerCase().includes(search) || l.judul.toLowerCase().includes(search)
    );

    const tbody = document.getElementById('table-peminjaman');

    if (filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="px-4 py-6 text-center text-gray-400">Tidak ada data.</td></tr>';
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

    tbody.innerHTML = filtered.map(l => `
        <tr>
            <td class="px-4 py-3">${escapeHtml(l.nama_siswa)}</td>
            <td class="px-4 py-3">${escapeHtml(l.judul)}</td>
            <td class="px-4 py-3 text-gray-500">${formatTanggal(l.tanggal_pinjam)}</td>
            <td class="px-4 py-3 text-gray-500">${formatTanggal(l.batas_kembali)}</td>
            <td class="px-4 py-3"><span class="text-xs px-2 py-1 rounded ${badgeClass[l.status]}">${badgeLabel[l.status]}</span></td>
            <td class="px-4 py-3 text-right">
                ${l.status === 'dikembalikan'
                    ? `<span class="text-xs text-gray-400">${formatTanggal(l.tanggal_kembali)}</span>`
                    : `<button onclick="tandaiKembali(${l.id})" class="text-xs border border-gray-300 rounded-lg px-3 py-1 hover:bg-gray-50">Tandai kembali</button>`
                }
            </td>
        </tr>
    `).join('');
}

async function tandaiKembali(id) {
    if (!confirm('Tandai buku ini sebagai sudah dikembalikan?')) return;
    const res = await apiFetch(`/loans.php?id=${id}`, { method: 'PUT' });
    if (res.success) loadLoans();
    else alert(res.message);
}

document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn').forEach(b => b.className = 'tab-btn px-3 py-1.5 text-sm rounded-lg border border-gray-300');
        btn.className = 'tab-btn px-3 py-1.5 text-sm rounded-lg bg-orange-500 text-white';
        currentStatus = btn.dataset.status;
        loadLoans();
    });
});

document.getElementById('search').addEventListener('input', renderLoans);

loadLoans();
</script>

</body>
</html>
