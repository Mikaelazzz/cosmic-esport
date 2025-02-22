<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>UKM - Esport</title>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    .carousel {
      position: relative;
      max-width: 600px;
      margin: auto;
      overflow: hidden;
    }
    .carousel-controls {
      display: flex;
      justify-content: space-between;
      padding: 10px;
    }
    .carousel-control-prev, .carousel-control-next {
      color: white;
      border: none;
      cursor: pointer;
      padding: 20px;
    }
    .carousel-inner {
      display: flex;
      transition: transform 0.5s ease;
    }
    .carousel-item {
      min-width: 100%;
      box-sizing: border-box;
    }
    .carousel-item.active {
      display: block;
    }
    .carousel-item:not(.active) {
      display: none;
    }
    .carousel-indicators {
      display: flex;
      justify-content: center;
      padding: 10px;
    }
    .carousel-indicator {
      width: 10px;
      height: 10px;
      border-radius: 50%;
      background-color: #ccc;
      margin: 5px;
    }
    .carousel-indicator.active {
      background-color: #333;
    }

    
  </style>
</head>
<body>  
<!-- Header -->
<div class="flex flex-col md:flex-row justify-between items-center p-4 font-bold text-lg text-slate-200" style="font-family: 'Poppins'; background-color: #727DB6; position: sticky; top: 0; z-index: 1;">
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
  <div class="text-center">
    <img src="../src/logo.png" alt="">
    <h1 class="font-bold text-3xl pb-2">WELCOME TO COSMIC ESPORT</h1>
    <div class="pb-16 font-medium text-lg">Deskripsi</div>
    <br><br><br>
    <div class="grid justify-center">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-down">
        <polyline points="6 9 12 3 18 9"></polyline>
      </svg>
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-down">
        <polyline points="6 9 12 3 18 9"></polyline>
      </svg>
  </div>
  <div class="scroll-text pb-14">Scroll Ke bawah</div>
  </div>
</div>
  </section>

  <!-- BPH UKM -->
<section id="pengurus" class="bg-sky-100 pb-6" style="font-family: 'Poppins'; color:#646565">
  <p class="text-center font-bold text-2xl pt-6 pb-7">BPH UKM - ESPORT</p>
  <div class="flex flex-wrap justify-center gap-6 text-center pb-5 px-5 md:px-10 lg:px-20">
    <div class="w-full sm:w-1/2 md:w-1/3 lg:w-1/4 xl:w-1/5">
      <img src="../src/1.png" alt="" class="rounded-lg w-full h-100 object-cover">
      <div class="pt-4">
        <span class="font-bold text-xl">KETUA</span>
      </div>
    </div>
    <div class="w-full sm:w-1/2 md:w-1/3 lg:w-1/4 xl:w-1/5">
      <img src="../src/2.png" alt="" class="rounded-lg w-full h-100 object-cover">
      <div class="pt-4">
        <span class="font-bold text-xl">WAKIL KETUA</span>
      </div>
    </div>
    <div class="w-full sm:w-1/2 md:w-1/3 lg:w-1/4 xl:w-1/5">
      <img src="../src/3.png" alt="" class="rounded-lg w-full h-100 object-cover">
      <div class="pt-4">
        <span class="font-bold text-xl">BENDAHARA</span>
      </div>
    </div>
    <div class="w-full sm:w-1/2 md:w-1/3 lg:w-1/4 xl:w-1/5">
      <img src="../src/4.png" alt="" class="rounded-lg w-full h-100 object-cover">
      <div class="pt-4">
        <span class="font-bold text-xl">SEKRETARIS</span>
      </div>
    </div>
  </div>
</section>

  <!-- Tim Creatif UKM -->
  <section class="bg-sky-100 pb-9" style="font-family: 'Poppins'; color:#646565">
    <p class="text-center font-bold text-2xl pt-6 pb-7">TIM CREATIF UKM - ESPORT</p>
    <div class="flex flex-wrap justify-center gap-6 text-center pb-5 px-5 md:px-10 lg:px-20">
      <div class="w-full sm:w-1/2 md:w-1/3 lg:w-1/4 xl:w-1/5">
        <img src="../src/5.png" alt="" class="rounded-lg w-full h-100 object-cover">
        <div class="pt-4">
          <span class="font-bold text-xl">ACARA</span>
        </div>
      </div>
      <div class="w-full sm:w-1/2 md:w-1/3 lg:w-1/4 xl:w-1/5">
        <img src="../src/6.png" alt="" class="rounded-lg w-full h-100 object-cover">
        <div class="pt-4">
          <span class="font-bold text-xl">ACARA</span>
        </div>
      </div>
      <div class="w-full sm:w-1/2 md:w-1/3 lg:w-1/4 xl:w-1/5">
        <img src="../src/7.png" alt="" class="rounded-lg w-full h-100 object-cover">
        <div class="pt-4">
          <span class="font-bold text-xl">PDD</span>
        </div>
      </div>
      <div class="w-full sm:w-1/2 md:w-1/3 lg:w-1/4 xl:w-1/5">
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
    <div class="carousel">
      <div class="carousel-controls">
        <button class="carousel-control-prev" onclick="prevSlide()">
          <span class="carousel-control-prev-icon text-2xl hover:text-zinc-700 font-bold"><</span>
        </button>
        <div class="carousel-inner">
          <img src="../src/Vincent.png" alt="Image 1" class="carousel-item active">
          <img src="../src/logo.png" alt="Image 2" class="carousel-item">
          <img src="../src/Vincent.png" alt="Image 3" class="carousel-item">
        </div>
        <button class="carousel-control-next" onclick="nextSlide()">
          <span class="carousel-control-next-icon text-2xl hover:text-zinc-700 font-bold">></span>
        </button>
      </div>
    </div>
    <div class="flex justify-center mt-4" id="dots"></div>
  </section>
  

