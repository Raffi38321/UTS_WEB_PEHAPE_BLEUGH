<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();

$pageTitle = 'Booking Lapangan';
$error = ''; $success = '';

$lapangan = $conn->query("SELECT * FROM lapangan WHERE status='aktif'")->fetch_all(MYSQLI_ASSOC);
$tanggal = $_GET['tanggal'] ?? date('Y-m-d');
$lapangan_id = $_GET['lapangan_id'] ?? '';
if ($tanggal < date('Y-m-d')) $tanggal = date('Y-m-d');

function getJamTerpakai($conn, $lapangan_id, $tanggal) {
    if (!$lapangan_id) return [];
    $stmt = $conn->prepare("SELECT jam_mulai, jam_selesai FROM bookings WHERE lapangan_id=? AND tanggal=? AND status != 'ditolak'");
    $stmt->bind_param("is", $lapangan_id, $tanggal);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lapangan_id_post = (int)$_POST['lapangan_id'];
    $tanggal_post = $_POST['tanggal'];
    $jam_mulai = $_POST['jam_mulai'];
    $durasi = (int)$_POST['durasi'];
    $jam_selesai = date('H:i', strtotime($jam_mulai) + ($durasi * 3600));

    $jamTerpakai = getJamTerpakai($conn, $lapangan_id_post, $tanggal_post);
    $konflik = false;
    foreach ($jamTerpakai as $j) {
        if ($jam_mulai < $j['jam_selesai'] && $jam_selesai > $j['jam_mulai']) { $konflik = true; break; }
    }

    if ($konflik) {
        $error = 'Jadwal tersebut sudah dibooking. Pilih jam lain.';
    } elseif ($tanggal_post < date('Y-m-d')) {
        $error = 'Tidak bisa booking tanggal yang sudah lewat.';
    } else {
        $lStmt = $conn->prepare("SELECT harga_per_jam FROM lapangan WHERE id=?");
        $lStmt->bind_param("i", $lapangan_id_post); $lStmt->execute();
        $lData = $lStmt->get_result()->fetch_assoc();
        $total = $lData['harga_per_jam'] * $durasi;
        $catatan = trim($_POST['catatan'] ?? '');
        $user_id = $_SESSION['user_id'];

        $stmt = $conn->prepare("INSERT INTO bookings (user_id, lapangan_id, tanggal, jam_mulai, jam_selesai, durasi, total_harga, catatan) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param("iisssiis", $user_id, $lapangan_id_post, $tanggal_post, $jam_mulai, $jam_selesai, $durasi, $total, $catatan);
        $stmt->execute();
        $booking_id = $conn->insert_id;
        kirimNotifikasi($conn, $user_id, $booking_id, "Booking Anda pada $tanggal_post pukul $jam_mulai sedang menunggu konfirmasi admin.");
        $success = "Booking berhasil! Menunggu konfirmasi admin.";
    }
}

$jamTerpakai = getJamTerpakai($conn, $lapangan_id, $tanggal);
$jamTerpakaiArr = [];
foreach ($jamTerpakai as $j) {
    $start = strtotime($j['jam_mulai']); $end = strtotime($j['jam_selesai']);
    for ($t = $start; $t < $end; $t += 3600) $jamTerpakaiArr[] = date('H:i', $t);
}

include 'includes/header.php';
?>

<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="index.php" class="text-sm text-muted hover:text-charcoal transition">&#8592; Kembali</a>
        <h1 class="text-2xl font-bold text-charcoal mt-2">Form Booking</h1>
    </div>

    <?php if ($error): ?>
        <div class="bg-terra-light text-terra border border-terra/20 px-4 py-3 rounded-xl mb-5 text-sm"><?= $error ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="bg-green-50 text-green-700 border border-green-200 px-4 py-3 rounded-xl mb-5 text-sm">
            <?= $success ?> <a href="status.php" class="font-semibold underline">Lihat Status</a>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl border border-cream-dark p-6 shadow-sm">
        <form method="POST" id="bookingForm" class="space-y-5">
            <!-- Lapangan -->
            <div>
                <label class="block text-sm font-medium text-charcoal mb-1.5">Pilih Lapangan</label>
                <select name="lapangan_id" id="lapangan_id" required
                    class="w-full border border-cream-dark bg-cream rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-terra/30 focus:border-terra transition"
                    onchange="updatePage()">
                    <option value="">-- Pilih Lapangan --</option>
                    <?php foreach ($lapangan as $l): ?>
                        <option value="<?= $l['id'] ?>" <?= $lapangan_id == $l['id'] ? 'selected' : '' ?>
                            data-harga="<?= $l['harga_per_jam'] ?>">
                            <?= htmlspecialchars($l['nama']) ?> — Rp <?= number_format($l['harga_per_jam'], 0, ',', '.') ?>/jam
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Tanggal -->
            <div>
                <label class="block text-sm font-medium text-charcoal mb-1.5">Tanggal</label>
                <input type="date" name="tanggal" id="tanggal" required
                    min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($tanggal) ?>"
                    class="w-full border border-cream-dark bg-cream rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-terra/30 focus:border-terra transition"
                    onchange="updatePage()">
            </div>

            <!-- Jam -->
            <div>
                <label class="block text-sm font-medium text-charcoal mb-2">Pilih Jam Mulai</label>
                <div class="grid grid-cols-5 gap-2">
                    <?php for ($h = 7; $h <= 21; $h++):
                        $jam = sprintf('%02d:00', $h);
                        $terpakai = in_array($jam, $jamTerpakaiArr);
                    ?>
                    <label class="<?= $terpakai ? 'cursor-not-allowed opacity-40' : 'cursor-pointer' ?>">
                        <input type="radio" name="jam_mulai" value="<?= $jam ?>"
                            <?= $terpakai ? 'disabled' : '' ?> class="hidden peer" required>
                        <div class="text-center py-2 rounded-xl border text-xs font-medium transition
                            <?= $terpakai
                                ? 'bg-cream-dark border-cream-dark text-muted line-through'
                                : 'border-cream-dark hover:border-terra hover:text-terra peer-checked:bg-terra peer-checked:text-white peer-checked:border-terra' ?>">
                            <?= $jam ?>
                        </div>
                    </label>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Durasi -->
            <div>
                <label class="block text-sm font-medium text-charcoal mb-1.5">Durasi</label>
                <select name="durasi" id="durasi" required
                    class="w-full border border-cream-dark bg-cream rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-terra/30 focus:border-terra transition"
                    onchange="hitungTotal()">
                    <option value="1">1 Jam</option>
                    <option value="2">2 Jam</option>
                    <option value="3">3 Jam</option>
                    <option value="4">4 Jam</option>
                </select>
            </div>

            <!-- Total -->
            <div class="bg-cream rounded-xl px-5 py-4 flex justify-between items-center border border-cream-dark">
                <span class="text-sm text-muted">Total Harga</span>
                <span class="text-xl font-bold text-charcoal" id="totalHarga">Rp 0</span>
            </div>

            <!-- Catatan -->
            <div>
                <label class="block text-sm font-medium text-charcoal mb-1.5">Catatan <span class="text-muted font-normal">(opsional)</span></label>
                <textarea name="catatan" rows="2"
                    class="w-full border border-cream-dark bg-cream rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-terra/30 focus:border-terra transition"
                    placeholder="Catatan tambahan..."></textarea>
            </div>

            <button type="submit"
                class="w-full bg-terra text-white py-3 rounded-xl font-semibold hover:bg-terra-dark transition">
                Konfirmasi Booking
            </button>
        </form>
    </div>
</div>

<script>
function updatePage() {
    const l = document.getElementById('lapangan_id').value;
    const t = document.getElementById('tanggal').value;
    if (l && t) window.location.href = `booking.php?lapangan_id=${l}&tanggal=${t}`;
}
function hitungTotal() {
    const sel = document.getElementById('lapangan_id');
    const opt = sel.options[sel.selectedIndex];
    const harga = opt ? parseInt(opt.dataset.harga || 0) : 0;
    const durasi = parseInt(document.getElementById('durasi').value || 1);
    document.getElementById('totalHarga').textContent = 'Rp ' + (harga * durasi).toLocaleString('id-ID');
}
document.getElementById('lapangan_id').addEventListener('change', hitungTotal);
document.getElementById('durasi').addEventListener('change', hitungTotal);
hitungTotal();
</script>

<?php include 'includes/footer.php'; ?>
