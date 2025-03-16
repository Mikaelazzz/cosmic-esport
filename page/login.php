<?php
// Mulai session
session_start();

// Proses Login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Koneksi ke SQLite
    $db = new SQLite3('../db/ukm.db');

    // Ambil data dari form
    $nim = $_POST['nim'];
    $password = $_POST['password'];

    // Validasi NIM hanya angka
    if (!ctype_digit($nim)) {
        $_SESSION['error'] = 'NIM hanya boleh mengandung angka.';
        header("Location: login.php");
        exit();
    }

    // Cari pengguna berdasarkan NIM
    $query = $db->prepare("SELECT * FROM users WHERE nim = :nim");
    $query->bindValue(':nim', $nim, SQLITE3_TEXT);
    $result = $query->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);

    // Verifikasi password
    if ($user && password_verify($password, $user['password'])) {
        // Simpan data pengguna ke session
        $_SESSION['user'] = $user;

        // Set session untuk menandakan bahwa pengguna baru saja login
        $_SESSION['just_logged_in'] = true;

        // Redirect berdasarkan role
        if ($user['role'] === 'admin') {
            header("Location: ../admin/index.php");
        } else {
            header("Location: ../page/index.php");
        }
        exit();
    } else {
        // Simpan pesan kesalahan dalam session
        $_SESSION['error'] = 'NIM atau password salah.';
    }
}
?>


<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Esport</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://unpkg.com/@zxing/library@latest/umd/index.min.js"></script>
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
            background-color: #E0E7FF; /* Warna biru muda */
            border: none;
            outline: none;
            transition: all 0.3s ease;
        }

        .custom-input:focus {
            box-shadow: 0 0 0 2px #727DB6; /* Warna biru tua */
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
            <button class="border-2 border-white rounded px-3 py-1 hover:bg-white hover:text-[#727DB6] transition duration-300"><a href="register.php">REGISTER</a></button>
        </div>

        <!-- Right -->
        <div class="flex flex-col justify-center items-center w-full md:w-1/2 custom-bg-right p-8">
            <h2 class="text-2xl font-bold mb-6" style="color: #727DB6;">LOGIN</h2>
            <form action="login.php" method="POST" class="w-full">
                <!-- Input NIM -->
                <input type="text" name="nim" placeholder="NIM" class="w-full p-4 rounded-lg bg-blue-100 border border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500 mb-4" required>
                
                <!-- Input Password -->
                <div class="relative w-full mb-4">
                    <input type="password" name="password" id="password" placeholder="PASSWORD" class="w-full p-4 rounded-lg bg-blue-100 border border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <span class="absolute right-3 inset-y-0 flex items-center text-gray-500 cursor-pointer" id="togglePassword">
                        <i class="fas fa-eye-slash"></i>
                    </span>
                </div>
                
                <!-- Lupa Password dan Tombol Masuk -->
                <div class="flex flex-col items-center w-full">
                    <small class="text-gray-600 font-semibold text-base mb-4">
                        <a href="lupa_password.php">Lupa Password ?</a>
                    </small>
                    <button class="text-white px-3 py-1 rounded" style="background-color: #727DB6;">Masuk</button>
                </div>
            </form>
        </div>
    </section>

    <script>
        $(document).ready(function() {

            $('#nim').on('input', function() {
                const nim = $(this).val();
                // Cek apakah input mengandung huruf
                if (/[^0-9]/.test(nim)) {
                    $(this).val(nim.replace(/[^0-9]/g, '')); // Hapus karakter non-angka
                    Swal.fire({
                        icon: 'warning',
                        title: 'Perhatian!',
                        text: 'NIM hanya boleh mengandung angka.',
                    });
                }
            });
    
            $('#togglePassword').click(function() {
                const passwordInput = $('#password');
                const icon = $(this).find('i');
                const type = passwordInput.attr('type') === 'password' ? 'text' : 'password';
                passwordInput.attr('type', type);
                icon.toggleClass('fa-eye fa-eye-slash');
            });

            // Tampilkan SweetAlert2 jika ada pesan kesalahan
            <?php if (isset($_SESSION['error'])): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: '<?php echo $_SESSION['error']; ?>',
                });
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
        });
    </script>
</body>
</html>