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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://unpkg.com/@zxing/library@latest/umd/index.min.js"></script>

</head>
<body style="font-family: 'Poppins';">
<section class="bg-gray-100 font-poppins h-screen flex flex-col">
        <!-- Header -->
        <header class="bg-[#727DB6] text-white p-4 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <span class="text-xl">Kegiatan UKM</span>
            </div>
            <a href="kegiatan.php" class="text-white p-3 border-2 rounded-full hover:bg-[#5c6491] border-white w-10 h-10 flex items-center justify-center flex-col space-y-0">
                <span class="text-lg">×</span>
            </a>
        </header>
    
        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto p-6">
            <section class="max-w-2xl mx-auto p-6">
                <!-- Gambar Kegiatan -->
                <div class="bg-gray-200 h-64 rounded-lg mb-6 overflow-hidden">
                    <?php if (!empty($kegiatan['gambar'])): ?>
                        <img 
                            src="<?php echo htmlspecialchars($kegiatan['gambar']); ?>" 
                            alt="<?php echo htmlspecialchars($kegiatan['nama_kegiatan']); ?>" 
                            class="w-full h-full object-cover"
                        >
                    <?php else: ?>
                        <span class="text-gray-600">No Image</span>
                    <?php endif; ?>
                </div>
    
                <!-- Detail Kegiatan -->
                <div class="space-y-4">
                    <h2 class="text-xl font-semibold"><?php echo htmlspecialchars($kegiatan['nama_kegiatan']); ?></h2>
                    <p class="text-gray-600"><strong>Tanggal Pelaksanaan :</strong> <?php echo htmlspecialchars($kegiatan['tanggal_pelaksanaan']); ?></p>
                    
                    <h3 class="text-lg font-medium">Deskripsi</h3>
                    <p class="text-gray-700 text-justify">
                        <?php echo htmlspecialchars($kegiatan['deskripsi']); ?>
                    </p>
    
                    <h3 class="text-lg font-medium">Syarat dan Ketentuan</h3>
                    <p class="text-gray-700 text-justify">
                        <?php echo htmlspecialchars($kegiatan['syarat_dan_ketentuan']); ?>
                    </p>
                </div>
    
                <!-- Tombol Pendaftaran -->
                <a 
                    href="<?php echo htmlspecialchars($kegiatan['link_pendaftaran']); ?>" 
                    target="_blank" 
                    class="bg-[#727DB6] text-white px-6 py-2 rounded mt-6 w-full hover:bg-[#5c6491] block text-center"
                >
                    Form Pendaftaran
                </a>
            </section>
        </main>
    </section>
</body>
</html>