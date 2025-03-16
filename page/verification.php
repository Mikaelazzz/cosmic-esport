<?php
session_start();

if (!isset($_SESSION['verification_code']) || !isset($_SESSION['email'])) {
    header("Location: lupa_password.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_code = $_POST['verification_code'];

    if ($user_code == $_SESSION['verification_code']) {
        $_SESSION['verified'] = true;
        header("Location: reset_password.php");
        exit();
    } else {
        $_SESSION['error'] = 'Kode verifikasi salah.';
        header("Location: verification.php");
        exit();
    }
}
?>

<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Kode</title>
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
            <button class="border-2 border-white rounded px-3 py-1 hover:bg-white hover:text-[#727DB6] transition duration-300"><a href="register.php">KEMBALI</a></button>
        </div>

        <!-- Right -->
        <div class="flex flex-col justify-center items-center w-full md:w-1/2 custom-bg-right p-8">
            <h2 class="text-2xl font-bold mb-6" style="color: #727DB6;">KODE VERIFIKASI</h2>
            <form action="verification.php" method="POST" class="w-full">
                <!-- Input Kode Verifikasi -->
                <div class="flex justify-center space-x-4 mb-6">
                    <input type="text" name="verification_code[]" maxlength="1" class="w-12 h-12 text-center text-2xl rounded-lg bg-blue-100 border border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <input type="text" name="verification_code[]" maxlength="1" class="w-12 h-12 text-center text-2xl rounded-lg bg-blue-100 border border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <input type="text" name="verification_code[]" maxlength="1" class="w-12 h-12 text-center text-2xl rounded-lg bg-blue-100 border border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <input type="text" name="verification_code[]" maxlength="1" class="w-12 h-12 text-center text-2xl rounded-lg bg-blue-100 border border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <input type="text" name="verification_code[]" maxlength="1" class="w-12 h-12 text-center text-2xl rounded-lg bg-blue-100 border border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <input type="text" name="verification_code[]" maxlength="1" class="w-12 h-12 text-center text-2xl rounded-lg bg-blue-100 border border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                
                <!-- Tombol Verifikasi -->
                <div class="flex flex-col items-center w-full">
                    <button class="text-white px-3 py-1 rounded" style="background-color: #727DB6;">VERIFIKASI</button>
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

            // Auto focus dan move to next input
            $('input[name="verification_code[]"]').on('input', function() {
                if ($(this).val().length === 1) {
                    $(this).next('input').focus();
                }
            });
        });
    </script>
</body>
</html>