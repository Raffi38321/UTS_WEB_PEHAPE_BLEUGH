<?php
$isAdmin = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
$base = $isAdmin ? '../' : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'BookingLapangan' ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        cream: '#FAF6F1',
                        'cream-dark': '#F0E9DF',
                        charcoal: '#1C1C1C',
                        'charcoal-light': '#3D3D3D',
                        terra: '#E8572A',
                        'terra-dark': '#C94820',
                        'terra-light': '#FDEEE8',
                        muted: '#8A8070',
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-cream min-h-screen font-sans text-charcoal">

<nav class="bg-cream border-b border-cream-dark sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
        <a href="<?= $base ?>index.php" class="font-bold text-xl tracking-tight text-charcoal">
            Booking<span class="text-terra">Lapangan</span>
        </a>
        <div class="flex items-center gap-5">
            <?php if (isLoggedIn()): ?>
                <?php $notifCount = countNotifikasi($conn, $_SESSION['user_id']); ?>
                <a href="<?= $base ?>notifikasi.php" class="relative text-muted hover:text-charcoal transition">
                    <i class="fas fa-bell text-base"></i>
                    <?php if ($notifCount > 0): ?>
                        <span class="absolute -top-1.5 -right-1.5 bg-terra text-white text-xs rounded-full w-4 h-4 flex items-center justify-center leading-none"><?= $notifCount ?></span>
                    <?php endif; ?>
                </a>
                <?php if (isAdmin()): ?>
                    <a href="<?= $base ?>admin/index.php" class="text-sm text-muted hover:text-charcoal transition">
                        <i class="fas fa-cog mr-1"></i>Admin
                    </a>
                <?php endif; ?>
                <span class="text-sm text-muted"><?= htmlspecialchars($_SESSION['nama']) ?></span>
                <a href="<?= $base ?>logout.php" class="text-sm border border-charcoal text-charcoal px-4 py-1.5 rounded-full hover:bg-charcoal hover:text-cream transition">Logout</a>
            <?php else: ?>
                <a href="<?= $base ?>login.php" class="text-sm text-muted hover:text-charcoal transition">Login</a>
                <a href="<?= $base ?>register.php" class="text-sm bg-terra text-white px-5 py-2 rounded-full hover:bg-terra-dark transition font-medium">Daftar</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<div class="max-w-7xl mx-auto px-6 py-8">
