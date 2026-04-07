<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireAdmin();

$pageTitle = 'Dashboard Admin';

$totalBooking = $conn->query("SELECT COUNT(*) as c FROM bookings")->fetch_assoc()['c'];
$pending      = $conn->query("SELECT COUNT(*) as c FROM bookings WHERE status='pending'")->fetch_assoc()['c'];
$dikonfirmasi = $conn->query("SELECT COUNT(*) as c FROM bookings WHERE status='dikonfirmasi'")->fetch_assoc()['c'];
$totalUser    = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='user'")->fetch_assoc()['c'];

$bookings = $conn->query("
    SELECT b.*, u.nama as user_nama, l.nama as lapangan_nama
    FROM bookings b JOIN users u ON b.user_id=u.id JOIN lapangan l ON b.lapangan_id=l.id
    ORDER BY b.created_at DESC LIMIT 8
")->fetch_all(MYSQLI_ASSOC);

$statusStyle = [
    'pending'      => 'bg-amber-50 text-amber-700 border-amber-200',
    'dikonfirmasi' => 'bg-green-50 text-green-700 border-green-200',
    'ditolak'      => 'bg-red-50 text-red-600 border-red-200',
    'selesai'      => 'bg-gray-100 text-gray-500 border-gray-200',
];

include '../includes/header.php';
?>

<div class="flex justify-between items-center mb-8">
    <div>
        <p class="text-sm text-muted">Selamat datang, <?= htmlspecialchars($_SESSION['nama']) ?></p>
        <h1 class="text-2xl font-bold text-charcoal">Dashboard Admin</h1>
    </div>
    <a href="../index.php" class="text-sm border border-cream-dark text-muted px-4 py-2 rounded-full hover:bg-cream-dark transition">
        Lihat Website ↗
    </a>
</div>

<!-- Stats -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <?php
    $stats = [
        ['Total Booking', $totalBooking, '📋'],
        ['Menunggu', $pending, '⏳'],
        ['Dikonfirmasi', $dikonfirmasi, '✅'],
        ['Total User', $totalUser, '👤'],
    ];
    foreach ($stats as [$label, $val, $icon]):
    ?>
    <div class="bg-white rounded-2xl border border-cream-dark p-5 shadow-sm">
        <div class="text-2xl mb-2"><?= $icon ?></div>
        <div class="text-2xl font-bold text-charcoal"><?= $val ?></div>
        <div class="text-sm text-muted mt-0.5"><?= $label ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Menu -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
    <a href="bookings.php" class="bg-charcoal text-cream rounded-2xl p-5 flex items-center gap-4 hover:bg-charcoal-light transition">
        <span class="text-2xl">📅</span>
        <div>
            <div class="font-semibold">Kelola Booking</div>
            <div class="text-cream/60 text-sm">Konfirmasi & tolak booking</div>
        </div>
    </a>
    <a href="jadwal.php" class="bg-terra text-white rounded-2xl p-5 flex items-center gap-4 hover:bg-terra-dark transition">
        <span class="text-2xl">🏟️</span>
        <div>
            <div class="font-semibold">Kelola Lapangan</div>
            <div class="text-white/70 text-sm">Tambah & edit lapangan</div>
        </div>
    </a>
    <a href="jadwal.php" class="bg-white border border-cream-dark rounded-2xl p-5 flex items-center gap-4 hover:border-terra/40 transition">
        <span class="text-2xl">🗓️</span>
        <div>
            <div class="font-semibold text-charcoal">Jadwal</div>
            <div class="text-muted text-sm">Lihat semua jadwal</div>
        </div>
    </a>
    <a href="laporan.php" class="bg-white border border-cream-dark rounded-2xl p-5 flex items-center gap-4 hover:border-terra/40 transition">
        <span class="text-2xl">📊</span>
        <div>
            <div class="font-semibold text-charcoal">Laporan</div>
            <div class="text-muted text-sm">Chart & export PDF</div>
        </div>
    </a>
</div>

<!-- Tabel booking terbaru -->
<div class="bg-white rounded-2xl border border-cream-dark shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-cream-dark flex justify-between items-center">
        <h2 class="font-semibold text-charcoal">Booking Terbaru</h2>
        <a href="bookings.php" class="text-sm text-terra hover:underline">Lihat semua</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-muted text-xs border-b border-cream-dark bg-cream">
                    <th class="px-6 py-3 font-medium">User</th>
                    <th class="px-6 py-3 font-medium">Lapangan</th>
                    <th class="px-6 py-3 font-medium">Tanggal</th>
                    <th class="px-6 py-3 font-medium">Jam</th>
                    <th class="px-6 py-3 font-medium">Total</th>
                    <th class="px-6 py-3 font-medium">Status</th>
                    <th class="px-6 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-cream-dark">
                <?php foreach ($bookings as $b): ?>
                <tr class="hover:bg-cream/50 transition">
                    <td class="px-6 py-3 font-medium text-charcoal"><?= htmlspecialchars($b['user_nama']) ?></td>
                    <td class="px-6 py-3 text-muted"><?= htmlspecialchars($b['lapangan_nama']) ?></td>
                    <td class="px-6 py-3 text-muted"><?= $b['tanggal'] ?></td>
                    <td class="px-6 py-3 text-muted"><?= substr($b['jam_mulai'],0,5) ?>–<?= substr($b['jam_selesai'],0,5) ?></td>
                    <td class="px-6 py-3 font-medium">Rp <?= number_format($b['total_harga'],0,',','.') ?></td>
                    <td class="px-6 py-3">
                        <span class="text-xs border px-2.5 py-1 rounded-full <?= $statusStyle[$b['status']] ?>">
                            <?= ucfirst($b['status']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-3">
                        <a href="bookings.php" class="text-terra text-xs hover:underline">Detail</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
