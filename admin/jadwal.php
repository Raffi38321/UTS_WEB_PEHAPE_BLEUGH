<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireAdmin();

$pageTitle = 'Kelola Lapangan';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    if ($aksi === 'tambah' || $aksi === 'edit') {
        $nama = trim($_POST['nama']);
        $deskripsi = trim($_POST['deskripsi']);
        $harga = (float)$_POST['harga_per_jam'];
        $status = $_POST['status'];
        if ($aksi === 'tambah') {
            $stmt = $conn->prepare("INSERT INTO lapangan (nama, deskripsi, harga_per_jam, status) VALUES (?,?,?,?)");
            $stmt->bind_param("ssds", $nama, $deskripsi, $harga, $status);
        } else {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("UPDATE lapangan SET nama=?, deskripsi=?, harga_per_jam=?, status=? WHERE id=?");
            $stmt->bind_param("ssdsi", $nama, $deskripsi, $harga, $status, $id);
        }
        $stmt->execute();
    } elseif ($aksi === 'hapus') {
        $id = (int)$_POST['id'];
        $del = $conn->prepare("DELETE FROM lapangan WHERE id=?");
        $del->bind_param("i", $id); $del->execute();
    }
    header("Location: jadwal.php"); exit;
}

$lapangan = $conn->query("SELECT * FROM lapangan ORDER BY id")->fetch_all(MYSQLI_ASSOC);

$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('m');
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
if ($bulan < 1) { $bulan = 12; $tahun--; }
if ($bulan > 12) { $bulan = 1; $tahun++; }
$lapangan_filter = $_GET['lapangan_id'] ?? ($lapangan[0]['id'] ?? 0);

