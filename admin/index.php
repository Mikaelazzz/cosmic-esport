<?php
// Mulai session
session_start();

// Cek apakah pengguna sudah login
if (!isset($_SESSION['user'])) {
    header("Location: ../page/login.php");
    exit();
}

// Cek role pengguna
$user = $_SESSION['user'];
if ($user['role'] !== 'admin') {
    header("Location: ../page/home.php");
    exit();
}

// Koneksi ke database SQLite3
$db = new SQLite3('../db/ukm.db');

// Query untuk mengambil data pertemuan yang belum selesai (jam_akhir IS NULL)
$queryPertemuan = "
    SELECT * 
    FROM jadwal_pertemuan 
    WHERE status IN ('belum_mulai', 'berlangsung')
    ORDER BY tanggal DESC
";
$resultPertemuan = $db->query($queryPertemuan);

// Query untuk mengambil jumlah anggota
$queryJumlahAnggota = "SELECT COUNT(*) as jumlah_anggota FROM users";
$resultJumlahAnggota = $db->query($queryJumlahAnggota);
$rowJumlahAnggota = $resultJumlahAnggota->fetchArray(SQLITE3_ASSOC);
$jumlahAnggota = $rowJumlahAnggota['jumlah_anggota'];

$queryJumlahKegiatan = "SELECT COUNT(*) as jumlah_kegiatan FROM kegiatan";
$resultJumlahKegiatan = $db->query($queryJumlahKegiatan);
$rowJumlahKegiatan = $resultJumlahKegiatan->fetchArray(SQLITE3_ASSOC);
$jumlahKegiatan = $rowJumlahKegiatan['jumlah_kegiatan'];

$queryJumlahPertemuan = "SELECT COUNT(*) as jumlah_pertemuan FROM jadwal_pertemuan";
$resultJumlahPertemuan = $db->query($queryJumlahPertemuan);
$rowJumlahPertemuan = $resultJumlahPertemuan->fetchArray(SQLITE3_ASSOC);
$jumlahPertemuan = $rowJumlahPertemuan['jumlah_pertemuan'];

$queryJumlahInformasi = "SELECT COUNT(*) as jumlah_informasi FROM informasi";
$resultJumlahInformasi = $db->query($queryJumlahInformasi);
$rowJumlahInformasi = $resultJumlahInformasi->fetchArray(SQLITE3_ASSOC);
$jumlahInformasi = $rowJumlahInformasi['jumlah_informasi'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cosmic Esport</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://unpkg.com/@zxing/library@latest/umd/index.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .swal2-progress-bar {
        background-color: #4CAF50 !important; /* Warna hijau */
    }
    </style>
