<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

$pageTitle = 'BookingLapangan — Booking Lapangan Online';

$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('m');
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
if ($bulan < 1) { $bulan = 12; $tahun--; }
if ($bulan > 12) { $bulan = 1; $tahun++; }

$hariPertama = mktime(0, 0, 0, $bulan, 1, $tahun);
$jumlahHari = date('t', $hariPertama);
$hariAwal = date('N', $hariPertama);

$bookingBulanIni = [];
$stmt = $conn->prepare("SELECT tanggal, COUNT(*) as total FROM bookings WHERE MONTH(tanggal)=? AND YEAR(tanggal)=? AND status != 'ditolak' GROUP BY tanggal");
$stmt->bind_param("ii", $bulan, $tahun);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $bookingBulanIni[$row['tanggal']] = $row['total'];
}

$lapangan = $conn->query("SELECT * FROM lapangan WHERE status='aktif'")->fetch_all(MYSQLI_ASSOC);
$namaBulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

include 'includes/header.php';
?>

<!-- Hero -->
<div class="mb-10">
    <p class="text-muted text-sm mb-1">Sistem pemesanan lapangan</p>
    <h1 class="text-4xl font-bold text-charcoal leading-tight mb-3">
        Booking lapangan<br>mudah & cepat.
    </h1>
    <p class="text-muted mb-6">Pilih lapangan, tentukan jadwal, konfirmasi instan.</p>
    <div class="flex gap-3">
        <a href="booking.php" class="bg-charcoal text-cream px-6 py-2.5 rounded-full font-semibold text-sm hover:bg-charcoal-light transition">
            Booking Sekarang
        </a>
        <?php if (isLoggedIn()): ?>
        <a href="status.php" class="border border-charcoal text-charcoal px-6 py-2.5 rounded-full font-semibold text-sm hover:bg-cream-dark transition">
            Riwayat Saya
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Kalender -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-2xl border border-cream-dark p-6 shadow-sm">
            <!-- Nav bulan -->
            <div class="flex justify-between items-center mb-6">
                <a href="?bulan=<?= $bulan-1 ?>&tahun=<?= $tahun ?>"
                   class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-cream-dark transition text-muted">
                    &#8249;
                </a>
                <h2 class="font-semibold text-charcoal"><?= $namaBulan[$bulan] . ' ' . $tahun ?></h2>
                <a href="?bulan=<?= $bulan+1 ?>&tahun=<?= $tahun ?>"
                   class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-cream-dark transition text-muted">
                    &#8250;
                </a>
            </div>

            <!-- Header hari -->
            <div class="grid grid-cols-7 text-center mb-2">
                <?php foreach (['Sen','Sel','Rab','Kam','Jum','Sab','Min'] as $h): ?>
                    <div class="text-xs font-medium text-muted py-1"><?= $h ?></div>
                <?php endforeach; ?>
            </div>

            <!-- Grid hari -->
            <div class="grid grid-cols-7 gap-1">
                <?php
                for ($i = 1; $i < $hariAwal; $i++) echo '<div></div>';
                $today = date('Y-m-d');
                for ($hari = 1; $hari <= $jumlahHari; $hari++):
                    $tgl = sprintf('%04d-%02d-%02d', $tahun, $bulan, $hari);
                    $isToday = ($tgl === $today);
                    $isPast = ($tgl < $today);
                    $adaBooking = isset($bookingBulanIni[$tgl]);
                ?>
                <div onclick="<?= !$isPast ? "window.location='booking.php?tanggal={$tgl}'" : '' ?>"
                     class="relative flex flex-col items-center justify-center aspect-square rounded-xl text-sm transition
                     <?= $isPast ? 'text-muted/40 cursor-not-allowed' : 'cursor-pointer hover:bg-cream-dark' ?>
                     <?= $isToday ? 'bg-terra text-white hover:bg-terra-dark' : '' ?>">
                    <span><?= $hari ?></span>
                    <?php if ($adaBooking && !$isPast): ?>
                        <span class="absolute bottom-1 w-1 h-1 rounded-full <?= $isToday ? 'bg-white/70' : 'bg-terra' ?>"></span>
                    <?php endif; ?>
                </div>
                <?php endfor; ?>
            </div>

            <div class="flex gap-5 mt-5 pt-4 border-t border-cream-dark text-xs text-muted">
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 bg-terra rounded-full"></span> Hari ini</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 bg-terra/30 rounded-full border border-terra"></span> Ada booking</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 bg-cream-dark rounded-full"></span> Klik untuk booking</span>
            </div>
        </div>
    </div>

    <!-- Lapangan -->
    <div class="space-y-4">
        <h2 class="font-semibold text-charcoal">Lapangan Tersedia</h2>
        <?php foreach ($lapangan as $l): ?>
        <div class="bg-white rounded-2xl border border-cream-dark p-5 shadow-sm hover:border-terra/40 transition">
            <div class="flex justify-between items-start mb-2">
                <h3 class="font-semibold text-charcoal text-sm"><?= htmlspecialchars($l['nama']) ?></h3>
                <span class="text-xs bg-terra-light text-terra px-2 py-0.5 rounded-full">Aktif</span>
            </div>
            <p class="text-xs text-muted mb-3"><?= htmlspecialchars($l['deskripsi']) ?></p>
            <p class="font-bold text-charcoal text-sm mb-3">Rp <?= number_format($l['harga_per_jam'], 0, ',', '.') ?><span class="font-normal text-muted">/jam</span></p>
            <a href="booking.php?lapangan_id=<?= $l['id'] ?>"
               class="block text-center bg-terra text-white py-2 rounded-xl text-sm font-medium hover:bg-terra-dark transition">
                Pesan Sekarang
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
