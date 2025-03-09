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
            <header class=" text-white p-4 flex justify-between items-center" style="background-color: #727DB6; z-index: 999;">
                <button id="menuButton" class="p-2">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <h1 class="text-3xl font-bold">COSMIC ESPORT</h1>
                <!-- Profile Image -->
                <a href="profil.php" class="w-16 h-16 rounded-full overflow-hidden">
                    <img src="<?php echo !empty($user['profile_image']) ? $user['profile_image'] : '../src/1.png'; ?>" alt="Profile Image" class="w-full h-full object-cover">
                </a>
            </header>


            <main class="flex-1 overflow-y-auto h-[calc(100vh-5rem)]">
                <section class="p-4">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-gray-700 text-xl">Manage User</h2>
                        <button onclick="showAddUserForm()" class="bg-purple-500 text-white px-4 py-2 rounded-md flex items-center">
                            <i class="fas fa-plus mr-2"></i> Add User
                        </button>
                    </div>
                    <div class="bg-white rounded-lg shadow-md p-4">
                    <div class="flex justify-between items-center mb-4">
                    <h3 class="text-gray-700 text-lg">User List</h3>
                    <div class="flex items-center space-x-4">
                        <!-- Dropdown Filter -->
                        <div class="relative">
                            <button id="filterButton" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md flex items-center">
                                <i class="fas fa-filter mr-2"></i> Filter
                            </button>
                            <div id="filterDropdown" class="absolute right-0 mt-2 w-48 bg-white border border-gray-300 rounded-md shadow-lg hidden" style="z-index:1000;">
                                <div class="p-2">
                                    <label class="flex items-center space-x-2">
                                        <input type="checkbox" id="filterBPH" class="form-checkbox" value="BPH">
                                        <span>BPH</span>
                                    </label>
                                    <label class="flex items-center space-x-2">
                                        <input type="checkbox" id="filterAnggota" class="form-checkbox" value="Anggota">
                                        <span>Anggota</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <!-- Search Input -->
                        <div class="relative">
                            <input type="text" id="searchInput" placeholder="Search User" class="border border-gray-300 rounded-md pl-4 pr-10 py-2">
                            <i id="searchIcon" class="fas fa-search absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 cursor-pointer"></i>
                        </div>
    </div>
</div>
                        <table class="w-full text-left">
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
                            <?php while ($row = $result->fetchArray(SQLITE3_ASSOC)): ?>
                                <tr class="<?php echo $no % 2 === 0 ? 'bg-blue-200' : 'bg-blue-100'; ?>" data-jabatan="<?php echo htmlspecialchars($row['jabatan'] ?? ''); ?>">
                                    <td class="p-2"><?php echo $no++; ?></td>
                                    <td class="p-2">
                                        <?php if (!empty($row['profile_image'])): ?>
                                            <img src="<?php echo htmlspecialchars($row['profile_image']); ?>" alt="Profile Image" class="w-10 h-10 rounded-full object-cover">
                                        <?php else: ?>
                                            <div class="bg-gray-300 rounded-full w-10 h-10"></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-2"><?php echo htmlspecialchars($row['nama_lengkap'] ?? ''); ?></td>
                                    <td class="p-2"><?php echo htmlspecialchars($row['nim'] ?? ''); ?></td>
                                    <td class="p-2"><?php echo htmlspecialchars($row['email'] ?? ''); ?></td>
                                    <td class="p-2"><?php echo htmlspecialchars($row['jabatan'] ?? ''); ?></td>
                                    <td class="p-2"><?php echo htmlspecialchars($row['total_absen'] ?? ''); ?>/14</td>
                                    <td class="p-2">
                                        <button onclick="confirmDelete(<?php echo $row['id']; ?>)" class="bg-red-500 text-white px-4 py-2 rounded-md flex items-center">
                                            <i class="fas fa-trash mr-2"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                        </table>
                        <div class="flex justify-between items-center mt-4">
                            <div class="text-gray-700">Rows per page: 
                                <select class="border border-gray-300 rounded-md p-1">
                                    <option>10</option>
                                    <option>20</option>
                                    <option>30</option>
                                </select>
                            </div>
                            <div class="text-gray-700">1-10 of 50 items 
                                <button class="ml-2"><i class="fas fa-chevron-left"></i></button>
                                <button class="ml-2"><i class="fas fa-chevron-right"></i></button>
                            </div>
                        </div>
                    </div>
                </section>

                <section>
                    
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

        // Toggle dropdown filter
        const filterButton = document.getElementById('filterButton');
        const filterDropdown = document.getElementById('filterDropdown');

            filterButton.addEventListener('click', (e) => {
            e.stopPropagation(); // Mencegah event bubbling
            filterDropdown.classList.toggle('hidden');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (event) => {
            if (!filterButton.contains(event.target) && !filterDropdown.contains(event.target)) {
                filterDropdown.classList.add('hidden');
            }
        });

        // Function to filter table based on jabatan and search input
        function filterTable() {
            const filterBPH = document.getElementById('filterBPH').checked;
            const filterAnggota = document.getElementById('filterAnggota').checked;
            const searchText = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const jabatan = row.getAttribute('data-jabatan').toLowerCase(); // Ambil jabatan dari atribut data-jabatan
                
                // Check if row matches the selected jabatan filter
                const isBPH = 
                    jabatan === 'ketua' ||
                    jabatan === 'wakil' ||
                    jabatan === 'bendahara' ||
                    jabatan === 'sekretaris';

                const matchesJabatan = 
                    (filterBPH && isBPH) || // Jika BPH dipilih, tampilkan Ketua, Wakil, Bendahara, Sekretaris
                    (filterAnggota && jabatan === 'anggota') || // Jika Anggota dipilih, tampilkan Anggota
                    (!filterBPH && !filterAnggota); // Tampilkan semua jika tidak ada filter yang dipilih

                // Check if row matches the search text
                const nama = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                const nim = row.querySelector('td:nth-child(4)').textContent.toLowerCase();
                const email = row.querySelector('td:nth-child(5)').textContent.toLowerCase();
                
                const matchesSearch = 
                    nama.includes(searchText) ||
                    nim.includes(searchText) ||
                    email.includes(searchText) ||
                    jabatan.includes(searchText);

                // Show row only if it matches both filter and search criteria
                row.style.display = (matchesJabatan && matchesSearch) ? '' : 'none';
            });
        }

        // Event listeners for filter checkboxes
        document.getElementById('filterBPH').addEventListener('change', filterTable);
        document.getElementById('filterAnggota').addEventListener('change', filterTable);

        // Event listener for Enter key in search input
        document.getElementById('searchInput').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                filterTable(); // Jalankan pencarian saat Enter ditekan
            }
        });

        // Event listener for search icon click
        document.getElementById('searchIcon').addEventListener('click', () => {
            filterTable(); // Jalankan pencarian saat ikon search diklik
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
                    // Jika user menekan "Yakin", kirim request penghapusan ke server
                    window.location.href = `../api/delete_user.php?id=${userId}`;
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
                                    jabatan: jabatan
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



    </script>
    </body>
</html>