<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

if (isLoggedIn()) { header("Location: index.php"); exit; }

$error = ''; $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $konfirmasi = $_POST['konfirmasi'];
    if ($password !== $konfirmasi) {
        $error = 'Password dan konfirmasi tidak cocok.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email); $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'Email sudah terdaftar.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (nama, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $nama, $email, $hash); $stmt->execute();
            $success = 'Akun berhasil dibuat!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - BookingLapangan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: {
                cream: '#FAF6F1', 'cream-dark': '#F0E9DF',
                charcoal: '#1C1C1C', muted: '#8A8070',
                terra: '#E8572A', 'terra-dark': '#C94820', 'terra-light': '#FDEEE8'
            }}}
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-cream min-h-screen font-['Inter'] flex items-center justify-center px-4">
<div class="w-full max-w-md">
    <div class="text-center mb-8">
        <a href="index.php" class="text-2xl font-bold text-charcoal">Booking<span class="text-terra">Lapangan</span></a>
        <p class="text-muted mt-2 text-sm">Buat akun baru</p>
    </div>

    <div class="bg-white rounded-2xl border border-cream-dark p-8 shadow-sm">
        <?php if ($error): ?>
            <div class="bg-terra-light text-terra border border-terra/20 px-4 py-3 rounded-xl mb-5 text-sm"><?= $error ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="bg-green-50 text-green-700 border border-green-200 px-4 py-3 rounded-xl mb-5 text-sm">
                <?= $success ?> <a href="login.php" class="font-semibold underline">Login sekarang</a>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-charcoal mb-1.5">Nama Lengkap</label>
                <input type="text" name="nama" required
                    class="w-full border border-cream-dark bg-cream rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-terra/30 focus:border-terra transition"
                    placeholder="Nama Anda">
            </div>
            <div>
                <label class="block text-sm font-medium text-charcoal mb-1.5">Email</label>
                <input type="email" name="email" required
                    class="w-full border border-cream-dark bg-cream rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-terra/30 focus:border-terra transition"
                    placeholder="email@contoh.com">
            </div>
            <div>
                <label class="block text-sm font-medium text-charcoal mb-1.5">Password</label>
                <input type="password" name="password" required
                    class="w-full border border-cream-dark bg-cream rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-terra/30 focus:border-terra transition"
                    placeholder="Min. 6 karakter">
            </div>
            <div>
                <label class="block text-sm font-medium text-charcoal mb-1.5">Konfirmasi Password</label>
                <input type="password" name="konfirmasi" required
                    class="w-full border border-cream-dark bg-cream rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-terra/30 focus:border-terra transition"
                    placeholder="Ulangi password">
            </div>
            <button type="submit"
                class="w-full bg-terra text-white py-2.5 rounded-xl font-semibold text-sm hover:bg-terra-dark transition mt-2">
                Buat Akun
            </button>
        </form>
    </div>

    <p class="text-center text-sm text-muted mt-5">
        Sudah punya akun? <a href="login.php" class="text-terra font-semibold hover:underline">Login</a>
    </p>
</div>
</body>
</html>
