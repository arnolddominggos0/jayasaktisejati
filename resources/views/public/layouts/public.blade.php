<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="PT Jaya Sakti Sejati - Solusi Logistik Terpercaya. Melayani pengiriman domestik dan internasional dengan jangkauan seluruh Indonesia.">
    <meta name="keywords" content="logistik, freight forwarding, container, pengiriman, jasa transportasi">
    <title>@yield('title', 'PT Jaya Sakti Sejati')</title>

    <!--Favicon-->
    <link rel="icon" type="image/png" href="{{ asset('images/favicon/favicon-32x32.png') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Custom Styles -->
    <style>
        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Fluid container for ultra-wide screens */
        .container-fluid {
            max-width: 1440px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Gradient utilities */
        .gradient-blue {
            background: linear-gradient(135deg, #1e40af 0%, #2563eb 50%, #3b82f6 100%);
        }

        .gradient-text {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Modern card hover effects */
        .hover-lift {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .hover-lift:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.15);
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        ::-webkit-scrollbar-thumb {
            background: #94a3b8;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }

        /* Responsive typography */
        @media (max-width: 640px) {
            .hero-title {
                font-size: 2rem;
                line-height: 1.2;
            }
        }

        /* Navbar transition */
        #navbar {
            transition: all 0.3s ease;
        }

        #navbar.scrolled {
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 4px 20px -4px rgba(0, 0, 0, 0.1);
        }

        /* Button animations */
        .btn-primary {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px -10px rgba(37, 99, 235, 0.4);
        }

        /* Glass morphism effect */
        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Section spacing for fluid design */
        section {
            position: relative;
        }

        /* Focus styles for accessibility */
        a:focus-visible,
        button:focus-visible {
            outline: 2px solid #2563eb;
            outline-offset: 2px;
        }

        /* Animation utilities */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out forwards;
        }

        /* Stagger animation delays */
        .delay-100 {
            animation-delay: 100ms;
        }

        .delay-200 {
            animation-delay: 200ms;
        }

        .delay-300 {
            animation-delay: 300ms;
        }

        .delay-400 {
            animation-delay: 400ms;
        }
    </style>

    @stack('styles')
</head>

<body class="antialiased text-slate-800 bg-white smooth-scroll">

    <!-- Navigation - Modern Corporate Style -->
    <nav id="navbar" class="fixed w-full z-50 transition-all duration-300 bg-white/95 backdrop-blur-md border-b border-slate-200/80 shadow-sm">
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 xl:px-12">
            <div class="flex justify-between items-center h-20">
                <!-- LEFT: Logo -->
                <div class="flex-shrink-0 flex items-center">
                    <a href="{{ route('landing') }}" class="flex items-center space-x-3 group">
                        <div class="relative">
                            <img src="{{ asset('images/logo.png') }}" alt="JSS Logo" class="h-12 w-auto transition-transform group-hover:scale-105">
                        </div>
                        <div class="hidden lg:block">
                            <span class="text-slate-900 font-bold text-lg block leading-tight tracking-tight">Jaya Sakti Sejati</span>
                            <span class="text-blue-600 text-xs font-medium uppercase tracking-wider">Freight Forwarding</span>
                        </div>
                    </a>
                </div>

                <!-- CENTER: Navigation Menu -->
                <div class="hidden md:flex items-center space-x-1">
                    <a href="{{ route('landing') }}" class="px-4 py-2 text-slate-600 hover:text-blue-600 font-medium transition-all rounded-lg hover:bg-blue-50/50">Beranda</a>
                    <a href="{{ route('landing') }}#layanan" class="px-4 py-2 text-slate-600 hover:text-blue-600 font-medium transition-all rounded-lg hover:bg-blue-50/50">Layanan</a>
                    <a href="{{ route('landing') }}#kontak" class="px-4 py-2 text-slate-600 hover:text-blue-600 font-medium transition-all rounded-lg hover:bg-blue-50/50">Kontak</a>
                </div>

                <!-- RIGHT: CTA Buttons -->
                <div class="hidden md:flex items-center space-x-3">
                    <!-- Tracking Button - Primary CTA -->
                    <a href="{{ route('tracking') }}" class="inline-flex items-center px-5 py-2.5 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-all shadow-lg shadow-blue-600/20 hover:shadow-xl hover:shadow-blue-600/30 hover:-translate-y-0.5">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        Lacak Pengiriman
                    </a>

                    <!-- Login Button - Secondary -->
                    <a href="{{ url('/portal') }}" class="inline-flex items-center px-5 py-2.5 border-2 border-slate-200 text-slate-700 font-semibold rounded-xl hover:border-blue-600 hover:text-blue-600 hover:bg-blue-50/50 transition-all">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Login
                    </a>
                </div>

                <!-- Mobile Menu Button -->
                <div class="md:hidden flex items-center space-x-3">
                    <!-- Mobile Tracking CTA -->
                    <a href="{{ route('tracking') }}" class="inline-flex items-center px-3 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg" aria-label="Lacak Pengiriman">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </a>

                    <button id="mobile-menu-btn" class="text-slate-600 hover:text-blue-600 focus:outline-none p-2 rounded-lg hover:bg-slate-100 transition-colors" aria-label="Toggle menu" aria-expanded="false" aria-controls="mobile-menu">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu - Slide Down -->
        <div id="mobile-menu" class="hidden md:hidden bg-white border-t border-slate-200 shadow-lg">
            <div class="px-4 py-4 space-y-2 max-w-[1440px] mx-auto">
                <a href="{{ route('landing') }}" class="flex items-center px-4 py-3 text-slate-700 hover:text-blue-600 hover:bg-blue-50 rounded-xl font-medium transition-colors">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Beranda
                </a>
                <a href="{{ route('landing') }}#layanan" class="flex items-center px-4 py-3 text-slate-700 hover:text-blue-600 hover:bg-blue-50 rounded-xl font-medium transition-colors">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    Layanan
                </a>
                <a href="{{ route('landing') }}#kontak" class="flex items-center px-4 py-3 text-slate-700 hover:text-blue-600 hover:bg-blue-50 rounded-xl font-medium transition-colors">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    Kontak
                </a>
                <div class="pt-2 border-t border-slate-200 mt-2">
                    <a href="{{ url('/portal') }}" class="flex items-center px-4 py-3 text-blue-600 font-semibold hover:bg-blue-50 rounded-xl transition-colors">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Login Portal
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer id="footer" class="bg-slate-900 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Company Info -->
                <div>
                    <div class="flex items-center space-x-3 mb-4">
                        <img src="{{ asset('images/logo.png') }}" alt="JSS Logo" class="h-14 w-auto">
                        <div>
                            <span class="font-bold text-xl block leading-tight">PT Jaya Sakti Sejati</span>
                            <span class="text-slate-400 text-sm">Freight Forwarding</span>
                        </div>
                    </div>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Solusi logistik terpercaya untuk pengiriman domestik dan internasional dengan jangkauan seluruh Indonesia sejak 1995.
                    </p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h3 class="font-semibold text-lg mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="{{ route('landing') }}" class="text-slate-400 hover:text-white transition-colors">Beranda</a></li>
                        <li><a href="{{ route('tracking') }}" class="text-slate-400 hover:text-white transition-colors">Lacak Pengiriman</a></li>
                        <li><a href="{{ route('landing') }}#layanan" class="text-slate-400 hover:text-white transition-colors">Layanan Kami</a></li>
                        <li><a href="{{ url('/portal') }}" class="text-slate-400 hover:text-white transition-colors">Customer Portal</a></li>
                    </ul>
                </div>

                <!-- Services -->
                <div>
                    <h3 class="font-semibold text-lg mb-4">Layanan</h3>
                    <ul class="space-y-2">
                        <li><span class="text-slate-400">International Freight Forwarder</span></li>
                        <li><span class="text-slate-400">Container Depot</span></li>
                        <li><span class="text-slate-400">Inland Transport</span></li>
                        <li><span class="text-slate-400">Project Logistics</span></li>
                        <li><span class="text-slate-400">Container Reefer</span></li>
                    </ul>
                </div>

                <!-- Contact -->
                <div>
                    <h3 class="font-semibold text-lg mb-4">Kontak Kami</h3>
                    <div class="space-y-3 text-slate-400 text-sm">
                        <div>
                            <p class="text-blue-400">Email: jayasaktisejati1@gmail.com</p>
                        </div>
                        <div class="pt-2 text-slate-500">
                            <p>Detail alamat & WhatsApp:</p>
                            <a href="{{ route('landing') }}#kontak" class="text-blue-400 hover:text-white transition-colors">Lihat di Kantor Kami →</a>
                        </div>
                    </div>

                    <!-- Social Media -->
                    @php
                    $socials = config('contact.social_media');
                    @endphp

                    <div class="mt-4 flex space-x-3">

                        <!-- Facebook -->
                        <a href="{{ $socials['facebook'] }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="w-10 h-10 bg-slate-800 rounded-lg flex items-center justify-center transition-all duration-300 hover:-translate-y-1 hover:bg-[#1877F2]">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                            </svg>
                        </a>

                        <!-- Instagram -->
                        <a href="{{ $socials['instagram'] }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="w-10 h-10 bg-slate-800 rounded-lg flex items-center justify-center transition-all duration-300 hover:-translate-y-1 hover:bg-[#E4405F]">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M7.75 2C4.574 2 2 4.574 2 7.75v8.5C2 19.426 4.574 22 7.75 22h8.5C19.426 22 22 19.426 22 16.25v-8.5C22 4.574 19.426 2 16.25 2h-8.5zm0 2h8.5A3.75 3.75 0 0120 7.75v8.5A3.75 3.75 0 0116.25 20h-8.5A3.75 3.75 0 014 16.25v-8.5A3.75 3.75 0 017.75 4zM17.5 5.5a1 1 0 100 2 1 1 0 000-2zM12 7a5 5 0 100 10 5 5 0 000-10zm0 2a3 3 0 110 6 3 3 0 010-6z" />
                            </svg>
                        </a>

                        <!-- LinkedIn -->
                        <a href="{{ $socials['linkedin'] }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="w-10 h-10 bg-slate-800 rounded-lg flex items-center justify-center transition-all duration-300 hover:-translate-y-1 hover:bg-[#0A66C2]">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.354V9h3.414v1.561h.049c.476-.9 1.637-1.852 3.37-1.852 3.602 0 4.268 2.368 4.268 5.455v6.288zM5.337 7.433c-1.144 0-2.07-.926-2.07-2.07s.926-2.07 2.07-2.07 2.07.926 2.07 2.07-.926 2.07-2.07 2.07zM7.114 20.452H3.56V9h3.554v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.771v20.452C0 23.226.792 24 1.771 24h20.452C23.206 24 24 23.226 24 22.225V1.771C24 .774 23.206 0 22.225 0z" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
            <!-- Bottom Bar -->
            <div class="border-t border-slate-800 mt-8 pt-8 text-center">
                <p class="text-slate-500 text-sm">
                    &copy; {{ date('Y') }} PT Jaya Sakti Sejati. All rights reserved.
                </p>
            </div>
        </div>
    </footer>

    <!-- Navigation & Interaction Scripts -->
    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-btn').addEventListener('click', function() {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('hidden');

            // Toggle aria-expanded for accessibility
            const isExpanded = !mobileMenu.classList.contains('hidden');
            this.setAttribute('aria-expanded', isExpanded);
        });

        // Navbar scroll effect
        const navbar = document.getElementById('navbar');
        let lastScroll = 0;

        window.addEventListener('scroll', function() {
            const currentScroll = window.pageYOffset;

            // Add/remove scrolled class
            if (currentScroll > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }

            // Hide/show navbar on scroll (optional - uncomment if needed)
            // if (currentScroll > lastScroll && currentScroll > 100) {
            //     navbar.style.transform = 'translateY(-100%)';
            // } else {
            //     navbar.style.transform = 'translateY(0)';
            // }

            lastScroll = currentScroll;
        });

        // Close mobile menu when clicking on a link
        document.querySelectorAll('#mobile-menu a').forEach(link => {
            link.addEventListener('click', function() {
                document.getElementById('mobile-menu').classList.add('hidden');
                document.getElementById('mobile-menu-btn').setAttribute('aria-expanded', 'false');
            });
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(e) {
            const mobileMenu = document.getElementById('mobile-menu');
            const menuBtn = document.getElementById('mobile-menu-btn');

            if (!mobileMenu.contains(e.target) && !menuBtn.contains(e.target)) {
                mobileMenu.classList.add('hidden');
                menuBtn.setAttribute('aria-expanded', 'false');
            }
        });
    </script>

    @stack('scripts')
</body>

</html>