<!-- Kegiatan Prestasi -->
<section id="kepres" class="bg-sky-100 pb-9" style="font-family: 'Poppins'; color:#646565">
  <p class="text-center font-bold text-2xl pt-6 pb-7">KEGIATAN - PRESTASI</p>
  
  <div class="marquee-container">
    <!-- Marquee Top (Gambar 1-8, bergerak ke kanan) -->
    <marquee scrolldelay="1" direction="right" >
      <div class="bg-black marquee-bottom bottom-0 w-full h-1/2 flex whitespace-nowrap animate-marquee-bottom">
        <img src="../src/Vincent.png" alt="Image 1" class="w-56 h-56 object-cover mr-2">
        <img src="../src/logo.png" alt="Image 2" class="w-56 h-56 object-cover mr-2">
        <img src="../src/Vincent.png" alt="Image 3" class="w-56 h-56 object-cover mr-2">
        <img src="../src/logo.png" alt="Image 4" class="w-56 h-56 object-cover mr-2">
        <img src="../src/Vincent.png" alt="Image 1" class="w-56 h-56 object-cover mr-2">
        <img src="../src/logo.png" alt="Image 2" class="w-56 h-56 object-cover mr-2">
        <img src="../src/Vincent.png" alt="Image 3" class="w-56 h-56 object-cover mr-2">
        <img src="../src/logo.png" alt="Image 4" class="w-56 h-56 object-cover">
       </div>
    </marquee>

    <!-- Marquee Bottom (Gambar 9-16, bergerak ke kiri) -->
     <marquee scrolldelay="1" direction="left">
       <div class="bg-black marquee-bottom bottom-0 w-full h-1/2 flex whitespace-nowrap animate-marquee-bottom">
        <img src="../src/Vincent.png" alt="Image 1" class="w-56 h-56 object-cover mr-2">
        <img src="../src/logo.png" alt="Image 2" class="w-56 h-56 object-cover mr-2">
        <img src="../src/Vincent.png" alt="Image 3" class="w-56 h-56 object-cover mr-2">
        <img src="../src/logo.png" alt="Image 4" class="w-56 h-56 object-cover mr-2">
        <img src="../src/Vincent.png" alt="Image 1" class="w-56 h-56 object-cover mr-2">
        <img src="../src/logo.png" alt="Image 2" class="w-56 h-56 object-cover mr-2">
        <img src="../src/Vincent.png" alt="Image 3" class="w-56 h-56 object-cover mr-2">
        <img src="../src/logo.png" alt="Image 4" class="w-56 h-56 object-cover">
       </div>
     </marquee>
  </div>
</section>

<!-- Kontak -->
<section id="contact" class="text-slate-200 pb-9 p-4 md:p-6 lg:p-8" style="background-color: #727DB6;">
  <p class="text-center font-bold text-2xl py-4 md:text-3xl lg:text-4xl">About Us</p>
  <div class="flex flex-col items-center space-y-6 md:flex-row md:justify-between md:space-y-0 md:space-x-6 lg:space-x-12">
    <!-- Alamat Section -->
    <div class="text-center md:text-left max-w-md lg:max-w-2xl">
      <h1 class="font-bold text-xl md:text-2xl lg:text-3xl">Alamat</h1>
      <p class="text-justify sm:text-lg lg:text-lg">
        Jl. Dr. Ir. H. Soekarno No.201, Klampis Ngasem, Kec. Sukolilo, Kota SBY, Jawa Timur 60117
      </p>
    </div>

    <!-- Contact Section -->
    <div class="text-center md:text-left">
      <h1 class="font-bold text-xl md:text-2xl lg:text-3xl">Contact</h1>
      <p class="text-lg">Whatsapp : 082112683644</p>
    </div>
  </div>
</section>

  <script>
    let slideIndex = 0;
  const carouselItems = document.querySelectorAll('.carousel-item');
  const dotsContainer = document.getElementById('dots');

  function prevSlide() {
    slideIndex--;
    if (slideIndex < 0) {
      slideIndex = carouselItems.length - 1;
    }
    updateSlide();
  }

  function nextSlide() {
    slideIndex++;
    if (slideIndex >= carouselItems.length) {
      slideIndex = 0;
    }
    updateSlide();
  }

  function updateSlide() {
    carouselItems.forEach((item, index) => {
      item.classList.remove('active');
      if (index === slideIndex) {
        item.classList.add('active');
      }
    });
    const dots = [];
    carouselItems.forEach((item, index) => {
      const dot = document.createElement('span');
      dot.classList.add('h-2', 'w-2', 'rounded-full', 'mx-1');
      if (index === slideIndex) {
        dot.classList.add('bg-slate-100');
      } else {
        dot.classList.add('bg-gray-400');
      }
      dots.push(dot);
    });
    dotsContainer.innerHTML = '';
    dots.forEach((dot) => {
      dotsContainer.appendChild(dot);
    });
  }

  updateSlide();

  // Otomatisasi slide
  setInterval(() => {
    nextSlide();
  }, 3000);
  </script>
</body>
</html>