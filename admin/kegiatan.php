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

// Query to fetch activities from the database
$query = "SELECT * FROM kegiatan"; // Assuming 'kegiatan' is the table name
$result = $db->query($query);

// Check if the query was successful
if (!$result) {
    die("Error fetching activities: " . $db->lastErrorMsg());
}

// Tanggal saat ini (dianggap 00:00:00)
$tanggalSekarang = date('Y-m-d');

// Query untuk kegiatan aktif
$queryAktif = "SELECT * FROM kegiatan 
               WHERE DATE(:tanggalSekarang) BETWEEN DATE(tanggal_awal) AND DATE(tanggal_akhir)";
$stmtAktif = $db->prepare($queryAktif);
$stmtAktif->bindValue(':tanggalSekarang', $tanggalSekarang, SQLITE3_TEXT);
$resultAktif = $stmtAktif->execute();

// Query untuk riwayat kegiatan
$queryRiwayat = "SELECT * FROM kegiatan 
                 WHERE DATE(:tanggalSekarang) > DATE(tanggal_akhir)";
$stmtRiwayat = $db->prepare($queryRiwayat);
$stmtRiwayat->bindValue(':tanggalSekarang', $tanggalSekarang, SQLITE3_TEXT);
$resultRiwayat = $stmtRiwayat->execute();
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
    #gambarPreview {
        aspect-ratio: 16 / 9;
        object-fit: cover;
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
                    <img src="<?php echo !empty($user['profile_image']) ? $user['profile_image'] : '../src/default.png'; ?>" alt="Profile Image" class="w-full h-full object-cover">
                </a>
            </header>


            <main class="flex-1 overflow-y-auto h-[calc(100vh-5rem)]">
                <section class="p-4">
                <div class="flex justify-between items-center mb-4">
                        <h2 class="text-gray-700 text-base md:text-xl">Manage Kegiatan</h2>
                        <button onclick="showAddUserForm()" class="bg-[#727DB6] hover:bg-[#5c6491] text-white px-4 py-2 rounded-md flex items-center">
                            <i class="fas fa-plus mr-2"></i> Add Kegiatan
                        </button>
                    </div>
                </section>

                <section class="p-4">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-gray-700 text-base md:text-xl">List Kegiatan</h2>
                            <!-- Search Input -->
                <div class="relative">
                    <input type="text" id="searchInput" placeholder="Search Kegiatan" class="w-[200px] md:w-full border border-gray-300 rounded-md pl-4 pr-10 py-2">
                    <i id="searchIcon" class="fas fa-search absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 cursor-pointer"></i>
                </div> 
                </div>  
                <!-- Grid of Activity Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 pb-20">
                <?php while ($row = $resultAktif->fetchArray(SQLITE3_ASSOC)): ?>
    <a href="../admin/detail_kegiatan.php?id=<?php echo htmlspecialchars($row['id']); ?>">
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <!-- Gambar Kegiatan -->
            <div class="bg-gray-200 h-[720] w-[1280] flex items-center justify-center overflow-hidden">
                <?php if (!empty($row['gambar'])): ?>
                    <img 
                        src="<?php echo htmlspecialchars($row['gambar']); ?>" 
                        alt="<?php echo htmlspecialchars($row['nama_kegiatan']); ?>" 
                        class="w-full h-full object-cover"
                    >
                <?php else: ?>
                    <span class="text-gray-600">No Image</span>
                <?php endif; ?>
            </div>
            <!-- Nama Kegiatan -->
            <div class="p-4">
                <h3 class="text-lg font-semibold"><?php echo htmlspecialchars($row['nama_kegiatan']); ?></h3>
            </div>
        </div>
    </a>
<?php endwhile; ?>
                </div>
                </section>

                <section class="p-4">
                    <h2 class="text-gray-700 text-base md:text-xl">Riwayat Kegiatan</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 pb-20">
                        <?php while ($row = $resultRiwayat->fetchArray(SQLITE3_ASSOC)): ?>
                            <a href="../admin/detail_kegiatan.php?id=<?php echo htmlspecialchars($row['id']); ?>">
                                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                                    <!-- Gambar Kegiatan -->
                                    <div class="bg-gray-200 h-[237px] w-full flex items-center justify-center overflow-hidden">
                                        <?php if (!empty($row['gambar'])): ?>
                                            <img 
                                                src="<?php echo htmlspecialchars($row['gambar']); ?>" 
                                                alt="<?php echo htmlspecialchars($row['nama_kegiatan']); ?>" 
                                                class="w-full h-full object-cover"
                                            >
                                        <?php else: ?>
                                            <span class="text-gray-600">No Image</span>
                                        <?php endif; ?>
                                    </div>
                                    <!-- Nama Kegiatan -->
                                    <div class="p-4">
                                        <h3 class="text-lg font-semibold"><?php echo htmlspecialchars($row['nama_kegiatan']); ?></h3>
                                    </div>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    </div>
                </section>
            </main>
        <script>
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('sidebar');
    const menuButton = document.getElementById('menuButton');
    const closeSidebar = document.getElementById('closeSidebar');
    const modeButton = document.getElementById('modeButton');
    const modeMenu = document.getElementById('modeMenu');
    const modeIcon = document.getElementById('modeIcon');

    if (menuButton) {
        menuButton.addEventListener('click', () => {
            sidebar.classList.toggle('hidden');
        });
    }

    if (closeSidebar) {
        closeSidebar.addEventListener('click', () => {
            sidebar.classList.add('hidden');
        });
    }

    if (modeButton) {
        modeButton.addEventListener('click', (e) => {
            e.preventDefault();
            modeMenu.classList.toggle('hidden');
            modeIcon.classList.toggle('rotate-180');
        });
    }
});


