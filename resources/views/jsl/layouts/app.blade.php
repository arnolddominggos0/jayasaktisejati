<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ $settings->tagline ?? 'Jaya Sakti Line - Marine Vessel Trading & Chartering' }}">
    <meta name="keywords" content="vessel trading, ship chartering, tugboat, barge, tanker, marine, Indonesia">
    <meta property="og:title" content="{{ $settings->site_name ?? 'Jaya Sakti Line' }}">
    <meta property="og:description" content="{{ $settings->tagline ?? 'Marine Vessel Trading & Chartering' }}">
    <meta property="og:type" content="website">
    <title>@yield('title', $settings->site_name ?? 'Jaya Sakti Line')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        * { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; }
        html { scroll-behavior: smooth; }
        body { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }

        .jsl-primary { background-color: #0137A1; }
        .jsl-primary-text { color: #0137A1; }
        .jsl-primary-hover:hover { background-color: #002d8a; }
        .jsl-dark { background-color: #0a1a3a; }
        .jsl-darker { background-color: #061227; }

        .jsl-gradient {
            background: linear-gradient(135deg, #0137A1 0%, #0a2a6e 50%, #061227 100%);
        }
        .jsl-gradient-hero {
            background: linear-gradient(160deg, #0137A1 0%, #0a2a6e 40%, #061227 100%);
        }

        .jsl-nav { transition: all 0.3s ease; }
        .jsl-nav.scrolled {
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 4px 20px -4px rgba(0, 0, 0, 0.12);
        }

        .hover-lift {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .hover-lift:hover {
            transform: translateY(-6px);
            box-shadow: 0 24px 48px -16px rgba(1, 55, 161, 0.18);
        }

        .jsl-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .jsl-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px -12px rgba(1, 55, 161, 0.15);
            border-color: #0137A1;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(24px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in-up { animation: fadeInUp 0.7s ease-out forwards; }
        .delay-100 { animation-delay: 100ms; }
        .delay-200 { animation-delay: 200ms; }
        .delay-300 { animation-delay: 300ms; }

        .jsl-wave svg { display: block; width: 100%; height: 60px; }
        .jsl-section { padding: 5rem 0; }
        @media (min-width: 768px) { .jsl-section { padding: 6rem 0; } }

        a:focus-visible, button:focus-visible {
            outline: 2px solid #0137A1;
            outline-offset: 2px;
        }

        .jsl-link {
            position: relative;
        }
        .jsl-link::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background-color: #0137A1;
            transition: width 0.3s ease;
        }
        .jsl-link:hover::after { width: 100%; }

        .jsl-btn-primary {
            background-color: #0137A1;
            color: white;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .jsl-btn-primary:hover {
            background-color: #002d8a;
            transform: translateY(-2px);
            box-shadow: 0 12px 32px -10px rgba(1, 55, 161, 0.4);
        }

        .jsl-btn-outline {
            border: 2px solid #0137A1;
            color: #0137A1;
            transition: all 0.3s ease;
        }
        .jsl-btn-outline:hover {
            background-color: #0137A1;
            color: white;
        }
    </style>
    @stack('styles')
</head>
<body class="antialiased text-slate-800 bg-white">

    <!-- Navigation -->
    <nav id="navbar" class="jsl-nav fixed w-full z-50 bg-white/95 backdrop-blur-md border-b border-slate-200/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <a href="{{ route('jsl.home') }}" class="flex items-center space-x-3 group">
                    <img src="{{ asset('images/logo.png') }}" alt="JSL Logo" class="h-12 w-auto transition-transform group-hover:scale-105">
                    <div class="hidden lg:block">
                        <span class="text-slate-900 font-bold text-lg block leading-tight">{{ $settings->site_name ?? 'Jaya Sakti Line' }}</span>
                        <span class="jsl-primary-text text-xs font-medium uppercase tracking-wider">Marine Vessel Trading</span>
                    </div>
                </a>

                <div class="hidden md:flex items-center space-x-1">
                    <a href="{{ route('jsl.home') }}" class="px-4 py-2 text-slate-700 hover:jsl-primary-text font-medium transition-all rounded-lg hover:bg-blue-50/50 {{ request()->routeIs('jsl.home') ? 'jsl-primary-text font-semibold' : '' }}">Home</a>
                    <a href="{{ route('jsl.about') }}" class="px-4 py-2 text-slate-700 hover:jsl-primary-text font-medium transition-all rounded-lg hover:bg-blue-50/50 {{ request()->routeIs('jsl.about') ? 'jsl-primary-text font-semibold' : '' }}">About</a>
                    <a href="{{ route('jsl.services') }}" class="px-4 py-2 text-slate-700 hover:jsl-primary-text font-medium transition-all rounded-lg hover:bg-blue-50/50 {{ request()->routeIs('jsl.services') ? 'jsl-primary-text font-semibold' : '' }}">Services</a>
                    <a href="{{ route('jsl.trading.index') }}" class="px-4 py-2 text-slate-700 hover:jsl-primary-text font-medium transition-all rounded-lg hover:bg-blue-50/50 {{ request()->routeIs('jsl.trading.*') ? 'jsl-primary-text font-semibold' : '' }}">Vessels</a>
                    <a href="{{ route('jsl.gallery') }}" class="px-4 py-2 text-slate-700 hover:jsl-primary-text font-medium transition-all rounded-lg hover:bg-blue-50/50 {{ request()->routeIs('jsl.gallery') ? 'jsl-primary-text font-semibold' : '' }}">Gallery</a>
                    <a href="{{ route('jsl.contact') }}" class="px-4 py-2 text-slate-700 hover:jsl-primary-text font-medium transition-all rounded-lg hover:bg-blue-50/50 {{ request()->routeIs('jsl.contact*') ? 'jsl-primary-text font-semibold' : '' }}">Contact</a>
                </div>

                <div class="hidden md:flex items-center">
                    <a href="{{ route('jsl.contact') }}" class="jsl-btn-primary inline-flex items-center px-5 py-2.5 font-semibold rounded-xl">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        Inquire Now
                    </a>
                </div>

                <button id="mobile-menu-btn" class="md:hidden text-slate-700 hover:jsl-primary-text p-2 rounded-lg hover:bg-slate-100 transition-colors" aria-label="Toggle menu" aria-expanded="false" aria-controls="mobile-menu">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>

        <div id="mobile-menu" class="hidden md:hidden bg-white border-t border-slate-200 shadow-lg">
            <div class="px-4 py-4 space-y-1 max-w-7xl mx-auto">
                <a href="{{ route('jsl.home') }}" class="block px-4 py-3 text-slate-700 hover:jsl-primary-text hover:bg-blue-50 rounded-xl font-medium transition-colors">Home</a>
                <a href="{{ route('jsl.about') }}" class="block px-4 py-3 text-slate-700 hover:jsl-primary-text hover:bg-blue-50 rounded-xl font-medium transition-colors">About</a>
                <a href="{{ route('jsl.services') }}" class="block px-4 py-3 text-slate-700 hover:jsl-primary-text hover:bg-blue-50 rounded-xl font-medium transition-colors">Services</a>
                <a href="{{ route('jsl.trading.index') }}" class="block px-4 py-3 text-slate-700 hover:jsl-primary-text hover:bg-blue-50 rounded-xl font-medium transition-colors">Vessels</a>
                <a href="{{ route('jsl.gallery') }}" class="block px-4 py-3 text-slate-700 hover:jsl-primary-text hover:bg-blue-50 rounded-xl font-medium transition-colors">Gallery</a>
                <a href="{{ route('jsl.contact') }}" class="block px-4 py-3 text-slate-700 hover:jsl-primary-text hover:bg-blue-50 rounded-xl font-medium transition-colors">Contact</a>
                <div class="pt-2 border-t border-slate-200 mt-2">
                    <a href="{{ route('jsl.contact') }}" class="block px-4 py-3 jsl-primary-text font-semibold hover:bg-blue-50 rounded-xl transition-colors">Inquire Now</a>
                </div>
            </div>
        </div>
    </nav>

    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="jsl-darker text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10">
                <div>
                    <div class="flex items-center space-x-3 mb-5">
                        <img src="{{ asset('images/logo-white.svg') }}" alt="JSL Logo" class="h-12 w-auto">
                        <div>
                            <span class="font-bold text-xl block">{{ $settings->site_name ?? 'Jaya Sakti Line' }}</span>
                            <span class="text-slate-400 text-sm">Marine Vessel Trading</span>
                        </div>
                    </div>
                    <p class="text-slate-400 text-sm leading-relaxed mb-6">
                        {{ $settings->footer_text ?? 'Your trusted partner in marine vessel trading and chartering across Indonesian waters and beyond.' }}
                    </p>
                    @if($settings->social_facebook_url || $settings->social_instagram_url || $settings->social_linkedin_url)
                    <div class="flex space-x-3">
                        @if($settings->social_facebook_url)
                        <a href="{{ $settings->social_facebook_url }}" target="_blank" rel="noopener" class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center hover:jsl-primary transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                        @endif
                        @if($settings->social_instagram_url)
                        <a href="{{ $settings->social_instagram_url }}" target="_blank" rel="noopener" class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center hover:jsl-primary transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                        </a>
                        @endif
                        @if($settings->social_linkedin_url)
                        <a href="{{ $settings->social_linkedin_url }}" target="_blank" rel="noopener" class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center hover:jsl-primary transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                        </a>
                        @endif
                    </div>
                    @endif
                </div>

                <div>
                    <h3 class="font-semibold text-lg mb-5">Navigation</h3>
                    <ul class="space-y-3">
                        <li><a href="{{ route('jsl.home') }}" class="text-slate-400 hover:text-white transition-colors">Home</a></li>
                        <li><a href="{{ route('jsl.about') }}" class="text-slate-400 hover:text-white transition-colors">About Us</a></li>
                        <li><a href="{{ route('jsl.services') }}" class="text-slate-400 hover:text-white transition-colors">Services</a></li>
                        <li><a href="{{ route('jsl.trading.index') }}" class="text-slate-400 hover:text-white transition-colors">Vessels for Sale</a></li>
                        <li><a href="{{ route('jsl.gallery') }}" class="text-slate-400 hover:text-white transition-colors">Gallery</a></li>
                        <li><a href="{{ route('jsl.contact') }}" class="text-slate-400 hover:text-white transition-colors">Contact</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="font-semibold text-lg mb-5">Contact</h3>
                    <ul class="space-y-3 text-slate-400 text-sm">
                        @if($settings->contact_address)
                        <li class="flex items-start space-x-3">
                            <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span>{{ $settings->contact_address }}</span>
                        </li>
                        @endif
                        @if($settings->contact_phone_display)
                        <li class="flex items-center space-x-3">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            <span>{{ $settings->contact_phone_display }}</span>
                        </li>
                        @endif
                        @if($settings->contact_email_display)
                        <li class="flex items-center space-x-3">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <span>{{ $settings->contact_email_display }}</span>
                        </li>
                        @endif
                    </ul>
                </div>

                <div>
                    <h3 class="font-semibold text-lg mb-5">Inquiries</h3>
                    <p class="text-slate-400 text-sm mb-4">Looking to buy or charter a vessel? Get in touch with our broker team.</p>
                    @if($settings->broker_email)
                    <a href="mailto:{{ $settings->broker_email }}" class="jsl-btn-primary inline-flex items-center px-5 py-2.5 font-semibold rounded-xl text-sm">
                        Contact Broker
                    </a>
                    @endif
                </div>
            </div>

            <div class="border-t border-white/10 mt-12 pt-8 text-center">
                <p class="text-slate-500 text-sm">
                    &copy; {{ date('Y') }} {{ $settings->site_name ?? 'Jaya Sakti Line' }}. All rights reserved.
                </p>
            </div>
        </div>
    </footer>

    <script>
        document.getElementById('mobile-menu-btn').addEventListener('click', function() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
            this.setAttribute('aria-expanded', !menu.classList.contains('hidden'));
        });

        const navbar = document.getElementById('navbar');
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        document.querySelectorAll('#mobile-menu a').forEach(link => {
            link.addEventListener('click', function() {
                document.getElementById('mobile-menu').classList.add('hidden');
                document.getElementById('mobile-menu-btn').setAttribute('aria-expanded', 'false');
            });
        });
    </script>
    @stack('scripts')
</body>
</html>