$bookingKalender = [];
if ($lapangan_filter) {
    $stmt = $conn->prepare("SELECT b.tanggal, b.jam_mulai, b.jam_selesai, b.status, u.nama as user_nama
        FROM bookings b JOIN users u ON b.user_id=u.id
        WHERE b.lapangan_id=? AND MONTH(tanggal)=? AND YEAR(tanggal)=? AND status != 'ditolak'
        ORDER BY tanggal, jam_mulai");
    $stmt->bind_param("iii", $lapangan_filter, $bulan, $tahun);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $bookingKalender[$row['tanggal']][] = $row;
}

$namaBulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$hariPertama = mktime(0,0,0,$bulan,1,$tahun);
$jumlahHari = date('t', $hariPertama);
$hariAwal = date('N', $hariPertama);

include '../includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <a href="index.php" class="text-sm text-muted hover:text-charcoal transition">&#8592; Dashboard</a>
        <h1 class="text-2xl font-bold text-charcoal mt-1">Kelola Lapangan</h1>
    </div>
    <button onclick="document.getElementById('modalTambah').classList.remove('hidden')"
        class="bg-terra text-white px-5 py-2 rounded-full text-sm font-medium hover:bg-terra-dark transition">
        + Tambah Lapangan
    </button>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Daftar Lapangan -->
    <div class="space-y-3">
        <h2 class="font-semibold text-charcoal text-sm">Daftar Lapangan</h2>
        <?php foreach ($lapangan as $l): ?>
        <div class="bg-white rounded-2xl border border-cream-dark p-4 shadow-sm">
            <div class="flex justify-between items-start mb-2">
                <h3 class="font-semibold text-charcoal text-sm"><?= htmlspecialchars($l['nama']) ?></h3>
                <span class="text-xs px-2 py-0.5 rounded-full <?= $l['status']==='aktif' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-600 border border-red-200' ?>">
                    <?= ucfirst($l['status']) ?>
                </span>
            </div>
            <p class="text-xs text-muted mb-1"><?= htmlspecialchars($l['deskripsi']) ?></p>
            <p class="text-sm font-bold text-charcoal mb-3">Rp <?= number_format($l['harga_per_jam'],0,',','.') ?>/jam</p>
            <div class="flex gap-2">
                <button onclick='editLapangan(<?= json_encode($l) ?>)'
                    class="flex-1 text-center border border-cream-dark text-muted py-1.5 rounded-xl text-xs hover:border-charcoal hover:text-charcoal transition">
                    Edit
                </button>
                <form method="POST" onsubmit="return confirm('Hapus lapangan ini?')" class="flex-1">
                    <input type="hidden" name="aksi" value="hapus">
                    <input type="hidden" name="id" value="<?= $l['id'] ?>">
                    <button class="w-full border border-red-200 text-red-500 py-1.5 rounded-xl text-xs hover:bg-red-50 transition">
                        Hapus
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Kalender Jadwal -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-2xl border border-cream-dark p-5 shadow-sm">
            <div class="flex justify-between items-center mb-5 flex-wrap gap-3">
                <div class="flex items-center gap-3">
                    <a href="?bulan=<?= $bulan-1 ?>&tahun=<?= $tahun ?>&lapangan_id=<?= $lapangan_filter ?>"
                       class="w-7 h-7 flex items-center justify-center rounded-full hover:bg-cream-dark transition text-muted text-lg">&#8249;</a>
                    <span class="font-semibold text-charcoal"><?= $namaBulan[$bulan] . ' ' . $tahun ?></span>
                    <a href="?bulan=<?= $bulan+1 ?>&tahun=<?= $tahun ?>&lapangan_id=<?= $lapangan_filter ?>"
                       class="w-7 h-7 flex items-center justify-center rounded-full hover:bg-cream-dark transition text-muted text-lg">&#8250;</a>
                </div>
                <select onchange="window.location.href='?bulan=<?= $bulan ?>&tahun=<?= $tahun ?>&lapangan_id='+this.value"
                    class="border border-cream-dark bg-cream rounded-xl px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-terra/30">
                    <?php foreach ($lapangan as $l): ?>
                        <option value="<?= $l['id'] ?>" <?= $lapangan_filter == $l['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($l['nama']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-cols-7 text-center mb-2">
                <?php foreach (['Sen','Sel','Rab','Kam','Jum','Sab','Min'] as $h): ?>
                    <div class="text-xs font-medium text-muted py-1"><?= $h ?></div>
                <?php endforeach; ?>
            </div>

            <div class="grid grid-cols-7 gap-1">
                <?php
                for ($i = 1; $i < $hariAwal; $i++) echo '<div></div>';
                for ($hari = 1; $hari <= $jumlahHari; $hari++):
                    $tgl = sprintf('%04d-%02d-%02d', $tahun, $bulan, $hari);
                    $adaBooking = isset($bookingKalender[$tgl]);
                    $isToday = ($tgl === date('Y-m-d'));
                ?>
                <div onclick="showDetail('<?= $tgl ?>')"
                     class="relative flex flex-col items-center justify-center aspect-square rounded-xl text-xs cursor-pointer transition
                     <?= $isToday ? 'bg-terra text-white' : 'hover:bg-cream-dark text-charcoal' ?>">
                    <span class="font-medium"><?= $hari ?></span>
                    <?php if ($adaBooking): ?>
                        <span class="absolute bottom-1 w-1 h-1 rounded-full <?= $isToday ? 'bg-white/70' : 'bg-terra' ?>"></span>
                    <?php endif; ?>
                </div>
                <?php endfor; ?>
            </div>

            <!-- Detail -->
            <div id="detailBooking" class="mt-5 pt-4 border-t border-cream-dark hidden">
                <h3 class="font-semibold text-charcoal text-sm mb-3" id="detailTanggal"></h3>
                <div id="detailList" class="space-y-2"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah -->
<div id="modalTambah" class="hidden fixed inset-0 bg-charcoal/40 flex items-center justify-center z-50 px-4">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-xl">
        <h2 class="font-bold text-lg text-charcoal mb-5">Tambah Lapangan</h2>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="aksi" value="tambah">
            <div>
                <label class="block text-sm font-medium text-charcoal mb-1.5">Nama Lapangan</label>
                <input type="text" name="nama" required class="w-full border border-cream-dark bg-cream rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-terra/30 focus:border-terra">
            </div>
            <div>
                <label class="block text-sm font-medium text-charcoal mb-1.5">Deskripsi</label>
                <textarea name="deskripsi" rows="2" class="w-full border border-cream-dark bg-cream rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-terra/30 focus:border-terra"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-charcoal mb-1.5">Harga per Jam (Rp)</label>
                <input type="number" name="harga_per_jam" required class="w-full border border-cream-dark bg-cream rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-terra/30 focus:border-terra">
            </div>
            <div>
                <label class="block text-sm font-medium text-charcoal mb-1.5">Status</label>
                <select name="status" class="w-full border border-cream-dark bg-cream rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-terra/30 focus:border-terra">
                    <option value="aktif">Aktif</option>
                    <option value="nonaktif">Nonaktif</option>
                </select>
            </div>
            <div class="flex gap-3 pt-1">
                <button type="submit" class="flex-1 bg-terra text-white py-2.5 rounded-xl text-sm font-semibold hover:bg-terra-dark transition">Simpan</button>
                <button type="button" onclick="document.getElementById('modalTambah').classList.add('hidden')"
                    class="flex-1 border border-cream-dark text-muted py-2.5 rounded-xl text-sm hover:bg-cream-dark transition">Batal</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit -->
<div id="modalEdit" class="hidden fixed inset-0 bg-charcoal/40 flex items-center justify-center z-50 px-4">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-xl">
        <h2 class="font-bold text-lg text-charcoal mb-5">Edit Lapangan</h2>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="aksi" value="edit">
            <input type="hidden" name="id" id="editId">
            <div>
                <label class="block text-sm font-medium text-charcoal mb-1.5">Nama Lapangan</label>
                <input type="text" name="nama" id="editNama" required class="w-full border border-cream-dark bg-cream rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-terra/30 focus:border-terra">
            </div>
            <div>
                <label class="block text-sm font-medium text-charcoal mb-1.5">Deskripsi</label>
                <textarea name="deskripsi" id="editDeskripsi" rows="2" class="w-full border border-cream-dark bg-cream rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-terra/30 focus:border-terra"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-charcoal mb-1.5">Harga per Jam (Rp)</label>
                <input type="number" name="harga_per_jam" id="editHarga" required class="w-full border border-cream-dark bg-cream rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-terra/30 focus:border-terra">
            </div>
            <div>
                <label class="block text-sm font-medium text-charcoal mb-1.5">Status</label>
                <select name="status" id="editStatus" class="w-full border border-cream-dark bg-cream rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-terra/30 focus:border-terra">
                    <option value="aktif">Aktif</option>
                    <option value="nonaktif">Nonaktif</option>
                </select>
            </div>
            <div class="flex gap-3 pt-1">
                <button type="submit" class="flex-1 bg-charcoal text-cream py-2.5 rounded-xl text-sm font-semibold hover:bg-charcoal-light transition">Update</button>
                <button type="button" onclick="document.getElementById('modalEdit').classList.add('hidden')"
                    class="flex-1 border border-cream-dark text-muted py-2.5 rounded-xl text-sm hover:bg-cream-dark transition">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
const bookingData = <?= json_encode($bookingKalender) ?>;

function editLapangan(data) {
    document.getElementById('editId').value = data.id;
    document.getElementById('editNama').value = data.nama;
    document.getElementById('editDeskripsi').value = data.deskripsi;
    document.getElementById('editHarga').value = data.harga_per_jam;
    document.getElementById('editStatus').value = data.status;
    document.getElementById('modalEdit').classList.remove('hidden');
}

function showDetail(tgl) {
    const detail = document.getElementById('detailBooking');
    const list = document.getElementById('detailList');
    document.getElementById('detailTanggal').textContent = 'Jadwal ' + tgl;
    list.innerHTML = '';
    if (bookingData[tgl] && bookingData[tgl].length > 0) {
        bookingData[tgl].forEach(b => {
            const div = document.createElement('div');
            div.className = 'bg-cream rounded-xl px-4 py-2.5 flex justify-between items-center text-sm';
            div.innerHTML = `<span class="font-medium text-charcoal">${b.jam_mulai.substring(0,5)} – ${b.jam_selesai.substring(0,5)}</span>
                             <span class="text-muted">${b.user_nama}</span>
                             <span class="text-xs bg-terra-light text-terra px-2 py-0.5 rounded-full">${b.status}</span>`;
            list.appendChild(div);
        });
    } else {
        list.innerHTML = '<p class="text-muted text-sm">Tidak ada booking pada hari ini.</p>';
    }
    detail.classList.remove('hidden');
}
</script>

<?php include '../includes/footer.php'; ?>
