<?php
session_start();

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

// Simpan ID pertemuan ke dalam session
$_SESSION['pertemuan_id'] = $id_pertemuan;


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
    <script src="https://unpkg.com/@zxing/library@latest/umd/index.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs/qrcode.min.js"></script>

    <style>
        .blur-effect {
            filter: blur(6px); /* Efek blur */
            pointer-events: none; /* Nonaktifkan interaksi */
            user-select: none; /* Nonaktifkan seleksi teks */
        }
    </style>
</head>
<body style="font-family: 'Poppins';">
<section class="bg-gray-100 font-poppins h-screen flex flex-col">
    <!-- Header -->
    <header class="bg-[#727DB6] text-white p-4 flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <span class="text-xl">Detail Pertemuan</span>
        </div>
        <a href="pertemuan.php" class="text-white p-3 border-2 rounded-full hover:bg-[#5c6491] border-white w-10 h-10 flex items-center justify-center flex-col space-y-0">
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

            <!-- Tambahkan tombol Mulai Sesi, Akhiri Sesi, dan Presensi QR -->
            <div class="flex justify-between items-center mb-4">
                <div>
                    <button id="mulaiSesi" class="bg-green-500 text-white px-4 py-2 rounded-lg">Mulai Sesi</button>
                    <button id="akhiriSesi" class="bg-red-500 text-white px-4 py-2 rounded-lg hidden">Akhiri Sesi</button>
                    <button id="presensiQR" class="bg-blue-500 text-white px-4 py-2 rounded-lg hidden">Presensi QR</button>
                </div>
            </div>

            <!-- Statistik Kehadiran -->
            <div class="mb-6">
                <p class="text-gray-700"><strong>Jumlah Anggota Hadir:</strong> <span class="jumlah-hadir"><?php echo $hadir; ?></span></p>
                <p class="text-gray-700"><strong>Hadir:</strong> <span class="persentase-hadir"><?php echo $persentase_hadir; ?></span>%</p>
                <p class="text-gray-700"><strong>Alpha:</strong> <span class="persentase-alpha"><?php echo $persentase_alpha; ?></span>%</p>
            </div>

