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
if ($user['role'] !== 'admin') {
    header("Location: ../page/home.php");
    exit();
}

// Koneksi ke database SQLite3
$db = new SQLite3('../db/ukm.db');

// Inisialisasi variabel pagination
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Jika request AJAX, kembalikan data dalam format JSON
if (isset($_GET['ajax'])) {
    // Parameter filter
    $filterBPH = isset($_GET['filterBPH']) ? $_GET['filterBPH'] : false;
    $filterAnggota = isset($_GET['filterAnggota']) ? $_GET['filterAnggota'] : false;
    $searchText = isset($_GET['search']) ? $_GET['search'] : '';
    
    // Query dasar
    $baseQuery = "
        SELECT u.id, u.nim, u.nama_lengkap, u.email, u.jabatan, u.profile_image, COUNT(a.nim) AS total_absen
        FROM users u
        LEFT JOIN absen a ON u.nim = a.nim
    ";
    
    // Tambahkan kondisi WHERE berdasarkan filter
    $whereConditions = [];
    $params = [];
    
    if ($filterBPH == 'true') {
        $whereConditions[] = "(u.jabatan IN ('Ketua', 'Wakil', 'Sekretaris', 'Bendahara', 'Acara', 'PDD'))";
    }
    
    if ($filterAnggota == 'true') {
        $whereConditions[] = "(u.jabatan = 'Anggota')";
    }
    
    if (!empty($searchText)) {
        $whereConditions[] = "(u.nama_lengkap LIKE :search OR u.nim LIKE :search OR u.email LIKE :search)";
        $params[':search'] = '%' . $searchText . '%';
    }
    
    // Gabungkan query
    $query = $baseQuery;
    if (!empty($whereConditions)) {
        $query .= " WHERE " . implode(' OR ', $whereConditions);
    }
    $query .= " GROUP BY u.id LIMIT :limit OFFSET :offset";
    
    // Prepare statement
    $stmt = $db->prepare($query);
    
    // Bind parameter limit dan offset
    $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
    $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
    
    // Bind parameter search jika ada
    if (!empty($searchText)) {
        $stmt->bindValue(':search', $searchText, SQLITE3_TEXT);
    }
    
    // Eksekusi query
    $result = $stmt->execute();
    
    // Query untuk total data dengan filter yang sama
    $totalQuery = "SELECT COUNT(*) as total FROM ($query)";
    $totalStmt = $db->prepare(str_replace('LIMIT :limit OFFSET :offset', '', $totalQuery));
    
    // Bind parameter untuk total query
    if (!empty($searchText)) {
        $totalStmt->bindValue(':search', $searchText, SQLITE3_TEXT);
    }
    
    $totalResult = $totalStmt->execute();
    $totalUsers = $totalResult->fetchArray(SQLITE3_ASSOC)['total'];

    $data = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $data[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode([
        'data' => $data,
        'total' => $totalUsers,
        'page' => $page,
        'limit' => $limit
    ]);
    exit();
}

// Untuk halaman normal, tampilkan HTML
$totalQuery = "SELECT COUNT(*) as total FROM users";
$totalResult = $db->query($totalQuery);
$totalUsers = $totalResult->fetchArray(SQLITE3_ASSOC)['total'];

// Query untuk mengambil data dengan pagination
$query = "
    SELECT u.id, u.nim, u.nama_lengkap, u.email, u.jabatan, u.profile_image, COUNT(a.nim) AS total_absen
    FROM users u
    LEFT JOIN absen a ON u.nim = a.nim
    GROUP BY u.id
    LIMIT :limit OFFSET :offset
";

$stmt = $db->prepare($query);
$stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
$stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
$result = $stmt->execute();

// Inisialisasi nomor urut
$no = ($page - 1) * $limit + 1;
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
    #filterDropdown {
        z-index: 1000; /* Pastikan dropdown muncul di atas elemen lain */
    }
    
    @media (max-width: 768px) {
        .mobile-view-card {
            display: block;
        }
        .desktop-view-table {
            display: none;
        }
        .header h1 {
            font-size: 1.5rem; /* Ukuran font header lebih kecil */
        }
        .header button {
            padding: 0.5rem; /* Padding tombol lebih kecil */
        }
        .sidebar {
            width: 100%; /* Sidebar full width pada mobile */
        }
    }
    
    @media (min-width: 769px) {
        .mobile-view-card {
            display: none;
        }
        .desktop-view-table {
            display: table;
        }
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
                <h1 class="text-xl md:text-3xl font-bold">COSMIC ESPORT</h1>
                <!-- Profile Image -->
                <a href="profil.php" class="w-10 h-10 md:w-16 md:h-16 rounded-full overflow-hidden">
                    <img src="<?php echo !empty($user['profile_image']) ? $user['profile_image'] : '../src/default.png'; ?>" alt="Profile Image" class="w-full h-full object-cover">
                </a>
            </header>

            <main class="flex-1 overflow-y-auto h-[calc(100vh-5rem)]">
                <section class="p-2 md:p-4">
                <div class="flex flex-1 md:flex-row justify-between items-start md:items-center mb-4">
                    <h2 class="text-gray-700 text-xl">Manage User</h2>
                    <button onclick="showAddUserForm()" class="bg-[#727DB6] hover:bg-[#5c6491] text-white px-4 py-2 rounded-md flex items-center">
                        <i class="fas fa-plus mr-2"></i> Add User
                    </button>
                </div>
                    
    <div class="bg-white rounded-lg shadow-md p-2 md:p-4">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4">
    <h3 class="text-gray-700 text-base md:text-lg mb-2 md:mb-0">User List</h3>
    <div class="flex flex-col md:flex-row w-full md:w-auto">
        <!-- Filter and Search Row for Mobile -->
        <div class="flex w-full space-x-2 md:hidden mb-2">
            <!-- Dropdown Filter - Mobile -->
            <div class="relative flex-1">
                <button id="filterButton" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md flex items-center text-xs w-full justify-between">
                    <span><i class="fas fa-filter mr-2"></i>FILTER</span>
                    <i class="fas fa-chevron-down ml-1"></i>
                </button>
                <div id="filterDropdown" class="absolute left-0 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg hidden" style="z-index:1000;">
                    <div class="p-2">
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" id="filterBPH" class="form-checkbox" value="BPH">
                            <span class="text-xs">BPH</span>
                        </label>
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" id="filterAnggota" class="form-checkbox" value="Anggota">
                            <span class="text-xs">Anggota</span>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Search Input - Mobile -->
            <div class="relative flex-1">
                <input type="text" id="searchInputMobile" placeholder="SEARCH" class="border border-gray-300 rounded-md pl-2 pr-6 py-2 w-full text-xs">
                <i id="searchIconMobile" class="fas fa-search absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-500 cursor-pointer"></i>
            </div>
        </div>
        
        <!-- Desktop Filter and Search -->
        <div class="hidden md:flex md:space-x-4 w-full md:w-auto">
            <!-- Dropdown Filter - Desktop -->
            <div class="relative w-full md:w-auto mb-2 md:mb-0">
                <button id="filterButtonDesktop" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md flex items-center text-base w-full md:w-auto justify-between">
                    <span><i class="fas fa-filter mr-2"></i> Filter</span>
                    <i class="fas fa-chevron-down ml-2"></i>
                </button>
                <div id="filterDropdownDesktop" class="absolute left-0 md:right-0 mt-2 w-full md:w-48 bg-white border border-gray-300 rounded-md shadow-lg hidden" style="z-index:1000;">
                    <div class="p-2">
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" id="filterBPHDesktop" class="form-checkbox" value="BPH">
                            <span>BPH</span>
                        </label>
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" id="filterAnggotaDesktop" class="form-checkbox" value="Anggota">
                            <span>Anggota</span>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Search Input - Desktop -->
            <div class="relative w-full md:w-auto">
                <input type="text" id="searchInput" placeholder="Search User" class="border border-gray-300 rounded-md pl-4 pr-10 py-2 w-full">
                <i id="searchIcon" class="fas fa-search absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 cursor-pointer"></i>
            </div>
        </div>
    </div>
</div>
                        
                        <!-- Desktop View: Table -->
                        <div class="overflow-x-auto">
                        <table class="w-full text-left desktop-view-table">
                            <thead>
                                <tr class="bg-gray-200">
                                    <th class="p-2">No</th>
                                    <th class="p-2">Profile</th>
                                    <th class="p-2">Nama</th>
                                    <th class="p-2">NIM</th>
                                    <th class="p-2">Email</th>
                                    <th class="p-2">Jabatan</th>
                                    <th class="p-2">Absen</th>
                                    <th class="p-2">Action</th>
                                </tr>
                            </thead>
                            <tbody id="desktopTableBody">
                                <?php while ($row = $result->fetchArray(SQLITE3_ASSOC)): ?>
                                <tr class="<?php echo $no % 2 === 0 ? 'bg-blue-200' : 'bg-blue-100'; ?>" data-jabatan="<?php echo htmlspecialchars($row['jabatan'] ?? ''); ?>">
                                    <td class="p-2"><?php echo $no++; ?></td>
                                    <td class="p-2">
                                        <img src="<?php echo !empty($row['profile_image']) ? htmlspecialchars($row['profile_image']) : '../src/default.png'; ?>" alt="Profile Image" class="w-10 h-10 rounded-full object-cover">
                                    </td>
                                    <td class="p-2"><?php echo htmlspecialchars($row['nama_lengkap'] ?? ''); ?></td>
                                    <td class="p-2"><?php echo htmlspecialchars($row['nim'] ?? ''); ?></td>
                                    <td class="p-2"><?php echo htmlspecialchars($row['email'] ?? ''); ?></td>
                                    <td class="p-2"><?php echo htmlspecialchars($row['jabatan'] ?? ''); ?></td>
                                    <td class="p-2"><?php echo htmlspecialchars($row['total_absen'] ?? ''); ?>/14</td>
                                    <td class="p-2 flex">
                                        <button onclick="window.location.href='profil_user.php?id=<?php echo $row['id']; ?>'" class="mr-2 bg-blue-500 hover:bg-blue-700 text-white px-2 py-1 md:px-4 md:py-2 rounded-md flex items-center text-sm">
                                            <i class="fas fa-edit mr-1 md:mr-2"></i> Edit
                                        </button>
                                        <button onclick="confirmDelete(<?php echo $row['id']; ?>)" class="bg-red-500 hover:bg-red-700 text-white px-2 py-1 md:px-4 md:py-2 rounded-md flex items-center text-sm">
                                            <i class="fas fa-trash mr-1 md:mr-2"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                        <!-- Mobile View: Cards -->
                        <div class="mobile-view-card space-y-4" id="mobileCardsContainer">
                        <?php 
                        // Reset result pointer
                        $stmt->reset();
                        $result = $stmt->execute();
                        $no = ($page - 1) * $limit + 1;
                        while ($row = $result->fetchArray(SQLITE3_ASSOC)): 
                        ?>
                            <div class="p-3 rounded-lg bg-blue-200 shadow-sm" data-jabatan="<?php echo htmlspecialchars($row['jabatan'] ?? ''); ?>">
                                <div class="flex items-center justify-center mb-2">
                                    <img src="<?php echo !empty($row['profile_image']) ? htmlspecialchars($row['profile_image']) : '../src/default.png'; ?>" alt="Profile Image" class="w-20 h-20 rounded-full object-cover mr-3">
                                </div>
                                
                                <div class="text-sm mb-3">
                                    <div class="flex justify-between">
                                        <h4 class="font-semibold"><?php echo htmlspecialchars($row['nama_lengkap'] ?? ''); ?></h4>
                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($row['jabatan'] ?? ''); ?></p>
                                    </div>
                                    <p class="text-xs mb-1 mt-2"><?php echo htmlspecialchars($row['nim'] ?? ''); ?></p>
                                    <p class="text-xs mb-1"><?php echo htmlspecialchars($row['email'] ?? ''); ?></p>
                                    <p class="text-xs mb-1 mt-2"><?php echo htmlspecialchars($row['total_absen'] ?? ''); ?>/14</p>
                                </div>
                                
                                <div class="flex space-x-2">
                                    <button onclick="window.location.href='profil_user.php?id=<?php echo $row['id']; ?>'" class="flex-1 bg-blue-500 hover:bg-blue-700 text-white px-3 py-2 rounded-md flex items-center justify-center text-sm">
                                        <i class="fas fa-edit mr-2"></i> Edit
                                    </button>
                                    <button onclick="confirmDelete(<?php echo $row['id']; ?>)" class="flex-1 bg-red-500 hover:bg-red-700 text-white px-3 py-2 rounded-md flex items-center justify-center text-sm">
                                        <i class="fas fa-trash mr-2"></i> Delete
                                    </button>
                                </div>
                            </div>
                        <?php $no++; endwhile; ?>
                    </div>
                    
                        
                        <div class="flex flex-col md:flex-row justify-between items-center mt-4 text-sm">
                            <div class="text-gray-700 mb-2 md:mb-0">
                                <span class="mr-2">Rows per page:</span>
                                <select id="rowsPerPage" class="border border-gray-300 rounded-md p-1" onchange="changeRowsPerPage()">
                                    <option value="10">10</option>
                                    <option value="20">20</option>
                                    <option value="30">30</option>
                                </select>
                            </div>
                            <div class="text-gray-700 flex items-center">
                                <span id="paginationInfo">1-10 of <?php echo $totalUsers; ?> items</span>
                                <button id="prevPage" class="ml-2 p-1" onclick="changePage(-1)" disabled><i class="fas fa-chevron-left"></i></button>
                                <button id="nextPage" class="ml-2 p-1" onclick="changePage(1)"><i class="fas fa-chevron-right"></i></button>
                            </div>
                        </div>
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
        // Variabel global untuk pagination
        let currentPage = 1;
        let rowsPerPage = 10;
        let totalUsers = <?php echo $totalUsers; ?>;
        let filteredUsers = totalUsers;
        let filterBPHValue = false;
        let filterAnggotaValue = false;
        let searchValue = "";
        
        // Fungsi untuk memuat data dari server
        function loadData(page, limit) {
            const url = new URL(window.location);
            url.searchParams.set('page', page);
            url.searchParams.set('limit', limit);
            url.searchParams.set('ajax', 'true');

            // Tambahkan parameter filter
            const filterBPH = document.getElementById('filterBPH').checked || document.getElementById('filterBPHDesktop').checked;
            const filterAnggota = document.getElementById('filterAnggota').checked || document.getElementById('filterAnggotaDesktop').checked;
            const searchText = (document.getElementById('searchInput').value || document.getElementById('searchInputMobile').value).trim();
            
            if (filterBPH) url.searchParams.set('filterBPH', 'true');
            if (filterAnggota) url.searchParams.set('filterAnggota', 'true');
            if (searchText) url.searchParams.set('search', searchText);

            fetch(url.toString())
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (!data.data || data.data.length === 0) {
                        // Tampilkan pesan jika tidak ada hasil
                        showNoResultsMessage();
                    } else {
                        updateTable(data.data);
                    }
                    totalUsers = data.total;
                    currentPage = data.page;
                    rowsPerPage = data.limit;
                    updatePagination();
                    
                    // Hapus parameter ajax dari URL
                    const cleanUrl = new URL(url.toString());
                    cleanUrl.searchParams.delete('ajax');
                    window.history.pushState({}, '', cleanUrl.toString());
                })
                .catch(error => {
                    console.error('Error loading data:', error);
                    showErrorLoadingData();
                });
        }

        // Fungsi untuk menampilkan pesan tidak ada hasil
        function showNoResultsMessage() {
            const desktopTableBody = document.getElementById('desktopTableBody');
            const mobileCardsContainer = document.getElementById('mobileCardsContainer');
            
            desktopTableBody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center p-4">Tidak ditemukan data yang sesuai dengan pencarian</td>
                </tr>
            `;
            
            mobileCardsContainer.innerHTML = `
                <div class="p-4 text-center">Tidak ditemukan data yang sesuai dengan pencarian</div>
            `;
        }

        // Fungsi untuk menampilkan pesan error
        function showErrorLoadingData() {
            const desktopTableBody = document.getElementById('desktopTableBody');
            const mobileCardsContainer = document.getElementById('mobileCardsContainer');
            
            desktopTableBody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center p-4 text-red-500">Gagal memuat data. Silakan coba lagi.</td>
                </tr>
            `;
            
            mobileCardsContainer.innerHTML = `
                <div class="p-4 text-center text-red-500">Gagal memuat data. Silakan coba lagi.</div>
            `;
        }

        // Fungsi untuk menghapus parameter ajax dari URL
        function removeAjaxParam(urlString) {
            const url = new URL(urlString);
            url.searchParams.delete('ajax');
            return url.toString();
        }

                // Fungsi untuk memperbarui tabel dengan data baru
                function updateTable(data) {
            // Update desktop table
            const desktopTableBody = document.getElementById('desktopTableBody');
            desktopTableBody.innerHTML = '';
            
            // Update mobile cards
            const mobileCardsContainer = document.getElementById('mobileCardsContainer');
            mobileCardsContainer.innerHTML = '';
            
            data.forEach((row, index) => {
                // Nomor urut berdasarkan halaman dan limit
                const rowNumber = (currentPage - 1) * rowsPerPage + index + 1;
                
                // Tambahkan baris untuk desktop
                desktopTableBody.innerHTML += `
                    <tr class="${rowNumber % 2 === 0 ? 'bg-blue-200' : 'bg-blue-100'}" data-jabatan="${row.jabatan || ''}">
                        <td class="p-2">${rowNumber}</td>
                        <td class="p-2">
                            <img src="${row.profile_image || '../src/default.png'}" alt="Profile Image" class="w-10 h-10 rounded-full object-cover">
                        </td>
                        <td class="p-2">${row.nama_lengkap || ''}</td>
                        <td class="p-2">${row.nim || ''}</td>
                        <td class="p-2">${row.email || ''}</td>
                        <td class="p-2">${row.jabatan || ''}</td>
                        <td class="p-2">${row.total_absen || 0}/14</td>
                        <td class="p-2 flex">
                            <button onclick="window.location.href='profil_user.php?id=${row.id}'" class="mr-2 bg-blue-500 hover:bg-blue-700 text-white px-2 py-1 md:px-4 md:py-2 rounded-md flex items-center text-sm">
                                <i class="fas fa-edit mr-1 md:mr-2"></i> Edit
                            </button>
                            <button onclick="confirmDelete(${row.id})" class="bg-red-500 hover:bg-red-700 text-white px-2 py-1 md:px-4 md:py-2 rounded-md flex items-center text-sm">
                                <i class="fas fa-trash mr-1 md:mr-2"></i> Delete
                            </button>
                        </td>
                    </tr>
                `;
                
                // Tambahkan card untuk mobile
                mobileCardsContainer.innerHTML += `
                    <div class="p-3 rounded-lg bg-blue-200 shadow-sm" data-jabatan="${row.jabatan || ''}">
                        <div class="flex items-center justify-center mb-2">
                            <img src="${row.profile_image || '../src/default.png'}" alt="Profile Image" class="w-20 h-20 rounded-full object-cover mr-3">
                        </div>
                        <div class="text-sm mb-3">
                            <div class="flex justify-between">
                                <h4 class="font-semibold">${row.nama_lengkap || ''}</h4>
                                <p class="text-sm text-gray-600">${row.jabatan || ''}</p>
                            </div>
                            <p class="text-xs mb-1 mt-2">${row.nim || ''}</p>
                            <p class="text-xs mb-1">${row.email || ''}</p>
                            <p class="text-xs mb-1 mt-2">${row.total_absen || 0}/14</p>
                        </div>
                        <div class="flex space-x-2">
                            <button onclick="window.location.href='profil_user.php?id=${row.id}'" class="flex-1 bg-blue-500 hover:bg-blue-700 text-white px-3 py-2 rounded-md flex items-center justify-center text-sm">
                                <i class="fas fa-edit mr-2"></i> Edit
                            </button>
                            <button onclick="confirmDelete(${row.id})" class="flex-1 bg-red-500 hover:bg-red-700 text-white px-3 py-2 rounded-md flex items-center justify-center text-sm">
                                <i class="fas fa-trash mr-2"></i> Delete
                            </button>
                        </div>
                    </div>
                `;
            });
        }

                // Fungsi untuk mengupdate tampilan pagination
                function updatePagination() {
            const start = (currentPage - 1) * rowsPerPage + 1;
            const end = Math.min(currentPage * rowsPerPage, totalUsers);
            
            document.getElementById('paginationInfo').textContent = `${start}-${end} of ${totalUsers} items`;
            
            // Disable tombol previous jika di halaman pertama
            document.getElementById('prevPage').disabled = currentPage === 1;
            
            // Disable tombol next jika di halaman terakhir
            document.getElementById('nextPage').disabled = end >= totalUsers;
            
            // Update nilai dropdown rows per page
            document.getElementById('rowsPerPage').value = rowsPerPage;
        }

        // Fungsi untuk mengubah jumlah baris per halaman
        function changeRowsPerPage() {
            rowsPerPage = parseInt(document.getElementById('rowsPerPage').value);
            currentPage = 1; // Reset ke halaman pertama
            loadData(currentPage, rowsPerPage);
        }

        // Fungsi untuk mengganti halaman
        function changePage(direction) {
            const newPage = currentPage + direction;
            loadData(newPage, rowsPerPage);
        }

        // Tangani perubahan URL dengan popstate
        window.addEventListener('popstate', function(event) {
            // Baca parameter dari URL saat ini
            const urlParams = new URLSearchParams(window.location.search);
            const page = urlParams.get('page') || 1;
            const limit = urlParams.get('limit') || 10;
            
            // Perbarui tampilan dropdown
            document.getElementById('rowsPerPage').value = limit;
            
            // Muat data sesuai parameter URL
            loadData(parseInt(page), parseInt(limit));
        });

        // Fungsi untuk memperbarui URL dengan parameter pagination
        function updateURLWithPagination() {
            const url = new URL(window.location);
            url.searchParams.set('page', currentPage);
            url.searchParams.set('limit', rowsPerPage);
            
            // Update URL without reloading the page
            window.history.pushState({}, '', url);
        }

        // Fungsi untuk membaca parameter pagination dari URL
        function readPaginationFromURL() {
            const urlParams = new URLSearchParams(window.location.search);
            
            // Read page and limit from URL if they exist
            if (urlParams.has('page')) {
                currentPage = parseInt(urlParams.get('page'));
            }
            
            if (urlParams.has('limit')) {
                rowsPerPage = parseInt(urlParams.get('limit'));
            }
        }

        function updateVisibleRows() {
    const startIndex = (currentPage - 1) * rowsPerPage;
    const endIndex = startIndex + rowsPerPage;
    
    // Reset counters
    let visibleCount = 0;
    let displayedCount = 0;
    
    // Desktop: Update table
    const desktopRows = document.querySelectorAll('.desktop-view-table tbody tr');
    desktopRows.forEach(row => {
        // First determine if row should be visible based on filters
        if (row.dataset.filtered !== 'hidden') {
            // This is a row that passes the filter
            visibleCount++;
            
            // Now determine if it should be shown on current page
            if (visibleCount > startIndex && visibleCount <= endIndex) {
                row.style.display = '';
                displayedCount++;
            } else {
                row.style.display = 'none';
            }
        } else {
            row.style.display = 'none';
        }
    });
    
    // Mobile: Update cards with same logic
    visibleCount = 0;
    const mobileCards = document.querySelectorAll('.mobile-view-card > div');
    mobileCards.forEach(card => {
        if (card.dataset.filtered !== 'hidden') {
            visibleCount++;
            
            if (visibleCount > startIndex && visibleCount <= endIndex) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        } else {
            card.style.display = 'none';
        }
    });
}

    // Fungsi untuk handle search ketika icon diklik
    function handleSearchClick() {
        const searchInput = document.getElementById('searchInput');
        const searchInputMobile = document.getElementById('searchInputMobile');
        const searchText = searchInput.value.trim() || searchInputMobile.value.trim();
        
        // Jika search kosong, hapus parameter dari URL
        if (!searchText) {
            const url = new URL(window.location);
            url.searchParams.delete('search');
            window.history.pushState({}, '', url.toString());
        }
        
        // Panggil filterTable untuk memuat data
        filterTable();
    }


    // Event listener untuk tombol search icon
    document.getElementById('searchIcon').addEventListener('click', function() {
        const searchInput = document.getElementById('searchInput');
        if (searchInput.value.trim() === '') {
            // Jika search kosong, hapus parameter dari URL
            const url = new URL(window.location);
            url.searchParams.delete('search');
            window.history.pushState({}, '', url.toString());
        }
        loadData(1, rowsPerPage); // Selalu reset ke halaman pertama saat search
    });
    document.getElementById('searchIconMobile').addEventListener('click', function() {
        const searchInputMobile = document.getElementById('searchInputMobile');
        if (searchInputMobile.value.trim() === '') {
            // Jika search kosong, hapus parameter dari URL
            const url = new URL(window.location);
            url.searchParams.delete('search');
            window.history.pushState({}, '', url.toString());
        }
        loadData(1, rowsPerPage); // Selalu reset ke halaman pertama saat search
    });

    // Event listener untuk input search (desktop dan mobile)
    document.getElementById('searchInput').addEventListener('input', function() {
        clearTimeout(this.timer);
        this.timer = setTimeout(() => {
            filterTable();
        }, 500);
    });

    document.getElementById('searchInputMobile').addEventListener('input', function() {
        clearTimeout(this.timer);
        this.timer = setTimeout(() => {
            filterTable();
        }, 500);
    });

    // Pastikan kedua input search selalu sinkron
    document.getElementById('searchInput').addEventListener('change', function() {
        document.getElementById('searchInputMobile').value = this.value;
    });

    document.getElementById('searchInputMobile').addEventListener('change', function() {
        document.getElementById('searchInput').value = this.value;
    });
        
    // Set initial filter state for all rows/cards
    document.addEventListener('DOMContentLoaded', function() {
    
        const urlParams = new URLSearchParams(window.location.search);
    // Event listener untuk checkbox filter
    document.querySelectorAll('#filterDropdown input[type="checkbox"], #filterDropdownDesktop input[type="checkbox"]').forEach(checkbox => {
        checkbox.addEventListener('change', filterTable);
    });

    // Set nilai search input jika ada di URL
    if (urlParams.has('search')) {
    const searchValue = urlParams.get('search');
    document.getElementById('searchInput').value = searchValue;
    document.getElementById('searchInputMobile').value = searchValue;
    }
    
    // Ganti dengan inisialisasi default unchecked
    document.getElementById('filterBPH').checked = false;
    document.getElementById('filterBPHDesktop').checked = false;
    document.getElementById('filterAnggota').checked = false;
    document.getElementById('filterAnggotaDesktop').checked = false;

    // Load data awal
    const page = urlParams.get('page') || 1;
    const limit = urlParams.get('limit') || 10;
    document.getElementById('rowsPerPage').value = limit;
    loadData(parseInt(page), parseInt(limit));
});

        // Sidebar
        menuButton.addEventListener('click', () => {
            sidebar.classList.toggle('hidden');
        });

        closeSidebar.addEventListener('click', () => {
            sidebar.classList.add('hidden');
        });

        // Toggle dropdown filter (Desktop)
        const filterButtonDesktop = document.getElementById('filterButtonDesktop');
        const filterDropdownDesktop = document.getElementById('filterDropdownDesktop');

        filterButtonDesktop.addEventListener('click', (e) => {
            e.stopPropagation();
            filterDropdownDesktop.classList.toggle('hidden');
        });

        // Toggle dropdown filter (Mobile)
        const filterButton = document.getElementById('filterButton');
        const filterDropdown = document.getElementById('filterDropdown');

        filterButton.addEventListener('click', (e) => {
            e.stopPropagation();
            filterDropdown.classList.toggle('hidden');
        });

        // Close dropdowns when clicking outside
        document.addEventListener('click', (event) => {
            if (!filterButtonDesktop.contains(event.target) && !filterDropdownDesktop.contains(event.target)) {
                filterDropdownDesktop.classList.add('hidden');
            }
            if (!filterButton.contains(event.target) && !filterDropdown.contains(event.target)) {
                filterDropdown.classList.add('hidden');
            }
        });

        // Modifikasi fungsi filterTable untuk langsung memuat data saat checkbox diubah
        function filterTable() {
            const filterBPH = document.getElementById('filterBPH').checked || document.getElementById('filterBPHDesktop').checked;
            const filterAnggota = document.getElementById('filterAnggota').checked || document.getElementById('filterAnggotaDesktop').checked;
            const searchText = (document.getElementById('searchInput').value || document.getElementById('searchInputMobile').value).trim();
            
            // Reset ke halaman pertama saat filter berubah
            currentPage = 1;
            
            // Buat URL baru tanpa parameter filter terlebih dahulu
            const url = new URL(window.location);
            url.searchParams.delete('filterBPH');
            url.searchParams.delete('filterAnggota');
            url.searchParams.delete('search');
            
            // Tambahkan parameter filter hanya jika checkbox aktif
            if (filterBPH) {
                url.searchParams.set('filterBPH', 'true');
            }
            if (filterAnggota) {
                url.searchParams.set('filterAnggota', 'true');
            }
            // Tambahkan parameter search hanya jika ada teks pencarian
            if (searchText) {
                url.searchParams.set('search', searchText);
            }
            
            // Update URL sebelum memuat data
            window.history.pushState({}, '', url.toString());
            
            // Muat data dengan filter baru
            loadData(currentPage, rowsPerPage);
        }

        // Tambahkan event listener langsung untuk checkbox yang memanggil filterTable
        document.getElementById('filterBPH').addEventListener('change', filterTable);
        document.getElementById('filterBPHDesktop').addEventListener('change', filterTable);
        document.getElementById('filterAnggota').addEventListener('change', filterTable);
        document.getElementById('filterAnggotaDesktop').addEventListener('change', filterTable);


        // Event listeners for search inputs
        document.getElementById('searchInput').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                filterTable();
            }
        });

        document.getElementById('searchInputMobile').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                filterTable();
            }
        });

        // Event listener for search icon clicks
        document.getElementById('searchIcon').addEventListener('click', () => {
            filterTable();
        });

        document.getElementById('searchIconMobile').addEventListener('click', () => {
            filterTable();
        });

        // Delete User
        function confirmDelete(userId) {
            Swal.fire({
                title: 'Apakah ingin Menghapus User ini?',
                text: "Anda tidak dapat mengembalikan data yang telah dihapus!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yakin',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Kirim request penghapusan ke server
                    fetch(`../api/delete_user.php?id=${userId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('Sukses!', data.message, 'success').then(() => {
                                    // Redirect ke halaman anggota.php setelah SweetAlert ditutup
                                    window.location.href = '../admin/anggota.php';
                                });
                            } else {
                                Swal.fire('Gagal!', data.message, 'error');
                            }
                        })
                        .catch(error => {
                            Swal.fire('Error!', 'Terjadi kesalahan saat menghapus pengguna', 'error');
                        });
                }
            });
        }

        // Role Change
        function handleRoleChange() {
            const role = document.getElementById('role').value;
            const jabatanContainer = document.getElementById('jabatanContainer');

            if (role === 'admin') {
                jabatanContainer.classList.remove('hidden');
            } else {
                jabatanContainer.classList.add('hidden');
            }
        }

        // Validate Password Form
        function validatePassword(password) {
            // Validasi password: minimal 1 huruf kapital dan 1 angka
            const hasCapital = /[A-Z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            return hasCapital && hasNumber;
        }

        // Validate NIM Form
        function validateNIM(nim) {
            // Validasi panjang NIM: 9-10 karakter dan hanya angka
            const isNIMValid = /^\d{9,10}$/.test(nim);
            if (!isNIMValid) {
                Swal.showValidationMessage('NIM harus terdiri dari 9-10 angka');
                return false;
            }

            // Validasi apakah NIM sudah digunakan
            return fetch(`../api/check_nim.php?nim=${nim}`)
                .then(response => response.json())
                .then(data => {
                    if (data.exists) {
                        Swal.showValidationMessage('NIM sudah digunakan');
                        return false;
                    }
                    return true;
                });
        }

        // Validate Email Form
        function validateEmail(email) {
            // Validasi email: pastikan email belum digunakan
            return fetch(`../api/check_email.php?email=${email}`)
                .then(response => response.json())
                .then(data => {
                    if (data.exists) {
                        Swal.showValidationMessage('Email sudah digunakan');
                        return false;
                    }
                    return true;
                });
        }

        // Form Add User
        function showAddUserForm() {
            Swal.fire({
                title: 'Tambah User Baru',
                html: `<form id="addUserForm" class="text-left">
                            <div class="mb-4">
                                <label for="nama_lengkap" class="block text-gray-700 text-left">Nama Lengkap</label>
                                <input type="text" id="nama_lengkap" name="nama_lengkap" class="w-full px-3 py-2 border rounded-md" required>
                            </div>
                            <div class="mb-4">
                                <label for="nim" class="block text-gray-700 text-left">NIM</label>
                                <input type="text" id="nim" name="nim" class="w-full px-3 py-2 border rounded-md" required>
                                <small class="text-gray-500">NIM harus terdiri dari 9-10 angka.</small>
                            </div>
                            <div class="mb-4">
                                <label for="email" class="block text-gray-700 text-left">Email</label>
                                <input type="email" id="email" name="email" class="w-full px-3 py-2 border rounded-md" required>
                            </div>
                            <div class="mb-4">
                                <label for="password" class="block text-gray-700 text-left">Password</label>
                                <input type="password" id="password" name="password" class="w-full px-3 py-2 border rounded-md" required>
                                <small class="text-gray-500">Password harus mengandung minimal 1 huruf kapital dan 1 angka.</small>
                            </div>
                            <div class="mb-4">
                                <label for="role" class="block text-gray-700 text-left">Role</label>
                                <select id="role" name="role" class="w-full px-3 py-2 border rounded-md" required onchange="handleRoleChange()">
                                    <option value="anggota">Anggota</option>
                                    <option value="admin">BPH</option>
                                </select>
                            </div>
                            <div id="jabatanContainer" class="mb-4 hidden">
                                <label for="jabatan" class="block text-gray-700 text-left">Jabatan</label>
                                <select id="jabatan" name="jabatan" class="w-full px-3 py-2 border rounded-md">
                                    <option value="Ketua">Ketua</option>
                                    <option value="Wakil">Wakil</option>
                                    <option value="Sekretaris">Sekretaris</option>
                                    <option value="Bendahara">Bendahara</option>
                                    <option value="Acara">Acara</option>
                                    <option value="PDD">PDD</option>
                                </select>
                            </div>
                        </form>`,
                showCancelButton: true,
                confirmButtonText: 'Simpan',
                cancelButtonText: 'Batal',
                focusConfirm: false,
                preConfirm: () => {
                    const namaLengkap = document.getElementById('nama_lengkap').value;
                    const nim = document.getElementById('nim').value;
                    const email = document.getElementById('email').value;
                    const password = document.getElementById('password').value;
                    const role = document.getElementById('role').value;
                    const jabatan = role === 'admin' ? document.getElementById('jabatan').value : 'Anggota';
                    // Generate token
                    const token = generateToken();

                    // Validasi input
                    if (!namaLengkap || !nim || !email || !password || !role) {
                        Swal.showValidationMessage('Harap isi semua field');
                        return false;
                    }

                    // Validasi NIM
                    if (!/^\d{9,10}$/.test(nim)) {
                        Swal.showValidationMessage('NIM harus terdiri dari 9-10 angka');
                        return false;
                    }

                    // Validasi password
                    if (!validatePassword(password)) {
                        Swal.showValidationMessage('Password harus mengandung minimal 1 huruf kapital dan 1 angka');
                        return false;
                    }

                    // Validasi NIM dan Email
                    return Promise.all([validateNIM(nim), validateEmail(email)])
                        .then(results => {
                            if (results.includes(false)) {
                                return false;
                            }

                            // Kirim data ke server
                            return fetch('../api/add_user.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({
                                    nama_lengkap: namaLengkap,
                                    nim: nim,
                                    email: email,
                                    password: password,
                                    role: role,
                                    jabatan: jabatan,
                                    token: token
                                })
                            })
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error('Gagal menambahkan user');
                                }
                                return response.json();
                            })
                            .then(data => {
                                if (data.success) {
                                    Swal.fire('Sukses!', 'User berhasil ditambahkan', 'success');
                                    // Refresh halaman setelah berhasil
                                    setTimeout(() => {
                                        window.location.reload();
                                    }, 1500);
                                } else {
                                    Swal.fire('Gagal!', data.message, 'error');
                                }
                            })
                            .catch(error => {
                                Swal.fire('Error!', error.message, 'error');
                            });
                        });
                }
            });
        }
        // Fungsi untuk generate token
        function generateToken(length = 8) {
            const characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            let token = '';
            for (let i = 0; i < length; i++) {
                token += characters[Math.floor(Math.random() * characters.length)];
            }
            return token;
        }
    </script>
    </body>
</html>