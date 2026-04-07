<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();

$pageTitle = 'Riwayat Booking';
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT b.*, l.nama as lapangan_nama FROM bookings b
    JOIN lapangan l ON b.lapangan_id = l.id
    WHERE b.user_id = ? ORDER BY b.created_at DESC
");
$stmt->bind_param("i", $user_id); $stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$statusStyle = [
    'pending'      => ['bg-amber-50 text-amber-700 border-amber-200', 'Menunggu'],
    'dikonfirmasi' => ['bg-green-50 text-green-700 border-green-200', 'Dikonfirmasi'],
    'ditolak'      => ['bg-red-50 text-red-600 border-red-200', 'Ditolak'],
    'selesai'      => ['bg-cream-dark text-muted border-cream-dark', 'Selesai'],
];
$namaBulan = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];

include 'includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <a href="index.php" class="text-sm text-muted hover:text-charcoal transition">&#8592; Kembali</a>
        <h1 class="text-2xl font-bold text-charcoal mt-1">Riwayat Booking</h1>
    </div>
    <a href="booking.php" class="bg-terra text-white px-5 py-2 rounded-full text-sm font-medium hover:bg-terra-dark transition">
        + Booking Baru
    </a>
</div>

<?php if (empty($bookings)): ?>
    <div class="bg-white rounded-2xl border border-cream-dark p-16 text-center">
        <div class="text-4xl mb-3">📅</div>
        <p class="text-muted">Belum ada booking. <a href="booking.php" class="text-terra font-semibold hover:underline">Booking sekarang</a></p>
    </div>
<?php else: ?>
    <div class="space-y-3">
        <?php foreach ($bookings as $b):
            $tgl = new DateTime($b['tanggal']);
            [$styleClass, $label] = $statusStyle[$b['status']];
        ?>
        <div class="bg-white rounded-2xl border border-cream-dark p-5 shadow-sm flex flex-col md:flex-row md:justify-between md:items-center gap-4">
            <div class="flex gap-4 items-start">
                <div class="bg-cream rounded-xl p-3 text-center min-w-[52px]">
                    <div class="text-xl font-bold text-charcoal leading-none"><?= $tgl->format('d') ?></div>
                    <div class="text-xs text-muted mt-0.5"><?= $namaBulan[(int)$tgl->format('m')] ?></div>
                </div>
                <div>
                    <h3 class="font-semibold text-charcoal"><?= htmlspecialchars($b['lapangan_nama']) ?></h3>
                    <p class="text-sm text-muted mt-0.5">
                        <?= substr($b['jam_mulai'],0,5) ?> – <?= substr($b['jam_selesai'],0,5) ?>
                        &middot; <?= $b['durasi'] ?> jam
                    </p>
                    <?php if ($b['catatan']): ?>
                        <p class="text-xs text-muted mt-1 italic">"<?= htmlspecialchars($b['catatan']) ?>"</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <span class="font-bold text-charcoal text-sm">Rp <?= number_format($b['total_harga'],0,',','.') ?></span>
                <span class="text-xs border px-3 py-1 rounded-full <?= $styleClass ?>"><?= $label ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
