<?php
// Mulai session
session_start();

// Cek apakah pengguna sudah login
if (!isset($_SESSION['user'])) {
    header("Location: ../page/login.php");
    exit();
}

// Set waktu aktivitas terakhir jika belum ada
if (!isset($_SESSION['last_activity'])) {
    $_SESSION['last_activity'] = time();
}

// Cek jika waktu tidak aktif melebihi 1 jam (3600 detik)
if (time() - $_SESSION['last_activity'] > 3600) {
    // Hapus session dan redirect ke halaman login
    session_unset();
    session_destroy();
    header("Location: ../page/login.php");
    exit();
}

// Perbarui waktu aktivitas terakhir
$_SESSION['last_activity'] = time();

// Ambil data pengguna dari session
$user = $_SESSION['user'];

// Koneksi ke database SQLite3
$db = new SQLite3('../db/ukm.db');

// Query untuk mengambil data pertemuan yang belum selesai (jam_akhir IS NULL) dan status kehadiran
$queryPertemuan = "
    SELECT jp.*, a.status as status_kehadiran
    FROM jadwal_pertemuan jp
    LEFT JOIN absen a ON jp.id = a.pertemuan_id AND a.nim = :nim
    WHERE jp.status IN ('selesai')
    ORDER BY jp.tanggal DESC
";
$stmt = $db->prepare($queryPertemuan);
$stmt->bindValue(':nim', $user['nim'], SQLITE3_TEXT); // Asumsikan nim ada di session user
$resultPertemuan = $stmt->execute();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cosmic Esport</title>
    <link rel="icon" type="image/*" href="../src/logo.png">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://unpkg.com/@zxing/library@latest/umd/index.min.js"></script>

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
                            <a href="../page/index.php" class="flex items-center p-2 hover:bg-slate-600 rounded">
                                <i class="fa-solid fa-house mr-2"></i>
                                Home
                            </a>
                        </li>
                        <li>
                            <a href="../page/kegiatan.php" class="flex items-center p-2 hover:bg-slate-600 rounded">
                                <i class="fa-solid fa-clipboard-list mr-2"></i>
                                Daftar Kegiatan
                            </a>
                        </li>
                        <li>
                            <a href="../page/pertemuan.php" class="flex items-center p-2 hover:bg-slate-600 rounded">
                                <i class="fa-solid fa-calendar mr-2"></i>
                                Pertemuan
                            </a>
                        </li>
                        <li>
                            <a href="../page/anggota.php" class="flex items-center p-2 hover:bg-slate-600 rounded">
                                <i class="fa-solid fa-users mr-2"></i>
                                Anggota UKM
                            </a>
                        </li>
                        <li>
                            <button id="modeButton" class="flex items-center w-full p-2 hover:bg-slate-600 rounded">
                                <i class="fa-solid fa-gamepad mr-2"></i>
                                Mode 
                                <i class="fa-solid fa-chevron-down ml-auto transition-transform duration-300" id="modeIcon"></i>
                            </button>
                            <ul id="modeMenu" class="pl-6 mt-2 space-y-2 hidden">
                                <li><a href="../page/calc.php" class="block p-2 hover:bg-slate-600 rounded">Calculator WR</a></li>
                                <li><a href="../page/sg.php" class="block p-2 hover:bg-slate-600 rounded">Search NickGame</a></li>
                            </ul>
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
            <header class=" text-white p-4 flex justify-between items-center" style="background-color: #727DB6; z-index: 999;">
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


                <section class="p-4">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-bold">List Pertemuan</h2>
                </div>
                <!-- Daftar Pertemuan Rutin -->
                <div id="daftarPertemuan" class="space-y-4">
                        <?php while ($row = $resultPertemuan->fetchArray(SQLITE3_ASSOC)): ?>
                            <div class="block p-4 bg-white rounded-lg shadow hover:bg-gray-50 transition">
                                <h3 class="text-xl font-bold"><?php echo htmlspecialchars($row['nama_topik']); ?></h3>
                                <p class="text-gray-600">Hari, Tanggal : <?php echo htmlspecialchars($row['hari']); ?>, <?php echo htmlspecialchars($row['tanggal']); ?></p>
                                <p class="text-gray-600">Jam : <?php echo htmlspecialchars($row['jam_pertemuan']); ?></p>
                                <p class="text-gray-600">Ruangan : <span class="text-purple-600 font-bold"><?php echo htmlspecialchars($row['kelas']); ?></span></p>
                                <p class="text-gray-600">Kehadiran : <?php echo isset($row['status_kehadiran']) ? htmlspecialchars($row['status_kehadiran']) : 'Tidak Hadir'; ?> </p>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </section>
            </main>
        </div>  
        <script>
        const sidebar = document.getElementById('sidebar');
        const menuButton = document.getElementById('menuButton');
        const closeSidebar = document.getElementById('closeSidebar');
        const modeButton = document.getElementById('modeButton');
        const modeMenu = document.getElementById('modeMenu');
        const modeIcon = document.getElementById('modeIcon');

        menuButton.addEventListener('click', () => {
            sidebar.classList.toggle('hidden');
        });

        closeSidebar.addEventListener('click', () => {
            sidebar.classList.add('hidden');
        });

        modeButton.addEventListener('click', (e) => {
            e.preventDefault();
            modeMenu.classList.toggle('hidden');
            modeIcon.classList.toggle('rotate-180');
        });

    </script>
    </body>
</html>