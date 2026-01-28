<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';
// Farmer landing page (protected)
$user = require_farmer_access();
$user_name = (string)($user['full_name'] ?? 'User');
$two_step_enabled = user_two_step_enabled($user);
$flash = flash_get();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Agrimo - Experience The Power of Nature</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
    }
  </style>
</head>
<body class="min-h-screen">

<?php if ($flash): ?>
  <div class="fixed top-4 right-4 z-50 max-w-sm p-3 rounded-lg shadow-lg <?= $flash['type']==='error' ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700' ?>">
    <?= h((string)$flash['message']) ?>
  </div>
<?php endif; ?>


  <!-- Header Navigation -->
  <header class="absolute shadow-sm sticky top-0 z-10 top-0 left-0 right-0 z-10 bg-white/80 backdrop-blur-sm">
    <nav class="container mx-auto px-6 py-4 flex items-center justify-between hidden md:flex space-x-8 ">
      <div class="flex items-center space-x-8">
        <img src="assets/planting.png" alt="Agrimo Logo" class="h-10">
        
        <ul class="hidden md:flex items-center space-x-8 text-gray-700 font-medium">

             <li class="relative">
        <a href="#" class="hover:text-orange-500 flex items-center" onclick="toggleDropdown()">
          Services
          <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
          </svg>
        </a>
        <ul id="servicesDropdown" class="absolute hidden bg-teal-200 text-gray-700 mt-2 rounded-lg shadow-lg w-64">
          <li><a href="login.php" class="block px-4 py-2 hover:bg-teal-300">Cropes disease detection</a></li>
          <li><a href="login.php" class="block px-4 py-2 hover:bg-teal-300">Nearby agri-servicing center</a></li>
          <li><a href="login.php" class="block px-4 py-2 hover:bg-teal-300">Online consultancy</a></li>
          <li><a href="weather_based.html" class="block px-4 py-2 hover:bg-teal-300">weather</a></li>
          <li><a href="test.php" class="block px-4 py-2 hover:bg-teal-300">Profile</a></li>
          <li><a href="login.php" class="block px-4 py-2 hover:bg-teal-300">Animal disease detection</a></li>
        </ul>
      </li>
          <li><a href="home.php" class="hover:text-green-600 transition">Home</a></li>
        
          <li><a href="#" class="hover:text-green-600 transition">Pages</a></li>
           
          <li><a href="#" class="hover:text-green-600 transition">Portfolio</a></li>
          <li><a href="blog/index.php" class="hover:text-green-600 transition">Blog</a></li>
          <li><a href="#" class="hover:text-green-600 transition">Contact Us</a></li>
        </ul>
      </div>

      <div class="flex items-center space-x-6">
        <?php /* Auth UI */ ?>
        <div class="hidden md:flex items-center space-x-3">
          <span class="text-gray-700 font-medium text-sm">Hi, <?= e($user_name) ?></span>
          <form method="post" action="toggle_two_step.php" class="flex items-center space-x-2">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="two_step_enabled" value="<?= $two_step_enabled ? '0' : '1' ?>">
            <span class="text-xs font-medium <?= $two_step_enabled ? 'text-green-700' : 'text-gray-500' ?>">Two-step: <?= $two_step_enabled ? 'ON' : 'OFF' ?></span>
            <button class="text-xs px-3 py-1 rounded-full border <?= $two_step_enabled ? 'border-green-600 text-green-700 hover:bg-green-50' : 'border-gray-300 text-gray-700 hover:bg-gray-50' ?>">
              <?= $two_step_enabled ? 'Disable' : 'Enable' ?>
            </button>
          </form>
          <a href="logout.php" class="text-sm font-semibold text-red-600 hover:underline">Logout</a>
        </div>

        <a href="tel:+1232995511" class="hidden md:flex items-center space-x-2 text-gray-700 font-medium">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
          <span>Call Us Now +880 xxxxxxxxxx</span>
        </a>
        <a href="disease/index.php"><button class="bg-yellow-400 hover:bg-yellow-500 text-gray-800 font-semibold px-6 py-3 rounded-full transition shadow-md">
          Disease Detection
        </button></a>
      </div>
    </nav>
    <script>
    function toggleDropdown() {
      const dropdown = document.getElementById("servicesDropdown");
      dropdown.classList.toggle("hidden");
    }
  </script>
  </header>

  <!-- Hero Section -->
  <section class="relative h-screen flex items-center justify-center bg-cover bg-center bg-no-repeat"
    style="background-image: url('assets/Background.png');">
    
    <!-- Dark overlay for better text readability -->
    <div class="absolute inset-0 bg-black/40"></div>
