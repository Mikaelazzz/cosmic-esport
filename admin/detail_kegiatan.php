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

// Ambil ID kegiatan dari parameter URL
if (!isset($_GET['id'])) {
    header("Location: kegiatan.php"); // Redirect jika tidak ada ID
    exit();
}
$id_kegiatan = $_GET['id'];

// Query untuk mengambil data kegiatan berdasarkan ID
$query = "SELECT * FROM kegiatan WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindValue(':id', $id_kegiatan, SQLITE3_INTEGER);
$result = $stmt->execute();
$kegiatan = $result->fetchArray(SQLITE3_ASSOC);

// Jika data tidak ditemukan, redirect ke halaman kegiatan
if (!$kegiatan) {
    header("Location: kegiatan.php");
    exit();
}
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- SweetAlert2 -->
</head>
<body style="font-family: 'Poppins';">
<section class="bg-gray-100 font-poppins h-screen flex flex-col">
    <!-- Header -->
    <header class="bg-[#727DB6] text-white p-4 flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <span class="text-xl">Edit Kegiatan UKM</span>
        </div>
        <a href="kegiatan.php" class="text-white p-3 border-2 rounded-full hover:bg-[#5c6491] border-white w-10 h-10 flex items-center justify-center flex-col space-y-0">
            <span class="text-lg">×</span>
        </a>
    </header>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto p-6">
        <form id="editKegiatanForm" action="../api/update_kegiatan.php" method="POST" enctype="multipart/form-data" class="max-w-2xl mx-auto p-6">
            <!-- Input Hidden untuk ID Kegiatan -->
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($kegiatan['id']); ?>">

            <!-- Gambar Kegiatan -->
            <div class="bg-gray-200 h-64 rounded-lg mb-6 overflow-hidden">
                <?php if (!empty($kegiatan['gambar'])): ?>
                    <img 
                        id="gambarPreview"
                        src="<?php echo htmlspecialchars($kegiatan['gambar']); ?>" 
                        alt="<?php echo htmlspecialchars($kegiatan['nama_kegiatan']); ?>" 
                        class="w-full h-full object-cover"
                    >
                <?php else: ?>
                    <span id="gambarPreview" class="text-gray-600">No Image</span>
                <?php endif; ?>
            </div>

            <!-- Input untuk Upload Gambar Baru -->
            <div class="mb-6">
                <label for="gambar" class="block text-sm font-medium text-gray-700">Upload Gambar Baru (Rasio 16:9)</label>
                <input 
                    type="file" 
                    id="gambar" 
                    name="gambar" 
                    accept="image/*" 
                    class="mt-1 block w-full p-2 border border-gray-300 rounded-md"
                    onchange="previewImage(event)"
                >
            </div>

            <!-- Nama Kegiatan -->
            <div class="mb-6">
                <label for="nama_kegiatan" class="block text-sm font-medium text-gray-700">Nama Kegiatan</label>
                <input 
                    type="text" 
                    id="nama_kegiatan" 
                    name="nama_kegiatan" 
                    value="<?php echo htmlspecialchars($kegiatan['nama_kegiatan']); ?>" 
                    class="mt-1 block w-full p-2 border border-gray-300 rounded-md"
                    required
                >
            </div>

            <!-- Tanggal Pelaksanaan -->
            <div class="mb-6">
                <label for="tanggal_pelaksanaan" class="block text-sm font-medium text-gray-700">Tanggal Pelaksanaan</label>
                <input 
                    type="date" 
                    id="tanggal_pelaksanaan" 
                    name="tanggal_pelaksanaan" 
                    value="<?php echo htmlspecialchars($kegiatan['tanggal_pelaksanaan']); ?>" 
                    class="mt-1 block w-full p-2 border border-gray-300 rounded-md"
                    required
                >
            </div>

            <!-- Deskripsi -->
            <div class="mb-6">
                <label for="deskripsi" class="block text-sm font-medium text-gray-700">Deskripsi</label>
                <textarea 
                    id="deskripsi" 
                    name="deskripsi" 
                    rows="3" 
                    class="mt-1 block w-full p-2 border border-gray-300 rounded-md"
                    required
                ><?php echo htmlspecialchars($kegiatan['deskripsi']); ?></textarea>
            </div>

            <!-- Syarat dan Ketentuan -->
            <div class="mb-6">
                <label for="syarat_dan_ketentuan" class="block text-sm font-medium text-gray-700">Syarat dan Ketentuan</label>
                <textarea 
                    id="syarat_dan_ketentuan" 
                    name="syarat_dan_ketentuan" 
                    rows="3" 
                    class="mt-1 block w-full p-2 border border-gray-300 rounded-md"
                    required
                ><?php echo htmlspecialchars($kegiatan['syarat_dan_ketentuan']); ?></textarea>
            </div>

            <!-- Link Pendaftaran -->
            <div class="mb-6">
                <label for="link_pendaftaran" class="block text-sm font-medium text-gray-700">Link Pendaftaran</label>
                <input 
                    type="url" 
                    id="link_pendaftaran" 
                    name="link_pendaftaran" 
                    value="<?php echo htmlspecialchars($kegiatan['link_pendaftaran']); ?>" 
                    class="mt-1 block w-full p-2 border border-gray-300 rounded-md"
                    required
                >
            </div>

            <!-- Tombol Save dan Hapus -->
            <div class="flex justify-between">
                <button type="button" onclick="confirmDelete(<?php echo htmlspecialchars($kegiatan['id']); ?>)" class="bg-red-500 text-white px-6 py-2 rounded hover:bg-red-600">
                    Hapus Kegiatan
                </button>
                <button type="submit" class="bg-[#727DB6] text-white px-6 py-2 rounded hover:bg-[#5c6491]">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </main>
</section>

<script>
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

// Fungsi untuk konfirmasi hapus
function confirmDelete(id) {
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: "Anda tidak dapat mengembalikan data ini!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yakin',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `../api/delete_kegiatan.php?id=${id}`;
        }
    });
}
</script>
</body>
</html>