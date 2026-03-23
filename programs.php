<?php
// Optional JSON mode for compatibility: programs.php?format=json
if (isset($_GET['format']) && $_GET['format'] === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $files = [];
    $programsDir = __DIR__ . '/programs';
    if (is_dir($programsDir)) {
        $items = scandir($programsDir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $programsDir . DIRECTORY_SEPARATOR . $item;
            if (!is_file($path)) continue;
            $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
            if (in_array($ext, $imageExts, true)) $files[] = 'programs/' . rawurlencode($item);
        }
    }
    echo json_encode(array_values(array_unique($files)));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reality Dream Institute | Courses</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .course-thumb {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 0.75rem;
            margin-bottom: 0.9rem;
            border: 1px solid #e2e8f0;
        }
    </style>
</head>
<body style="font-family:'Poppins',sans-serif;background:#f8fafc;color:#111827;">
    <nav class="sticky top-0 z-50 bg-transparent" style="background-color: #121826;">
        <div class="container mx-auto px-4 py-2">
            <div class="flex flex-wrap justify-between items-center">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center">
                        <img src="./logo.jpg" alt="Reality Dream Institute Logo" class="w-6 h-6 rounded-full object-cover">
                    </div>
                    <div>
                        <h1 class="text-md md:text-lg font-bold" style="background: linear-gradient(90deg, #377D3E, #6B3E93, #E38822); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; font-weight: 700;">Reality Dream Institute</h1>
                        <p class="text-xs text-gray-300 mobile-hidden">Business, Tech & Innovation Hub</p>
                    </div>
                </div>

                <div class="hidden lg:flex space-x-5 xl:space-x-6 items-center">
                    <a href="index.html#home" class="text-gray-300 hover:text-green-300 font-medium transition text-sm xl:text-sm">Home</a>
                    <a href="about.html" class="text-gray-300 hover:text-green-300 font-medium transition text-sm xl:text-sm">About</a>
                    <div class="relative group">
                        <a href="programs.php" class="text-green-300 font-medium transition text-sm xl:text-sm inline-flex items-center">Courses <i class="fas fa-chevron-down ml-1.5 text-[10px]"></i></a>
                        <div class="absolute left-0 top-full mt-2 w-56 bg-slate-900 border border-slate-700 rounded-lg shadow-xl p-2 hidden group-hover:block group-focus-within:block z-50">
                            <a href="programs.php#cctv-installation" class="block px-3 py-2 rounded text-xs text-gray-200 hover:text-green-300 hover:bg-slate-800">CCTV Installation</a>
                            <a href="programs.php#solar-installation" class="block px-3 py-2 rounded text-xs text-gray-200 hover:text-green-300 hover:bg-slate-800">Solar Installation</a>
                            <a href="programs.php#entrepreneurship" class="block px-3 py-2 rounded text-xs text-gray-200 hover:text-green-300 hover:bg-slate-800">Entrepreneurship</a>
                            <a href="programs.php#front-desk-cashier" class="block px-3 py-2 rounded text-xs text-gray-200 hover:text-green-300 hover:bg-slate-800">Front Desk & Cashier</a>
                            <a href="programs.php#computer-packages" class="block px-3 py-2 rounded text-xs text-gray-200 hover:text-green-300 hover:bg-slate-800">Computer Packages</a>
                            <a href="programs.php#content-videography" class="block px-3 py-2 rounded text-xs text-gray-200 hover:text-green-300 hover:bg-slate-800">Content & Videography</a>
                        </div>
                    </div>
                    <a href="gallery.html" class="text-gray-300 hover:text-green-300 font-medium transition text-sm xl:text-sm">Gallery</a>
                    <div class="relative group">
                        <a href="shop.html" class="text-gray-300 hover:text-green-300 font-medium transition text-sm xl:text-sm inline-flex items-center">Shop <i class="fas fa-chevron-down ml-1.5 text-[10px]"></i></a>
                        <div class="absolute left-0 top-full mt-2 w-56 bg-slate-900 border border-slate-700 rounded-lg shadow-xl p-2 hidden group-hover:block group-focus-within:block z-50">
                            <a href="shop.html#cctv-cameras" class="block px-3 py-2 rounded text-xs text-gray-200 hover:text-green-300 hover:bg-slate-800">CCTV Cameras</a>
                            <a href="shop.html#key-cutting" class="block px-3 py-2 rounded text-xs text-gray-200 hover:text-green-300 hover:bg-slate-800">Key Cuttings</a>
                            <a href="shop.html#wifi-installation" class="block px-3 py-2 rounded text-xs text-gray-200 hover:text-green-300 hover:bg-slate-800">WiFi Installation</a>
                            <a href="shop.html#solar-panels" class="block px-3 py-2 rounded text-xs text-gray-200 hover:text-green-300 hover:bg-slate-800">Solar Panels</a>
                            <a href="shop.html#electric-fence" class="block px-3 py-2 rounded text-xs text-gray-200 hover:text-green-300 hover:bg-slate-800">Electric Fence</a>
                            <a href="shop.html#laser-sensors" class="block px-3 py-2 rounded text-xs text-gray-200 hover:text-green-300 hover:bg-slate-800">Laser Sensors</a>
                        </div>
                    </div>
                    <a href="blog.html" class="text-gray-300 hover:text-green-300 font-medium py-1.5 transition text-center text-sm">Blog</a>
                    <a href="programs.php" class="text-gray-300 hover:text-green-300 font-medium transition text-sm xl:text-sm">Fees</a>
                    <a href="contact.php" class="text-gray-300 hover:text-green-300 font-medium transition text-sm xl:text-sm">Contact</a>
                    <a href="enroll_redirect.php" class="px-3 py-1.5 rounded-md text-white text-sm font-semibold" style="background:#6B3E93;">Enroll</a>
                </div>

                <div class="lg:hidden flex items-center">
                    <button id="mobile-menu-button" class="text-gray-300 focus:outline-none">
                        <i class="fas fa-bars text-lg"></i>
                    </button>
                </div>
            </div>

            <div id="mobile-menu" class="lg:hidden hidden mt-3 pb-2">
                <div class="flex flex-col space-y-3">
                    <a href="index.html#home" class="text-gray-300 hover:text-green-300 font-medium py-1.5 transition text-center text-sm">Home</a>
                    <a href="about.html" class="text-gray-300 hover:text-green-300 font-medium py-1.5 transition text-center text-sm">About</a>
                    <a href="programs.php" class="text-green-300 font-medium py-1.5 transition text-center text-sm">Courses</a>
                    <a href="programs.php#cctv-installation" class="text-gray-400 hover:text-green-300 font-medium py-1 transition text-center text-xs">- CCTV Installation</a>
                    <a href="programs.php#solar-installation" class="text-gray-400 hover:text-green-300 font-medium py-1 transition text-center text-xs">- Solar Installation</a>
                    <a href="programs.php#entrepreneurship" class="text-gray-400 hover:text-green-300 font-medium py-1 transition text-center text-xs">- Entrepreneurship</a>
                    <a href="programs.php#front-desk-cashier" class="text-gray-400 hover:text-green-300 font-medium py-1 transition text-center text-xs">- Front Desk & Cashier</a>
                    <a href="programs.php#computer-packages" class="text-gray-400 hover:text-green-300 font-medium py-1 transition text-center text-xs">- Computer Packages</a>
                    <a href="programs.php#content-videography" class="text-gray-400 hover:text-green-300 font-medium py-1 transition text-center text-xs">- Content & Videography</a>
                    <a href="gallery.html" class="text-gray-300 hover:text-green-300 font-medium py-1.5 transition text-center text-sm">Gallery</a>
                    <a href="shop.html" class="text-gray-300 hover:text-green-300 font-medium py-1.5 transition text-center text-sm">Shop</a>
                    <a href="shop.html#cctv-cameras" class="text-gray-400 hover:text-green-300 font-medium py-1 transition text-center text-xs">- CCTV Cameras</a>
                    <a href="shop.html#key-cutting" class="text-gray-400 hover:text-green-300 font-medium py-1 transition text-center text-xs">- Key Cuttings</a>
                    <a href="shop.html#wifi-installation" class="text-gray-400 hover:text-green-300 font-medium py-1 transition text-center text-xs">- WiFi Installation</a>
                    <a href="shop.html#solar-panels" class="text-gray-400 hover:text-green-300 font-medium py-1 transition text-center text-xs">- Solar Panels</a>
                    <a href="shop.html#electric-fence" class="text-gray-400 hover:text-green-300 font-medium py-1 transition text-center text-xs">- Electric Fence</a>
                    <a href="shop.html#laser-sensors" class="text-gray-400 hover:text-green-300 font-medium py-1 transition text-center text-xs">- Laser Sensors</a>
                    <a href="blog.html" class="text-gray-300 hover:text-green-300 font-medium py-1.5 transition text-center text-sm">Blog</a>
                    <a href="programs.php" class="text-gray-300 hover:text-green-300 font-medium py-1.5 transition text-center text-sm">Fees</a>
                    <a href="contact.php" class="text-gray-300 hover:text-green-300 font-medium py-1.5 transition text-center text-sm">Contact</a>
                    <a href="enroll_redirect.php" class="text-white font-semibold py-2 rounded-md flex items-center justify-center space-x-2 text-sm" style="background-color: #6B3E93;">
                        <i class="fas fa-user-graduate"></i>
                        <span>Enroll Now</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <header class="py-14 md:py-16" style="background:linear-gradient(135deg,#0f172a,#1e293b);">
        <div class="container mx-auto px-4 md:px-6 text-white">
            <p class="uppercase tracking-widest text-xs text-green-300 font-semibold mb-3">Training Programs</p>
            <h2 class="text-3xl md:text-5xl font-extrabold mb-3">Our Courses</h2>
            <p class="text-slate-300 max-w-2xl">Choose from practical programs built for employment, entrepreneurship, and real-world technical confidence.</p>
        </div>
    </header>

    <main class="py-10 md:py-14">
        <div class="container mx-auto px-4 md:px-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <article id="cctv-installation" class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                    <img src="Assets/images/SETUP.jpg" alt="CCTV gadgets and installation tools" class="course-thumb">
                    <h3 class="text-xl font-bold mb-2">CCTV Installation</h3>
                    <p class="text-slate-600 text-sm mb-4">Install, configure, and maintain surveillance systems.</p>
                    <div class="text-sm mb-4"><span class="font-semibold">Duration:</span> 7 months<br><span class="font-semibold">Fee:</span> Ksh 35,000</div>
                    <a href="enroll_redirect.php" class="inline-block px-4 py-2 rounded-md text-white font-semibold text-sm" style="background:#6B3E93;">Enroll Now</a>
                </article>
                <article id="solar-installation" class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                    <img src="Assets/images/SOLARR.jpg" alt="Solar installation training equipment" class="course-thumb">
                    <h3 class="text-xl font-bold mb-2">Solar Installation</h3>
                    <p class="text-slate-600 text-sm mb-4">Hands-on solar setup, wiring, and maintenance.</p>
                    <div class="text-sm mb-4"><span class="font-semibold">Duration:</span> 7 months<br><span class="font-semibold">Fee:</span> Ksh 35,000</div>
                    <a href="enroll_redirect.php" class="inline-block px-4 py-2 rounded-md text-white font-semibold text-sm" style="background:#6B3E93;">Enroll Now</a>
                </article>
                <article id="entrepreneurship" class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                    <img src="Assets/images/Entre 2.jpg" alt="Entrepreneurship practical training session" class="course-thumb">
                    <h3 class="text-xl font-bold mb-2">Entrepreneurship</h3>
                    <p class="text-slate-600 text-sm mb-4">Build business foundations, branding, and customer flow.</p>
                    <div class="text-sm mb-4"><span class="font-semibold">Duration:</span> 3 months<br><span class="font-semibold">Fee:</span> Ksh 15,000</div>
                    <a href="enroll_redirect.php" class="inline-block px-4 py-2 rounded-md text-white font-semibold text-sm" style="background:#6B3E93;">Enroll Now</a>
                </article>
                <article id="front-desk-cashier" class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                    <img src="Assets/images/desk 1.jpg" alt="Front desk and cashier workstation setup" class="course-thumb">
                    <h3 class="text-xl font-bold mb-2">Front Desk & Cashier</h3>
                    <p class="text-slate-600 text-sm mb-4">Reception, office workflows, and cashier operations.</p>
                    <div class="text-sm mb-4"><span class="font-semibold">Duration:</span> 2 months<br><span class="font-semibold">Fee:</span> Ksh 15,000</div>
                    <a href="enroll_redirect.php" class="inline-block px-4 py-2 rounded-md text-white font-semibold text-sm" style="background:#6B3E93;">Enroll Now</a>
                </article>
                <article id="computer-packages" class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                    <img src="Assets/images/student 4.jpg" alt="Computer training gadgets and lab setup" class="course-thumb">
                    <h3 class="text-xl font-bold mb-2">Computer Packages</h3>
                    <p class="text-slate-600 text-sm mb-4">Word, Excel, PowerPoint, Publisher, and office productivity.</p>
                    <div class="text-sm mb-4"><span class="font-semibold">Duration:</span> 3 months<br><span class="font-semibold">Fee:</span> Ksh 8,500</div>
                    <a href="enroll_redirect.php" class="inline-block px-4 py-2 rounded-md text-white font-semibold text-sm" style="background:#6B3E93;">Enroll Now</a>
                </article>
                <article id="content-videography" class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                    <img src="Assets/images/content%20and%20video.jpg" alt="Content creation and videography gear" class="course-thumb">
                    <h3 class="text-xl font-bold mb-2">Content Creation & Videography</h3>
                    <p class="text-slate-600 text-sm mb-4">Shoot, edit, and publish engaging digital content.</p>
                    <div class="text-sm mb-4"><span class="font-semibold">Duration:</span> 3 months<br><span class="font-semibold">Fee:</span> Ksh 15,000</div>
                    <a href="enroll_redirect.php" class="inline-block px-4 py-2 rounded-md text-white font-semibold text-sm" style="background:#6B3E93;">Enroll Now</a>
                </article>
            </div>
        </div>
    </main>

    <footer class="py-6 md:py-8" style="background-color: #121826; color: white;">
        <div class="container mx-auto px-4 md:px-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 md:gap-6 footer-grid">
                <div>
                    <div class="flex items-start mb-3 md:mb-4">
                        <div class="flex-shrink-0 mr-2">
                            <img src="./logo.svg" alt="Reality Dream Institute Logo" class="w-8 h-8 md:w-10 md:h-10 rounded-lg">
                        </div>
                        <div>
                            <h2 class="text-base md:text-lg font-bold leading-tight">Reality Dream Institute</h2>
                            <p class="text-xs md:text-sm text-gray-300 mt-0.5">Business, Tech & Innovation Hub</p>
                        </div>
                    </div>
                    <p class="text-gray-300 text-sm md:text-base mb-3 leading-relaxed">
                        Empowering learners with practical technical skills for employment, entrepreneurship, and community transformation.
                    </p>
                    <div class="space-y-1">
                        <div class="flex items-start">
                            <i class="fas fa-map-marker-alt mr-2 mt-0.5 text-gray-400 text-xs flex-shrink-0"></i>
                            <p class="text-gray-300 text-sm md:text-base">Kilifi Town, Ar Rayan Complex</p>
                        </div>
                        <div class="flex items-start">
                            <i class="fas fa-phone mr-2 mt-0.5 text-gray-400 text-xs flex-shrink-0"></i>
                            <div class="text-sm md:text-base">
                                <a href="tel:+254722729198" class="text-gray-300 hover:text-green-300 transition">0722 729 198</a>
                                <span class="text-gray-500 mx-1">|</span>
                                <a href="tel:+254743187154" class="text-gray-300 hover:text-green-300 transition">0743 187 154</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <h3 class="text-base md:text-lg font-bold mb-2 md:mb-4">Quick Links</h3>
                    <ul class="space-y-1 md:space-y-2">
                        <li><a href="index.html" class="text-gray-300 hover:text-green-300 transition text-sm md:text-base">Home</a></li>
                        <li><a href="about.html" class="text-gray-300 hover:text-green-300 transition text-sm md:text-base">About Us</a></li>
                        <li><a href="programs.php" class="text-gray-300 hover:text-green-300 transition text-sm md:text-base">Courses</a></li>
                        <li><a href="programs.php" class="text-gray-300 hover:text-green-300 transition text-sm md:text-base">Course Fees</a></li>
                        <li><a href="contact.php" class="text-gray-300 hover:text-green-300 transition text-sm md:text-base">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-base md:text-lg font-bold mb-2 md:mb-4">Popular Courses</h3>
                    <ul class="space-y-1 md:space-y-2">
                        <li><a href="programs.php" class="text-gray-300 hover:text-green-300 transition text-sm md:text-base">CCTV Installation Training</a></li>
                        <li><a href="programs.php" class="text-gray-300 hover:text-green-300 transition text-sm md:text-base">Solar Installation Training</a></li>
                        <li><a href="programs.php" class="text-gray-300 hover:text-green-300 transition text-sm md:text-base">Entrepreneurship Training</a></li>
                        <li><a href="programs.php" class="text-gray-300 hover:text-green-300 transition text-sm md:text-base">Computer Packages</a></li>
                        <li><a href="programs.php" class="text-gray-300 hover:text-green-300 transition text-sm md:text-base">Content Creation & Videography</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-base md:text-lg font-bold mb-2 md:mb-4">Newsletter</h3>
                    <p class="text-gray-300 text-sm md:text-base mb-2 md:mb-3 leading-relaxed">
                        Subscribe to get updates on new courses and offers.
                    </p>
                    <div class="flex mb-3 md:mb-4">
                        <input type="email" placeholder="Your email" class="flex-grow p-2 rounded-l-md text-gray-800 focus:outline-none text-sm md:text-base w-full">
                        <button class="px-3 md:px-4 rounded-r-md font-medium text-xs md:text-sm whitespace-nowrap" style="background-color: #377D3E;">
                            Subscribe
                        </button>
                    </div>
                    <div class="space-y-2">
                        <div class="flex items-center">
                            <i class="fas fa-envelope mr-2 text-gray-400 text-xs flex-shrink-0"></i>
                            <p class="text-gray-300 text-sm md:text-base break-all">realitydreamacademy@gmail.com</p>
                        </div>
                        <div class="flex flex-wrap gap-2 pt-2">
                            <a href="https://www.facebook.com/realitydreamacademy" target="_blank" aria-label="Facebook" class="w-9 h-9 rounded-full text-gray-200 flex items-center justify-center hover:text-white transition" style="background: linear-gradient(135deg,#1877f2,#0d5fcb);">
                                <i class="fab fa-facebook-f text-sm"></i>
                            </a>
                            <a href="https://www.instagram.com/realitydreamacademy" target="_blank" aria-label="Instagram" class="w-9 h-9 rounded-full text-white flex items-center justify-center hover:opacity-90 transition" style="background: linear-gradient(135deg,#f58529,#dd2a7b,#8134af,#515bd4);">
                                <i class="fab fa-instagram text-sm"></i>
                            </a>
                            <a href="https://www.twitter.com/realitydreamacademy" target="_blank" aria-label="X / Twitter" class="w-9 h-9 rounded-full text-white flex items-center justify-center hover:opacity-90 transition border border-white/30 shadow-sm" style="background: linear-gradient(135deg,#000000,#1f2937);">
                                <span class="font-bold text-xs tracking-wide">X</span>
                            </a>
                            <a href="https://wa.me/254722729198" target="_blank" aria-label="WhatsApp" class="w-9 h-9 rounded-full text-white flex items-center justify-center hover:opacity-90 transition" style="background: linear-gradient(135deg,#25D366,#128C7E);">
                                <i class="fab fa-whatsapp text-sm"></i>
                            </a>
                        </div>
                        <div class="pt-3 space-y-2">
                            <p class="text-xs uppercase tracking-wider text-gray-400 font-semibold">Quick Contact</p>
                            <div class="bg-gray-800/60 rounded-lg p-2.5 border border-gray-700">
                                <p class="text-xs text-gray-300 mb-2">0722 729 198</p>
                                <div class="flex gap-2">
                                    <a href="tel:+254722729198" class="inline-flex items-center justify-center px-3 py-1.5 rounded-md text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 transition w-full">
                                        <i class="fas fa-phone mr-1.5"></i>Call
                                    </a>
                                    <a href="https://wa.me/254722729198" target="_blank" class="inline-flex items-center justify-center px-3 py-1.5 rounded-md text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 transition w-full">
                                        <i class="fab fa-whatsapp mr-1.5"></i>WhatsApp
                                    </a>
                                </div>
                            </div>
                            <div class="bg-gray-800/60 rounded-lg p-2.5 border border-gray-700">
                                <p class="text-xs text-gray-300 mb-2">0743 187 154</p>
                                <div class="flex gap-2">
                                    <a href="tel:+254743187154" class="inline-flex items-center justify-center px-3 py-1.5 rounded-md text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 transition w-full">
                                        <i class="fas fa-phone mr-1.5"></i>Call
                                    </a>
                                    <a href="https://wa.me/254743187154" target="_blank" class="inline-flex items-center justify-center px-3 py-1.5 rounded-md text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 transition w-full">
                                        <i class="fab fa-whatsapp mr-1.5"></i>WhatsApp
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-4 md:mt-6 pt-4 md:pt-6 text-center">
                <p class="text-gray-300 text-sm md:text-base">
                    &copy; <span id="current-year">2024</span> Reality Dream Institute. All rights reserved. | Dream It, Build It.
                </p>
                <p class="mt-1 text-gray-400 text-xs md:text-sm">
                    Business, Tech & Innovation Hub | Kilifi Town, Kenya
                </p>
            </div>
        </div>
    </footer>
    <script>
        const mobileMenuBtn = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');
        if (mobileMenuBtn && mobileMenu) {
            mobileMenuBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                mobileMenu.classList.toggle('hidden');
            });
            document.addEventListener('click', function (event) {
                if (!mobileMenu.contains(event.target) && !mobileMenuBtn.contains(event.target) && !mobileMenu.classList.contains('hidden')) {
                    mobileMenu.classList.add('hidden');
                }
            });
        }
    </script>
</body>
</html>



