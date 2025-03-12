<?php
session_start();

// Proses validasi token dan NIM
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify'])) {
    // Koneksi ke database
    $db = new SQLite3('../db/ukm.db');

    // Ambil data dari form
    $nim = $_POST['nim'];
    $token = $_POST['token'];

    // Cek apakah NIM dan token valid
    $query = $db->prepare("SELECT id FROM users WHERE nim = :nim AND token = :token");
    $query->bindValue(':nim', $nim, SQLITE3_TEXT);
    $query->bindValue(':token', $token, SQLITE3_TEXT);
    $result = $query->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);

    if ($user) {
        // Jika valid, simpan NIM ke session dan arahkan ke halaman reset password
        $_SESSION['reset_nim'] = $nim;
        header('Location: reset_password.php');
        exit();
    } else {
        // Jika tidak valid, tampilkan pesan error
        $_SESSION['error'] = 'NIM atau Token tidak valid. Silakan coba lagi.';
        header('Location: lupa_password.php');
        exit();
    }
}
?>

<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .custom-bg-left {
            background-color: #727DB6;
        }
        .custom-bg-right {
            background-color: #F0F4FF;
        }
        .custom-input {
            background-color: #E0E7FF;
        }

        .custom-input {
            width: 100%;
            padding: 1rem;
            border-radius: 0.5rem;
            background-color: #E0E7FF;
            border: none;
            outline: none;
            transition: all 0.3s ease;
        }

        .custom-input:focus {
            box-shadow: 0 0 0 2px #727DB6;
        }
    </style>
</head>
<body>
    <section class="h-screen flex flex-col md:flex-row" style="font-family: 'Poppins';">
        <!-- Left -->
        <div class="flex flex-col justify-center items-center w-full md:w-1/2 custom-bg-left p-8 text-slate-200">
            <img src="../src/logo.png" alt="Colorful logo with overlapping shapes" class="w-72 h-72 mb-3">
            <h1 class="text-3xl font-bold mb-4">Selamat Datang!</h1>
            <p class="text-lg mb-6 text-justify">Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.</p>
            <button onclick="window.history.back()" class="border-2 border-white rounded px-3 py-1 hover:bg-white hover:text-[#727DB6] transition duration-300">Kembali</button>
        </div>

        <!-- Right -->
        <div class="flex flex-col justify-center items-center w-full md:w-1/2 custom-bg-right p-8">
            <h2 class="text-2xl font-bold mb-6" style="color: #727DB6;">RESET PASSWORD</h2>
            <form action="lupa_password.php" method="POST" class="w-full">
                <!-- Input NIM -->
                <input type="text" name="nim" placeholder="NIM" class="w-full p-4 rounded-lg bg-blue-100 border border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500 mb-4" required>
                
                <!-- Input Token -->
                <input type="text" name="token" placeholder="Token" class="w-full p-4 rounded-lg bg-blue-100 border border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500 mb-4" required>
                
                <!-- Tombol Verifikasi -->
                <div class="flex flex-col items-center w-full">
                    <button type="submit" name="verify" class="text-white px-3 py-1 rounded" style="background-color: #727DB6;">VERIFIKASI</button>
                </div>
            </form>
        </div>
    </section>

    <script>
        $(document).ready(function() {
            // Tampilkan SweetAlert2 jika ada pesan kesalahan
            <?php if (isset($_SESSION['error'])): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: '<?php echo $_SESSION['error']; ?>',
                });
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            // Tampilkan SweetAlert2 jika ada pesan sukses
            <?php if (isset($_SESSION['success'])): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Sukses!',
                    text: '<?php echo $_SESSION['success']; ?>',
                });
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
        });
    </script>
</body>
</html>