<!-- Daftar Anggota -->
<div class="mb-6 blur-effect relative" id="daftarAnggota">
    <div class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-60">
    </div>

    <h3 class="text-xl font-bold mb-4">List Anggota</h3>
    <div class="overflow-x-auto ">
        <table class="min-w-full bg-white border border-gray-200 rounded-lg">
            <thead>
                <tr class="bg-gray-100">
                    <th class="px-4 py-2 text-left text-gray-700">No</th>
                    <th class="px-4 py-2 text-left text-gray-700">Nama</th>
                    <th class="px-4 py-2 text-left text-gray-700">NIM</th>
                    <th class="px-4 py-2 text-left text-gray-700">Hadir</th>
                    <th class="px-4 py-2 text-left text-gray-700">Alpha</th>
                    <th class="px-4 py-2 text-left text-gray-700">Reset</th> <!-- Kolom baru untuk tombol reset -->
                </tr>
            </thead>
            <tbody>
                <?php
                $nomor = 1; // Variabel untuk nomor urut
                while ($anggota = $resultAnggota->fetchArray(SQLITE3_ASSOC)) {
                    echo '<tr class="border-b border-gray-200 hover:bg-gray-50">
                            <td class="px-4 py-2 text-gray-700">' . $nomor . '</td>
                            <td class="px-4 py-2 text-gray-700">' . htmlspecialchars($anggota['nama_lengkap']) . '</td>
                            <td class="px-4 py-2 text-gray-700">' . htmlspecialchars($anggota['nim']) . '</td>
                            <td class="px-4 py-2 text-center">
                                <input type="radio" name="status[' . htmlspecialchars($anggota['nim']) . ']" value="Hadir" ' . ($anggota['status'] === 'Hadir' ? 'checked' : '') . ' class="form-radio text-green-500">
                            </td>
                            <td class="px-4 py-2 text-center">
                                <input type="radio" name="status[' . htmlspecialchars($anggota['nim']) . ']" value="Alpha" ' . ($anggota['status'] === 'Alpha' ? 'checked' : '') . ' class="form-radio text-red-500">
                            </td>
                            <td class="px-4 py-2 text-center">
                                <button onclick="resetKehadiran(\'' . htmlspecialchars($anggota['nim']) . '\')" class="bg-yellow-500 text-white px-3 py-1 rounded-lg hover:bg-yellow-600 transition">
                                    Reset
                                </button>
                            </td>
                        </tr>';
                    $nomor++; // Increment nomor urut
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
// JavaScript untuk tombol Mulai Sesi, Akhiri Sesi, dan QR Presensi
document.addEventListener('DOMContentLoaded', function() {
        const mulaiSesi = document.getElementById('mulaiSesi');
        const akhiriSesi = document.getElementById('akhiriSesi');
        const presensiQR = document.getElementById('presensiQR');

        mulaiSesi.addEventListener('click', () => {
        const jamMulai = new Date().toLocaleTimeString();
        Swal.fire({
            title: 'Sesi Dimulai',
            text: `Sesi telah dimulai pada jam ${jamMulai}`,
            icon: 'success',
            confirmButtonText: 'OK'
        }).then(() => {
            // Update jam mulai di database
            fetch('../api/update_jam_mulai.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ jamMulai })
            }).then(response => response.json())
              .then(data => {
                  if (data.success) {
                      // Tampilkan tombol Akhiri Sesi dan Presensi QR
                      akhiriSesi.classList.remove('hidden');
                      presensiQR.classList.remove('hidden');
                      mulaiSesi.classList.add('hidden'); // Sembunyikan tombol Mulai Sesi

                      // Hapus efek blur dan pesan "Sesi Belum Dimulai"
                      const daftarAnggota = document.getElementById('daftarAnggota');
                      daftarAnggota.classList.remove('blur-effect');
                      daftarAnggota.querySelector('.absolute').remove(); // Hapus overlay
                  }
              });
        });
    });

    // Logika untuk tombol Akhiri Sesi
    akhiriSesi.addEventListener('click', () => {
        const jamAkhir = new Date().toLocaleTimeString();
        Swal.fire({
            title: 'Sesi Berakhir',
            text: `Sesi telah berakhir pada jam ${jamAkhir}`,
            icon: 'success',
            confirmButtonText: 'OK'
        }).then(() => {
            // Update jam akhir di database
            fetch('../api/update_jam_akhir.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ jamAkhir })
            }).then(response => response.json())
              .then(data => {
                  if (data.success) {
                      // Alihkan ke index.php
                      window.location.href = 'index.php';
                  }
              });
        });
    });

        // Presensi QR Code
        presensiQR.addEventListener('click', () => {
            const pertemuanId = <?php echo $id_pertemuan; ?>; // Ambil ID pertemuan dari PHP

        // Tampilkan modal dengan SweetAlert2
        Swal.fire({
            title: 'Scan QR untuk Presensi',
            html: `
                <div style="display: flex; justify-content: center; align-items: center; height: 100%;">
                    <div id="qrcode"></div>
                </div>
            `, // Tempat untuk menampilkan QR
            showConfirmButton: false,
            didOpen: () => {
                const qrCodeElement = document.getElementById('qrcode');

                // Fungsi untuk menghasilkan QR Code
                const generateQRCode = () => {
                    const timestamp = Date.now(); // Tambahkan timestamp untuk membuat data unik
                    const qrData = `presensi:${pertemuanId}:${timestamp}`; // Data yang akan dienkripsi ke dalam QR

                    // Hapus QR Code lama
                    qrCodeElement.innerHTML = '';

                    // Generate QR Code baru
                    new QRCode(qrCodeElement, {
                        text: qrData,
                        width: 200,
                        height: 200
                    });

                    // Kirim data QR yang baru ke server
                    fetch('../api/update_qr.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            pertemuan_id: pertemuanId,
                            timestamp: timestamp
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            console.error('Gagal memperbarui kode QR di server.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
                };

                // Generate QR Code pertama kali
                generateQRCode();

                // Update QR Code setiap 10 detik
                setInterval(generateQRCode, 10000); // 10 detik
            }
        });
    });

    document.querySelectorAll('input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const nim = this.name.match(/\[(.*?)\]/)[1]; // Ambil NIM dari nama radio button
            const status = this.value; // Ambil status (Hadir/Alpha)
            const pertemuanId = <?php echo $id_pertemuan; ?>; // Ambil ID pertemuan dari PHP

            // Kirim data ke server menggunakan AJAX
            fetch('../api/absensi.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    nim: nim,
                    status: status,
                    pertemuan_id: pertemuanId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Perbarui statistik kehadiran
                    document.querySelector('.jumlah-hadir').textContent = data.hadir;
                    document.querySelector('.persentase-hadir').textContent = data.persentase_hadir + '%';
                    document.querySelector('.persentase-alpha').textContent = data.persentase_alpha + '%';
                } else {
                    Swal.fire('Error', 'Gagal menyimpan data kehadiran.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'Terjadi kesalahan saat mengirim data.', 'error');
            });
        });
    });

    
});

// Fungsi untuk mereset kehadiran
function resetKehadiran(nim) {
    const pertemuanId = <?php echo $id_pertemuan; ?>; // Ambil ID pertemuan dari PHP

    // Konfirmasi sebelum reset
    Swal.fire({
        title: 'Reset Kehadiran',
        text: 'Apakah Anda yakin ingin mereset kehadiran ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, Reset!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            // Kirim data ke server menggunakan AJAX
            fetch('../api/reset_kehadiran.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    nim: nim,
                    pertemuan_id: pertemuanId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Tampilkan pesan sukses
                    Swal.fire('Berhasil!', 'Kehadiran berhasil direset.', 'success');
                    // Perbarui statistik kehadiran
                    document.querySelector('.jumlah-hadir').textContent = data.hadir;
                    document.querySelector('.persentase-hadir').textContent = data.persentase_hadir + '%';
                    document.querySelector('.persentase-alpha').textContent = data.persentase_alpha + '%';
                    // Reload halaman untuk memperbarui tabel
                    setTimeout(() => location.reload(), 1000);
                } else {
                    Swal.fire('Error', 'Gagal mereset kehadiran.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'Terjadi kesalahan saat mengirim data.', 'error');
            });
        }
    });
}

</script>
</body>
</html>