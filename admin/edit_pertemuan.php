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

// Ambil ID pertemuan dari parameter URL
if (!isset($_GET['id'])) {
    header("Location: pertemuan.php"); // Redirect jika tidak ada ID
    exit();
}
$id_pertemuan = $_GET['id'];

// Query untuk mengambil data pertemuan berdasarkan ID
$query = "SELECT * FROM jadwal_pertemuan WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindValue(':id', $id_pertemuan, SQLITE3_INTEGER);
$result = $stmt->execute();
$pertemuan = $result->fetchArray(SQLITE3_ASSOC);

// Jika data tidak ditemukan, redirect ke halaman pertemuan
if (!$pertemuan) {
    header("Location: pertemuan.php");
    exit();
}
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- SweetAlert2 -->
</head>
<body style="font-family: 'Poppins';">
<section class="bg-gray-100 font-poppins h-screen flex flex-col">
    <!-- Header -->
    <header class="bg-[#727DB6] text-white p-4 flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <span class="text-xl">Edit Pertemuan UKM</span>
        </div>
        <a href="pertemuan.php" class="text-white p-3 border-2 rounded-full hover:bg-[#5c6491] border-white w-10 h-10 flex items-center justify-center flex-col space-y-0">
            <span class="text-lg">×</span>
        </a>
    </header>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto p-6">
        <form id="editPertemuanForm" action="../api/update_pertemuann.php" method="POST" class="max-w-2xl mx-auto p-6">
            <!-- Input Hidden untuk ID Pertemuan -->
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($pertemuan['id']); ?>">

            <!-- Nama Topik -->
            <div class="mb-6">
                <label for="nama_topik" class="block text-sm font-medium text-gray-700">Topik Acara (Opsional)</label>
                <input 
                    type="text" 
                    id="nama_topik" 
                    name="nama_topik" 
                    value="<?php echo htmlspecialchars(str_replace("Pertemuan Rutin - " . $pertemuan['id'] . " ", "", $pertemuan['nama_topik'])); ?>" 
                    class="w-full px-3 py-2 border rounded-md"
                >
                <small class="text-gray-500">Jika tidak diisi, akan menggunakan format default.</small>
            </div>

            <!-- Hari -->
            <div class="mb-6">
                <label for="hari" class="block text-sm font-medium text-gray-700">Hari</label>
                <input 
                    type="text" 
                    id="hari" 
                    name="hari" 
                    value="<?php echo htmlspecialchars($pertemuan['hari']); ?>" 
                    class="mt-1 block w-full p-2 border border-gray-300 rounded-md"
                    required
                >
            </div>

            <!-- Tanggal -->
            <div class="mb-6">
                <label for="tanggal" class="block text-sm font-medium text-gray-700">Tanggal</label>
                <input 
                    type="date" 
                    id="tanggal" 
                    name="tanggal" 
                    value="<?php echo htmlspecialchars($pertemuan['tanggal']); ?>" 
                    class="mt-1 block w-full p-2 border border-gray-300 rounded-md"
                    required
                >
            </div>

            <!-- Kelas -->
            <div class="mb-6">
                <label for="kelas" class="block text-sm font-medium text-gray-700">Kelas</label>
                <input 
                    type="text" 
                    id="kelas" 
                    name="kelas" 
                    value="<?php echo htmlspecialchars($pertemuan['kelas']); ?>" 
                    class="mt-1 block w-full p-2 border border-gray-300 rounded-md"
                    required
                >
            </div>

            <!-- Jam Pertemuan -->
            <div class="mb-6">
                <label for="jam_pertemuan" class="block text-sm font-medium text-gray-700">Jam Pertemuan</label>
                <input 
                    type="time" 
                    id="jam_pertemuan" 
                    name="jam_pertemuan" 
                    value="<?php echo htmlspecialchars($pertemuan['jam_pertemuan']); ?>" 
                    class="mt-1 block w-full p-2 border border-gray-300 rounded-md"
                    required
                >
            </div>

            <!-- Tombol Save dan Hapus -->
            <div class="flex justify-between">
                <button type="button" onclick="confirmDelete(<?php echo htmlspecialchars($pertemuan['id']); ?>)" class="bg-red-500 text-white px-6 py-2 rounded hover:bg-red-600">
                    Hapus Pertemuan
                </button>
                <button type="submit" class="bg-[#727DB6] text-white px-6 py-2 rounded hover:bg-[#5c6491]">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </main>
</section>

<script>
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
            window.location.href = `../api/delete_pertemuan.php?id=${id}`;
        }
    });
}
</script>
</body>
</html>