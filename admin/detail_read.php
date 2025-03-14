<?php
session_start();

date_default_timezone_set('Asia/Jakarta'); // Atur zona waktu

// Cek apakah pengguna sudah login
if (!isset($_SESSION['user'])) {
    header("Location: ../page/login.php");
    exit();
}

// Ambil data pengguna dari session
$user = $_SESSION['user'];
if ($user['role'] !== 'admin') {
    header("Location: ../page/home.php");
    exit();
}

// Ambil ID pertemuan dari parameter URL
if (!isset($_GET['id'])) {
    header("Location: pertemuan.php"); // Redirect jika tidak ada ID
    exit();
}
$id_pertemuan = $_GET['id'];

// Koneksi ke database SQLite3
$db = new SQLite3('../db/ukm.db');

// Query untuk mengambil data pertemuan berdasarkan ID
$query = "SELECT * FROM jadwal_pertemuan WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindValue(':id', $id_pertemuan, SQLITE3_INTEGER);
$result = $stmt->execute();
$pertemuan = $result->fetchArray(SQLITE3_ASSOC);

// Jika data tidak ditemukan, redirect ke halaman pertemuan
if (!$pertemuan) {
    header("Location: pertemuan.php");
    exit();
}

// Query untuk mengambil statistik kehadiran
$queryStatistik = "
    SELECT 
        COUNT(*) AS total_anggota,
        SUM(CASE WHEN status = 'Hadir' THEN 1 ELSE 0 END) AS hadir,
        SUM(CASE WHEN status = 'Alpha' THEN 1 ELSE 0 END) AS alpha
    FROM 
        absen
    WHERE 
        pertemuan_id = :pertemuan_id
";
$stmtStatistik = $db->prepare($queryStatistik);
$stmtStatistik->bindValue(':pertemuan_id', $id_pertemuan, SQLITE3_INTEGER);
$resultStatistik = $stmtStatistik->execute();
$statistik = $resultStatistik->fetchArray(SQLITE3_ASSOC);

$total_anggota = $statistik['total_anggota'];
$hadir = $statistik['hadir'];
$alpha = $statistik['alpha'];
$persentase_hadir = $total_anggota > 0 ? round(($hadir / $total_anggota) * 100, 2) : 0;
$persentase_alpha = $total_anggota > 0 ? round(($alpha / $total_anggota) * 100, 2) : 0;

// Query untuk mengambil data anggota yang hadir
$queryAnggota = "
    SELECT 
        u.nama_lengkap, 
        u.nim, 
        a.status
    FROM 
        users u
    LEFT JOIN 
        absen a ON u.nim = a.nim AND a.pertemuan_id = :pertemuan_id
