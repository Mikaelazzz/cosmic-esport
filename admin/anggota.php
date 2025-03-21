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

// Query untuk mengambil data dari tabel users dan absen
$query = "
    SELECT u.id, u.nim, u.nama_lengkap, u.email, u.jabatan, u.profile_image, COUNT(a.nim) AS total_absen
    FROM users u
    LEFT JOIN absen a ON u.nim = a.nim
    GROUP BY u.id
";
$result = $db->query($query);

// Inisialisasi nomor urut
$no = 1;
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
                                <tbody>
                                <?php 
                                // Reset counter for desktop view
                                $no = 1;
                                // Reset result pointer
                                $db->query("BEGIN"); // Start transaction to reset query
                                $result = $db->query($query);
                                while ($row = $result->fetchArray(SQLITE3_ASSOC)): 
                                ?>
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
                        <div class="mobile-view-card space-y-4">
                            <?php 
                            // Reset counter for mobile view
                            $no = 1;
                            $result = $db->query($query);
                            while ($row = $result->fetchArray(SQLITE3_ASSOC)): 
                            ?>
                                <div class="p-3 rounded-lg bg-blue-200 shadow-sm" data-jabatan="<?php echo htmlspecialchars($row['jabatan'] ?? ''); ?>">
                                    <div class="flex items-center justify-center mb-2 ">
                                        <img src="<?php echo !empty($row['profile_image']) ? htmlspecialchars($row['profile_image']) : '../src/default.png'; ?>" alt="Profile Image" class="w-20 h-20 rounded-full object-cover mr-3 ">
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
                                <select class="border border-gray-300 rounded-md p-1">
                                    <option>10</option>
                                    <option>20</option>
                                    <option>30</option>
                                </select>
                            </div>
                            <div class="text-gray-700 flex items-center">
                                <span>1-10 of 50 items</span>
                                <button class="ml-2 p-1"><i class="fas fa-chevron-left"></i></button>
                                <button class="ml-2 p-1"><i class="fas fa-chevron-right"></i></button>
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

        // Function to filter table based on jabatan and search input
        function filterTable() {
            // Get all filter values
            const filterBPH = document.getElementById('filterBPH').checked || document.getElementById('filterBPHDesktop').checked;
            const filterAnggota = document.getElementById('filterAnggota').checked || document.getElementById('filterAnggotaDesktop').checked;
            const searchText = (document.getElementById('searchInput').value || document.getElementById('searchInputMobile').value).toLowerCase();
            
            // Filter desktop table rows
            const desktopRows = document.querySelectorAll('.desktop-view-table tbody tr');
            desktopRows.forEach(row => {
                filterRow(row, filterBPH, filterAnggota, searchText);
            });
            
            // Filter mobile cards
            const mobileCards = document.querySelectorAll('.mobile-view-card > div');
            mobileCards.forEach(card => {
                filterRow(card, filterBPH, filterAnggota, searchText);
            });
        }

        function filterRow(row, filterBPH, filterAnggota, searchText) {
            const jabatan = row.getAttribute('data-jabatan').toLowerCase();
            
            // Check if row matches the selected jabatan filter
            const isBPH = 
                jabatan === 'ketua' ||
                jabatan === 'wakil' ||
                jabatan === 'bendahara' ||
                jabatan === 'sekretaris'||
                jabatan === 'acara' ||
                jabatan === 'pdd';

            const matchesJabatan = 
                (filterBPH && isBPH) || // Jika BPH dipilih, tampilkan Ketua, Wakil, Bendahara, Sekretaris
                (filterAnggota && jabatan === 'anggota') || // Jika Anggota dipilih, tampilkan Anggota
                (!filterBPH && !filterAnggota); // Tampilkan semua jika tidak ada filter yang dipilih

            // Check if row matches the search text
            const rowText = row.textContent.toLowerCase();
            const matchesSearch = rowText.includes(searchText);

            // Show row only if it matches both filter and search criteria
            row.style.display = (matchesJabatan && matchesSearch) ? '' : 'none';
        }

        // Sync desktop and mobile filter checkboxes
        document.getElementById('filterBPH').addEventListener('change', function() {
            document.getElementById('filterBPHDesktop').checked = this.checked;
            filterTable();
        });

        document.getElementById('filterBPHDesktop').addEventListener('change', function() {
            document.getElementById('filterBPH').checked = this.checked;
            filterTable();
        });

        document.getElementById('filterAnggota').addEventListener('change', function() {
            document.getElementById('filterAnggotaDesktop').checked = this.checked;
            filterTable();
        });

        document.getElementById('filterAnggotaDesktop').addEventListener('change', function() {
            document.getElementById('filterAnggota').checked = this.checked;
            filterTable();
        });

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