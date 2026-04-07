<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireAdmin();

$pageTitle = 'Kelola Booking';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    $booking_id = (int)$_POST['booking_id'];
    $aksi = $_POST['aksi'];
    $statusMap = ['konfirmasi'=>'dikonfirmasi','tolak'=>'ditolak','selesai'=>'selesai'];
    if (isset($statusMap[$aksi])) {
        $newStatus = $statusMap[$aksi];
        $stmt = $conn->prepare("UPDATE bookings SET status=? WHERE id=?");
        $stmt->bind_param("si", $newStatus, $booking_id); $stmt->execute();
        $bStmt = $conn->prepare("SELECT user_id, tanggal, jam_mulai FROM bookings WHERE id=?");
        $bStmt->bind_param("i", $booking_id); $bStmt->execute();
        $bData = $bStmt->get_result()->fetch_assoc();
        $jam = substr($bData['jam_mulai'],0,5);
        $pesanMap = [
            'dikonfirmasi' => "Booking Anda pada {$bData['tanggal']} pukul $jam telah DIKONFIRMASI.",
            'ditolak'      => "Booking Anda pada {$bData['tanggal']} pukul $jam telah DITOLAK.",
            'selesai'      => "Booking Anda pada {$bData['tanggal']} pukul $jam telah selesai.",
        ];
        kirimNotifikasi($conn, $bData['user_id'], $booking_id, $pesanMap[$newStatus]);
    }
    header("Location: bookings.php"); exit;
}

$filterStatus = $_GET['status'] ?? '';
$where = $filterStatus ? "WHERE b.status = '$filterStatus'" : '';
$bookings = $conn->query("
    SELECT b.*, u.nama as user_nama, u.email as user_email, l.nama as lapangan_nama
    FROM bookings b JOIN users u ON b.user_id=u.id JOIN lapangan l ON b.lapangan_id=l.id
    $where ORDER BY b.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$statusStyle = [
    'pending'      => 'bg-amber-50 text-amber-700 border-amber-200',
    'dikonfirmasi' => 'bg-green-50 text-green-700 border-green-200',
    'ditolak'      => 'bg-red-50 text-red-600 border-red-200',
    'selesai'      => 'bg-gray-100 text-gray-500 border-gray-200',
];

include '../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <a href="index.php" class="text-sm text-muted hover:text-charcoal transition">&#8592; Dashboard</a>
        <h1 class="text-2xl font-bold text-charcoal mt-1">Kelola Booking</h1>
    </div>
</div>

<!-- Filter tabs -->
<div class="flex gap-2 mb-6 flex-wrap">
    <?php foreach ([''=>'Semua','pending'=>'Menunggu','dikonfirmasi'=>'Dikonfirmasi','ditolak'=>'Ditolak','selesai'=>'Selesai'] as $val => $label): ?>
        <a href="?status=<?= $val ?>"
           class="px-4 py-1.5 rounded-full text-sm font-medium transition border
           <?= $filterStatus === $val
               ? 'bg-charcoal text-cream border-charcoal'
               : 'bg-white text-muted border-cream-dark hover:border-charcoal hover:text-charcoal' ?>">
            <?= $label ?>
        </a>
    <?php endforeach; ?>
</div>

<?php if (empty($bookings)): ?>
    <div class="bg-white rounded-2xl border border-cream-dark p-16 text-center">
        <div class="text-4xl mb-3">📭</div>
        <p class="text-muted">Tidak ada booking.</p>
    </div>
<?php endif; ?>

<div class="space-y-3">
    <?php foreach ($bookings as $b): ?>
    <div class="bg-white rounded-2xl border border-cream-dark p-5 shadow-sm">
        <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-4">
            <div class="flex gap-4 items-start">
                <div class="bg-cream rounded-xl p-3 text-center min-w-[52px] shrink-0">
                    <div class="text-lg font-bold text-charcoal leading-none"><?= date('d', strtotime($b['tanggal'])) ?></div>
                    <div class="text-xs text-muted"><?= date('M', strtotime($b['tanggal'])) ?></div>
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-semibold text-charcoal"><?= htmlspecialchars($b['user_nama']) ?></span>
                        <span class="text-xs text-muted"><?= htmlspecialchars($b['user_email']) ?></span>
                    </div>
                    <p class="text-sm text-muted mt-0.5"><?= htmlspecialchars($b['lapangan_nama']) ?></p>
                    <p class="text-sm text-muted"><?= substr($b['jam_mulai'],0,5) ?> – <?= substr($b['jam_selesai'],0,5) ?> &middot; <?= $b['durasi'] ?> jam &middot; <span class="font-medium text-charcoal">Rp <?= number_format($b['total_harga'],0,',','.') ?></span></p>
                    <?php if ($b['catatan']): ?>
                        <p class="text-xs text-muted italic mt-1">"<?= htmlspecialchars($b['catatan']) ?>"</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="flex items-center gap-3 shrink-0">
                <span class="text-xs border px-3 py-1 rounded-full <?= $statusStyle[$b['status']] ?>">
                    <?= ucfirst($b['status']) ?>
                </span>
                <?php if ($b['status'] === 'pending'): ?>
                <form method="POST" class="flex gap-2">
                    <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                    <button name="aksi" value="konfirmasi"
                        class="bg-charcoal text-cream px-4 py-1.5 rounded-full text-xs font-medium hover:bg-charcoal-light transition">
                        Konfirmasi
                    </button>
                    <button name="aksi" value="tolak"
                        class="border border-red-300 text-red-500 px-4 py-1.5 rounded-full text-xs font-medium hover:bg-red-50 transition">
                        Tolak
                    </button>
                </form>
                <?php elseif ($b['status'] === 'dikonfirmasi'): ?>
                <form method="POST">
                    <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                    <button name="aksi" value="selesai"
                        class="border border-cream-dark text-muted px-4 py-1.5 rounded-full text-xs font-medium hover:bg-cream-dark transition">
                        Tandai Selesai
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php include '../includes/footer.php'; ?>
