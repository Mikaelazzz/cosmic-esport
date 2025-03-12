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

// Query untuk mengambil data informasi aktif (yang belum berakhir)
$queryAktif = "SELECT * FROM informasi WHERE tanggal_berakhir >= date('now')";
$resultAktif = $db->query($queryAktif);

// Query untuk mengambil data informasi riwayat (yang sudah berakhir)
$queryRiwayat = "SELECT * FROM informasi WHERE tanggal_berakhir < date('now')";
$resultRiwayat = $db->query($queryRiwayat);
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
                        <h2 class="text-gray-700 text-xl">Manage Informasi</h2>
                        <button onclick="showAddInfoForm()" class="bg-[#727DB6] hover:bg-[#5c6491] text-white px-4 py-2 rounded-md flex items-center">
                            <i class="fas fa-plus mr-2"></i> Add Informasi
                        </button>
                    </div>
                </section>

                <section class="p-4">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-gray-700 text-xl">List Kegiatan</h2>
                                <!-- Search Input -->
                    <div class="relative">
                        <input type="text" id="searchInput" placeholder="Search User" class="border border-gray-300 rounded-md pl-4 pr-10 py-2">
                        <i id="searchIcon" class="fas fa-search absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 cursor-pointer"></i>
                    </div> 
                    </div>  
                    <!-- Grid of Information Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 pb-20">
                            <?php while ($row = $resultAktif->fetchArray(SQLITE3_ASSOC)): ?>
                                <div class="bg-white rounded-lg shadow-md overflow-hidden cursor-pointer" onclick="window.open('<?php echo htmlspecialchars($row['link']); ?>', '_blank')">
                                    <!-- Gambar Informasi -->
                                    <div class="bg-gray-200 h-[237px] w-full flex items-center justify-center overflow-hidden">
                                        <?php if (!empty($row['gambar'])): ?>
                                            <img 
                                                src="<?php echo htmlspecialchars($row['gambar']); ?>" 
                                                alt="<?php echo htmlspecialchars($row['nama_informasi']); ?>" 
                                                class="w-full h-full object-cover"
                                            >
                                        <?php else: ?>
                                            <span class="text-gray-600">No Image</span>
                                        <?php endif; ?>
                                    </div>
                                    <!-- Nama Informasi -->
                                    <div class="p-4">
                                        <h3 class="text-lg font-semibold"><?php echo htmlspecialchars($row['nama_informasi']); ?></h3>
                                    </div>
                                    <!-- Tombol Delete -->
                                    <div class="p-4 flex justify-end">
                                        <button onclick="event.stopPropagation(); deleteInformasi(<?php echo htmlspecialchars($row['id']); ?>)" class="bg-red-500 hover:bg-red-700 text-white px-4 py-2 rounded-md flex items-center z-50">
                                            <i class="fas fa-trash mr-2"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                </section>

                <section class="p-4">
                    <h2 class="text-gray-700 text-xl">Riwayat Informasi</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 pb-20">
                        <?php while ($row = $resultRiwayat->fetchArray(SQLITE3_ASSOC)): ?>
                            <div class="bg-white rounded-lg shadow-md overflow-hidden cursor-pointer" onclick="window.open('<?php echo htmlspecialchars($row['link']); ?>', '_blank')">
                                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                                    <!-- Gambar Informasi -->
                                    <div class="bg-gray-200 h-[237px] w-full flex items-center justify-center overflow-hidden">
                                        <?php if (!empty($row['gambar'])): ?>
                                            <img 
                                                src="<?php echo htmlspecialchars($row['gambar']); ?>" 
                                                alt="<?php echo htmlspecialchars($row['nama_informasi']); ?>" 
                                                class="w-full h-full object-cover"
                                            >
                                        <?php else: ?>
                                            <span class="text-gray-600">No Image</span>
                                        <?php endif; ?>
                                    </div>
                                    <!-- Nama Informasi -->
                                    <div class="p-4">
                                    <h3 class="text-lg font-semibold"><?php echo htmlspecialchars($row['nama_informasi']); ?></h3>
                                    </div>
                                    <!-- Tombol Delete -->
                                    <div class="p-4 flex justify-end">
                                        <button onclick="event.stopPropagation(); deleteInformasi(<?php echo htmlspecialchars($row['id']); ?>)" class="bg-red-500 text-white px-4 py-2 rounded-md flex items-center z-50">
                                            <i class="fas fa-trash mr-2"></i> Delete
                                        </button>
                                    </div>
                                </div>
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

            menuButton.addEventListener('click', () => {
                sidebar.classList.toggle('hidden');
            });

            closeSidebar.addEventListener('click', () => {
                sidebar.classList.add('hidden');
            });

            function showAddInfoForm() {
                Swal.fire({
                    title: 'Tambah Informasi',
                    html: `
                        <form id="infoForm" action="add_info.php" method="POST" enctype="multipart/form-data">
                            <div class="mb-4">
                                <label for="nama_informasi" class="block text-sm font-medium text-gray-700">Nama Informasi</label>
                                <input type="text" id="nama_informasi" name="nama_informasi" class="mt-1 block w-full border border-gray-300 rounded-md p-2" required>
                            </div>
                            <div class="mb-4">
                                <label for="gambar" class="block text-sm font-medium text-gray-700">Gambar (Aspect Ratio 16:9)</label>
                                <input type="file" id="gambar" name="gambar" class="mt-1 block w-full border border-gray-300 rounded-md p-2" accept="image/*" required onchange="previewImage(event)">
                                <div class="mt-2">
                                    <img id="gambarPreview" src="#" alt="Preview Gambar" class="hidden w-full aspect-video object-cover rounded-lg">
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="tanggal_publish" class="block text-sm font-medium text-gray-700">Tanggal Publish</label>
                                <input type="date" id="tanggal_publish" name="tanggal_publish" class="mt-1 block w-full border border-gray-300 rounded-md p-2" required>
                            </div>
                            <div class="mb-4">
                                <label for="tanggal_berakhir" class="block text-sm font-medium text-gray-700">Tanggal Berakhir</label>
                                <input type="date" id="tanggal_berakhir" name="tanggal_berakhir" class="mt-1 block w-full border border-gray-300 rounded-md p-2" required>
                            </div>
                            <div class="mb-4">
                                <label for="deskripsi" class="block text-sm font-medium text-gray-700">Deskripsi (Opsional)</label>
                                <textarea id="deskripsi" name="deskripsi" class="mt-1 block w-full border border-gray-300 rounded-md p-2"></textarea>
                            </div>
                            <div class="mb-4">
                                <label for="link" class="block text-sm font-medium text-gray-700">Link</label>
                                <input type="url" id="link" name="link" class="mt-1 block w-full border border-gray-300 rounded-md p-2" required>
                            </div>
                        </form>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Submit',
                    cancelButtonText: 'Cancel',
                    didOpen: () => {
                        // Tambahkan event listener untuk preview gambar
                        const gambarInput = Swal.getPopup().querySelector('#gambar');
                        gambarInput.addEventListener('change', previewImage);
                    },
                    preConfirm: () => {
                        const form = document.getElementById('infoForm');
                        const formData = new FormData(form);
                        return fetch('../api/add_info.php', {
                            method: 'POST',
                            body: formData
                        }).then(response => {
                            if (!response.ok) {
                                throw new Error(response.statusText);
                            }
                            return response.json();
                        }).catch(error => {
                            Swal.showValidationMessage(`Request failed: ${error}`);
                        });
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire('Success!', 'Informasi berhasil ditambahkan.', 'success').then(() => {
                            location.reload();
                        });
                    }
                });
            }

            // Fungsi untuk menghapus informasi
            function deleteInformasi(id) {
                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Anda tidak dapat mengembalikan data yang telah dihapus!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`../api/delete_info.php?id=${id}`, {
                            method: 'DELETE'
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Gagal menghapus data');
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                Swal.fire('Dihapus!', 'Informasi berhasil dihapus.', 'success').then(() => {
                                    location.reload(); // Muat ulang halaman setelah penghapusan
                                });
                            } else {
                                Swal.fire('Gagal!', data.message, 'error');
                            }
                        })
                        .catch(error => {
                            Swal.fire('Error!', error.message, 'error');
                        });
                    }
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