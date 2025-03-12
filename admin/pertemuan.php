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

// Query untuk mengambil data dari tabel jadwal_pertemuan
$query = "SELECT * FROM jadwal_pertemuan ORDER BY tanggal DESC";
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
/* Tambahkan style ini di head */
.mobile-view-card {
    display: none;
}

@media (max-width: 768px) {
    .mobile-view-card {
        display: block;
    }
    .desktop-view-table {
        display: none;
    }
    .header h1 {
        font-size: 1.5rem;
    }
    .header button {
        padding: 0.5rem;
    }
}

@media (min-width: 769px) {
    .mobile-view-card {
        display: none;
    }
    .desktop-view-table {
        display: block;
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
                        <h2 class="text-gray-700 text-xl">Manage Pertemuan</h2>
                        <button onclick="showAddPertemuanForm()" class="bg-[#727DB6] hover:bg-[#5c6491] text-white px-4 py-2 rounded-md flex items-center">
                            <i class="fas fa-plus mr-2"></i> Add Pertemuan
                        </button>
                    </div>
                    <div class="bg-white rounded-lg shadow-md p-4">
                        <div class="overflow-x-auto desktop-view-table">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="bg-gray-200">
                                        <th class="p-2">No</th>
                                        <th class="p-2">Nama Topik</th>
                                        <th class="p-2">Hari</th>
                                        <th class="p-2">Tanggal</th>
                                        <th class="p-2">Kelas</th>
                                        <th class="p-2">Jam Mulai</th>
                                        <th class="p-2">Jam Akhir</th>
                                        <th class="p-2">Jam Pertemuan</th>
                                        <th class="p-2">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetchArray(SQLITE3_ASSOC)): ?>
                                        <tr class="<?php echo $no % 2 === 0 ? 'bg-blue-200' : 'bg-blue-100'; ?> hover:bg-blue-300 cursor-pointer">
                                            <td class="p-2"><?php echo $no++; ?></td>
                                            <td class="p-2">
                                            <?php
                                            $namaTopik = htmlspecialchars($row['nama_topik']);
                                            echo ' ' . $namaTopik;
                                            ?>
                                            </td>
                                            <td class="p-2"><?php echo htmlspecialchars($row['hari'] ?? 'Jumat'); ?></td>
                                            <td class="p-2"><?php echo htmlspecialchars($row['tanggal'] ?? ''); ?></td>
                                            <td class="p-2"><?php echo htmlspecialchars($row['kelas'] ?? ''); ?></td>
                                            <td class="p-2"><?php echo htmlspecialchars($row['jam_mulai'] ?? '00:00'); ?></td>
                                            <td class="p-2"><?php echo htmlspecialchars($row['jam_akhir'] ?? '00:00'); ?></td>
                                            <td class="p-2"><?php echo htmlspecialchars($row['jam_pertemuan'] ?? ''); ?></td>
                                            <td class="p-2 flex">
                                                <button onclick="window.location.href='edit_pertemuan.php?id=<?php echo $row['id']; ?>'" class="bg-blue-500 hover:bg-blue-700 text-white px-4 py-2 mr-2 rounded-md flex items-center">
                                                    <i class="fas fa-edit mr-2"></i> Edit
                                                </button>
                                                <button onclick="confirmDelete(<?php echo $row['id']; ?>)" class="bg-red-500 hover:bg-red-700 text-white px-4 py-2 mr-2 rounded-md flex items-center">
                                                    <i class="fas fa-trash mr-2"></i> Delete
                                                </button>
                                                <button onclick="window.location.href='detail_read.php?id=<?php echo $row['id']; ?>'" class="bg-yellow-500 hover:bg-yellow-700 text-white px-4 py-2 rounded-md flex items-center">
                                                    <i class="fas fa-book-open-reader mr-2"></i> Lihat
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
                // Reset nomor urut
                $no = 1;
                $result->reset(); // Reset query result
                while ($row = $result->fetchArray(SQLITE3_ASSOC)): 
                ?>
                    <div class="p-3 rounded-lg bg-blue-200 shadow-md ">
                        <div class="text-sm mb-3">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <h4 class="font-semibold"><?php echo $no++.'. '.htmlspecialchars($row['nama_topik'] ?? 'Pertemuan Rutin'); ?></h4>
                                    <p class="text-xs text-gray-600 mt-1">
                                        <?php echo htmlspecialchars($row['hari'] ?? 'Jumat').', '.htmlspecialchars($row['tanggal'] ?? ''); ?>
                                    </p>
                                </div>
                                <span class="bg-purple-100 text-purple-600 text-xs font-bold px-2 py-1 rounded-full">
                                    <?php echo htmlspecialchars($row['kelas'] ?? ''); ?>
                                </span>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <div>
                                    <p class="text-gray-600">Jam Mulai</p>
                                    <p><?php echo htmlspecialchars($row['jam_mulai'] ?? '00:00'); ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-600">Jam Akhir</p>
                                    <p><?php echo htmlspecialchars($row['jam_akhir'] ?? '00:00'); ?></p>
                                </div>
                                <div class="col-span-2">
                                    <p class="text-gray-600">Jadwal Pertemuan</p>
                                    <p><?php echo htmlspecialchars($row['jam_pertemuan'] ?? ''); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex flex-col space-y-2">
                            <button onclick="window.location.href='edit_pertemuan.php?id=<?php echo $row['id']; ?>'" 
                                    class="bg-blue-500 hover:bg-blue-700 text-white px-3 py-2 rounded-md flex items-center justify-center text-sm">
                                <i class="fas fa-edit mr-2"></i> Edit
                            </button>
                            <button onclick="confirmDelete(<?php echo $row['id']; ?>)" 
                                    class="bg-red-500 hover:bg-red-700 text-white px-3 py-2 rounded-md flex items-center justify-center text-sm">
                                <i class="fas fa-trash mr-2"></i> Delete
                            </button>
                            <button onclick="window.location.href='detail_read.php?id=<?php echo $row['id']; ?>'" 
                                    class="bg-yellow-500 hover:bg-yellow-700 text-white px-3 py-2 rounded-md flex items-center justify-center text-sm">
                                <i class="fas fa-book-open-reader mr-2"></i> Lihat
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

                        <div class="flex flex-col md:flex-row justify-between items-center mt-4 text-sm">
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


        function showAddPertemuanForm() {
    Swal.fire({
        title: 'Tambah Pertemuan Baru',
        html: `<form id="addPertemuanForm" class="text-left">
                   <div class="mb-4">
                       <label for="nama_topik" class="block text-gray-700 text-left">Topik Acara (Opsional)</label>
                       <input type="text" id="nama_topik" name="nama_topik" class="w-full px-3 py-2 border rounded-md">
                       <small class="text-gray-500">Jika tidak diisi, akan menggunakan format default.</small>
                   </div>
                   <div class="mb-4">
                       <label for="hari" class="block text-gray-700 text-left">Hari</label>
                       <input type="text" id="hari" name="hari" class="w-full px-3 py-2 border rounded-md" value="Jumat" required>
                   </div>
                   <div class="mb-4">
                       <label for="tanggal" class="block text-gray-700 text-left">Tanggal</label>
                       <input type="date" id="tanggal" name="tanggal" class="w-full px-3 py-2 border rounded-md" required>
                   </div>
                   <div class="mb-4">
                       <label for="jam_pertemuan" class="block text-gray-700 text-left">Jam Pertemuan</label>
                       <input type="time" id="jam_pertemuan" name="jam_pertemuan" class="w-full px-3 py-2 border rounded-md" required>
                   </div>
                   <div class="mb-4">
                       <label for="kelas" class="block text-gray-700 text-left">Kelas</label>
                       <input type="text" id="kelas" name="kelas" class="w-full px-3 py-2 border rounded-md" required>
                   </div>
               </form>`,
        showCancelButton: true,
        confirmButtonText: 'Simpan',
        cancelButtonText: 'Batal',
        focusConfirm: false,
        preConfirm: () => {
            const namaTopik = document.getElementById('nama_topik').value;
            const hari = document.getElementById('hari').value;
            const tanggal = document.getElementById('tanggal').value;
            const kelas = document.getElementById('kelas').value;
            const jamPertemuan = document.getElementById('jam_pertemuan').value;

            // Validasi input
            if (!hari || !tanggal || !kelas || !jamPertemuan) {
                Swal.showValidationMessage('Harap isi semua field yang wajib');
                return false;
            }

            // Kirim data ke server
            return fetch('../api/add_pertemuan.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    nama_topik: namaTopik, // Kirim topik acara (opsional)
                    hari: hari,
                    tanggal: tanggal,
                    kelas: kelas,
                    jam_pertemuan: jamPertemuan,
                    jam_mulai: '00:00', // Default value
                    jam_akhir: '00:00'  // Default value
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Gagal menambahkan pertemuan');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    Swal.fire('Sukses!', 'Pertemuan berhasil ditambahkan', 'success');
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
        }
    });
}

    // Function to confirm delete pertemuan
    function confirmDelete(pertemuanId) {
        Swal.fire({
            title: 'Apakah ingin Menghapus Pertemuan ini?',
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
                window.location.href = `../api/delete_pertemuan.php?id=${pertemuanId}`;
            }
        });
    }

    </script>
    </body>
</html>