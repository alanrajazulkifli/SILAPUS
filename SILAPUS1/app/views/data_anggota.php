<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Data Anggota - Perpustakaan Sekolah</title>
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
            <span class="font-medium border-b-2 border-orange-500 pb-1">Anggota</span>
            <a href="?page=laporan_denda" class="text-gray-500 hover:text-gray-800">Laporan</a>
            <button onclick="logout()" class="text-gray-500 hover:text-red-600">Keluar</button>
        </div>
    </div>

    <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
        <div>
            <h1 class="text-xl font-medium mb-1">Data anggota</h1>
            <p class="text-gray-500 text-sm">Kelola akun siswa dan admin perpustakaan.</p>
        </div>
        <button onclick="openModal()" class="bg-orange-500 hover:bg-orange-600 text-white text-sm px-4 py-2 rounded-lg">+ Tambah anggota</button>
    </div>

    <!-- Search + filter -->
    <div class="flex gap-3 mb-4 flex-wrap">
        <input id="search" type="text" placeholder="Cari nama atau username..." class="flex-1 min-w-[220px] border border-gray-300 rounded-lg px-3 py-2 text-sm">
        <select id="filter-role" class="border border-gray-300 rounded-lg px-3 py-2 text-sm min-w-[160px]">
            <option value="">Semua role</option>
            <option value="admin">Admin</option>
            <option value="siswa">Siswa</option>
        </select>
    </div>

    <!-- Table -->
    <div class="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 text-gray-500 text-left">
                    <th class="px-4 py-3 font-medium">Nama</th>
                    <th class="px-4 py-3 font-medium">Username</th>
                    <th class="px-4 py-3 font-medium">Role</th>
                    <th class="px-4 py-3 font-medium">Terdaftar</th>
                    <th class="px-4 py-3 font-medium text-right">Aksi</th>
                </tr>
            </thead>
            <tbody id="table-anggota" class="divide-y divide-gray-100">
                <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">Memuat data...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal tambah/edit anggota -->
<div id="modal" class="hidden fixed inset-0 bg-black/45 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl p-6 w-full max-w-sm">
        <div class="flex items-center justify-between mb-4">
            <p id="modal-title" class="font-medium">Tambah anggota baru</p>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-700">&times;</button>
        </div>

        <form id="form-anggota" class="space-y-3">
            <input type="hidden" id="user-id">

            <div>
                <label class="text-xs text-gray-500 block mb-1">Nama lengkap</label>
                <input id="nama" type="text" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Username</label>
                <input id="username" type="text" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Password <span id="password-hint" class="text-gray-400 hidden">(kosongkan jika tidak diubah)</span></label>
                <input id="password" type="password" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Role</label>
                <select id="role" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="siswa">Siswa</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <p id="form-error" class="text-sm text-red-600 hidden"></p>

            <div class="flex gap-2 pt-2">
                <button type="button" onclick="closeModal()" class="flex-1 border border-gray-300 rounded-lg py-2 text-sm">Batal</button>
                <button type="submit" class="flex-1 bg-orange-500 hover:bg-orange-600 text-white rounded-lg py-2 text-sm">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
requireRole('admin');

let currentUser = getUser();
let currentAnggota = [];

async function loadAnggota() {
    const search = document.getElementById('search').value;
    const role = document.getElementById('filter-role').value;

    const params = new URLSearchParams();
    if (search) params.append('search', search);
    if (role) params.append('role', role);

    const res = await apiFetch('/users.php?' + params.toString());
    if (!res) return;

    const tbody = document.getElementById('table-anggota');
    const anggota = res.data || [];
    currentAnggota = anggota;

    if (anggota.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">Belum ada anggota.</td></tr>';
        return;
    }

    const badgeClass = {
        admin: 'bg-orange-50 text-orange-800',
        siswa: 'bg-emerald-50 text-emerald-800'
    };
    const badgeLabel = {
        admin: 'Admin',
        siswa: 'Siswa'
    };

    tbody.innerHTML = anggota.map(u => `
        <tr>
            <td class="px-4 py-3">${escapeHtml(u.nama)}</td>
            <td class="px-4 py-3 text-gray-500">${escapeHtml(u.username)}</td>
            <td class="px-4 py-3"><span class="text-xs px-2 py-1 rounded ${badgeClass[u.role]}">${badgeLabel[u.role]}</span></td>
            <td class="px-4 py-3 text-gray-500">${formatTanggal(u.created_at)}</td>
            <td class="px-4 py-3 text-right">
                <button onclick="editAnggota(${u.id})" class="text-gray-500 hover:text-gray-800 mr-3">Edit</button>
                <button onclick="hapusAnggota(${u.id})" class="text-red-600 hover:text-red-800">Hapus</button>
            </td>
        </tr>
    `).join('');
}

function editAnggota(id) {
    const user = currentAnggota.find(u => u.id == id);
    if (user) openModal(user);
}

function openModal(user = null) {
    document.getElementById('form-error').classList.add('hidden');
    document.getElementById('form-anggota').reset();

    if (user) {
        document.getElementById('modal-title').textContent = 'Edit anggota';
        document.getElementById('user-id').value = user.id;
        document.getElementById('nama').value = user.nama;
        document.getElementById('username').value = user.username;
        document.getElementById('role').value = user.role;
        document.getElementById('password-hint').classList.remove('hidden');
        document.getElementById('password').required = false;
    } else {
        document.getElementById('modal-title').textContent = 'Tambah anggota baru';
        document.getElementById('user-id').value = '';
        document.getElementById('password-hint').classList.add('hidden');
        document.getElementById('password').required = true;
    }

    document.getElementById('modal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('modal').classList.add('hidden');
}

document.getElementById('form-anggota').addEventListener('submit', async function (e) {
    e.preventDefault();

    const id = document.getElementById('user-id').value;
    const payload = {
        nama: document.getElementById('nama').value,
        username: document.getElementById('username').value,
        password: document.getElementById('password').value,
        role: document.getElementById('role').value
    };

    const res = await apiFetch(id ? `/users.php?id=${id}` : '/users.php', {
        method: id ? 'PUT' : 'POST',
        body: JSON.stringify(payload)
    });

    if (!res.success) {
        const err = document.getElementById('form-error');
        err.textContent = res.message;
        err.classList.remove('hidden');
        return;
    }

    closeModal();
    loadAnggota();
});

async function hapusAnggota(id) {
    if (!confirm('Yakin hapus anggota ini?')) return;
    const res = await apiFetch(`/users.php?id=${id}`, { method: 'DELETE' });
    if (res.success) loadAnggota();
    else alert(res.message);
}

document.getElementById('search').addEventListener('input', debounce(loadAnggota, 400));
document.getElementById('filter-role').addEventListener('change', loadAnggota);

function debounce(fn, delay) {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), delay);
    };
}

loadAnggota();
</script>

</body>
</html>
