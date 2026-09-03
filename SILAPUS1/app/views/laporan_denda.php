<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Laporan &amp; Denda - Perpustakaan Sekolah</title>
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
            <a href="?page=kelola_peminjaman" class="text-gray-500 hover:text-gray-800">Peminjaman</a>
            <a href="?page=data_anggota" class="text-gray-500 hover:text-gray-800">Anggota</a>
            <span class="font-medium border-b-2 border-orange-500 pb-1">Laporan</span>
            <button onclick="logout()" class="text-gray-500 hover:text-red-600">Keluar</button>
        </div>
    </div>

    <h1 class="text-xl font-medium mb-1">Laporan peminjaman &amp; denda</h1>
    <p class="text-gray-500 text-sm mb-5">Rekap keterlambatan dan denda yang perlu dibayar siswa.</p>

    <!-- Filter -->
    <div class="flex items-end gap-3 mb-5 flex-wrap">
        <div>
            <label class="text-xs text-gray-500 block mb-1">Dari tanggal pinjam</label>
            <input id="filter-from" type="date" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
            <label class="text-xs text-gray-500 block mb-1">Sampai tanggal pinjam</label>
            <input id="filter-to" type="date" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
            <label class="text-xs text-gray-500 block mb-1">Status</label>
            <select id="filter-status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm min-w-[160px]">
                <option value="">Semua status</option>
                <option value="dipinjam">Dipinjam</option>
                <option value="dikembalikan">Dikembalikan</option>
                <option value="terlambat">Terlambat</option>
            </select>
        </div>
        <button onclick="loadLaporan()" class="bg-orange-500 hover:bg-orange-600 text-white text-sm px-4 py-2 rounded-lg">Terapkan</button>
        <button onclick="resetFilter()" class="border border-gray-300 text-gray-600 text-sm px-4 py-2 rounded-lg hover:bg-gray-50">Reset</button>
    </div>

    <!-- Ringkasan -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <div class="bg-orange-50 rounded-xl p-4">
            <p class="text-xs text-orange-800 mb-1">Total peminjaman</p>
            <p id="sum-total" class="text-xl font-medium text-orange-900">-</p>
        </div>
        <div class="bg-blue-50 rounded-xl p-4">
            <p class="text-xs text-blue-800 mb-1">Sudah dikembalikan</p>
            <p id="sum-dikembalikan" class="text-xl font-medium text-blue-900">-</p>
        </div>
        <div class="bg-red-50 rounded-xl p-4">
            <p class="text-xs text-red-800 mb-1">Terlambat</p>
            <p id="sum-terlambat" class="text-xl font-medium text-red-900">-</p>
        </div>
        <div class="bg-pink-50 rounded-xl p-4">
            <p class="text-xs text-pink-800 mb-1">Total denda</p>
            <p id="sum-denda" class="text-xl font-medium text-pink-900">-</p>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 text-gray-500 text-left">
                    <th class="px-4 py-3 font-medium">Siswa</th>
                    <th class="px-4 py-3 font-medium">Judul buku</th>
                    <th class="px-4 py-3 font-medium">Batas kembali</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium text-center">Terlambat</th>
                    <th class="px-4 py-3 font-medium text-right">Denda</th>
                    <th class="px-4 py-3 font-medium text-right">Aksi</th>
                </tr>
            </thead>
            <tbody id="table-laporan" class="divide-y divide-gray-100">
                <tr><td colspan="7" class="px-4 py-6 text-center text-gray-400">Memuat data...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
requireRole('admin');

function formatRupiah(angka) {
    return 'Rp ' + Number(angka || 0).toLocaleString('id-ID');
}

function resetFilter() {
    document.getElementById('filter-from').value = '';
    document.getElementById('filter-to').value = '';
    document.getElementById('filter-status').value = '';
    loadLaporan();
}

async function loadLaporan() {
    const from = document.getElementById('filter-from').value;
    const to = document.getElementById('filter-to').value;
    const status = document.getElementById('filter-status').value;

    const params = new URLSearchParams();
    if (from) params.append('from', from);
    if (to) params.append('to', to);
    if (status) params.append('status', status);

    const res = await apiFetch('/reports.php?' + params.toString());
    if (!res) return;

    if (!res.success) {
        alert(res.message);
        return;
    }

    const { rows, summary } = res.data;

    document.getElementById('sum-total').textContent = summary.total_peminjaman;
    document.getElementById('sum-dikembalikan').textContent = summary.total_dikembalikan;
    document.getElementById('sum-terlambat').textContent = summary.total_terlambat;
    document.getElementById('sum-denda').textContent = formatRupiah(summary.total_denda);

    const tbody = document.getElementById('table-laporan');

    if (rows.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="px-4 py-6 text-center text-gray-400">Tidak ada data.</td></tr>';
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

    tbody.innerHTML = rows.map(l => `
        <tr>
            <td class="px-4 py-3">${escapeHtml(l.nama_siswa)}</td>
            <td class="px-4 py-3">${escapeHtml(l.judul)}</td>
            <td class="px-4 py-3 text-gray-500">${formatTanggal(l.batas_kembali)}</td>
            <td class="px-4 py-3"><span class="text-xs px-2 py-1 rounded ${badgeClass[l.status]}">${badgeLabel[l.status]}</span></td>
            <td class="px-4 py-3 text-center text-gray-500">${l.hari_terlambat_tampil > 0 ? l.hari_terlambat_tampil + ' hari' : '-'}</td>
            <td class="px-4 py-3 text-right font-medium ${l.denda_tampil > 0 ? 'text-pink-700' : 'text-gray-400'}">${l.denda_tampil > 0 ? formatRupiah(l.denda_tampil) : '-'}</td>
            <td class="px-4 py-3 text-right">
                ${l.status === 'dikembalikan' && l.denda > 0
                    ? (l.denda_lunas == 1
                        ? '<span class="text-xs text-emerald-600">Lunas</span>'
                        : `<button onclick="tandaiLunas(${l.id})" class="text-xs border border-gray-300 rounded-lg px-3 py-1 hover:bg-gray-50">Tandai lunas</button>`)
                    : '<span class="text-xs text-gray-300">-</span>'
                }
            </td>
        </tr>
    `).join('');
}

async function tandaiLunas(id) {
    if (!confirm('Tandai denda peminjaman ini sudah lunas?')) return;
    const res = await apiFetch(`/reports.php?id=${id}&action=lunas`, { method: 'PUT' });
    if (res.success) loadLaporan();
    else alert(res.message);
}

loadLaporan();
</script>

</body>
</html>
