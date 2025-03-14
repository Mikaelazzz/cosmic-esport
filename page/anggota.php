<?php
// Mulai session
session_start();

// Cek apakah pengguna sudah login
if (!isset($_SESSION['user'])) {
    header("Location: ../page/login.php");
    exit();
}

// Ambil data pengguna dari session
$user = $_SESSION['user'];

// Koneksi ke database SQLite3
$db = new SQLite3('../db/ukm.db');

// Query untuk mengambil data anggota
$limit = 10; // Jumlah data per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Ambil kata kunci pencarian dari URL
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Query untuk mengambil data anggota dengan filter pencarian
$query = "
    SELECT 
        users.id, 
        users.nim, 
        users.nama_lengkap, 
        users.jabatan, 
        users.profile_image, 
        COUNT(absen.id) AS jumlah_absen 
    FROM 
        users 
    LEFT JOIN 
        absen ON users.nim = absen.nim 
    WHERE 
        users.nama_lengkap LIKE '%$search%' OR 
        users.nim LIKE '%$search%'
    GROUP BY 
        users.id 
    ORDER BY 
        users.nama_lengkap ASC 
    LIMIT $limit OFFSET $offset
";

// Query untuk menghitung total data dengan filter pencarian
$totalQuery = "SELECT COUNT(*) as total FROM users";
if (!empty($search)) {
    $totalQuery .= " WHERE nama_lengkap LIKE '%$search%' OR nim LIKE '%$search%'";
}
$totalResult = $db->query($totalQuery);
$totalRow = $totalResult->fetchArray(SQLITE3_ASSOC);
$totalData = $totalRow['total'];

// Eksekusi query utama
$result = $db->query($query);

// Cek apakah ada data yang ditemukan
$hasData = $result->fetchArray(SQLITE3_ASSOC) !== false;
$result->reset(); // Reset pointer hasil query untuk digunakan kembali
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

            <!-- Main Content Area -->
<!-- Main Content Area -->
<main class="flex-1 overflow-y-auto h-[calc(100vh-5rem)]">
                <section class="p-6 bg-gray-100 min-h-screen">
        <!-- Bagian Search -->
        <div class="sticky top-0 bg-gray-100 py-4 z-10">
            <div class="max-w-8xl">
                <div class="relative">
                    <input 
                        type="text" 
                        id="searchInput" 
                        placeholder="Search.." 
                        value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" 
                        class="w-full pl-4 pr-10 py-2 rounded-2xl bg-blue-100 border border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                    <button id="searchButton" class="absolute right-3 top-1/2 transform -translate-y-1/2">
                        <i class="fas fa-search text-gray-500"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Bagian h1 Kegiatan UKM -->
        <div class="max-w-7xl mt-4 mb-2">
            <h1 class="text-2xl font-bold">Anggota UKM</h1>
        </div>

                    <!-- Member List -->
                    <div class="space-y-4">
                        <?php if ($hasData): ?>
                            <?php while ($row = $result->fetchArray(SQLITE3_ASSOC)): ?>
                                <div class="bg-white rounded-lg shadow-md p-4 flex items-center justify-between">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-[100px] h-[100px] bg-gray-200 rounded-full overflow-hidden">
                                            <img src="<?php echo !empty($row['profile_image']) ? htmlspecialchars($row['profile_image']) : '../src/default.png'; ?>" alt="Profile Image" class="w-[100px] h-[100px] rounded-full object-cover">
                                        </div>
                                        <div class="max-w-[150px] sm:max-w-[400px] break-words">
                                            <h3 class="font-semibold text-base w-24 md:w-48 md:text-lg break-words"><?php echo htmlspecialchars($row['nama_lengkap']); ?></h3>
                                            <p class="text-gray-600"><?php echo htmlspecialchars($row['nim']); ?></p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-semibold"><?php echo htmlspecialchars($row['jumlah_absen']); ?> / 12</p>
                                        <p class="text-gray-600"><?php echo htmlspecialchars($row['jabatan']); ?></p>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <!-- Tampilkan pesan jika tidak ada data -->
                            <div class="text-center py-10">
                                <p class="text-gray-600 text-lg">
                                    Anggota <span class="font-semibold text-blue-600"><?php echo htmlspecialchars($search); ?></span> yang anda cari tidak ditemukan.
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalData > $limit): ?>
                        <div class="flex justify-center mt-6 space-x-4">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="px-6 py-2 bg-[#727DB6] text-white rounded-md hover:bg-[#5c6491] transition">
                                    Sebelumnya
                                </a>
                            <?php endif; ?>
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="px-6 py-2 bg-[#727DB6] text-white rounded-md hover:bg-[#5c6491] transition">
                                    Selanjutnya
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </main>

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

        // Search
        const searchInput = document.getElementById('searchInput');
        const searchButton = document.getElementById('searchButton');

        // Fungsi untuk melakukan pencarian
        function performSearch() {
            const searchTerm = searchInput.value.trim();
            if (searchTerm) {
                window.location.href = `?search=${encodeURIComponent(searchTerm)}&page=1`;
            } else {
                window.location.href = `?page=1`; // Jika search kosong, kembali ke halaman 1
            }
        }

        // Event listener untuk tombol Enter pada input search
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                performSearch();
            }
        });

        // Event listener untuk tombol search
        searchButton.addEventListener('click', () => {
            performSearch();
        });

    </script>
</body>
</html>

<?php
// Menutup koneksi database
$db->close();
?>