</head>
<body>
    <section class="flex h-screen" style="font-family: 'Poppins';">
        <!-- Sidebar -->
        <div id="sidebar" class="fixed h-full text-white w-64 transition-transform duration-300 ease-in-out md:translate-x-0 hidden" style="background-color: #727DB6; z-index: 1000;">
            <div class="p-4">
                <!-- Tombol Close di Tengah -->
                <div class="flex justify-center mb-6">
                    <button id="closeSidebar" class="p-2">
                        <i class="fa-solid fa-xmark text-2xl text-white hover:text-gray-300 transition"></i>
                    </button>
                </div>

                <nav>
                    <ul class="space-y-4">
                        <li>
                            <a href="../admin/index.php" class="flex items-center p-2 hover:bg-slate-600 rounded">
                                <i class="fa-solid fa-house mr-2"></i>
                                Home
                            </a>
                        </li>
                        <li>
                            <a href="../admin/kegiatan.php" class="flex items-center p-2 hover:bg-slate-600 rounded">
                                <i class="fa-solid fa-clipboard-list mr-2"></i>
                                Daftar Kegiatan
                            </a>
                        </li>
                        <li>
                            <a href="../admin/pertemuan.php" class="flex items-center p-2 hover:bg-slate-600 rounded">
                                <i class="fa-solid fa-calendar mr-2"></i>
                                List Pertemuan
                            </a>
                        </li>
                        <li>
                            <a href="../admin/anggota.php" class="flex items-center p-2 hover:bg-slate-600 rounded">
                                <i class="fa-solid fa-users mr-2"></i>
                                Manage User UKM
                            </a>
                        </li>
                        <li>
                            <a href="../admin/informasi.php" class="flex items-center p-2 hover:bg-slate-600 rounded">
                                <i class="fa-solid fa-circle-info mr-2"></i>
                                Manage Information
                            </a>
                        </li>
                        <li>
                            <a href="../page/logout.php" class="flex items-center p-2 hover:bg-slate-600 rounded mt-4">
                                <i class="fa-solid fa-right-from-bracket mr-2"></i>
                                Sign Out
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col">
            <!-- Header -->
            <header class="text-white p-4 flex justify-between items-center" style="background-color: #727DB6; z-index: 999;">
                <button id="menuButton" class="p-2">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <h1 class="text-3xl font-bold">COSMIC ESPORT</h1>
                <!-- Profile Image -->
                <a href="profil.php" class="w-16 h-16 rounded-full overflow-hidden">
                    <img src="<?php echo !empty($user['profile_image']) ? $user['profile_image'] : '../src/default.png'; ?>" alt="Profile Image" class="w-full h-full object-cover">
                </a>
            </header>

            <main class="flex-1 overflow-y-auto h-[calc(100vh-5rem)]">
                <section class="flex flex-wrap gap-4 p-4">
                    <!-- Kotak Jumlah Anggota -->
                    <div class="flex-1 min-w-[200px] p-6 bg-blue-500 text-white rounded-lg shadow-lg">
                        <h2 class="text-xl font-bold">Jumlah Anggota</h2>
                        <p class="text-3xl mt-2"><?php echo $jumlahAnggota; ?></p>
                    </div>

                    <!-- Kotak Jumlah Kegiatan -->
                    <div class="flex-1 min-w-[200px] p-6 bg-green-500 text-white rounded-lg shadow-lg">
                        <h2 class="text-xl font-bold">Total Kegiatan</h2>
                        <p class="text-3xl mt-2"><?php echo $jumlahKegiatan; ?></p>
                    </div>

                    <!-- Kotak Jumlah Pertemuan -->
                    <div class="flex-1 min-w-[200px] p-6 bg-purple-500 text-white rounded-lg shadow-lg">
                        <h2 class="text-xl font-bold">Total Pertemuan</h2>
                        <p class="text-3xl mt-2"><?php echo $jumlahPertemuan; ?></p>
                    </div>

                    <!-- Kotak Jumlah Informasi -->
                    <div class="flex-1 min-w-[200px] p-6 bg-red-500 text-white rounded-lg shadow-lg">
                        <h2 class="text-xl font-bold">Jumlah Informasi</h2>
                        <p class="text-3xl mt-2"><?php echo $jumlahInformasi; ?></p>
                    </div>
                </section>

                <section class="p-4">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-bold">Jadwal Pertemuan</h2>
                </div>

                <!-- Daftar Pertemuan Rutin -->
                <div id="daftarPertemuan" class="space-y-4">
                    <?php while ($row = $resultPertemuan->fetchArray(SQLITE3_ASSOC)): ?>
                        <a href="detail_pertemuan.php?id=<?php echo $row['id']; ?>" class="block p-4 bg-white rounded-lg shadow hover:bg-gray-50 transition">
                            <h3 class="text-xl font-bold"><?php echo htmlspecialchars($row['nama_topik']); ?></h3>
                            <p class="text-gray-600">Hari, Tanggal : <?php echo htmlspecialchars($row['hari']); ?>, <?php echo htmlspecialchars($row['tanggal']); ?></p>
                            <p class="text-gray-600">Jam : <?php echo htmlspecialchars($row['jam_pertemuan']); ?></p>
                            <p class="text-gray-600">Ruangan : <span class="text-purple-600 font-bold"><?php echo htmlspecialchars($row['kelas']); ?></span></p>
                        </a>
                    <?php endwhile; ?>
                </div>
            </section>

            </main>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const sidebar = document.getElementById('sidebar');
                const menuButton = document.getElementById('menuButton');
                const closeSidebar = document.getElementById('closeSidebar');

                menuButton.addEventListener('click', () => {
                    sidebar.classList.toggle('hidden');
                });

                closeSidebar.addEventListener('click', () => {
                    sidebar.classList.add('hidden');
                });

                 // Tampilkan SweetAlert2 mixin jika pengguna baru saja login
                 <?php if (isset($_SESSION['just_logged_in'])): ?>
                    const Toast = Swal.mixin({
                        toast: true,
                        position: "top-end",
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true,
                        customClass: {
                            timerProgressBar: 'swal2-progress-bar' // Gunakan class CSS khusus
                        }
                    });
                    Toast.fire({
                        icon: "success",
                        title: "Signed in successfully"
                    });

                    // Hapus session `just_logged_in` setelah SweetAlert ditampilkan
                    <?php unset($_SESSION['just_logged_in']); ?>
                <?php endif; ?>
            });
        </script>
    </body>
</html>