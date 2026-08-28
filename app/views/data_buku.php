<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Data Buku - Perpustakaan Sekolah</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="assets/auth-guard.js"></script>
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
            <span class="font-medium border-b-2 border-orange-500 pb-1">Data buku</span>
            <a href="?page=kelola_peminjaman" class="text-gray-500 hover:text-gray-800">Peminjaman</a>
            <button onclick="logout()" class="text-gray-500 hover:text-red-600">Keluar</button>
        </div>
    </div>

    <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
        <div>
            <h1 class="text-xl font-medium mb-1">Data buku</h1>
            <p class="text-gray-500 text-sm">Kelola koleksi buku perpustakaan.</p>
        </div>
        <div class="flex gap-2">
            <button onclick="openCategoryModal()" class="bg-white hover:bg-gray-50 text-gray-700 text-sm px-4 py-2 rounded-lg border border-gray-300">+ Kategori</button>
            <button onclick="openModal()" class="bg-orange-500 hover:bg-orange-600 text-white text-sm px-4 py-2 rounded-lg">+ Tambah buku</button>
        </div>
    </div>

    <!-- Search + filter -->
    <div class="flex gap-3 mb-4 flex-wrap">
        <input id="search" type="text" placeholder="Cari judul atau ISBN..." class="flex-1 min-w-[220px] border border-gray-300 rounded-lg px-3 py-2 text-sm">
        <select id="filter-kategori" class="border border-gray-300 rounded-lg px-3 py-2 text-sm min-w-[160px]">
            <option value="">Semua kategori</option>
        </select>
    </div>

    <!-- Table -->
    <div class="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 text-gray-500 text-left">
                    <th class="px-4 py-3 font-medium">Judul buku</th>
                    <th class="px-4 py-3 font-medium">ISBN</th>
                    <th class="px-4 py-3 font-medium">Kategori</th>
                    <th class="px-4 py-3 font-medium text-center">Stok</th>
                    <th class="px-4 py-3 font-medium text-right">Aksi</th>
                </tr>
            </thead>
            <tbody id="table-buku" class="divide-y divide-gray-100">
                <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">Memuat data...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal tambah/edit -->
<div id="modal" class="hidden fixed inset-0 bg-black/45 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl p-6 w-full max-w-sm">
        <div class="flex items-center justify-between mb-4">
            <p id="modal-title" class="font-medium">Tambah buku baru</p>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-700">&times;</button>
        </div>

        <form id="form-buku" class="space-y-3">
            <input type="hidden" id="book-id">

            <div>
                <label class="text-xs text-gray-500 block mb-1">Judul buku</label>
                <input id="judul" type="text" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 block mb-1">Kode ISBN</label>
                <input id="isbn" type="text" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div class="flex gap-3">
                <div class="flex-1">
                    <label class="text-xs text-gray-500 block mb-1">Kategori</label>
                    <select id="category_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></select>
                </div>
                <div class="flex-1">
                    <label class="text-xs text-gray-500 block mb-1">Stok</label>
                    <input id="stok" type="number" min="0" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>

            <p id="form-error" class="text-sm text-red-600 hidden"></p>

            <div class="flex gap-2 pt-2">
                <button type="button" onclick="closeModal()" class="flex-1 border border-gray-300 rounded-lg py-2 text-sm">Batal</button>
                <button type="submit" class="flex-1 bg-orange-500 hover:bg-orange-600 text-white rounded-lg py-2 text-sm">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal tambah kategori -->
<div id="category-modal" class="hidden fixed inset-0 bg-black/45 flex items-center justify-center p-4" style="z-index: 60;">
    <div class="bg-white rounded-2xl p-6 w-full max-w-xs">
        <div class="flex items-center justify-between mb-4">
            <p class="font-medium">Tambah kategori baru</p>
            <button onclick="closeCategoryModal()" class="text-gray-400 hover:text-gray-700">&times;</button>
        </div>

        <form id="form-category" class="space-y-3">
            <div>
                <label class="text-xs text-gray-500 block mb-1">Nama kategori</label>
                <input id="nama_kategori" type="text" required placeholder="Contoh: Fantasi" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>

            <p id="category-form-error" class="text-sm text-red-600 hidden"></p>

            <div class="flex gap-2 pt-2">
                <button type="button" onclick="closeCategoryModal()" class="flex-1 border border-gray-300 rounded-lg py-2 text-sm">Batal</button>
                <button type="submit" class="flex-1 bg-orange-500 hover:bg-orange-600 text-white rounded-lg py-2 text-sm">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
requireRole('admin');

let categories = [];
let currentBooks = [];

