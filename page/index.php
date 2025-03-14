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

// Query untuk mengambil data pertemuan yang belum selesai (jam_akhir IS NULL)
$queryPertemuan = "
    SELECT * 
    FROM jadwal_pertemuan 
    WHERE status IN ('belum_mulai', 'berlangsung')
    ORDER BY tanggal DESC
";
$resultPertemuan = $db->query($queryPertemuan);

// Query untuk mengambil data informasi yang akan ditampilkan di carousel
$queryCarousel = "SELECT gambar, link FROM informasi WHERE tanggal_berakhir >= date('now') ORDER BY tanggal_publish DESC";
$resultCarousel = $db->query($queryCarousel);

// Periksa apakah query berhasil dijalankan
if (!$resultCarousel) {
    die("Error fetching carousel data: " . $db->lastErrorMsg());
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
    <script src="https://cdn.jsdelivr.net/npm/jsqr/dist/jsQR.js"></script>
    <script src="https://webrtc.github.io/adapter/adapter-latest.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .carousel {
            position: relative;
            max-width: 700px; /* Lebar maksimum carousel */
            width: 100%; /* Lebar carousel relatif terhadap parent */
            margin: 0 auto; /* Pusatkan carousel secara horizontal */
            overflow: hidden;
            z-index: 50;
            aspect-ratio: 16 / 9; /* Aspect ratio 16:9 */
        }
        .carousel-inner {
            display: flex;
            transition: transform 0.5s ease;
            height: 100%;
        }
        .carousel-item {
            min-width: 100%;
            box-sizing: border-box;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .carousel-item img {
            width: 100%;
            height: 100%;
            object-fit: cover; /* Pastikan gambar menutupi area tanpa distorsi */
        }
        .carousel-control-prev, .carousel-control-next {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background-color: rgba(0, 0, 0, 0.5);
            color: white;
            border: none;
            cursor: pointer;
            padding: 10px;
            z-index: 100;
            border-radius: 99999%;
        }
        .carousel-control-prev {
            left: 10px;
        }
        .carousel-control-next {
            right: 10px;
        }



                @keyframes meteor {
            0% {
                transform: translate(-100%, -100%) rotate(215deg);
                opacity: 1;
            }
            100% {
                transform: translate(100vw, 100vh) rotate(215deg);
                opacity: 0;
            }
        }

        .animate-meteor-effect {
            animation: meteor linear infinite;
        }

        #meteor-container {
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 5; /* Meteor di belakang slider */
    }
        
    .swal2-progress-bar {
        background-color: #4CAF50 !important; /* Warna hijau */
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
                            <a href="../page/pertemuan.php" class="flex items-center p-2 hover:bg-slate-600 rounded">
                                <i class="fa-solid fa-calendar mr-2"></i>
                                Pertemuan
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

            <!-- Main Content Area -->
<!-- Main Content Area -->
<main class="flex-1 overflow-y-auto h-[calc(100vh-5rem)]">

    <!-- Section Meteor dan Slider -->
    <section class="w-full overflow-hidden bg-gradient-to-r from-gray-700 to-gray-800 py-5 h-56 max-h-96 sm:h-full sm:max-h-[60vh] relative flex justify-center items-center">
        <!-- Meteor Container -->
        <div id="meteor-container" class="absolute h-full w-full" style="z-index: 1;"></div>
 
        <!-- Slider Container -->
        <div class="carousel relative mx-8 rounded-lg shadow-md shadow-indigo-500 " style="z-index: 10;">
            <div class="carousel-inner">
                <?php while ($rowCarousel = $resultCarousel->fetchArray(SQLITE3_ASSOC)): ?>
                <div class="carousel-item w-full max-w-[300px] sm:max-w-[500px] lg:max-w-[700px]">
                    <a class="w-full h-auto object-cover" href="<?php echo htmlspecialchars($rowCarousel['link']); ?>" target="_blank">
                        <img src="<?php echo htmlspecialchars($rowCarousel['gambar']); ?>" alt="Event Image" class="w-full h-auto object-cover">
                    </a>
                </div>
                <?php endwhile; ?>
            </div>

            <!-- Slider Controls -->
            <!-- Slider Controls - Hidden on mobile, visible on sm and up -->
            <button class="carousel-control-prev hidden sm:block" onclick="prevSlide()">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-8 w-8 pr-1"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"></path></svg>
            </button>
            <button class="carousel-control-next hidden sm:block" onclick="nextSlide()">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="h-8 w-8 pl-1"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"></path></svg>
            </button>
        </div>
    </section>

    
    <section class="p-4">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-bold">Jadwal Pertemuan</h2>
                </div>
                <!-- Daftar Pertemuan Rutin -->
                <div id="daftarPertemuan" class="space-y-4">
                    <?php while ($row = $resultPertemuan->fetchArray(SQLITE3_ASSOC)): ?>
                        <div class="block p-4 bg-white rounded-lg shadow hover:bg-gray-50 transition">
                            <h3 class="text-xl font-bold"><?php echo htmlspecialchars($row['nama_topik']); ?></h3>
                            <p class="text-gray-600">Hari, Tanggal : <?php echo htmlspecialchars($row['hari']); ?>, <?php echo htmlspecialchars($row['tanggal']); ?></p>
                            <p class="text-gray-600">Jam : <?php echo htmlspecialchars($row['jam_pertemuan']); ?></p>
                            <p class="text-gray-600">Ruangan : <span class="text-purple-600 font-bold"><?php echo htmlspecialchars($row['kelas']); ?></span></p>
                            <div class="mt-2 flex space-x-2 w-14 rounded-full" style="background-color: #727DB6;">
                                <button 
                                    class="scanButton px-2 py-1 w-14 text-white rounded-full text-sm hover:bg-[#606a9a] transition" 
                                    data-pertemuan-id="<?php echo $row['id']; ?>"
                                >
                                    Scan
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    
                </div>
            </section>

        </div>
    </div>
</section>

        </div>
    </section>

    <!-- Modal untuk QR Code Scanner -->
<div id="qrScannerModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white p-6 rounded-lg w-11/12 max-w-md">
        <h2 class="text-xl font-bold mb-4">Absen - QR</h2>
        <div id="scanner-container" class="w-full h-64 bg-gray-200 flex items-center justify-center">
            <video id="scanner-video" class="w-full h-full object-cover"></video>
        </div>
        <div class="mt-4 flex justify-end space-x-2">
            <button id="closeScannerModal" class="bg-[#727DB6] hover:bg-[#5c6491] text-white px-4 py-2 rounded-lg  transition">
                Tutup
            </button>
        </div>
    </div>
</div>
</main>

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


        // Meteor Animate
        const meteorContainer = document.getElementById('meteor-container');
        let meteorCount = 0; // Track the number of meteors

        function createMeteor() {
            if (meteorCount >= 30) return; // Stop creating meteors if the limit is reached

            const meteor = document.createElement('span');
            meteor.className = 'absolute left-1/2 top-1/2 h-1 w-1 rotate-[215deg] animate-meteor-effect rounded-[9999px] bg-white shadow-[0_0_0_1px_#ffffff10] before:absolute before:top-1/2 before:h-[1px] before:w-[80px] before:-translate-y-[0%] before:transform before:bg-gradient-to-r before:from-white before:to-transparent before:content-[""]';

            // Randomly choose starting position: top or left-bottom
            const startFromTop = Math.random() < 0.5; // 50% chance to start from top
            let startX, startY;

            if (startFromTop) {
                // Start from top of the screen
                startX = Math.random() * window.innerWidth; // Random X position
                startY = 0; // Start from the top
            } else {
                // Start from left-bottom of the screen
                startX = 0; // Start from the left
                startY = Math.random() * window.innerHeight; // Random Y position
            }

            // Random animation duration for varied speed
            const duration = 2 + Math.random() * 6; // Random duration between 2s and 5s

            // Apply styles
            meteor.style.left = `${startX}px`;
            meteor.style.top = `${startY}px`;
            meteor.style.animationDuration = `${duration}s`;

            meteorContainer.appendChild(meteor);
            meteorCount++; // Increment the meteor count

            // Remove meteor after animation ends
            meteor.addEventListener('animationend', () => {
                meteor.remove();
                meteorCount--; // Decrement the meteor count
            });
        }

        // Create 15 meteors immediately when the page loads
        for (let i = 0; i < 10; i++) {
            createMeteor();
        }

        // Create new meteors frequently for a dense meteor shower effect
        setInterval(createMeteor, 100); // New meteor every 
        
        // Slider 
        let currentSlide = 0;
        const slides = document.querySelectorAll('.carousel-item');
        const totalSlides = slides.length;
        const carouselInner = document.querySelector('.carousel-inner');
        const carousel = document.querySelector('.carousel');

        function showSlide(index) {
            currentSlide = index;
            if (currentSlide < 0) {
                currentSlide = totalSlides - 1;
            } else if (currentSlide >= totalSlides) {
                currentSlide = 0;
            }
            
            const offset = -currentSlide * 100;
            carouselInner.style.transform = `translateX(${offset}%)`;
        }

        function nextSlide() {
            showSlide(currentSlide + 1);
        }

        function prevSlide() {
            showSlide(currentSlide - 1);
        }

        // Auto slide every 8 seconds
        setInterval(nextSlide, 8000);

        // Initialize first slide
        showSlide(currentSlide);

        // Swipe functionality for mobile
        let touchStartX = 0;
        let touchEndX = 0;

        carousel.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
        }, false);

        carousel.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        }, false);

        function handleSwipe() {
            const swipeThreshold = 50; // Minimum distance for a swipe to register
            if (touchStartX - touchEndX > swipeThreshold) {
                // Swipe left - next slide
                nextSlide();
            }
            if (touchEndX - touchStartX > swipeThreshold) {
                // Swipe right - previous slide
                prevSlide();
            }
        }

        const qrScannerModal = document.getElementById('qrScannerModal');
        const scannerVideo = document.getElementById('scanner-video');
        const closeScannerModal = document.getElementById('closeScannerModal');
        let stream = null;

