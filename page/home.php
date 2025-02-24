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
                            <a href="../page/home.php" class="flex items-center p-2 hover:bg-slate-600 rounded">
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
                                <li><a href="#" class="block p-2 hover:bg-slate-600 rounded">Calculator WR</a></li>
                                <li><a href="#" class="block p-2 hover:bg-slate-600 rounded">Search NickGame</a></li>
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
                    <img src="<?php echo !empty($user['profile_image']) ? $user['profile_image'] : '../src/1.png'; ?>" alt="Profile Image" class="w-full h-full object-cover">
                </a>
            </header>

            <!-- Main Content Area -->
<!-- Main Content Area -->
<main class="flex-1 overflow-hidden">

    <!-- Section Meteor dan Slider -->
    <section class="w-full overflow-hidden bg-gradient-to-r from-gray-700 to-gray-800 py-5 h-56 max-h-96 sm:h-full sm:max-h-[60vh] relative flex justify-center items-center">
        <!-- Meteor Container -->
        <div id="meteor-container" class="absolute h-full w-full" style="z-index: 1;"></div>
 
        <!-- Slider Container -->
        <div class="carousel relative mx-8 rounded-lg shadow-md shadow-indigo-500 " style="z-index: 10;">
            <div class="carousel-inner">
                <div class="carousel-item w-full max-w-[300px] sm:max-w-[500px] lg:max-w-[700px]">
                    <img src="../src/coba.jpg" alt="Event Image 1" class="w-full h-auto object-cover">
                </div>
                <div class="carousel-item w-full max-w-[300px] sm:max-w-[500px] lg:max-w-[700px]">
                    <img src="../page/uploads/profile_images/test.jpg" alt="Event Image 2" class="w-full h-auto object-cover">
                </div>
                <div class="carousel-item w-full max-w-[300px] sm:max-w-[500px] lg:max-w-[700px]">
                    <img src="../src/Vincent.png" alt="Event Image 3" class="w-full h-auto object-cover">
                </div>
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

    
    <section class="w-full px-4 py-4 bg-gray-100">
    <h3 class="text-xl font-semibold mb-4" style="font-family: 'Poppins';">Jadwal Pertemuan</h3>
    <div class="bg-white p-4 rounded-lg shadow-md flex justify-between items-center" style="font-family: 'Poppins';">
        <div>
            <p class="text-lg font-medium">Pertemuan Rutin – 1 [Topik Acara]</p>
            <p class="text-sm text-gray-600">Hari, Tanggal: Jumat, dd-mm-yyyy</p>
            <p class="text-sm text-gray-600">Jam: 00:00</p>
            <div class="flex items-center space-x-2">
                <span class="text-sm text-gray-600">Status :</span>
                <div class="flex space-x-2">
                    <button class="px-3 py-1 bg-green-500 text-white rounded-full text-sm">Pertemuan</button>
                    <button class="px-3 py-1 bg-red-500 text-white rounded-full text-sm">Libur</button>
                </div>
            </div>
        </div>
        <div class="flex flex-col items-end">
            <p class="text-sm text-gray-500">Jumlah Absen 0/12</p>
            <div class="mt-2 flex space-x-2 rounded-full" style="background-color: #727DB6;">
                <button id="scanButton" class="px-3 py-1 text-white rounded-full text-sm hover:bg-[#606a9a] transition" >Scan</button>
            </div>
        </div>
    </div>
