<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireAdmin();

$pageTitle = 'Laporan Pendapatan';

// Filter tahun
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
$tahunList = [];
$res = $conn->query("SELECT DISTINCT YEAR(tanggal) as y FROM bookings ORDER BY y DESC");
while ($r = $res->fetch_assoc()) $tahunList[] = $r['y'];
if (empty($tahunList)) $tahunList = [(int)date('Y')];

// Pendapatan per bulan (hanya booking dikonfirmasi/selesai)
$pendapatanBulan = array_fill(1, 12, 0);
$stmt = $conn->prepare("
    SELECT MONTH(tanggal) as bulan, SUM(total_harga) as total
    FROM bookings
    WHERE YEAR(tanggal) = ? AND status IN ('dikonfirmasi','selesai')
    GROUP BY MONTH(tanggal)
");
$stmt->bind_param("i", $tahun);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) {
    $pendapatanBulan[(int)$r['bulan']] = (float)$r['total'];
}

// Pendapatan per lapangan
$pendapatanLapangan = $conn->prepare("
    SELECT l.nama, SUM(b.total_harga) as total, COUNT(*) as jumlah
    FROM bookings b JOIN lapangan l ON b.lapangan_id = l.id
    WHERE YEAR(b.tanggal) = ? AND b.status IN ('dikonfirmasi','selesai')
    GROUP BY l.id ORDER BY total DESC
");
$pendapatanLapangan->bind_param("i", $tahun);
$pendapatanLapangan->execute();
$lapanganData = $pendapatanLapangan->get_result()->fetch_all(MYSQLI_ASSOC);

// Summary
$summary = $conn->prepare("
    SELECT
        COUNT(*) as total_booking,
        SUM(total_harga) as total_pendapatan,
        COUNT(DISTINCT user_id) as total_user
    FROM bookings
    WHERE YEAR(tanggal) = ? AND status IN ('dikonfirmasi','selesai')
");
$summary->bind_param("i", $tahun);
$summary->execute();
$sum = $summary->get_result()->fetch_assoc();

// Detail per bulan untuk tabel
$detailBulan = $conn->prepare("
    SELECT MONTH(tanggal) as bulan, COUNT(*) as jumlah, SUM(total_harga) as total
    FROM bookings
    WHERE YEAR(tanggal) = ? AND status IN ('dikonfirmasi','selesai')
    GROUP BY MONTH(tanggal) ORDER BY bulan
");
$detailBulan->bind_param("i", $tahun);
$detailBulan->execute();
$detailRows = $detailBulan->get_result()->fetch_all(MYSQLI_ASSOC);
$detailMap = [];
foreach ($detailRows as $d) $detailMap[(int)$d['bulan']] = $d;

$namaBulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

include '../includes/header.php';
?>

<div class="flex justify-between items-center mb-6 flex-wrap gap-3">
    <div>
        <a href="index.php" class="text-sm text-muted hover:text-charcoal transition">&#8592; Dashboard</a>
        <h1 class="text-2xl font-bold text-charcoal mt-1">Laporan Pendapatan</h1>
    </div>
    <div class="flex items-center gap-3">
        <!-- Filter tahun -->
        <form method="GET">
            <select name="tahun" onchange="this.form.submit()"
                class="border border-cream-dark bg-cream rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-terra/30">
                <?php foreach ($tahunList as $y): ?>
                    <option value="<?= $y ?>" <?= $tahun == $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <!-- Export PDF -->
        <button onclick="exportPDF()"
            class="bg-terra text-white px-5 py-2 rounded-full text-sm font-medium hover:bg-terra-dark transition flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h4a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
            </svg>
            Export PDF
        </button>
    </div>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8" id="summaryCards">
    <div class="bg-white rounded-2xl border border-cream-dark p-5 shadow-sm">
        <p class="text-sm text-muted mb-1">Total Pendapatan <?= $tahun ?></p>
        <p class="text-2xl font-bold text-charcoal">Rp <?= number_format($sum['total_pendapatan'] ?? 0, 0, ',', '.') ?></p>
    </div>
    <div class="bg-white rounded-2xl border border-cream-dark p-5 shadow-sm">
        <p class="text-sm text-muted mb-1">Total Booking Selesai</p>
        <p class="text-2xl font-bold text-charcoal"><?= $sum['total_booking'] ?? 0 ?></p>
    </div>
    <div class="bg-white rounded-2xl border border-cream-dark p-5 shadow-sm">
        <p class="text-sm text-muted mb-1">User Aktif</p>
        <p class="text-2xl font-bold text-charcoal"><?= $sum['total_user'] ?? 0 ?></p>
    </div>
</div>

<!-- Charts -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Bar chart pendapatan bulanan -->
    <div class="lg:col-span-2 bg-white rounded-2xl border border-cream-dark p-6 shadow-sm">
        <h2 class="font-semibold text-charcoal mb-4">Pendapatan Bulanan <?= $tahun ?></h2>
        <canvas id="chartBulanan" height="100"></canvas>
    </div>

    <!-- Donut chart per lapangan -->
    <div class="bg-white rounded-2xl border border-cream-dark p-6 shadow-sm">
        <h2 class="font-semibold text-charcoal mb-4">Per Lapangan</h2>
        <?php if (empty($lapanganData)): ?>
            <div class="flex items-center justify-center h-40 text-muted text-sm">Belum ada data</div>
        <?php else: ?>
            <canvas id="chartLapangan"></canvas>
            <div class="mt-4 space-y-2" id="legendLapangan"></div>
        <?php endif; ?>
    </div>
</div>

<!-- Tabel detail per bulan -->
<div class="bg-white rounded-2xl border border-cream-dark shadow-sm overflow-hidden" id="tabelLaporan">
    <div class="px-6 py-4 border-b border-cream-dark">
        <h2 class="font-semibold text-charcoal">Rincian Per Bulan — <?= $tahun ?></h2>
    </div>
    <table class="w-full text-sm">
        <thead>
            <tr class="bg-cream text-left text-xs text-muted border-b border-cream-dark">
                <th class="px-6 py-3 font-medium">Bulan</th>
                <th class="px-6 py-3 font-medium text-right">Jumlah Booking</th>
                <th class="px-6 py-3 font-medium text-right">Pendapatan</th>
                <th class="px-6 py-3 font-medium text-right">Rata-rata/Booking</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-cream-dark">
            <?php
            $grandTotal = 0; $grandBooking = 0;
            for ($m = 1; $m <= 12; $m++):
                $d = $detailMap[$m] ?? null;
                $total = $d ? (float)$d['total'] : 0;
                $jumlah = $d ? (int)$d['jumlah'] : 0;
                $rata = $jumlah > 0 ? $total / $jumlah : 0;
                $grandTotal += $total; $grandBooking += $jumlah;
            ?>
            <tr class="hover:bg-cream/50 transition <?= !$d ? 'opacity-40' : '' ?>">
                <td class="px-6 py-3 font-medium text-charcoal"><?= $namaBulan[$m] ?></td>
                <td class="px-6 py-3 text-right text-muted"><?= $jumlah ?: '—' ?></td>
                <td class="px-6 py-3 text-right font-medium <?= $total > 0 ? 'text-charcoal' : 'text-muted' ?>">
                    <?= $total > 0 ? 'Rp ' . number_format($total, 0, ',', '.') : '—' ?>
                </td>
                <td class="px-6 py-3 text-right text-muted">
                    <?= $rata > 0 ? 'Rp ' . number_format($rata, 0, ',', '.') : '—' ?>
                </td>
            </tr>
            <?php endfor; ?>
        </tbody>
        <tfoot>
            <tr class="bg-cream border-t-2 border-cream-dark font-semibold">
                <td class="px-6 py-3 text-charcoal">Total</td>
                <td class="px-6 py-3 text-right text-charcoal"><?= $grandBooking ?></td>
                <td class="px-6 py-3 text-right text-terra">Rp <?= number_format($grandTotal, 0, ',', '.') ?></td>
                <td class="px-6 py-3 text-right text-muted">
                    <?= $grandBooking > 0 ? 'Rp ' . number_format($grandTotal / $grandBooking, 0, ',', '.') : '—' ?>
                </td>
            </tr>
        </tfoot>
    </table>

    <!-- Tabel per lapangan -->
    <?php if (!empty($lapanganData)): ?>
    <div class="px-6 py-4 border-t border-cream-dark">
        <h3 class="font-semibold text-charcoal mb-3">Rincian Per Lapangan</h3>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs text-muted">
                    <th class="py-2 font-medium">Lapangan</th>
                    <th class="py-2 font-medium text-right">Booking</th>
                    <th class="py-2 font-medium text-right">Pendapatan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-cream-dark">
                <?php foreach ($lapanganData as $ld): ?>
                <tr>
                    <td class="py-2.5 font-medium text-charcoal"><?= htmlspecialchars($ld['nama']) ?></td>
                    <td class="py-2.5 text-right text-muted"><?= $ld['jumlah'] ?></td>
                    <td class="py-2.5 text-right font-semibold text-charcoal">Rp <?= number_format($ld['total'], 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>

<script>
// Data dari PHP
const labelsBulan = <?= json_encode(array_values($namaBulan)) ?>.slice(1);
const dataBulan   = <?= json_encode(array_values($pendapatanBulan)) ?>;
const namaLapangan = <?= json_encode(array_column($lapanganData, 'nama')) ?>;
const dataLapangan = <?= json_encode(array_column($lapanganData, 'total')) ?>;
const jumlahLapangan = <?= json_encode(array_column($lapanganData, 'jumlah')) ?>;
const tahun = <?= $tahun ?>;
const totalPendapatan = <?= $sum['total_pendapatan'] ?? 0 ?>;
const totalBooking = <?= $sum['total_booking'] ?? 0 ?>;

const palette = ['#E8572A','#1C1C1C','#C94820','#3D3D3D','#F0A080','#8A8070'];

// Bar chart bulanan
const ctxBar = document.getElementById('chartBulanan').getContext('2d');
const chartBulanan = new Chart(ctxBar, {
    type: 'bar',
    data: {
        labels: labelsBulan,
        datasets: [{
            label: 'Pendapatan (Rp)',
            data: dataBulan,
            backgroundColor: dataBulan.map((v, i) => v > 0 ? '#E8572A' : '#F0E9DF'),
            borderRadius: 8,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => 'Rp ' + ctx.raw.toLocaleString('id-ID')
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: '#F0E9DF' },
                ticks: {
                    callback: v => 'Rp ' + (v/1000).toLocaleString('id-ID') + 'k',
                    color: '#8A8070'
                }
            },
            x: { grid: { display: false }, ticks: { color: '#8A8070' } }
        }
    }
});

// Donut chart per lapangan
if (namaLapangan.length > 0) {
    const ctxDonut = document.getElementById('chartLapangan').getContext('2d');
    new Chart(ctxDonut, {
        type: 'doughnut',
        data: {
            labels: namaLapangan,
            datasets: [{
                data: dataLapangan,
                backgroundColor: palette,
                borderWidth: 0,
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            cutout: '65%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => ctx.label + ': Rp ' + ctx.raw.toLocaleString('id-ID')
                    }
                }
            }
        }
    });

    // Custom legend
    const legend = document.getElementById('legendLapangan');
    namaLapangan.forEach((nama, i) => {
        const pct = totalPendapatan > 0 ? ((dataLapangan[i] / totalPendapatan) * 100).toFixed(1) : 0;
        legend.innerHTML += `
            <div class="flex items-center justify-between text-xs">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background:${palette[i]}"></span>
                    <span class="text-charcoal truncate max-w-[120px]">${nama}</span>
                </div>
                <span class="text-muted font-medium">${pct}%</span>
            </div>`;
    });
}

// Export PDF
function exportPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });

    const pageW = doc.internal.pageSize.getWidth();
    let y = 15;

    // Header
    doc.setFillColor(232, 87, 42);
    doc.rect(0, 0, pageW, 28, 'F');
    doc.setTextColor(255, 255, 255);
    doc.setFontSize(16);
    doc.setFont('helvetica', 'bold');
    doc.text('BookingLapangan', 14, 12);
    doc.setFontSize(10);
    doc.setFont('helvetica', 'normal');
    doc.text('Laporan Pendapatan Tahun ' + tahun, 14, 20);
    doc.text('Dicetak: ' + new Date().toLocaleDateString('id-ID', {day:'2-digit',month:'long',year:'numeric'}), pageW - 14, 20, { align: 'right' });

    y = 38;

    // Summary boxes
    doc.setTextColor(28, 28, 28);
    doc.setFontSize(9);
    doc.setFont('helvetica', 'normal');

    const boxes = [
        ['Total Pendapatan', 'Rp ' + totalPendapatan.toLocaleString('id-ID')],
        ['Total Booking', totalBooking.toString()],
    ];
    const boxW = (pageW - 28 - 6) / 2;
    boxes.forEach((b, i) => {
        const bx = 14 + i * (boxW + 6);
        doc.setFillColor(250, 246, 241);
        doc.roundedRect(bx, y, boxW, 16, 3, 3, 'F');
        doc.setTextColor(138, 128, 112);
        doc.setFontSize(8);
        doc.text(b[0], bx + 5, y + 6);
        doc.setTextColor(28, 28, 28);
        doc.setFontSize(11);
        doc.setFont('helvetica', 'bold');
        doc.text(b[1], bx + 5, y + 13);
        doc.setFont('helvetica', 'normal');
    });

    y += 24;

    // Tabel bulanan
    doc.setFontSize(11);
    doc.setFont('helvetica', 'bold');
    doc.setTextColor(28, 28, 28);
    doc.text('Rincian Pendapatan Per Bulan', 14, y);
    y += 4;

    const namaBulanArr = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    const tableRows = namaBulanArr.map((nm, i) => {
        const val = dataBulan[i];
        return [
            nm,
            val > 0 ? jumlahLapangan.reduce ? '—' : '—' : '—',
            val > 0 ? 'Rp ' + val.toLocaleString('id-ID') : '—'
        ];
    });

    // Ambil jumlah booking per bulan dari PHP
    const bookingPerBulan = <?= json_encode(array_map(fn($m) => $detailMap[$m]['jumlah'] ?? 0, range(1,12))) ?>;
    const tableRowsFinal = namaBulanArr.map((nm, i) => [
        nm,
        bookingPerBulan[i] > 0 ? bookingPerBulan[i] : '—',
        dataBulan[i] > 0 ? 'Rp ' + dataBulan[i].toLocaleString('id-ID') : '—',
        dataBulan[i] > 0 && bookingPerBulan[i] > 0
            ? 'Rp ' + Math.round(dataBulan[i] / bookingPerBulan[i]).toLocaleString('id-ID')
            : '—'
    ]);

    doc.autoTable({
        startY: y,
        head: [['Bulan', 'Booking', 'Pendapatan', 'Rata-rata/Booking']],
        body: tableRowsFinal,
        foot: [['Total',
            totalBooking,
            'Rp ' + totalPendapatan.toLocaleString('id-ID'),
            totalBooking > 0 ? 'Rp ' + Math.round(totalPendapatan/totalBooking).toLocaleString('id-ID') : '—'
        ]],
        theme: 'plain',
        headStyles: { fillColor: [240, 233, 223], textColor: [138,128,112], fontSize: 8, fontStyle: 'bold' },
        bodyStyles: { fontSize: 9, textColor: [28,28,28] },
        footStyles: { fillColor: [240, 233, 223], textColor: [232,87,42], fontStyle: 'bold', fontSize: 9 },
        alternateRowStyles: { fillColor: [250, 246, 241] },
        columnStyles: {
            0: { cellWidth: 35 },
            1: { halign: 'right' },
            2: { halign: 'right' },
            3: { halign: 'right' }
        },
        margin: { left: 14, right: 14 }
    });

    y = doc.lastAutoTable.finalY + 10;

    // Tabel per lapangan
    if (namaLapangan.length > 0) {
        doc.setFontSize(11);
        doc.setFont('helvetica', 'bold');
        doc.setTextColor(28, 28, 28);
        doc.text('Rincian Per Lapangan', 14, y);
        y += 4;

        doc.autoTable({
            startY: y,
            head: [['Lapangan', 'Jumlah Booking', 'Total Pendapatan']],
            body: namaLapangan.map((nm, i) => [
                nm,
                jumlahLapangan[i],
                'Rp ' + parseFloat(dataLapangan[i]).toLocaleString('id-ID')
            ]),
            theme: 'plain',
            headStyles: { fillColor: [240, 233, 223], textColor: [138,128,112], fontSize: 8, fontStyle: 'bold' },
            bodyStyles: { fontSize: 9, textColor: [28,28,28] },
            alternateRowStyles: { fillColor: [250, 246, 241] },
            columnStyles: { 1: { halign: 'right' }, 2: { halign: 'right' } },
            margin: { left: 14, right: 14 }
        });
    }

    // Footer
    const pageCount = doc.internal.getNumberOfPages();
    for (let i = 1; i <= pageCount; i++) {
        doc.setPage(i);
        doc.setFontSize(8);
        doc.setTextColor(138, 128, 112);
        doc.text('BookingLapangan — Laporan Pendapatan ' + tahun, 14, doc.internal.pageSize.getHeight() - 8);
        doc.text('Halaman ' + i + ' / ' + pageCount, pageW - 14, doc.internal.pageSize.getHeight() - 8, { align: 'right' });
    }

    doc.save('laporan-pendapatan-' + tahun + '.pdf');
}
</script>

<?php include '../includes/footer.php'; ?>
