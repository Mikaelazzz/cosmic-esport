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
                            <a href="../page/index.php" class="flex items-center p-2 hover:bg-slate-600 rounded">
                                <i class="fa-solid fa-house mr-2"></i>
                                Home
                            </a>
                        </li>
                        <li>
                            <a href="../page/kegiatan.php" class="flex items-center p-2 hover:bg-slate-600 rounded">
                                <i class="fa-solid fa-clipboard-list mr-2"></i>
                                Daftar Kegiatan
                            </a>
                        </li>
                        <li>
                            <a href="../page/anggota.php" class="flex items-center p-2 hover:bg-slate-600 rounded">
                                <i class="fa-solid fa-users mr-2"></i>
                                Anggota UKM
                            </a>
                        </li>
                        <li>
                            <button id="modeButton" class="flex items-center w-full p-2 hover:bg-slate-600 rounded">
                                <i class="fa-solid fa-gamepad mr-2"></i>
                                Mode 
                                <i class="fa-solid fa-chevron-down ml-auto transition-transform duration-300" id="modeIcon"></i>
                            </button>
                            <ul id="modeMenu" class="pl-6 mt-2 space-y-2 hidden">
                                <li><a href="../page/calc.php" class="block p-2 hover:bg-slate-600 rounded">Calculator WR</a></li>
                                <li><a href="../page/sg.php" class="block p-2 hover:bg-slate-600 rounded">Search NickGame</a></li>
                            </ul>
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

            <main class="flex-1 overflow-y-auto h-[calc(100vh-5rem)] bg-gray-100">
                <section class="max-w-2xl mx-auto p-6">
                    <!-- Logo -->
                    <div class="flex justify-center mb-8">
                        <img src="../src/logo.png" alt="Cosmic Esport Logo" class="w-32 h-auto">
                    </div>

                    <!-- Title and Description -->
                    <h1 class="text-2xl font-bold text-center mb-4">Kalkulator Win Rate</h1>
                    <p class="text-center text-gray-600 mb-6">
                        Digunakan untuk menghitung total jumlah pertandingan yang harus diambil untuk mencapai target tingkat kemenangan yang diinginkan.
                    </p>

                    <!-- Form Inputs -->
                    <!-- Form Inputs -->
                    <form id="winRateForm" class="space-y-4">
                        <div>
                            <label for="tMatch" class="block text-sm font-medium text-gray-700">Total Pertandingan Kamu Saat Ini</label>
                            <input type="number" id="tMatch" name="tMatch" placeholder="Contoh: 223" class="w-full p-2 rounded-2xl bg-blue-100 border border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500 mt-2 mb-4">
                        </div>
                        <div>
                            <label for="tWr" class="block text-sm font-medium text-gray-700">Total Win Rate Kamu Saat Ini</label>
                            <input type="number" id="tWr" name="tWr" placeholder="Contoh: 54" class="w-full p-2 rounded-2xl bg-blue-100 border border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500 mt-2 mb-4">
                        </div>
                        <div>
                            <label for="wrReq" class="block text-sm font-medium text-gray-700">Total Win Rate Target Kamu</label>
                            <input type="number" id="wrReq" name="wrReq" placeholder="Contoh: 54" class="w-full p-2 rounded-2xl bg-blue-100 border border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500 mt-2 mb-4">
                        </div>

                        <!-- Calculate Button -->
                        <button type="button" id="hasil" class="w-full py-2 bg-[#727DB6] text-white rounded-full hover:bg-[#5c6491] transition mt-6">Hitung</button>
                    </form>

                    <!-- Result Display -->
                    <div id="resultText" class="mt-6 bg-[#4d5ba2] rounded-2xl py-3 text-center border-4 border-blue-300 text-slate-200 hidden"></div>
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


        // Win Rate Calculator Logic (Using your provided functions)
        const hasil = document.querySelector("#hasil");
            const resultText = document.querySelector("#resultText");

            function validation() {
                const tMatch = parseFloat(document.querySelector("#tMatch").value);
                const tWr = parseFloat(document.querySelector("#tWr").value);
                const wrReq = parseFloat(document.querySelector("#wrReq").value);

                const resultNum = rumus(tMatch, tWr, wrReq);
                const loseNum = rumusLose(tMatch, tWr, wrReq);

                let text = "";
                if (isNaN(tMatch) || isNaN(tWr) || isNaN(wrReq)) {
                    text = `Field harus diisi bro.`;
                    display(text);
                } else if (tMatch < 0 || tWr < 0 || wrReq < 0) {
                    text = `Field tidak boleh lebih kecil dari 0`;
                    display(text);
                } else if (tMatch % 1 != 0) {
                    text = `Field harus bilangan bulat`;
                    display(text);
                } else if (tWr == 100 && wrReq == 100) {
                    text = `Kamu perlu <b>0</b> win tanpa lose untuk mendapatkan win rate <b>${wrReq}%</b>`;
                    display(text);
                } else if (wrReq > 100 || tWr > 100) {
                    text = `WR tidak boleh lebih dari 100%`;
                    display(text);
                } else if (tWr > wrReq) {
                    text = `Kamu perlu <b>${loseNum}</b> lose tanpa win untuk mendapatkan win rate <b>${wrReq}%</b>`;
                    display(text);
                } else if (tMatch == 0 && tWr == 0 && wrReq == 100) {
                    text = `Kamu perlu <b>1</b> win tanpa lose untuk mendapatkan win rate <b>${wrReq}%</b>`;
                    display(text);
                } else if (wrReq == 100) {
                    text = `yo ndak bisa bree<br> yang bisa cuman Moonton`;
                    display(text);
                } else if (resultNum >= 100000) {
                    text = `Kamu perlu lebih dari <b>100.000</b> win tanpa lose untuk mendapatkan win rate <b>${wrReq}%</b>`;
                    display(text);
                } else {
                    text = `Kamu perlu <b>${resultNum}</b> win tanpa lose untuk mendapatkan win rate <b>${wrReq}%</b>`;
                    display(text);
                }
            }

            function display(text) {
                resultText.innerHTML = text;
                resultText.classList.remove('hidden');
            }

            function rumus(tMatch, tWr, wrReq) {
                let tWin = tMatch * (tWr / 100);
                let tLose = tMatch - tWin;
                let sisaWr = 100 - wrReq;
                let wrResult = 100 / sisaWr;
                let seratusPersen = tLose * wrResult;
                let final = seratusPersen - tMatch;
                return Math.round(final);
            }

            function rumusLose(tMatch, tWr, wrReq) {
                let totalWin = (tMatch * tWr) / 100;
                let win = (totalWin / (wrReq / 100)) - tMatch;
                return Math.round(win);
            }

            // Main
            window.addEventListener("load", init);

            function init() {
                load();
                eventListener();
            }

            function load() {
                // Removed checkLS and welcomeMsg calls since they are not implemented
                // You can implement these if needed
            }

            function eventListener() {
                hasil.addEventListener("click", validation);
            }

            // Removed unused placeholder functions (checkLS and welcomeMsg) to avoid errors

    </script>
    </body>
</html>