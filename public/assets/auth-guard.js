// Helper autentikasi & fetch API, dipakai bersama oleh semua halaman view.
// Path di file ini relative terhadap URL public/index.php (front controller),
// BUKAN terhadap lokasi fisik file ini di disk (public/assets/auth-guard.js).

// public/ ada 1 level di bawah root proyek, api/ ada di root -> cukup 1x '../'
const API_BASE = '../api';

function getToken() {
    return localStorage.getItem('token');
}

function getUser() {
    return JSON.parse(localStorage.getItem('user') || 'null');
}

function logout() {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    window.location.href = '?page=login';
}

// Pastikan user sudah login & role sesuai, kalau tidak redirect ke login.
function requireRole(requiredRole) {
    const token = getToken();
    const user = getUser();

    if (!token || !user) {
        window.location.href = '?page=login';
        return null;
    }

    if (requiredRole && user.role !== requiredRole) {
        window.location.href = user.role === 'admin' ? '?page=dashboard_admin' : '?page=peminjaman_siswa';
        return null;
    }

    return user;
}

// Wrapper fetch yang otomatis menyertakan header Authorization: Bearer <token>.
// PENTING: function ini didesain supaya TIDAK PERNAH throw tanpa tertangkap -
// kalau fetch gagal (server mati, path salah) atau response bukan JSON valid
// (biasanya PHP fatal error), tetap balikin object {success:false, message:...}
// yang konsisten, supaya halaman yang manggil nggak macet selamanya di
// "Memuat data..." tanpa penjelasan apa-apa.
async function apiFetch(path, options = {}) {
    const token = getToken();
    const headers = Object.assign(
        { 'Content-Type': 'application/json' },
        options.headers || {},
        token ? { Authorization: 'Bearer ' + token } : {}
    );

    let res;
    try {
        res = await fetch(API_BASE + path, Object.assign({}, options, { headers }));
    } catch (networkErr) {
        // fetch() sendiri gagal total - server PHP nggak jalan, path API_BASE
        // salah, atau masalah jaringan/CORS.
        return {
            success: false,
            message: 'Gagal terhubung ke server. Pastikan server PHP aktif dan path API benar.',
        };
    }

    if (res.status === 401) {
        logout();
        return null;
    }

    try {
        return await res.json();
    } catch (parseErr) {
        // Response diterima tapi bukan JSON valid - biasanya karena PHP
        // fatal error yang keluar sebagai HTML/teks, bukan JSON.
        return {
            success: false,
            message: 'Server mengembalikan respons yang tidak valid (kemungkinan ada error di backend PHP). Cek tab Network di DevTools untuk detail.',
        };
    }
}

function formatTanggal(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}

// Mencegah data dari database (judul, ISBN, nama, dst) merusak HTML saat mengandung
// karakter seperti < > " ' &
function escapeHtml(value) {
    if (value === null || value === undefined) return '';
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
