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
  <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.10.4/gsap.min.js"></script>
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
  <style>
        .carousel {
            position: relative;
            max-width: 700px; /* Lebar maksimum carousel */
            width: 100%; /* Lebar carousel relatif terhadap parent */
            margin: 0 auto; /* Pusatkan carousel secara horizontal */
            overflow: hidden;
            aspect-ratio: 16 / 9; /* Aspect ratio 16:9 */
            user-select: none; /* Mencegah seleksi teks */
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
            pointer-events: none; /* Mencegah interaksi dengan gambar saat drag */
            -webkit-user-drag: none; /* Mencegah drag gambar di browser berbasis WebKit (Chrome, Safari) */
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
            border-radius: 99999%;
        }
        .carousel-control-prev {
            left: 10px;
        }
        .carousel-control-next {
            right: 10px;
        }

        /* Modal CAPTCHA */
    #captchaModal {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.9);
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 1000;
    }
    .captcha-container {
      padding: 20px;
      border-radius: 10px;
      text-align: center;
    }
    .captcha-container h2 {
      margin-bottom: 20px;
      font-size: 24px;
      color: #ffffff;
    }
    .hidden {
      display: none;
    }

    .infinite-scroll-container {
    width: 100%;
    overflow: hidden;
    position: relative;
     /* Sesuaikan dengan kebutuhan */
    margin-bottom: 1rem; /* Jarak antara dua container */
  }

  .infinite-scroll-track {
    display: flex;
    width: max-content;
  }

  .scroll-left {
    animation: scroll-left 40s linear infinite; /* Animasi scroll ke kiri */
  }

  .scroll-right {
    animation: scroll-right 20s linear infinite; /* Animasi scroll ke kanan */
  }

  .infinite-scroll-item {
    display: flex;
    gap: 0.5rem; /* Jarak antara gambar */
    padding: 0 1rem; /* Padding untuk gambar */
  }

  .infinite-scroll-item img {
    width: 14rem; /* Sesuaikan ukuran gambar */
    height: 14rem;
    object-fit: cover;
    border-radius: 0.5rem; /* Optional: Tambahkan border-radius */
  }

  /* Efek fade di sisi kiri dan kanan */
.infinite-scroll-container::before,
.infinite-scroll-container::after {
  content: '';
  position: absolute;
  top: 0;
  bottom: 0;
  width: 10%; /* Lebar area fade */
  z-index: 2;
  pointer-events: none; /* Agar tidak mengganggu interaksi */
}
  .infinite-scroll-container::before {
  left: 0;
  background: linear-gradient(to right, black, transparent); /* Fade dari kiri */
}

.infinite-scroll-container::after {
  right: 0;
  background: linear-gradient(to left, black, transparent); /* Fade dari kanan */
}

  @keyframes scroll-left {
    0% {
      transform: translateX(0);
    }
    100% {
      transform: translateX(-50%);
    }
  }

  @keyframes scroll-right {
    0% {
      transform: translateX(-50%);
    }
    100% {
      transform: translateX(0);
    }
  }

  .typewriter {
    overflow: hidden; /* Menyembunyikan teks yang belum ditampilkan */
    border-right: 0.15em solid white; /* Kursor berkedip */
    white-space: nowrap; /* Mencegah teks pindah ke baris baru */
    margin: 0 auto;
    animation: typing 3.5s steps(40, end), blink-caret 0.75s step-end infinite;
}

/* Animasi mengetik */
@keyframes typing {
    from {
        width: 0;
    }
    to {
        width: 100%;
    }
}

/* Animasi kursor berkedip */
@keyframes blink-caret {
    from, to {
        border-color: transparent;
    }
    50% {
        border-color: white;
    }
}
  

    
  </style>
