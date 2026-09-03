<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Perpustakaan Sekolah</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">

<div class="w-full max-w-sm bg-white rounded-2xl shadow p-7 border border-gray-100">

    <div class="text-center mb-6">
        <div class="w-12 h-12 rounded-xl bg-orange-200 flex items-center justify-center mx-auto mb-2">
            <span class="text-xl">📚</span>
        </div>
        <p class="font-medium text-lg">Perpustakaan Sekolah</p>
        <p class="text-sm text-gray-500">Masuk untuk melanjutkan</p>
    </div>

    <!-- Tab role -->
    <div class="flex bg-gray-100 rounded-lg p-1 mb-5">
        <button id="tab-siswa" onclick="switchRole('siswa')" class="flex-1 py-2 text-sm rounded-md bg-white shadow border border-gray-200">Siswa</button>
        <button id="tab-admin" onclick="switchRole('admin')" class="flex-1 py-2 text-sm rounded-md text-gray-500">Admin</button>
    </div>

    <form id="form-login" class="space-y-4">
        <div>
            <label class="text-xs text-gray-500 block mb-1">Username</label>
            <input type="text" id="username" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400">
        </div>
        <div>
            <label class="text-xs text-gray-500 block mb-1">Kata sandi</label>
            <input type="password" id="password" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400">
        </div>

        <p id="error-msg" class="text-sm text-red-600 hidden"></p>

        <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white rounded-lg py-2.5 text-sm font-medium transition">Masuk</button>
    </form>

    <p class="text-center text-xs text-gray-500 mt-4">Belum punya akun? <span class="text-orange-600 cursor-pointer">Hubungi admin perpustakaan</span></p>
</div>

<script>
let selectedRole = 'siswa';

function switchRole(role) {
    selectedRole = role;
    document.getElementById('tab-siswa').className = role === 'siswa'
        ? 'flex-1 py-2 text-sm rounded-md bg-white shadow border border-gray-200'
        : 'flex-1 py-2 text-sm rounded-md text-gray-500';
    document.getElementById('tab-admin').className = role === 'admin'
        ? 'flex-1 py-2 text-sm rounded-md bg-white shadow border border-gray-200'
        : 'flex-1 py-2 text-sm rounded-md text-gray-500';
}

document.getElementById('form-login').addEventListener('submit', async function (e) {
    e.preventDefault();
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    const errorMsg = document.getElementById('error-msg');
    errorMsg.classList.add('hidden');

    try {
        const res = await fetch('<?= htmlspecialchars($appBaseUrl, ENT_QUOTES, 'UTF-8') ?>/api/auth.php?action=login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username, password })
        });
        const result = await res.json();

        if (!result.success) {
            errorMsg.textContent = result.message;
            errorMsg.classList.remove('hidden');
            return;
        }

        localStorage.setItem('token', result.data.token);
        localStorage.setItem('user', JSON.stringify(result.data.user));

        // Redirect sesuai role akun yang login (bukan tab yang dipilih)
        window.location.href = result.data.user.role === 'admin'
            ? '?page=dashboard_admin'
            : '?page=peminjaman_siswa';

    } catch (err) {
        errorMsg.textContent = 'Terjadi kesalahan, coba lagi.';
        errorMsg.classList.remove('hidden');
    }
});
</script>

</body>
</html>