";
$stmtAnggota = $db->prepare($queryAnggota);
$stmtAnggota->bindValue(':pertemuan_id', $id_pertemuan, SQLITE3_INTEGER);
$resultAnggota = $stmtAnggota->execute();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pertemuan - Cosmic Esport</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
</head>
<body style="font-family: 'Poppins';">
<section class="bg-gray-100 font-poppins h-screen flex flex-col">
    <!-- Header -->
    <header class="bg-[#727DB6] text-white p-4 flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <span class="text-xl">Detail Pertemuan</span>
        </div>
        <a onclick="window.history.back()" class="text-white p-3 border-2 rounded-full hover:bg-[#5c6491] border-white w-10 h-10 flex items-center justify-center flex-col space-y-0">
            <span class="text-lg">×</span>
        </a>
    </header>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto p-6">
        <div class="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow">
            <!-- Judul Pertemuan -->
            <h2 class="text-2xl font-bold mb-4">Pertemuan Rutin - <?php echo htmlspecialchars($pertemuan['id']); ?> [<?php echo htmlspecialchars($pertemuan['nama_topik']); ?>]</h2>

            <!-- Informasi Acara -->
            <div class="mb-6">
                <p class="text-gray-700"><strong>Acara:</strong> <?php echo htmlspecialchars($pertemuan['hari']); ?>, <?php echo htmlspecialchars($pertemuan['tanggal']); ?> - <?php echo htmlspecialchars($pertemuan['jam_pertemuan']); ?></p>
                <p class="text-gray-700"><strong>Ruangan:</strong> <?php echo htmlspecialchars($pertemuan['kelas']); ?></p>
            </div>

            <!-- Statistik Kehadiran -->
            <div class="mb-6">
                <p class="text-gray-700"><strong>Jumlah Anggota Hadir:</strong> <span class="jumlah-hadir"><?php echo $hadir; ?></span></p>
                <p class="text-gray-700"><strong>Hadir:</strong> <span class="persentase-hadir"><?php echo $persentase_hadir; ?></span>%</p>
                <p class="text-gray-700"><strong>Alpha:</strong> <span class="persentase-alpha"><?php echo $persentase_alpha; ?></span>%</p>
            </div>
            <button id="downloadPdf" class="bg-red-500 text-white px-4 py-2 rounded-lg mb-4 hover:bg-red-700 transition">
                <i class="fas fa-download mr-2"></i> Download PDF
            </button>

            <!-- Daftar Anggota -->
            <div class="mb-6">
                <h3 class="text-xl font-bold mb-4">List Anggota</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border border-gray-200 rounded-lg">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="px-4 py-2 text-left text-gray-700">No</th>
                                <th class="px-4 py-2 text-left text-gray-700">Nama</th>
                                <th class="px-4 py-2 text-left text-gray-700">NIM</th>
                                <th class="px-4 py-2 text-left text-gray-700">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                            $nomor = 1;
                            while ($anggota = $resultAnggota->fetchArray(SQLITE3_ASSOC)) {
                                $nama_lengkap = $anggota['nama_lengkap'] ?? '';
                                $nim = $anggota['nim'] ?? '';
                                $status = $anggota['status'] ?? 'Tidak Tercatat';

                                echo '<tr class="border-b border-gray-200 hover:bg-gray-50">
                                        <td class="px-4 py-2 text-gray-700">' . $nomor . '</td>
                                        <td class="px-4 py-2 text-gray-700">' . htmlspecialchars($nama_lengkap) . '</td>
                                        <td class="px-4 py-2 text-gray-700">' . htmlspecialchars($nim) . '</td>
                                        <td class="px-4 py-2 text-gray-700">' . htmlspecialchars($status) . '</td>
                                      </tr>';
                                $nomor++;
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</section>

<script>
document.getElementById('downloadPdf').addEventListener('click', function() {
    // Inisialisasi jsPDF
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('p', 'mm', 'a4');

    // Judul PDF
    const title = "Detail Pertemuan - Cosmic Esport";
    doc.setFontSize(18);
    doc.text(title, 15, 20);

    // Informasi Pertemuan
    const pertemuanInfo = [
        `Acara: ${document.querySelector('p:nth-child(1)').innerText}`,
        `Ruangan: ${document.querySelector('p:nth-child(2)').innerText}`,
        `Jumlah Anggota Hadir: ${document.querySelector('.jumlah-hadir').innerText}`,
        `Hadir: ${document.querySelector('.persentase-hadir').innerText}%`,
        `Alpha: ${document.querySelector('.persentase-alpha').innerText}%`
    ];
    doc.setFontSize(12);
    pertemuanInfo.forEach((info, index) => {
        doc.text(info, 15, 40 + (index * 10));
    });

    // Data Tabel Anggota
    const table = document.querySelector('table');
    const headers = [];
    const rows = [];

    // Ambil header tabel
    table.querySelectorAll('thead th').forEach(th => {
        headers.push(th.innerText);
    });

    // Ambil baris data tabel
    table.querySelectorAll('tbody tr').forEach(tr => {
        const row = [];
        tr.querySelectorAll('td').forEach(td => {
            row.push(td.innerText);
        });
        rows.push(row);
    });

    // Tambahkan tabel ke PDF menggunakan autoTable
    doc.autoTable({
        head: [headers],
        body: rows,
        startY: 90, // Posisi awal tabel
        theme: 'grid', // Gaya tabel
        styles: { 
            fontSize: 10, // Ukuran font
            halign: 'center', // Rata tengah untuk semua teks di tabel
            cellPadding: 3 // Padding sel
        },
        headStyles: { 
            fillColor: [114, 125, 182], // Warna header
            halign: 'center' // Rata tengah untuk teks header
        },
        columnStyles: {
            0: { halign: 'center' }, // Kolom No (rata tengah)
            1: { halign: 'left' }, // Kolom Nama (rata tengah)
            2: { halign: 'center' }, // Kolom NIM (rata tengah)
            3: { halign: 'center' }  // Kolom Status (rata tengah)
        }
    });

    // Simpan PDF
    doc.save('detail_pertemuan.pdf');
});
</script>
</body>
</html>