// Fungsi untuk membuka modal scanner
function openQRScanner(pertemuanId) {
    // Periksa status sesi dari server
    fetch(`../api/get_session_status.php?pertemuan_id=${pertemuanId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json(); // Parse the response as JSON
        })
        .then(data => {
            if (data.success && data.status === 'berlangsung') {
                // Jika sesi berlangsung, buka modal scanner
                console.log("Membuka modal scanner...");
                qrScannerModal.classList.remove('hidden');

                // Periksa apakah mediaDevices tersedia
                if (!navigator.mediaDevices) {
                    console.error("Browser tidak mendukung navigator.mediaDevices.");
                    Swal.fire('Error', 'Browser Anda tidak mendukung akses kamera.', 'error');
                    closeQRScanner();
                    return;
                }

                // Minta izin kamera sebelum memulai scanner
                navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } }) // Gunakan kamera belakang
                    .then(function(s) {
                        stream = s;
                        scannerVideo.srcObject = stream;
                        scannerVideo.play();
                        requestAnimationFrame(scanQRCode);
                    })
                    .catch(function(err) {
                        console.error("Izin kamera ditolak:", err);
                        Swal.fire('Error', 'Izin kamera diperlukan untuk memindai QR Code.', 'error');
                        closeQRScanner();
                    });
            } else {
                // Jika sesi belum dimulai, tampilkan pesan
                Swal.fire({
                    icon: 'warning',
                    title: 'Sesi Belum Dimulai',
                    text: 'Anda tidak dapat melakukan scan karena sesi pertemuan belum dimulai.',
                    confirmButtonText: 'OK'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'Terjadi kesalahan saat memeriksa status sesi.', 'error');
        });
}

// Fungsi untuk menutup modal scanner
function closeQRScanner() {
    console.log("Menutup modal scanner...");
    qrScannerModal.classList.add('hidden');
    if (stream) {
        stream.getTracks().forEach(track => track.stop()); // Hentikan stream kamera
    }
}

// Fungsi untuk memindai QR code
function scanQRCode() {
    if (scannerVideo.readyState === scannerVideo.HAVE_ENOUGH_DATA) {
        const canvas = document.createElement('canvas');
        canvas.width = scannerVideo.videoWidth;
        canvas.height = scannerVideo.videoHeight;
        const context = canvas.getContext('2d');
        context.drawImage(scannerVideo, 0, 0, canvas.width, canvas.height);
        const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
        const code = jsQR(imageData.data, imageData.width, imageData.height);

        if (code) {
            console.log("Hasil scan:", code.data);
            handleScanResult(code.data);
            closeQRScanner();
        } else {
            requestAnimationFrame(scanQRCode); // Lanjutkan pemindaian jika QR code tidak ditemukan
        }
    } else {
        requestAnimationFrame(scanQRCode); // Lanjutkan pemindaian jika video belum siap
    }
}

// Fungsi untuk menangani hasil scan QR code
function handleScanResult(resultText) {
    console.log("Menangani hasil scan:", resultText);
    Swal.fire({
        title: 'Berhasil!',
        text: `QR Code berhasil dipindai: ${resultText}`,
        icon: 'success',
        confirmButtonText: 'OK'
    });

    // Kirim data kehadiran ke server
    fetch('../api/absensi.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            qr_data: resultText,
            nim: '<?php echo $user['nim']; ?>', // NIM pengguna yang melakukan scan
            status: 'Hadir' // Status default
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire('Berhasil!', 'Kehadiran berhasil dicatat.', 'success');
        } else {
            Swal.fire('Error', 'Gagal menyimpan data kehadiran.', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error', 'Terjadi kesalahan saat mengirim data.', 'error');
    });
}

// Event listener untuk tombol scan
document.querySelectorAll('.scanButton').forEach(button => {
    button.addEventListener('click', (event) => {
        event.preventDefault(); // Mencegah perilaku default tombol
        const pertemuanId = button.getAttribute('data-pertemuan-id'); // Ambil ID pertemuan dari data attribute
        openQRScanner(pertemuanId); // Panggil fungsi dengan ID pertemuan
    });
});

// Event listener untuk tombol tutup modal
closeScannerModal.addEventListener('click', closeQRScanner);
        

        //Pop Up Login
        <?php if (isset($_SESSION['just_logged_in'])): ?>
            const Toast = Swal.mixin({
                toast: true,
                position: "top-end",
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                customClass: {
                    timerProgressBar: 'swal2-progress-bar' // Gunakan class CSS khusus
                }
            });
            Toast.fire({
                icon: "success",
                title: "Signed in successfully"
            });

            // Hapus session `just_logged_in` setelah SweetAlert ditampilkan
            <?php unset($_SESSION['just_logged_in']); ?>
        <?php endif; ?>
    </script>
</body>
</html>