</head>
<body>  
    <!-- Modal CAPTCHA -->
    <div id="captchaModal">
    <div class="captcha-container">
      <h2>Please verify that you are not a robot</h2>
      <div class="cf-turnstile" data-sitekey="0x4AAAAAABBzpXkax6KDyeoA" data-callback="onCaptchaSuccess"></div>
    </div>
  </div>

     <!-- Konten utama -->
     <div id="mainContent" class="hidden ">
        
       <!-- Header -->
       <div class="flex flex-col md:flex-row justify-between items-center p-4 font-bold text-lg text-slate-200" style="font-family: 'Poppins'; background-color: #727DB6; position: sticky; top: 0; z-index: 3;">
         <!-- Menu Navigasi -->
         <ul class="flex flex-wrap justify-center space-x-5 mb-4 md:mb-0">
           <li>
             <a href="#home" class="hover:text-gray-300">Home</a>
           </li>
           <li>
             <a href="#pengurus" class="hover:text-gray-300">Pengurus</a>
           </li>
           <li>
             <a href="#proker" class="hover:text-gray-300">Program Kerja</a>
           </li>
           <li>
             <a href="#kepres" class="hover:text-gray-300">Kegiatan - Prestasi</a>
           </li>
           <li>
             <a href="#contact" class="hover:text-gray-300">Contact</a>
           </li>
         </ul>
       
         <!-- Login dan Daftar -->
         <div class="flex space-x-4 justify-center">
           <div class="border-2 border-white rounded px-3 py-1 hover:bg-white hover:text-[#727DB6] transition duration-300">
             <a href="../page/login.php">LOGIN</a></div>
           <div class="border-2 border-white rounded px-3 py-1 hover:bg-white hover:text-[#727DB6] transition duration-300"><a href="../page/register.php">REGISTER</a></div>
         </div>
       </div>
       
         <section id="home" style="font-family: 'Poppins'; background-color: #727DB6;">
           <!-- Main -->
           <div class="flex justify-center items-center text-slate-200">
            <div class="text-center flex flex-col items-center">
              <img src="../src/logo.png" alt="" class="mx-auto pointer-events-none select-none"> <!-- Gambar di tengah -->
              <div>
              <h1 class="font-bold text-2xl md:text-3xl pb-2">
                WELCOME TO&nbsp;<span class="typewriter"></span>
              </h1>
                <div class="pb-12 mx-2 text-center font-medium text-lg w-auto md:w-[760px]">
                  Cosmic Esport salah satu Organisasi Mahasiswa yang bertujuan untuk membangun dan mengembangkan bakat serta potensi para anggota dibidang Esport
                </div>
              </div>
              <div class="grid justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-down arrow">
                  <polyline points="6 9 12 3 18 9"></polyline>
                </svg>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-down arrow">
                  <polyline points="6 9 12 3 18 9"></polyline>
                </svg>
              </div>
              <div class="scroll-text pb-14">Scroll Ke bawah</div>
            </div>
          </div>
         </section>
       
         <!-- BPH UKM -->
       <section id="pengurus" class="bg-sky-100 pb-6 pointer-events-none select-none" style="font-family: 'Poppins'; color:#646565">
         <p class="text-center font-bold text-2xl pt-6 pb-7">BPH UKM - ESPORT</p>
         <div class="flex flex-wrap justify-center gap-6 text-center pb-5 px-5 md:px-10 lg:px-20">
           <div class="w-full sm:w-1/2 md:w-1/3 lg:w-1/4 xl:w-1/5" data-aos="fade-up" data-aos-duration="1000">
             <img src="../src/1.png" alt="" class="rounded-lg w-full h-100 object-cover">
             <div class="pt-4">
               <span class="font-bold text-xl">KETUA</span>
             </div>
           </div>
           <div class="w-full sm:w-1/2 md:w-1/3 lg:w-1/4 xl:w-1/5" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="200">
             <img src="../src/2.png" alt="" class="rounded-lg w-full h-100 object-cover">
             <div class="pt-4">
               <span class="font-bold text-xl">WAKIL KETUA</span>
             </div>
           </div>
           <div class="w-full sm:w-1/2 md:w-1/3 lg:w-1/4 xl:w-1/5" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="400">
             <img src="../src/3.png" alt="" class="rounded-lg w-full h-100 object-cover">
             <div class="pt-4">
               <span class="font-bold text-xl">BENDAHARA</span>
             </div>
           </div>
           <div class="w-full sm:w-1/2 md:w-1/3 lg:w-1/4 xl:w-1/5" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="600">
             <img src="../src/4.png" alt="" class="rounded-lg w-full h-100 object-cover">
             <div class="pt-4">
               <span class="font-bold text-xl">SEKRETARIS</span>
             </div>
           </div>
         </div>
       </section>
       
         <!-- Tim Creatif UKM -->
         <section class="bg-sky-100 pb-9 pointer-events-none select-none" style="font-family: 'Poppins'; color:#646565">
           <p class="text-center font-bold text-2xl pt-6 pb-7">TIM CREATIF UKM - ESPORT</p>
           <div class="flex flex-wrap justify-center gap-6 text-center pb-5 px-5 md:px-10 lg:px-20">
             <div class="w-full sm:w-1/2 md:w-1/3 lg:w-1/4 xl:w-1/5" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="700">
               <img src="../src/5.png" alt="" class="rounded-lg w-full h-100 object-cover">
               <div class="pt-4">
                 <span class="font-bold text-xl">HUMAS</span>
               </div>
             </div>
             <div class="w-full sm:w-1/2 md:w-1/3 lg:w-1/4 xl:w-1/5" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="900">
               <img src="../src/6.png" alt="" class="rounded-lg w-full h-100 object-cover">
               <div class="pt-4">
                 <span class="font-bold text-xl">HUMAS</span>
               </div>
             </div>
             <div class="w-full sm:w-1/2 md:w-1/3 lg:w-1/4 xl:w-1/5" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="1100">
               <img src="../src/7.png" alt="" class="rounded-lg w-full h-100 object-cover">
               <div class="pt-4">
                 <span class="font-bold text-xl">PDD</span>
               </div>
             </div>
             <div class="w-full sm:w-1/2 md:w-1/3 lg:w-1/4 xl:w-1/5" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="1300">
               <img src="../src/8.png" alt="" class="rounded-lg w-full h-100 object-cover">
               <div class="pt-4">
                 <span class="font-bold text-xl">PDD</span>
               </div>
             </div>
           </div>
         </section>
       
         <!-- Program Kerja -->
         <section id="proker" class="text-slate-200 pb-9" style="font-family: 'Poppins'; background-color: #727DB6;">
           <p class="text-center font-bold text-2xl py-4">PROGRAM KERJA</p>
       
           <div class="w-full overflow-hidden py-5 h-56 max-h-96 sm:h-full sm:max-h-[60vh] relative flex justify-center items-center">
             <!-- Slider Container -->
             <div class="carousel relative mx-8 rounded-lg shadow-md shadow-indigo-500 " >
                 <div class="carousel-inner">
                 <div class="carousel-item w-full max-w-[300px] sm:max-w-[500px] lg:max-w-[700px]">
                         <img src="/src/CSL.png" alt="Event Image" class="w-full h-auto object-cover">
                 </div>
                 <div class="carousel-item w-full max-w-[300px] sm:max-w-[500px] lg:max-w-[700px]">
                         <img src="/src/Patched.png" alt="Event Image" class="w-full h-auto object-cover">
                 </div>
                 <div class="carousel-item w-full max-w-[300px] sm:max-w-[500px] lg:max-w-[700px]">
                         <img src="/src/Challenge.png" alt="Event Image" class="w-full h-auto object-cover">
                 </div>
                 <div class="carousel-item w-full max-w-[300px] sm:max-w-[500px] lg:max-w-[700px]">
                         <img src="/src/Quiz.png" alt="Event Image" class="w-full h-auto object-cover">
                 </div>
                 <div class="carousel-item w-full max-w-[300px] sm:max-w-[500px] lg:max-w-[700px]">
                         <img src="/src/Nobar.png" alt="Event Image" class="w-full h-auto object-cover">
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
           </div>
         </section>
         
       
       <!-- Kegiatan Prestasi -->
       <section id="kepres" class="bg-sky-100 pb-9" style="font-family: 'Poppins'; color:#646565">
         <p class="text-center font-bold text-2xl pt-6 pb-7">KEGIATAN - PRESTASI</p>
         
         <div class="infinite-scroll-container pointer-events-none select-none">
            <!-- Scroll dari kanan ke kiri -->
            <div class="infinite-scroll-track scroll-left">
              <div class="infinite-scroll-item">
                <img src="../src/coh.JPG" alt="Image 1" class="w-56 h-56 object-cover">
                <img src="../src/WCSL.jpg" alt="Image 2" class="w-56 h-56 object-cover">
                <img src="../src/WCSL2.jpg" alt="Image 3" class="w-56 h-56 object-cover">
                <img src="../src/Welpart2.jpg" alt="Image 4" class="w-56 h-56 object-cover">
                <img src="../src/csl1.jpg" alt="Image 5" class="w-56 h-56 object-cover">
                <img src="../src/csl2.jpg" alt="Image 6" class="w-56 h-56 object-cover">
              </div>
              <div class="infinite-scroll-item" aria-hidden="true">
                <img src="../src/coh.JPG" alt="Image 1" class="w-56 h-56 object-cover">
                <img src="../src/WCSL.jpg" alt="Image 2" class="w-56 h-56 object-cover">
                <img src="../src/WCSL2.jpg" alt="Image 3" class="w-56 h-56 object-cover">
                <img src="../src/Welpart2.jpg" alt="Image 4" class="w-56 h-56 object-cover">
                <img src="../src/csl1.jpg" alt="Image 5" class="w-56 h-56 object-cover">
                <img src="../src/csl2.jpg" alt="Image 6" class="w-56 h-56 object-cover">
              </div>
            </div>
          </div>

          <div class="infinite-scroll-container pointer-events-none select-none">
            <!-- Scroll dari kiri ke kanan -->
            <div class="infinite-scroll-track scroll-right">
              <div class="infinite-scroll-item">
                <img src="../src/coh.JPG" alt="Image 1" class="w-56 h-56 object-cover">
                <img src="../src/WCSL.jpg" alt="Image 2" class="w-56 h-56 object-cover">
                <img src="../src/WCSL2.jpg" alt="Image 3" class="w-56 h-56 object-cover">
                <img src="../src/Welpart2.jpg" alt="Image 4" class="w-56 h-56 object-cover">
                <img src="../src/csl1.jpg" alt="Image 5" class="w-56 h-56 object-cover">
                <img src="../src/csl2.jpg" alt="Image 6" class="w-56 h-56 object-cover">
              </div>
              <div class="infinite-scroll-item" aria-hidden="true">
                <img src="../src/coh.JPG" alt="Image 1" class="w-56 h-56 object-cover">
                <img src="../src/WCSL.jpg" alt="Image 2" class="w-56 h-56 object-cover">
                <img src="../src/WCSL2.jpg" alt="Image 3" class="w-56 h-56 object-cover">
                <img src="../src/Welpart2.jpg" alt="Image 4" class="w-56 h-56 object-cover">
                <img src="../src/csl1.jpg" alt="Image 5" class="w-56 h-56 object-cover">
                <img src="../src/csl2.jpg" alt="Image 6" class="w-56 h-56 object-cover">
              </div>
            </div>
          </div>
       </section>
       
       <!-- Kontak -->
       <section id="contact" class="text-slate-200 pb-9 p-4 md:p-6 lg:p-8" style="background-color: #727DB6;">
         <p class="text-center font-bold text-2xl py-4 md:text-3xl lg:text-4xl">About Us</p>
         <div class="flex flex-col items-center space-y-6 md:flex-row md:justify-between md:space-y-0 md:space-x-6 lg:space-x-12">
           <!-- Alamat Section -->
           <div class="text-center md:text-left max-w-md lg:max-w-2xl">
             <h1 class="font-bold text-xl md:text-2xl lg:text-3xl">Alamat</h1>
             <p class="text-justify sm:text-lg lg:text-lg w-[250px] md:w-[500px]">
               Jl. Dr. Ir. H. Soekarno No.201, Klampis Ngasem, Kec. Sukolilo, Kota SBY, Jawa Timur 60117
             </p>
           </div>
       
           <!-- Contact Section -->
           <div class="text-center md:text-left">
              <h1 class="font-bold text-center md:text-right text-xl md:text-2xl lg:text-3xl mb-4">Contact</h1>
              <div class="flex justify-center md:justify-start space-x-4">
                  <!-- WhatsApp -->
                  <a href="https://wa.me/6282112683644" target="_blank" class="hover:opacity-80 transition-opacity">
                      <img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg" alt="WhatsApp" class="w-8 h-8 md:w-10 md:h-10">
                  </a>
                  <!-- Instagram -->
                  <a href="https://www.instagram.com/cosmic.ukdc" target="_blank" class="hover:opacity-80 transition-opacity">
                      <img src="https://upload.wikimedia.org/wikipedia/commons/a/a5/Instagram_icon.png" alt="Instagram" class="w-8 h-8 md:w-10 md:h-10">
                  </a>
                  <!-- Gmail -->
                  <a href="mailto:cosmicukdc@gmail.com" target="_blank" class="hover:opacity-80 transition-opacity">
                      <img src="https://upload.wikimedia.org/wikipedia/commons/4/4e/Gmail_Icon.png" alt="Gmail" class="w-8 h-8 md:w-10 md:h-10">
                  </a>
              </div>
          </div>
         </div>
       </section>
    </div>


  <script>
    
    // Slider 
    let currentSlide = 0;
    const slides = document.querySelectorAll('.carousel-item');
    const totalSlides = slides.length;
    const carouselInner = document.querySelector('.carousel-inner');
    const carousel = document.querySelector('.carousel');

    // Fungsi untuk mendeteksi bot
    function isBot() {
      const userAgent = navigator.userAgent.toLowerCase();
      const bots = ['bot', 'spider', 'crawler', 'curl', 'wget', 'python', 'java', 'php', 'ruby', 'perl', 'go-http', 'node-fetch'];
      return bots.some(bot => userAgent.includes(bot));
    }

    // Fungsi CAPTCHA
    function onCaptchaSuccess(token) {
      fetch('/api/validate-captcha.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ token: token }),
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          document.getElementById('captchaModal').style.display = 'none';
          document.getElementById('mainContent').style.display = 'block';
          localStorage.setItem('captchaVerified', 'true');
        } else {
          alert('CAPTCHA verification failed. Please try again.');
        }
      })
      .catch(error => {
        console.error('Error:', error);
      });
    }

    // Cek saat halaman dimuat
    document.addEventListener('DOMContentLoaded', function () {

    AOS.init({
      once: false,
      mirror: true,
      duration: 1000,
      easing: 'ease-in-out',
    });

    // Paksa AOS untuk memeriksa ulang elemen saat scroll
      window.addEventListener('scroll', function() {
        AOS.refresh();
      });

      const captchaVerified = localStorage.getItem('captchaVerified');

      if (isBot()) {
        window.location.href = 'blocked.php';
      } else if (captchaVerified === 'true') {
        document.getElementById('captchaModal').style.display = 'none';
        document.getElementById('mainContent').style.display = 'block';
      } else {
        document.getElementById('captchaModal').style.display = 'flex';
      }

      // Animasi panah
      gsap.to('.arrow', {
            y: 13, // Gerakkan panah ke bawah sejauh 10px
            duration: 0.85, // Durasi animasi 1 detik
            repeat: -1, // Ulang animasi terus-menerus
            yoyo: true, // Buat animasi bolak-balik
            ease: "power1.inOut" // Efek easing
        });

        const typewriterElement = document.querySelector('.typewriter');
  const text = "COSMIC ESPORT"; // Teks yang akan ditampilkan
  let index = 0;
  let isDeleting = false; // Status apakah sedang menghapus teks
  let waitBeforeRestart = 2500; // Waktu tunggu sebelum memulai ulang (2,5 detik)

  function typeWriter() {
    if (!isDeleting && index < text.length) {
      // Mengetik teks
      typewriterElement.textContent += text.charAt(index);
      index++;
      setTimeout(typeWriter, 150); // Kecepatan mengetik (ms)
    } else if (isDeleting && index > 0) {
      // Menghapus teks
      typewriterElement.textContent = text.substring(0, index - 1);
      index--;
      setTimeout(typeWriter, 150); // Kecepatan menghapus (ms)
    } else if (index === text.length) {
      // Setelah selesai mengetik, tunggu 10 detik lalu mulai menghapus
      isDeleting = true;
      setTimeout(typeWriter, 10000); // Tunggu 10 detik sebelum menghapus
    } else if (index === 0 && isDeleting) {
      // Setelah selesai menghapus, tunggu 5 detik lalu mulai mengetik ulang
      isDeleting = false;
      setTimeout(typeWriter, waitBeforeRestart); // Tunggu 2,5 detik sebelum mengetik ulang
    }
  }

  typeWriter(); // Mulai animasi pertama kali
    });


    const track = document.querySelector('.infinite-scroll-track');
    const items = document.querySelectorAll('.infinite-scroll-item');

    // Duplikat item untuk efek infinite
    items.forEach(item => {
      const clone = item.cloneNode(true);
      track.appendChild(clone);
    });

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
  </script>
</body>
</html>