</section>

        </div>
    </section>
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

        // Auto slide every 5 seconds
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

        // Fungsi untuk QR Scanner (Diperbarui)
        let html5QrcodeScanner;

        async function checkCameraPermission() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                stream.getTracks().forEach(track => track.stop()); // Hentikan akses setelah cek
                return true;
            } catch (error) {
                alert("Akses kamera ditolak atau tidak tersedia. Mohon izinkan akses kamera di pengaturan browser Anda.");
                console.error("Error checking camera permission:", error);
                return false;
            }
        }

        scanButton.addEventListener('click', async () => {
            if (!html5QrcodeScanner) {
                try {
                    // Periksa izin kamera
                    const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                    stream.getTracks().forEach(track => track.stop());

                    // Buat elemen video untuk menampilkan kamera
                    const videoElement = document.createElement('video');
                    videoElement.id = 'video';
                    videoElement.style = 'width: 100%; max-width: 500px; border-radius:15px;';

                    // Buat div untuk menampilkan scanner
                    const qrReaderDiv = document.createElement('div');
                    qrReaderDiv.id = 'qr-reader';
                    qrReaderDiv.className = 'fixed inset-0 bg-gray-800 bg-opacity-75 flex justify-center items-center z-50';
                    qrReaderDiv.innerHTML = `
                    <div class="bg-[#727DB6] p-4 rounded-lg shadow-lg w-full max-w-md h-[55vh] flex flex-col justify-between"> <!-- Menyesuaikan tinggi dengan 90% viewport -->
                        <div class="flex justify-between items-center mb-2">
                            <h2 class="text-lg text-slate-200 font-semibold">Absen</h2>
                            <button id="closeScanner" class="px-3 py-1 bg-red-500 text-slate-200 text-center rounded-full hover:bg-red-600 transition">
                                Tutup
                            </button>
                        </div>
                        <div id="reader" class="w-full flex-1 bg-black rounded"></div>
                    </div>
                    `;
                    document.body.appendChild(qrReaderDiv);

                    // Tambahkan video ke div reader
                    document.getElementById('reader').appendChild(videoElement);

                    // Inisialisasi ZXing
                    const codeReader = new ZXing.BrowserMultiFormatReader();
                    codeReader.decodeFromVideoDevice(null, 'video', (result, error) => {
                        if (result) {
                            // Ketika QR berhasil discan
                            recordAttendance(result.text);
                            codeReader.reset();
                            document.getElementById('qr-reader').remove();
                            alert(`Absensi berhasil dengan kode: ${result.text}`);
                        }
                        if (error && !(error instanceof ZXing.NotFoundException)) {
                            console.error("Error scanning QR:", error);
                        }
                    });

                    // Tambahkan tombol untuk menutup scanner
                    document.getElementById('closeScanner').addEventListener('click', () => {
                        codeReader.reset();
                        document.getElementById('qr-reader').remove();
                    });
                } catch (error) {
                    alert("Akses kamera ditolak atau tidak tersedia. Mohon izinkan akses kamera di pengaturan browser Anda.");
                    console.error("Error accessing camera:", error);
                }
            }
        });

        // Fungsi untuk merekam absensi ke server
        function recordAttendance(qrCode) {
            const userId = <?php echo json_encode($_SESSION['user']['id'] ?? ''); ?>; // Ambil ID pengguna dari session
            const meetingId = 1; // Ganti dengan ID pertemuan yang sesuai

            $.ajax({
                url: '../api/record_attendance.php', // File PHP untuk menyimpan absensi
                method: 'POST',
                data: {
                    user_id: userId,
                    meeting_id: meetingId,
                    qr_code: qrCode
                },
                success: function(response) {
                    console.log("Absensi berhasil disimpan:", response);
                    // Perbarui jumlah absen di UI jika diperlukan
                    updateAttendanceCount(response.attendance_count);
                },
                error: function(xhr, status, error) {
                    console.error("Gagal menyimpan absensi:", error);
                    alert("Gagal merekam absensi. Coba lagi.");
                }
            });
        }

        // Fungsi untuk memperbarui jumlah absen di UI
        function updateAttendanceCount(count) {
            const attendanceElement = document.querySelector('.text-sm.text-gray-500');
            if (attendanceElement) {
                attendanceElement.textContent = `Jumlah Absen ${count}/12`;
            }
        }
    </script>
</body>
</html>