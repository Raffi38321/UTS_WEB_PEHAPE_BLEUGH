<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header("Location: " . (isAdmin() ? "admin/index.php" : "index.php"));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['role'] = $user['role'];
        header("Location: " . ($user['role'] === 'admin' ? 'admin/index.php' : 'index.php'));
        exit;
    } else {
        $error = 'Email atau password salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BookingLapangan</title>
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
        <p class="text-muted mt-2 text-sm">Masuk ke akun Anda</p>
    </div>

    <div class="bg-white rounded-2xl border border-cream-dark p-8 shadow-sm">
        <?php if ($error): ?>
            <div class="bg-terra-light text-terra border border-terra/20 px-4 py-3 rounded-xl mb-5 text-sm"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
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
                    placeholder="••••••••">
            </div>
            <button type="submit"
                class="w-full bg-terra text-white py-2.5 rounded-xl font-semibold text-sm hover:bg-terra-dark transition mt-2">
                Masuk
            </button>
        </form>
    </div>

    <p class="text-center text-sm text-muted mt-5">
        Belum punya akun? <a href="register.php" class="text-terra font-semibold hover:underline">Daftar sekarang</a>
    </p>
</div>
</body>
</html>