<!-- 
    <div class="relative z-10 text-center text-white px-6 max-w-4xl mx-auto">
     
      <div class="inline-block bg-white/20 backdrop-blur-sm text-white px-6 py-2 rounded-full text-sm font-medium mb-8">
        BENEFICIAL FOR HEALTH
      </div>

      
      <h1 class="text-5xl md:text-6xl lg:text-7xl font-bold leading-tight mb-8">
        Experience<br>
        <span class="text-6xl md:text-7xl lg:text-8xl">The Power of Nature</span>
      </h1>

     
      <a href="#" class="inline-block bg-white text-gray-800 font-semibold text-lg px-10 py-4 rounded-full hover:bg-gray-100 transition shadow-lg">
        Contact Us
      </a>
    </div> -->

    <!-- Optional scroll indicator -->
    <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 text-white animate-bounce">
      <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
      </svg>
    </div>
  </section>








  <!-- Main Section -->
  <section class="relative min-h-screen flex flex-col justify-between py-8 px-6 md:px-12 lg:px-24 bg-yellow-200">
    
    <!-- Top Bar -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
      <div class="flex items-center space-x-4 text-gray-700 text-sm md:text-base">
        <div class="flex items-center space-x-2">
          <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
          </svg>
          <p>Any questions? Reach us at<br><strong>+880 xxxxxxxxxx</strong> - Toll free</p>
        </div>
      </div>

      <div class="mt-8 md:mt-0 text-center text-gray-700 text-sm md:text-base">
        <p>Agriculture Matters to<br>the Future of Development</p>
      </div>

      <div class="mt-4 md:mt-0 relative">
        <img src="https://thumbs.dreamstime.com/b/modern-red-tractor-glowing-high-tech-sensors-working-green-field-sunset-representing-smart-farming-modern-red-359319946.jpg" alt="Tractor at sunset" class="w-32 h-20 md:w-48 md:h-28 object-cover rounded-lg shadow-lg">
        <div class="absolute inset-0 flex items-center justify-center">
          <svg class="w-12 h-12 text-white bg-black/50 rounded-full p-3" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
          </svg>
        </div>
      </div>
    </div>

    <!-- Scroll Down Indicator -->
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
      <div class="bg-white rounded-full p-4 shadow-lg animate-bounce">
        <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
        </svg>
      </div>
    </div>

    <!-- Main Content Card -->
    <div class="bg-white rounded-3xl shadow-2xl p-8 md:p-12 lg:p-16 max-w-6xl mx-auto">
      <div class="grid md:grid-cols-2 gap-12 items-center">
        
        <!-- Left Image -->
        <div class="rounded-2xl overflow-hidden shadow-xl">
          <img src="assets/ashraful-haque-akash-MuiC_cZL80Q-unsplash.jpg" alt="Elderly farmer with oranges" class="w-full h-full object-cover">
        </div>

        <!-- Right Content -->
        <div class="flex flex-col justify-center">
          <!-- Small Image -->
          <div class="rounded-2xl overflow-hidden shadow-xl mb-8">
            <img src="assets/syed-rifat-hossain-uhgQ5W1JOtQ-unsplash.jpg" alt="Woman farmer with vegetables" class="w-full h-64 object-cover">
          </div>

          <!-- Text -->
          <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-6">We're Committed to Caring.</h2>
          <p class="text-gray-600 mb-8 leading-relaxed">
            Greetings from Dosner Organic Farms We distribute only organic herbs and produce directly to consumers.
          </p>

          <!-- List -->
          <ul class="space-y-4 mb-10">
            <li class="flex items-center space-x-4">
              <img src="https://goodeggs4.imgix.net/50c39b1a-12f3-45ef-9756-094ae2797865.jpg?w=840&h=525&fm=jpg&q=80&fit=crop" alt="Gourmet Mushrooms" class="w-12 h-12 rounded-full object-cover">
              <span class="text-gray-700 font-medium">Gourmet Mushrooms</span>
              <img src="https://www.treehugger.com/thmb/ckcaex5K5t3QjDm1s86r7a5NkC0=/1500x0/filters:no_upscale():max_bytes(150000):strip_icc()/certificationscopy_edit-0d2a065aa4b84ff9840697bc56490c3e.jpg" alt="Best Quality Standards" class="w-12 h-12 rounded-full object-cover ml-auto">
              <span class="text-gray-700 font-medium">Best Quality Standards</span>
            </li>
            <li class="flex items-center space-x-4">
              <img src="https://contenthandler.azureedge.net/cont/136/1/1600/0/farm-banner-box-lx.jpg" alt="Natural Healthy Products" class="w-12 h-12 rounded-full object-cover">
              <span class="text-gray-700 font-medium">Natural Healthy Products</span>
              <img src="https://media.gettyimages.com/id/493220526/vector/fertilizer-icon-flat-graphic-design.jpg?s=612x612&w=gi&k=20&c=KJSYYBp4XOu_GkLg0Kl4ry8WmWh5aHiszEMHLkVwYM0=" alt="Fertilizer Distribution" class="w-12 h-12 rounded-full object-cover ml-auto">
              <span class="text-gray-700 font-medium">Fertilizer Distribution</span>
            </li>
            <li class="flex items-center space-x-4">
              <img src="https://cdn.mos.cms.futurecdn.net/kX5oG9LCGufwT6GdNLusTj.jpg" alt="Lavender Farming" class="w-12 h-12 rounded-full object-cover">
              <span class="text-gray-700 font-medium">Lavender Farming</span>
              <img src="https://thumbs.dreamstime.com/b/organic-fertilizer-icon-ground-sprout-emblem-plant-farming-agriculture-useful-component-naturally-occurring-animal-wastes-215228721.jpg" alt="Organic Fertilizer" class="w-12 h-12 rounded-full object-cover ml-auto">
              <span class="text-gray-700 font-medium">Organic Fertilizer</span>
            </li>
          </ul>

          <!-- Button -->
          <a href="#" class="inline-flex items-center bg-green-600 hover:bg-green-700 text-white font-semibold px-8 py-4 rounded-full transition shadow-lg">
            Know More
            <svg class="w-5 h-5 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
            </svg>
          </a>
        </div>
      </div>
    </div>
  </section>



  <style>
    body { font-family: 'Poppins', sans-serif; }

    /* Seamless infinite scrolling carousel */
    .carousel-track {
      display: flex;
      animation: scroll 16s linear infinite; /* 4 cards × 4s each = 16s loop */
    }

    @keyframes scroll {
      0% { transform: translateX(0); }
      100% { transform: translateX(-50%); } /* Moves exactly half (original + duplicate set) */
    }

    /* Pause on hover */
    .carousel-container:hover .carousel-track {
      animation-play-state: paused;
    }

    /* Each card width */
    .service-card {
      flex: 0 0 100%; /* Full width on mobile */
    }

    @media (min-width: 768px) {
      .service-card {
        flex: 0 0 50%; /* 2 cards visible on md */
      }
    }

    @media (min-width: 1024px) {
      .service-card {
        flex: 0 0 33.333%; /* ~3 cards visible on lg, but smooth scroll */
      }
    }

    @media (min-width: 1280px) {
      .service-card {
        flex: 0 0 25%; /* 4 cards visible on large screens */
      }
    }
  </style>

  <section class="py-16 px-6 md:px-12 lg:px-24 bg-yellow-50">
    <div class="max-w-7xl mx-auto text-center">
      <!-- Title -->
      <p class="text-sm font-medium text-gray-600 mb-2">• Our Services</p>
      <h2 class="text-4xl md:text-5xl font-bold text-gray-800 mb-16">
        Best Agriculture Services
      </h2>

      <!-- Infinite Auto-Scrolling Carousel -->
      <div class="overflow-hidden">
        <div class="carousel-container">
          <div class="carousel-track">
            <!-- Card 1 -->
            <div class="service-card px-4">
              <div class="bg-white rounded-3xl shadow-xl p-8 text-center relative">
                <div class="absolute -top-4 left-1/2 transform -translate-x-1/2 bg-yellow-400 text-gray-800 rounded-full p-3 shadow-md">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                  </svg>
                </div>
                <div class="w-64 h-64 mx-auto mb-6 rounded-full overflow-hidden border-8 border-green-500">
                  <img src="https://www.tractortransport.com/blog/wp-content/uploads/2020/05/farmer-880567_1280.jpg" alt="Harvest Concepts" class="w-full h-full object-cover">
                </div>
                <div class="w-12 h-12 mx-auto mb-4 bg-green-100 rounded-full flex items-center justify-center">
                  <div class="w-6 h-6 bg-green-500 rounded-full"></div>
                </div>
                <h3 class="text-xl font-semibold text-gray-800">Harvest Concepts</h3>
              </div>
            </div>

            <!-- Card 2 -->
            <div class="service-card px-4">
              <div class="bg-white rounded-3xl shadow-xl p-8 text-center relative">
                <div class="absolute -top-4 left-1/2 transform -translate-x-1/2 bg-yellow-400 text-gray-800 rounded-full p-3 shadow-md">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                  </svg>
                </div>
                <div class="w-64 h-64 mx-auto mb-6 rounded-full overflow-hidden border-8 border-green-500">
                  <img src="https://www.courier-journal.com/gcdn/presto/2020/06/10/PLOU/d36766d1-4e16-435f-9065-82880b8d0a2c-gardening.jpg?width=660&height=441&fit=crop&format=pjpg&auto=webp" alt="Farming Products" class="w-full h-full object-cover">
                </div>
                <div class="w-12 h-12 mx-auto mb-4 bg-green-100 rounded-full flex items-center justify-center">
                  <div class="w-6 h-6 bg-green-500 rounded-full"></div>
                </div>
                <h3 class="text-xl font-semibold text-gray-800">Farming Products</h3>
              </div>
            </div>

            <!-- Card 3 -->
            <div class="service-card px-4">
              <div class="bg-white rounded-3xl shadow-xl p-8 text-center relative">
                <div class="absolute -top-4 left-1/2 transform -translate-x-1/2 bg-yellow-400 text-gray-800 rounded-full p-3 shadow-md">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                  </svg>
                </div>
                <div class="w-64 h-64 mx-auto mb-6 rounded-full overflow-hidden border-8 border-green-500">
                  <img src="https://www.shutterstock.com/shutterstock/videos/1087597031/thumb/6.jpg?ip=x480" alt="Soil Fertilization" class="w-full h-full object-cover">
                </div>
                <div class="w-12 h-12 mx-auto mb-4 bg-green-100 rounded-full flex items-center justify-center">
                  <div class="w-6 h-6 bg-green-500 rounded-full"></div>
                </div>
                <h3 class="text-xl font-semibold text-gray-800">Soil Fertilization</h3>
              </div>
            </div>

            <!-- Card 4 -->
            <div class="service-card px-4">
              <div class="bg-white rounded-3xl shadow-xl p-8 text-center relative">
                <div class="absolute -top-4 left-1/2 transform -translate-x-1/2 bg-yellow-400 text-gray-800 rounded-full p-3 shadow-md">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                  </svg>
                </div>
                <div class="w-64 h-64 mx-auto mb-6 rounded-full overflow-hidden border-8 border-green-500">
                  <img src="https://media.istockphoto.com/id/2201573650/photo/woman-harvesting-vegetables-in-lush-garden.jpg?s=612x612&w=0&k=20&c=0iILUGnKS3a9qRWkyiTm0Fv9_9j4ArjQ0FiJJKOngtI=" alt="Fresh Vegetables" class="w-full h-full object-cover">
                </div>
                <div class="w-12 h-12 mx-auto mb-4 bg-green-100 rounded-full flex items-center justify-center">
                  <div class="w-6 h-6 bg-green-500 rounded-full"></div>
                </div>
                <h3 class="text-xl font-semibold text-gray-800">Fresh Vegetables</h3>
              </div>
            </div>

            <!-- Duplicated cards for seamless infinite loop -->
            <div class="service-card px-4">
              <div class="bg-white rounded-3xl shadow-xl p-8 text-center relative">
                <div class="absolute -top-4 left-1/2 transform -translate-x-1/2 bg-yellow-400 text-gray-800 rounded-full p-3 shadow-md">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                  </svg>
                </div>
                <div class="w-64 h-64 mx-auto mb-6 rounded-full overflow-hidden border-8 border-green-500">
                  <img src="https://www.tractortransport.com/blog/wp-content/uploads/2020/05/farmer-880567_1280.jpg" alt="Harvest Concepts" class="w-full h-full object-cover">
                </div>
                <div class="w-12 h-12 mx-auto mb-4 bg-green-100 rounded-full flex items-center justify-center">
                  <div class="w-6 h-6 bg-green-500 rounded-full"></div>
                </div>
                <h3 class="text-xl font-semibold text-gray-800">Harvest Concepts</h3>
              </div>
            </div>

            <div class="service-card px-4">
              <div class="bg-white rounded-3xl shadow-xl p-8 text-center relative">
                <div class="absolute -top-4 left-1/2 transform -translate-x-1/2 bg-yellow-400 text-gray-800 rounded-full p-3 shadow-md">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                  </svg>
                </div>
                <div class="w-64 h-64 mx-auto mb-6 rounded-full overflow-hidden border-8 border-green-500">
                  <img src="https://www.courier-journal.com/gcdn/presto/2020/06/10/PLOU/d36766d1-4e16-435f-9065-82880b8d0a2c-gardening.jpg?width=660&height=441&fit=crop&format=pjpg&auto=webp" alt="Farming Products" class="w-full h-full object-cover">
                </div>
                <div class="w-12 h-12 mx-auto mb-4 bg-green-100 rounded-full flex items-center justify-center">
                  <div class="w-6 h-6 bg-green-500 rounded-full"></div>
                </div>
                <h3 class="text-xl font-semibold text-gray-800">Farming Products</h3>
              </div>
            </div>

            <div class="service-card px-4">
              <div class="bg-white rounded-3xl shadow-xl p-8 text-center relative">
                <div class="absolute -top-4 left-1/2 transform -translate-x-1/2 bg-yellow-400 text-gray-800 rounded-full p-3 shadow-md">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                  </svg>
                </div>
                <div class="w-64 h-64 mx-auto mb-6 rounded-full overflow-hidden border-8 border-green-500">
                  <img src="https://www.shutterstock.com/shutterstock/videos/1087597031/thumb/6.jpg?ip=x480" alt="Soil Fertilization" class="w-full h-full object-cover">
                </div>
                <div class="w-12 h-12 mx-auto mb-4 bg-green-100 rounded-full flex items-center justify-center">
                  <div class="w-6 h-6 bg-green-500 rounded-full"></div>
                </div>
                <h3 class="text-xl font-semibold text-gray-800">Soil Fertilization</h3>
              </div>
            </div>

            <div class="service-card px-4">
              <div class="bg-white rounded-3xl shadow-xl p-8 text-center relative">
                <div class="absolute -top-4 left-1/2 transform -translate-x-1/2 bg-yellow-400 text-gray-800 rounded-full p-3 shadow-md">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                  </svg>
                </div>
                <div class="w-64 h-64 mx-auto mb-6 rounded-full overflow-hidden border-8 border-green-500">
                  <img src="https://media.istockphoto.com/id/2201573650/photo/woman-harvesting-vegetables-in-lush-garden.jpg?s=612x612&w=0&k=20&c=0iILUGnKS3a9qRWkyiTm0Fv9_9j4ArjQ0FiJJKOngtI=" alt="Fresh Vegetables" class="w-full h-full object-cover">
                </div>
                <div class="w-12 h-12 mx-auto mb-4 bg-green-100 rounded-full flex items-center justify-center">
                  <div class="w-6 h-6 bg-green-500 rounded-full"></div>
                </div>
                <h3 class="text-xl font-semibold text-gray-800">Fresh Vegetables</h3>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Dots Indicator -->
      <div class="flex justify-center mt-12 space-x-3">
        <span class="w-3 h-3 bg-yellow-400 rounded-full"></span>
        <span class="w-3 h-3 bg-gray-300 rounded-full"></span>
        <span class="w-3 h-3 bg-gray-300 rounded-full"></span>
        <span class="w-3 h-3 bg-gray-300 rounded-full"></span>
      </div>
    </div>
  </section>



  <section class="py-16 px-6 md:px-12 lg:px-24 bg-yellow-50">
    <div class="max-w-7xl mx-auto text-center">
      <!-- Main Title -->
      <h2 class="text-4xl md:text-5xl font-bold text-gray-800 mb-16">
        Anyone Can Make Eco-Friendly<br>Products From Scratch
      </h2>

      <!-- Cards Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">

        <!-- Card 1 -->
        <div class="bg-white rounded-3xl shadow-xl p-8 hover:shadow-2xl transition-shadow">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-semibold text-gray-800">Agriculture Products</h3>
            <div class="w-16 h-16 bg-yellow-400 rounded-full flex items-center justify-center shadow-md">
              <svg class="w-8 h-8 text-gray-800" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2L2 12h3v8h14v-8h3L12 2zm0 4.5l6 5.5H6l6-5.5z"/>
              </svg>
            </div>
          </div>
          <p class="text-gray-600 leading-relaxed">
            There are many variations of passages of lorem ipsum available but the majority have suffered alteration.
          </p>
        </div>

        <!-- Card 2 -->
        <div class="bg-white rounded-3xl shadow-xl p-8 hover:shadow-2xl transition-shadow">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-semibold text-gray-800">Professional Farmers</h3>
            <div class="w-16 h-16 bg-yellow-400 rounded-full flex items-center justify-center shadow-md">
              <svg class="w-8 h-8 text-gray-800" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
              </svg>
            </div>
          </div>
          <p class="text-gray-600 leading-relaxed">
            There are many variations of passages of lorem ipsum available but the majority have suffered alteration.
          </p>
        </div>

        <!-- Card 3 -->
        <div class="bg-white rounded-3xl shadow-xl p-8 hover:shadow-2xl transition-shadow">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-semibold text-gray-800">Fresh Vegetables</h3>
            <div class="w-16 h-16 bg-yellow-400 rounded-full flex items-center justify-center shadow-md">
              <svg class="w-8 h-8 text-gray-800" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2L2 12h3v8h14v-8h3L12 2zm0 4.5l6 5.5H6l6-5.5z"/>
              </svg>
            </div>
          </div>
          <p class="text-gray-600 leading-relaxed">
            There are many variations of passages of lorem ipsum available but the majority have suffered alteration.
          </p>
        </div>

        <!-- Card 4 -->
        <div class="bg-white rounded-3xl shadow-xl p-8 hover:shadow-2xl transition-shadow">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-semibold text-gray-800">Dairy Products</h3>
            <div class="w-16 h-16 bg-yellow-400 rounded-full flex items-center justify-center shadow-md">
              <svg class="w-8 h-8 text-gray-800" fill="currentColor" viewBox="0 0 24 24">
                <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/>
              </svg>
            </div>
          </div>
          <p class="text-gray-600 leading-relaxed">
            There are many variations of passages of lorem ipsum available but the majority have suffered alteration.
          </p>
        </div>

        <!-- Card 5 -->
        <div class="bg-white rounded-3xl shadow-xl p-8 hover:shadow-2xl transition-shadow">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-semibold text-gray-800">Quality Products</h3>
            <div class="w-16 h-16 bg-yellow-400 rounded-full flex items-center justify-center shadow-md">
              <svg class="w-8 h-8 text-gray-800" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
              </svg>
            </div>
          </div>
          <p class="text-gray-600 leading-relaxed">
            There are many variations of passages of lorem ipsum available but the majority have suffered alteration.
          </p>
        </div>

        <!-- Card 6 -->
        <div class="bg-white rounded-3xl shadow-xl p-8 hover:shadow-2xl transition-shadow">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-semibold text-gray-800">Modern Equipment</h3>
            <div class="w-16 h-16 bg-yellow-400 rounded-full flex items-center justify-center shadow-md">
              <svg class="w-8 h-8 text-gray-800" fill="currentColor" viewBox="0 0 24 24">
                <path d="M19 7h-3V5c0-1.1-.9-2-2-2h-4c-1.1 0-2 .9-2 2v2H5c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V9c0-1.1-.9-2-2-2zm-9-2h4v2h-4V5zm9 14H5V9h14v10z"/>
              </svg>
            </div>
          </div>
          <p class="text-gray-600 leading-relaxed">
            There are many variations of passages of lorem ipsum available but the majority have suffered alteration.
          </p>
        </div>

      </div>
    </div>
  </section>




  

  

  <!-- Features / Quick Access -->
  <section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
        <!-- Plant Detection Card -->
        <div id="plants" class="bg-green-50 rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition">
          <div class="bg-gradient-to-r from-green-500 to-green-600 h-48 flex items-center justify-center">
            <img src="assets/eric-prouzet-Re5rjFVkhfU-unsplash.jpg" alt="Healthy crops" class="w-full h-full object-cover opacity-90">
          </div>
          <div class="p-8">
            <h3 class="text-3xl font-bold text-green-800 mb-4">Plant & Crop Detection</h3>
            <p class="text-gray-700 mb-6">
              Upload a photo of leaves, stems, or fruits. Our AI identifies common diseases like blight, rust, powdery mildew, and more.
            </p>
            <ul class="space-y-3 text-gray-600 mb-8">
              <li class="flex items-center"><span class="text-green-600 mr-3">✓</span> Supports 50+ crops (rice, tomato, potato, maize, etc.)</li>
              <li class="flex items-center"><span class="text-green-600 mr-3">✓</span> Instant results with treatment recommendations</li>
              <li class="flex items-center"><span class="text-green-600 mr-3">✓</span> Works offline (coming soon)</li>
            </ul>
            <a href="#" class="block text-center bg-green-600 text-white py-3 rounded-lg font-semibold hover:bg-green-700 transition">
              Start Plant Scan →
            </a>
          </div>
        </div>

        <!-- Animal Detection Card -->
        <div id="animals" class="bg-amber-50 rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition">
          <div class="bg-gradient-to-r from-amber-500 to-orange-600 h-48 flex items-center justify-center">
            <img src="assets/veterinarian_device_1.jpg" alt="Livestock" class="w-full h-full object-cover opacity-90">
          </div>
          <div class="p-8">
            <h3 class="text-3xl font-bold text-amber-800 mb-4">Livestock & Animal Detection</h3>
            <p class="text-gray-700 mb-6">
              Photograph affected areas (skin, eyes, mouth, hooves). Detect early signs of diseases in cows, poultry, goats, and pigs.
            </p>
            <ul class="space-y-3 text-gray-600 mb-8">
              <li class="flex items-center"><span class="text-amber-600 mr-3">✓</span> Foot-and-mouth, mastitis, avian flu, and more</li>
              <li class="flex items-center"><span class="text-amber-600 mr-3">✓</span> Severity level and urgency alerts</li>
              <li class="flex items-center"><span class="text-amber-600 mr-3">✓</span> Vet-approved prevention tips</li>
            </ul>
            <a href="#" class="block text-center bg-amber-600 text-white py-3 rounded-lg font-semibold hover:bg-amber-700 transition">
              Start Animal Scan →
            </a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- How It Works -->
  <section class="py-16 bg-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
      <h2 class="text-4xl font-bold text-gray-800 mb-12">How It Works</h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
        <div class="bg-white p-8 rounded-xl shadow-md">
          <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6 text-2xl font-bold text-green-700">1</div>
          <h4 class="text-xl font-semibold mb-4">Take a Clear Photo</h4>
          <p class="text-gray-600">Capture the affected leaf or animal part in good lighting.</p>
        </div>
        <div class="bg-white p-8 rounded-xl shadow-md">
          <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6 text-2xl font-bold text-green-700">2</div>
          <h4 class="text-xl font-semibold mb-4">Upload & Analyze</h4>
          <p class="text-gray-600">Our AI model processes the image in seconds.</p>
        </div>
        <div class="bg-white p-8 rounded-xl shadow-md">
          <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6 text-2xl font-bold text-green-700">3</div>
          <h4 class="text-xl font-semibold mb-4">Get Results & Advice</h4>
          <p class="text-gray-600">Receive diagnosis, confidence score, and recommended actions.</p>
        </div>
      </div>
    </div>
  </section>


  <!-- Find Nearest Help Section -->
  <section id="locate" class="py-16 bg-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
      <h2 class="text-4xl font-bold text-gray-800 mb-8">Find Help Near You</h2>
      <p class="text-xl text-gray-600 mb-12 max-w-3xl mx-auto">
        Get instant directions to the nearest veterinary hospital or agriculture extension center using your location.
      </p>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-10 max-w-4xl mx-auto">
        <!-- Nearest Vet Hospital -->
        <div class="bg-white rounded-2xl shadow-lg p-10 hover:shadow-2xl transition transform hover:-translate-y-2">
          <div class="w-32 h-28 rounded-full flex items-center justify-center mx-auto mb-6">
            
            <img class="fas fa-clinic-medical rounded-full text-4xl" src="assets/veterinarian_device_1.jpg" alt="" srcset="">
          </div>
          <h3 class="text-2xl font-bold text-gray-800 mb-4">Nearest Veterinary Hospital</h3>
          <p class="text-gray-600 mb-8">Quickly locate 24/7 animal clinics and emergency vet services around you.</p>
          <button onclick="getLocation('vet')" class="w-full bg-red-600 text-white py-4 rounded-lg font-semibold text-lg hover:bg-red-700 transition flex items-center justify-center">
            <i class="fas fa-location-arrow mr-3"></i> Find Nearest Vet Hospital
          </button>
        </div>

        <!-- Nearest Agriculture Help Center -->
        <div class="bg-white rounded-2xl shadow-lg p-10 hover:shadow-2xl transition transform hover:-translate-y-2">
          <div class="w-24 h-24   flex items-center justify-center mx-auto mb-6">
            <img class="rounded-2xl rounded-full" src="assets/henry-perks-BJXAxQ1L7dI-unsplash.jpg" alt="" srcset="">
          </div>
          <h3 class="text-2xl font-bold text-gray-800 mb-4">Nearest Agriculture Help Center</h3>
          <p class="text-gray-600 mb-8">Find government extension offices, krishi vigyan kendras, and farm advisory services.</p>
          <button onclick="getLocation('agri')" class="w-full bg-green-600 text-white py-4 rounded-lg font-semibold text-lg hover:bg-green-700 transition flex items-center justify-center">
            <i class="fas fa-location-arrow mr-3"></i> Find Nearest Agri Help Center
          </button>
        </div>
      </div>

      <p class="mt-12 text-sm text-gray-500">
        <i class="fas fa-info-circle mr-2"></i>
        This uses your device's location. Allow location access when prompted.
      </p>
    </div>
  </section>

  <!-- Simple Location Script (Demo) -->
  <script>
    function getLocation(type) {
      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
          (position) => {
            const lat = position.coords.latitude;
            const lon = position.coords.longitude;
            let url;
            if (type === 'vet') {
              url = `https://www.google.com/maps/search/?api=1&query=veterinary+hospital+near+${lat},${lon}`;
            } else {
              url = `https://www.google.com/maps/search/?api=1&query=agriculture+help+center+OR+krishi+kendra+near+${lat},${lon}`;
            }
            window.open(url, '_blank');
          },
          () => {
            alert("Location access denied. Please enable location to use this feature.");
          }
        );
      } else {
        alert("Geolocation is not supported by your browser.");
      }
    }
  </script>

  <!-- Footer -->
  <footer class="bg-gray-900 text-white py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
        <div>
          <h3 class="text-2xl font-bold text-green-400">AgriDetect</h3>
          <p class="mt-4 text-gray-400">Empowering farmers with AI for sustainable agriculture.</p>
        </div>
        <div>
          <h4 class="font-semibold mb-4">Quick Links</h4>
          <ul class="space-y-2 text-gray-400">
            <li><a href="#" class="hover:text-white">Plant Detection</a></li>
            <li><a href="#" class="hover:text-white">Animal Detection</a></li>
            <li><a href="#" class="hover:text-white">About Us</a></li>
          </ul>
        </div>
        <div>
          <h4 class="font-semibold mb-4">Resources</h4>
          <ul class="space-y-2 text-gray-400">
            <li><a href="blog/index.php" class="hover:text-white">Blog</a></li>
            <li><a href="#" class="hover:text-white">Guides</a></li>
            <li><a href="#" class="hover:text-white">Privacy Policy</a></li>
          </ul>
        </div>
        <div>
          <h4 class="font-semibold mb-4">Contact</h4>
          <p class="text-gray-400">support@agridetect.com</p>
          <p class="text-gray-400 mt-2">Available in English, Hindi, Spanish & more</p>
        </div>
      </div>
      <div class="mt-10 pt-8 border-t border-gray-800 text-center text-gray-400">
        © 2026 AgriDetect. All rights reserved.
      </div>
    </div>
  </footer>


</body>
</html>