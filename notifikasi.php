<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();

$pageTitle = 'Notifikasi';
$user_id = $_SESSION['user_id'];

$upd = $conn->prepare("UPDATE notifikasi SET dibaca=1 WHERE user_id=?");
$upd->bind_param("i", $user_id);
$upd->execute();

$stmt = $conn->prepare("
    SELECT n.*, b.tanggal, b.jam_mulai, l.nama as lapangan_nama
    FROM notifikasi n
    JOIN bookings b ON n.booking_id = b.id
    JOIN lapangan l ON b.lapangan_id = l.id
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notifs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include 'includes/header.php';
?>

<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="index.php" class="text-sm text-muted hover:text-charcoal transition">&#8592; Kembali</a>
        <h1 class="text-2xl font-bold text-charcoal mt-1">Notifikasi</h1>
    </div>

    <?php if (empty($notifs)): ?>
        <div class="bg-white rounded-2xl border border-cream-dark p-16 text-center">
            <div class="text-4xl mb-3">🔔</div>
            <p class="text-muted">Tidak ada notifikasi.</p>
        </div>
    <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($notifs as $n): ?>
            <div class="bg-white rounded-2xl border border-cream-dark p-4 flex items-start gap-4 shadow-sm">
                <div class="bg-terra-light text-terra rounded-xl p-2.5 mt-0.5 shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm text-charcoal"><?= htmlspecialchars($n['pesan']) ?></p>
                    <p class="text-xs text-muted mt-1"><?= $n['created_at'] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