function showAddUserForm() {
    Swal.fire({
        title: 'Tambah Kegiatan Baru',
        html: `
            <form id="addKegiatanForm">
                <!-- Upload Gambar -->
                <div class="mb-4">
                    <label for="gambar" class="block text-sm font-medium text-gray-700">Upload Gambar (Rasio 16:9)</label>
                    <input type="file" id="gambar" name="gambar" accept="image/*" class="mt-1 block w-full" onchange="previewImage(event)">
                    <div class="mt-2">
                        <img id="gambarPreview" src="#" alt="Preview Gambar" class="hidden w-full aspect-video object-cover rounded-lg">
                    </div>
                </div>

                <!-- Nama/Judul Kegiatan -->
                <div class="mb-4">
                    <label for="nama_kegiatan" class="block text-sm font-medium text-gray-700">Nama/Judul Kegiatan</label>
                    <input type="text" id="nama_kegiatan" name="nama_kegiatan" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
                </div>

                <!-- Tanggal Pelaksanaan -->
                <div class="mb-4">
                    <label for="tanggal_pelaksanaan" class="block text-sm font-medium text-gray-700">Tanggal Pelaksanaan</label>
                    <input type="date" id="tanggal_pelaksanaan" name="tanggal_pelaksanaan" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
                </div>

                <!-- Tanggal Awal -->
                <div class="mb-4">
                    <label for="tanggal_awal" class="block text-sm font-medium text-gray-700">Tanggal Awal Tampil</label>
                    <input type="date" id="tanggal_awal" name="tanggal_awal" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
                </div>

                <!-- Tanggal Akhir -->
                <div class="mb-4">
                    <label for="tanggal_akhir" class="block text-sm font-medium text-gray-700">Tanggal Akhir Tampil</label>
                    <input type="date" id="tanggal_akhir" name="tanggal_akhir" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
                </div>

                <!-- Deskripsi -->
                <div class="mb-4">
                    <label for="deskripsi" class="block text-sm font-medium text-gray-700">Deskripsi</label>
                    <textarea id="deskripsi" name="deskripsi" rows="3" class="mt-1 block w-full p-2 border border-gray-300 rounded-md"></textarea>
                </div>

                <!-- Syarat dan Ketentuan -->
                <div class="mb-4">
                    <label for="syarat_ketentuan" class="block text-sm font-medium text-gray-700">Syarat dan Ketentuan</label>
                    <textarea id="syarat_ketentuan" name="syarat_ketentuan" rows="3" class="mt-1 block w-full p-2 border border-gray-300 rounded-md"></textarea>
                </div>

                <!-- Link Pendaftaran -->
                <div class="mb-4">
                    <label for="link_pendaftaran" class="block text-sm font-medium text-gray-700">Link Pendaftaran</label>
                    <input type="url" id="link_pendaftaran" name="link_pendaftaran" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
                </div>
            </form>
        `,
        showCancelButton: true,
        confirmButtonText: 'Simpan',
        cancelButtonText: 'Batal',
        focusConfirm: false,
        preConfirm: () => {
            const form = document.getElementById('addKegiatanForm');
            const formData = new FormData(form);

            // Validasi form
            if (!formData.get('gambar') || !formData.get('nama_kegiatan') || !formData.get('tanggal_awal') || !formData.get('tanggal_akhir') || !formData.get('tanggal_pelaksanaan') || !formData.get('deskripsi') || !formData.get('syarat_ketentuan') || !formData.get('link_pendaftaran')) {
                Swal.showValidationMessage('Harap isi semua field');
                return false;
            }

            // Kirim data ke server menggunakan AJAX
            return fetch('../api/add_kegiatan.php', {
                method: 'POST',
                body: formData,
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Gagal menyimpan data');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    Swal.fire('Sukses!', 'Kegiatan berhasil ditambahkan', 'success');
                    // Refresh halaman atau update daftar kegiatan
                    location.reload();
                } else {
                    Swal.fire('Gagal!', data.message, 'error');
                }
            })
            .catch(error => {
                Swal.fire('Error!', error.message, 'error');
            });
        },
    });
}

// Fungsi untuk menampilkan preview gambar
function previewImage(event) {
    const input = event.target;
    const preview = document.getElementById('gambarPreview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            preview.src = e.target.result;
            preview.classList.remove('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.src = '#';
        preview.classList.add('hidden');
    }
}
    </script>
    </body>
</html>