async function loadCategories(selectNewestId = null) {
    const res = await apiFetch('/categories.php');
    if (!res) return;
    categories = res.data || [];

    const filterEl = document.getElementById('filter-kategori');
    const selectEl = document.getElementById('category_id');

    filterEl.innerHTML = '<option value="">Semua kategori</option>';
    selectEl.innerHTML = '';

    categories.forEach(c => {
        filterEl.innerHTML += `<option value="${c.id}">${escapeHtml(c.nama_kategori)}</option>`;
        selectEl.innerHTML += `<option value="${c.id}">${escapeHtml(c.nama_kategori)}</option>`;
    });

    if (selectNewestId) {
        selectEl.value = selectNewestId;
    }
}

function openCategoryModal() {
    document.getElementById('category-form-error').classList.add('hidden');
    document.getElementById('form-category').reset();
    document.getElementById('category-modal').classList.remove('hidden');
}

function closeCategoryModal() {
    document.getElementById('category-modal').classList.add('hidden');
}

document.getElementById('form-category').addEventListener('submit', async function (e) {
    e.preventDefault();

    const namaKategori = document.getElementById('nama_kategori').value;

    const res = await apiFetch('/categories.php', {
        method: 'POST',
        body: JSON.stringify({ nama_kategori: namaKategori })
    });

    if (!res.success) {
        const err = document.getElementById('category-form-error');
        err.textContent = res.message;
        err.classList.remove('hidden');
        return;
    }

    closeCategoryModal();
    // Refresh dropdown kategori di form buku, langsung pilih kategori yang baru dibuat
    await loadCategories(res.data.id);
});

async function loadBooks() {
    const search = document.getElementById('search').value;
    const categoryId = document.getElementById('filter-kategori').value;

    const params = new URLSearchParams();
    if (search) params.append('search', search);
    if (categoryId) params.append('category_id', categoryId);

    const res = await apiFetch('/books.php?' + params.toString());
    if (!res) return;

    const tbody = document.getElementById('table-buku');
    const books = res.data || [];
    currentBooks = books;

    if (books.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">Belum ada buku.</td></tr>';
        return;
    }

    tbody.innerHTML = books.map(b => `
        <tr>
            <td class="px-4 py-3">${escapeHtml(b.judul)}</td>
            <td class="px-4 py-3 text-gray-500">${escapeHtml(b.isbn)}</td>
            <td class="px-4 py-3"><span class="text-xs px-2 py-1 rounded bg-orange-50 text-orange-800">${escapeHtml(b.nama_kategori)}</span></td>
            <td class="px-4 py-3 text-center ${b.stok == 0 ? 'text-red-600' : ''}">${b.stok}</td>
            <td class="px-4 py-3 text-right">
                <button onclick="editBuku(${b.id})" class="text-gray-500 hover:text-gray-800 mr-3">Edit</button>
                <button onclick="hapusBuku(${b.id})" class="text-red-600 hover:text-red-800">Hapus</button>
            </td>
        </tr>
    `).join('');
}

function editBuku(id) {
    const book = currentBooks.find(b => b.id == id);
    if (book) openModal(book);
}

function openModal(book = null) {
    document.getElementById('form-error').classList.add('hidden');
    document.getElementById('form-buku').reset();

    if (book) {
        document.getElementById('modal-title').textContent = 'Edit buku';
        document.getElementById('book-id').value = book.id;
        document.getElementById('judul').value = book.judul;
        document.getElementById('isbn').value = book.isbn;
        document.getElementById('category_id').value = book.category_id;
        document.getElementById('stok').value = book.stok;
    } else {
        document.getElementById('modal-title').textContent = 'Tambah buku baru';
        document.getElementById('book-id').value = '';
    }

    document.getElementById('modal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('modal').classList.add('hidden');
}

document.getElementById('form-buku').addEventListener('submit', async function (e) {
    e.preventDefault();

    const id = document.getElementById('book-id').value;
    const payload = {
        judul: document.getElementById('judul').value,
        isbn: document.getElementById('isbn').value,
        category_id: document.getElementById('category_id').value,
        stok: document.getElementById('stok').value
    };

    const res = await apiFetch(id ? `/books.php?id=${id}` : '/books.php', {
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
    loadBooks();
});

async function hapusBuku(id) {
    if (!confirm('Yakin hapus buku ini?')) return;
    const res = await apiFetch(`/books.php?id=${id}`, { method: 'DELETE' });
    if (res.success) loadBooks();
    else alert(res.message);
}

document.getElementById('search').addEventListener('input', debounce(loadBooks, 400));
document.getElementById('filter-kategori').addEventListener('change', loadBooks);

function debounce(fn, delay) {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), delay);
    };
}

loadCategories().then(loadBooks);
</script>

</body>
</html>
