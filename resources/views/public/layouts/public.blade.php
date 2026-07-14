<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="PT Jaya Sakti Sejati - Solusi Logistik Terpercaya. Melayani pengiriman domestik dan internasional dengan jangkauan seluruh Indonesia.">
    <meta name="keywords" content="logistik, freight forwarding, container, pengiriman, jasa transportasi">
    <title>@yield('title', 'PT Jaya Sakti Sejati')</title>
    
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
        .delay-100 { animation-delay: 100ms; }
        .delay-200 { animation-delay: 200ms; }
        .delay-300 { animation-delay: 300ms; }
        .delay-400 { animation-delay: 400ms; }
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        Lacak Pengiriman
                    </a>
                    
                    <!-- Login Button - Secondary -->
                    <a href="{{ url('/portal') }}" class="inline-flex items-center px-5 py-2.5 border-2 border-slate-200 text-slate-700 font-semibold rounded-xl hover:border-blue-600 hover:text-blue-600 hover:bg-blue-50/50 transition-all">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Login
                    </a>
                </div>
                
                <!-- Mobile Menu Button -->
                <div class="md:hidden flex items-center space-x-3">
                    <!-- Mobile Tracking CTA -->
                    <a href="{{ route('tracking') }}" class="inline-flex items-center px-3 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg" aria-label="Lacak Pengiriman">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Beranda
                </a>
                <a href="{{ route('landing') }}#layanan" class="flex items-center px-4 py-3 text-slate-700 hover:text-blue-600 hover:bg-blue-50 rounded-xl font-medium transition-colors">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    Layanan
                </a>
                <a href="{{ route('landing') }}#kontak" class="flex items-center px-4 py-3 text-slate-700 hover:text-blue-600 hover:bg-blue-50 rounded-xl font-medium transition-colors">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Kontak
                </a>
                <div class="pt-2 border-t border-slate-200 mt-2">
                    <a href="{{ url('/portal') }}" class="flex items-center px-4 py-3 text-blue-600 font-semibold hover:bg-blue-50 rounded-xl transition-colors">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
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
                    <div class="mt-4 flex space-x-3">
                        <a href="#" class="w-10 h-10 bg-slate-800 rounded-lg flex items-center justify-center hover:bg-blue-600 transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                        </a>
                        <a href="#" class="w-10 h-10 bg-slate-800 rounded-lg flex items-center justify-center hover:bg-blue-600 transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                            </svg>
                        </a>
                        <a href="#" class="w-10 h-10 bg-slate-800 rounded-lg flex items-center justify-center hover:bg-blue-600 transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                            </svg>
                        </a>
                        <a href="#" class="w-10 h-10 bg-slate-800 rounded-lg flex items-center justify-center hover:bg-blue-600 transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
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
