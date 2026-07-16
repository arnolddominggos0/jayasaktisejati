@extends('public.layouts.public')

@section('title', 'PT Jaya Sakti Sejati - Solusi Freight Forwarding Terpercaya di Indonesia')

@section('content')

<!-- Hero Section - Modern Corporate Logistics -->
<section class="relative min-h-screen pt-20 lg:pt-0 flex items-center overflow-hidden">
    <!-- Background Image with Overlay -->
    <div class="absolute inset-0 z-0">
        <!-- Container/Port Background Image -->
        <div class="absolute inset-0 bg-cover bg-center bg-no-repeat scale-105" style="background-image: url('https://images.unsplash.com/photo-1578575437130-527eed3abbec?q=80&w=2070&auto=format&fit=crop');"></div>
        <!-- Dark Blue Gradient Overlay -->
        <div class="absolute inset-0 bg-gradient-to-br from-slate-900/95 via-blue-900/90 to-blue-800/85"></div>
        <!-- Subtle pattern -->
        <div class="absolute inset-0 opacity-[0.03]" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'1\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
    </div>
    
    <!-- Content Container - Full Width Fluid -->
    <div class="relative z-10 w-full max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 xl:px-12 py-12 lg:py-20">
        <div class="grid lg:grid-cols-2 gap-8 lg:gap-16 items-center">
            
            <!-- LEFT: Content -->
            <div class="text-center lg:text-left">
                <!-- Badge -->
                <div class="inline-flex items-center px-4 py-2 bg-blue-500/20 backdrop-blur-sm rounded-full text-blue-200 text-sm font-semibold mb-6 border border-blue-400/30">
                    <span class="w-2 h-2 bg-green-400 rounded-full mr-2 animate-pulse"></span>
                    Freight Forwarding & Logistics
                </div>
                
                <!-- Headline -->
                <h1 class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-extrabold text-white mb-6 leading-[1.1] tracking-tight">
                    Solusi Freight Forwarding
                    <span class="block text-transparent bg-clip-text bg-gradient-to-r from-blue-200 via-white to-blue-200">
                        Terpercaya di Indonesia
                    </span>
                </h1>
                
                <!-- Supporting Text -->
                <p class="text-base sm:text-lg text-blue-100/90 mb-8 leading-relaxed max-w-xl mx-auto lg:mx-0">
                    Pengiriman domestik & internasional dengan layanan door to door yang cepat, aman, dan terpercaya sejak 1995.
                </p>
                
                <!-- Key Benefits -->
                <div class="flex flex-col sm:flex-row flex-wrap justify-center lg:justify-start gap-3 sm:gap-4 mb-8">
                    <div class="flex items-center justify-center lg:justify-start text-blue-200 text-sm bg-white/5 px-4 py-2 rounded-full backdrop-blur-sm border border-white/10">
                        <svg class="w-5 h-5 mr-2 text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Door to Door Service
                    </div>
                    <div class="flex items-center justify-center lg:justify-start text-blue-200 text-sm bg-white/5 px-4 py-2 rounded-full backdrop-blur-sm border border-white/10">
                        <svg class="w-5 h-5 mr-2 text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Real-time Tracking
                    </div>
                    <div class="flex items-center justify-center lg:justify-start text-blue-200 text-sm bg-white/5 px-4 py-2 rounded-full backdrop-blur-sm border border-white/10">
                        <svg class="w-5 h-5 mr-2 text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        30+ Tahun Pengalaman
                    </div>
                </div>
                
                <!-- CTA Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                    <!-- Primary: Tracking -->
                    <a href="{{ route('tracking') }}" class="group inline-flex items-center justify-center px-8 py-4 bg-white text-blue-700 font-bold rounded-xl hover:bg-blue-50 transition-all shadow-xl shadow-white/20 hover:shadow-2xl hover:shadow-white/30 transform hover:-translate-y-0.5">
                        <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        Lacak Pengiriman
                    </a>
                    <!-- Secondary: Contact -->
                    <a href="#kontak" class="inline-flex items-center justify-center px-8 py-4 bg-transparent border-2 border-white/30 text-white font-bold rounded-xl hover:bg-white/10 hover:border-white/50 transition-all backdrop-blur-sm">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        Hubungi Kami
                    </a>
                </div>
            </div>
            
            <!-- RIGHT: Tracking Card Mockup -->
            <div class="hidden lg:block relative">
                <div class="relative max-w-md mx-auto">
                    <!-- Main Tracking Card -->
                    <div class="bg-white/95 backdrop-blur-xl rounded-2xl p-8 shadow-2xl border border-white/20">
                        <!-- Card Header -->
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12 bg-blue-100 rounded-2xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-slate-900">JSS0726SH-018</h3>
                                    <p class="text-sm text-slate-500">Container 20ft • FCL</p>
                                </div>
                            </div>
                            <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-semibold">On Vessel</span>
                        </div>

                        <!-- Shipment Details -->
                        <div class="grid grid-cols-2 gap-x-4 gap-y-1 mb-6 pb-5 border-b border-slate-100">
                            <div>
                                <p class="text-xs text-slate-400">Kapal</p>
                                <p class="text-sm font-semibold text-slate-800">KM Oriental Emerald</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-400">Rute</p>
                                <p class="text-sm font-semibold text-slate-800">Jakarta → Manado</p>
                            </div>
                        </div>

                        <!-- Timeline -->
                        <div class="space-y-6">
                            <!-- Step 1: Completed -->
                            <div class="flex items-start">
                                <div class="relative flex flex-col items-center mr-4">
                                    <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                    <div class="w-0.5 h-12 bg-blue-200 mt-1"></div>
                                </div>
                                <div class="flex-1 pt-1">
                                    <p class="font-semibold text-slate-900">Pickup & Stuffing</p>
                                    <p class="text-sm text-slate-500">Tj. Priok, Jakarta • 8 Jul 2026</p>
                                </div>
                            </div>

                            <!-- Step 2: Active -->
                            <div class="flex items-start">
                                <div class="relative flex flex-col items-center mr-4">
                                    <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center ring-4 ring-blue-100 animate-pulse">
                                        <div class="w-3 h-3 bg-white rounded-full"></div>
                                    </div>
                                    <div class="w-0.5 h-12 bg-slate-200 mt-1"></div>
                                </div>
                                <div class="flex-1 pt-1">
                                    <p class="font-semibold text-slate-900">In Transit</p>
                                    <p class="text-sm text-slate-500">Transit Tj. Perak, Surabaya • 11 Jul 2026</p>
                                    <p class="text-xs text-blue-600 mt-1 font-medium">Dalam pelayaran menuju Bitung</p>
                                </div>
                            </div>

                            <!-- Step 3: Pending -->
                            <div class="flex items-start opacity-50">
                                <div class="relative flex flex-col items-center mr-4">
                                    <div class="w-10 h-10 rounded-full bg-slate-200 flex items-center justify-center">
                                        <div class="w-3 h-3 bg-slate-400 rounded-full"></div>
                                    </div>
                                </div>
                                <div class="flex-1 pt-1">
                                    <p class="font-semibold text-slate-900">Delivery</p>
                                    <p class="text-sm text-slate-500">Bitung, Manado • Est. 16 Jul 2026</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Quick Action -->
                        <div class="mt-6 pt-6 border-t border-slate-100">
                            <a href="{{ route('tracking') }}" class="flex items-center justify-center w-full py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-colors">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                Lihat Detail
                            </a>
                        </div>
                    </div>
                    
                    <!-- Floating Stats Card -->
                    <div class="absolute top-1/2 right-[-20%] transform -translate-y-1/2 bg-white/90 backdrop-blur-lg rounded-2xl p-6 shadow-xl border border-white/30 w-64">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/30">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-3xl font-bold text-slate-900">100,000+</p>
                                <p class="text-slate-500 text-sm font-medium">Pengiriman Sukses</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Mobile: Simple CTA Card -->
            <div class="lg:hidden mt-8">
                <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 bg-blue-500/30 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-white">Cek Pengiriman Anda</h3>
                                <p class="text-sm text-blue-200">Tracking real-time 24/7</p>
                            </div>
                        </div>
                        <a href="{{ route('tracking') }}" class="px-4 py-2 bg-white text-blue-700 font-semibold rounded-lg">
                            Cek
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Wave Divider -->
    <div class="absolute bottom-0 left-0 right-0">
        <svg class="w-full h-auto" viewBox="0 0 1440 120" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
            <path d="M0 120L60 110C120 100 240 80 360 70C480 60 600 60 720 65C840 70 960 80 1080 85C1200 90 1320 90 1380 90L1440 90V120H1380C1320 120 1200 120 1080 120C960 120 840 120 720 120C600 120 480 120 360 120C240 120 120 120 60 120H0Z" fill="white"/>
        </svg>
    </div>
</section>

<!-- Stats Section with Counter Animation -->
<section class="py-16 bg-white relative -mt-1">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
            <div class="text-center group">
                <div class="w-16 h-16 bg-blue-100 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:bg-blue-200 transition-colors">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="text-4xl md:text-5xl font-bold text-slate-800 leading-none tabular-nums mb-3 counter" data-target="30">0</div>
                <div class="text-slate-500 font-medium">Tahun Pengalaman</div>
            </div>
            <div class="text-center group">
                <div class="w-16 h-16 bg-green-100 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:bg-green-200 transition-colors">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="text-4xl md:text-5xl font-bold text-slate-800 leading-none tabular-nums mb-3">
                    <span class="counter" data-target="100  ">0</span>rb+
                </div>
                <div class="text-slate-500 font-medium">Pengiriman Selesai</div>
            </div>
            <div class="text-center group">
                <div class="w-16 h-16 bg-purple-100 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:bg-purple-200 transition-colors">
                    <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <div class="text-4xl md:text-5xl font-bold text-slate-800 leading-none tabular-nums mb-3">
                    <span class="counter" data-target="500">0</span>+
                </div>
                <div class="text-slate-500 font-medium">Klien Aktif</div>
            </div>
            <div class="text-center group">
                <div class="w-16 h-16 bg-orange-100 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:bg-orange-200 transition-colors">
                    <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                </div>
                <div class="text-4xl md:text-5xl font-bold text-slate-800 leading-none tabular-nums mb-3">
                    <span class="counter" data-target="100">0</span>+
                </div>
                <div class="text-slate-500 font-medium">Unit Armada</div>
            </div>
        </div>
    </div>
</section>

<!-- Mode Pengiriman Section - Intent First -->
<section id="mode-pengiriman" class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="text-center mb-12">
            <span class="text-blue-600 font-semibold text-sm uppercase tracking-wide">Pilihan Pengiriman</span>
            <h2 class="text-3xl md:text-4xl font-bold text-slate-800 mt-2 mb-4">Mode Pengiriman</h2>
            <p class="text-slate-600 max-w-2xl mx-auto">Pilih cara pengiriman yang sesuai dengan kebutuhan supply chain Anda</p>
            <div class="w-20 h-1 bg-blue-600 mx-auto mt-4"></div>
        </div>

        <!-- Intent Selector Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-5xl mx-auto mb-12">
            <div class="group bg-gradient-to-br from-blue-50 to-blue-100 rounded-2xl p-6 border-2 border-blue-200 hover:border-blue-500 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 cursor-pointer">
                <div class="text-4xl mb-4">🚪</div>
                <h3 class="text-lg font-bold text-slate-800 mb-2">Door to Door</h3>
                <p class="text-slate-600 text-sm">Jemput dan antar ke lokasi</p>
            </div>
            <div class="group bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-2xl p-6 border-2 border-indigo-200 hover:border-indigo-500 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 cursor-pointer">
                <div class="text-4xl mb-4">⚓</div>
                <h3 class="text-lg font-bold text-slate-800 mb-2">Port Services</h3>
                <p class="text-slate-600 text-sm">Via pelabuhan</p>
            </div>
            <div class="group bg-gradient-to-br from-amber-50 to-amber-100 rounded-2xl p-6 border-2 border-amber-200 hover:border-amber-500 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 cursor-pointer">
                <div class="text-4xl mb-4">🏗️</div>
                <h3 class="text-lg font-bold text-slate-800 mb-2">Project Cargo</h3>
                <p class="text-slate-600 text-sm">Kargo besar</p>
            </div>
        </div>

        @php
            $defaultBranch = config('contact.default_branch');
            $defaultNumber = config('contact.branches.' . $defaultBranch . '.whatsapp');
        @endphp

        <!-- Detailed Mode Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 max-w-6xl mx-auto">
            <!-- Door to Door -->
            <div class="group bg-white rounded-2xl border-2 border-blue-500 shadow-lg p-6 hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center text-2xl">🚪</div>
                    <span class="bg-amber-400 text-white text-xs font-bold px-2 py-1 rounded-full">POPULER</span>
                </div>
                <h3 class="text-xl font-bold text-slate-800 mb-2">Door to Door</h3>
                <p class="text-slate-600 text-sm mb-4">Kami jemput barang dari lokasi Anda dan antar sampai tujuan. Tanpa repot.</p>
                <a href="https://wa.me/{{ $defaultNumber }}?text={{ urlencode(config('contact.whatsapp_templates.door_to_door')) }}" target="_blank" class="inline-flex items-center text-blue-600 font-semibold text-sm hover:text-blue-700">Hubungi via WhatsApp →</a>
            </div>

            <!-- Port to Door -->
            <div class="group bg-white rounded-2xl border border-slate-200 shadow-md p-6 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center text-2xl mb-4">⚓</div>
                <h3 class="text-xl font-bold text-slate-800 mb-2">Port to Door</h3>
                <p class="text-slate-600 text-sm mb-4">Antar barang ke pelabuhan, kami urus pengiriman sampai lokasi tujuan.</p>
                <a href="https://wa.me/{{ $defaultNumber }}?text={{ urlencode(config('contact.whatsapp_templates.port_to_door')) }}" target="_blank" class="inline-flex items-center text-blue-600 font-semibold text-sm hover:text-blue-700">Hubungi via WhatsApp →</a>
            </div>

            <!-- Port to Port -->
            <div class="group bg-white rounded-2xl border border-slate-200 shadow-md p-6 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                <div class="w-12 h-12 bg-cyan-100 rounded-xl flex items-center justify-center text-2xl mb-4">🚢</div>
                <h3 class="text-xl font-bold text-slate-800 mb-2">Port to Port</h3>
                <p class="text-slate-600 text-sm mb-4">Kirim antar pelabuhan untuk pengiriman internasional yang efisien.</p>
                <a href="https://wa.me/{{ $defaultNumber }}?text={{ urlencode(config('contact.whatsapp_templates.port_to_port')) }}" target="_blank" class="inline-flex items-center text-blue-600 font-semibold text-sm hover:text-blue-700">Hubungi via WhatsApp →</a>
            </div>

            <!-- Door to Port -->
            <div class="group bg-white rounded-2xl border border-slate-200 shadow-md p-6 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center text-2xl mb-4">📦</div>
                <h3 class="text-xl font-bold text-slate-800 mb-2">Door to Port</h3>
                <p class="text-slate-600 text-sm mb-4">Kami jemput dari lokasi Anda dan antar ke pelabuhan tujuan.</p>
                <a href="https://wa.me/{{ $defaultNumber }}?text={{ urlencode(config('contact.whatsapp_templates.door_to_port')) }}" target="_blank" class="inline-flex items-center text-blue-600 font-semibold text-sm hover:text-blue-700">Hubungi via WhatsApp →</a>
            </div>

            <!-- LCL -->
            <div class="group bg-white rounded-2xl border border-slate-200 shadow-md p-6 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center text-2xl mb-4">📋</div>
                <h3 class="text-xl font-bold text-slate-800 mb-2">LCL / Consolidation</h3>
                <p class="text-slate-600 text-sm mb-4">Gabungkan kargo dengan pengirim lain untuk biaya lebih hemat.</p>
                <a href="https://wa.me/{{ $defaultNumber }}?text={{ urlencode(config('contact.whatsapp_templates.lcl')) }}" target="_blank" class="inline-flex items-center text-blue-600 font-semibold text-sm hover:text-blue-700">Hubungi via WhatsApp →</a>
            </div>

            <!-- FCL -->
            <div class="group bg-white rounded-2xl border border-slate-200 shadow-md p-6 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                <div class="w-12 h-12 bg-rose-100 rounded-xl flex items-center justify-center text-2xl mb-4">🚛</div>
                <h3 class="text-xl font-bold text-slate-800 mb-2">FCL (Full Container)</h3>
                <p class="text-slate-600 text-sm mb-4">Container full 20-40 feet untuk muatan besar dan kargo khusus.</p>
                <a href="https://wa.me/{{ $defaultNumber }}?text={{ urlencode(config('contact.whatsapp_templates.fcl')) }}" target="_blank" class="inline-flex items-center text-blue-600 font-semibold text-sm hover:text-blue-700">Hubungi via WhatsApp →</a>
            </div>

            <!-- Project Cargo -->
            <div class="group bg-white rounded-2xl border border-slate-200 shadow-md p-6 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 md:col-span-2 lg:col-span-1">
                <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center text-2xl mb-4">🏗️</div>
                <h3 class="text-xl font-bold text-slate-800 mb-2">Project Cargo</h3>
                <p class="text-slate-600 text-sm mb-4">Kargo besar dan proyek khusus dengan handling profesional.</p>
                <a href="https://wa.me/{{ $defaultNumber }}?text={{ urlencode(config('contact.whatsapp_templates.project_cargo')) }}" target="_blank" class="inline-flex items-center text-blue-600 font-semibold text-sm hover:text-blue-700">Hubungi via WhatsApp →</a>
            </div>
        </div>

        <!-- Global CTA -->
        <div class="text-center mt-12">
            <a href="#kontak" class="inline-flex items-center justify-center px-8 py-4 border-2 border-transparent bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                Request Quote
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </a>
        </div>
    </div>
</section>

<!-- About Section - Simplified -->
<section class="py-20 bg-slate-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
            <div>
                <span class="text-blue-600 font-semibold text-sm uppercase tracking-wide">Tentang Kami</span>
                <h2 class="text-3xl md:text-4xl font-bold text-slate-800 mt-2 mb-6">
                    PT Jaya Sakti Sejati
                </h2>
                <p class="text-slate-600 mb-6 leading-relaxed">
                    Perusahaan freight forwarding dengan pengalaman lebih dari 30 tahun dalam melayani pengiriman domestik dan internasional menggunakan peti kemas (container).
                </p>
                
                <!-- Highlights -->
                <div class="grid sm:grid-cols-2 gap-4">
                    <div class="flex items-start">
                        <div class="w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <span class="ml-3 text-slate-700">30+ tahun pengalaman</span>
                    </div>
                    <div class="flex items-start">
                        <div class="w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <span class="ml-3 text-slate-700">Jangkauan seluruh Indonesia</span>
                    </div>
                    <div class="flex items-start">
                        <div class="w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <span class="ml-3 text-slate-700">Layanan domestik & internasional</span>
                    </div>
                    <div class="flex items-start">
                        <div class="w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <span class="ml-3 text-slate-700">Door to Door Service</span>
                    </div>
                    <div class="flex items-start">
                        <div class="w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <span class="ml-3 text-slate-700">Real-time tracking</span>
                    </div>
                    <div class="flex items-start">
                        <div class="w-6 h-6 bg-blue-500 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <span class="ml-3 text-slate-700">Tim profesional & berpengalaman</span>
                    </div>
                </div>
            </div>
            
            <div class="relative">
                <div class="bg-white rounded-2xl shadow-xl p-8 relative z-10">
                    <blockquote class="text-2xl text-slate-800 font-light italic mb-6 leading-relaxed">
                        "Always deliver more than expected."
                    </blockquote>
                    <p class="text-slate-500">
                        Komitmen kami untuk selalu memberikan pelayanan terbaik bagi setiap pelanggan.
                    </p>
                </div>
                <!-- Decorative Elements -->
                <div class="absolute -top-4 -right-4 w-full h-full bg-blue-100 rounded-2xl -z-0"></div>
                <div class="absolute -bottom-4 -left-4 w-24 h-24 bg-blue-500 rounded-full opacity-20"></div>
            </div>
        </div>
    </div>
</section>

<!-- Services Section with Different Icons -->
<section id="layanan" class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <span class="text-blue-600 font-semibold text-sm uppercase tracking-wide">Layanan Perusahaan</span>
            <h2 class="text-3xl md:text-4xl font-bold text-slate-800 mt-2 mb-4">Layanan Kami</h2>
            <p class="text-slate-600 max-w-2xl mx-auto">Layanan penunjang perusahaan yang mendukung setiap mode pengiriman Anda</p>
            <div class="w-20 h-1 bg-blue-600 mx-auto mt-4"></div>
        </div>
        
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- International Freight -->
            <div class="bg-slate-50 rounded-2xl p-8 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 group">
                <div class="w-16 h-16 bg-blue-100 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-blue-600 transition-colors">
                    <svg class="w-8 h-8 text-blue-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-slate-800 mb-3">International Freight Forwarder</h3>
                <p class="text-slate-600 mb-4">Jasa logistik domestik dan internasional dengan tim berpengalaman. Layanan cepat, aman, dan terjangkau.</p>
                
                <!-- Mode Availability Tags -->
                <div class="mb-4">
                    <p class="text-xs text-slate-500 mb-2">Tersedia untuk:</p>
                    <div class="flex flex-wrap gap-2">
                        <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full">DTD</span>
                        <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full">PTD</span>
                        <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full">PTP</span>
                        <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full">DTP</span>
                        <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full">LCL</span>
                        <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full">FCL</span>
                    </div>
                </div>
                
                <a href="#kontak" class="inline-flex items-center text-blue-600 font-semibold hover:text-blue-700">
                    Lihat Detail
                    <svg class="w-4 h-4 ml-1 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                    </svg>
                </a>
            </div>

            <!-- Container Depot -->
            <div class="bg-slate-50 rounded-2xl p-8 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 group">
                <div class="w-16 h-16 bg-green-100 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-green-600 transition-colors">
                    <svg class="w-8 h-8 text-green-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-slate-800 mb-3">Container Depot</h3>
                <p class="text-slate-600 mb-4">Penyediaan depo container dry/reefer dan peralatan handling yang lengkap untuk kebutuhan logistik Anda.</p>
                
                <!-- Mode Availability Tags -->
                <div class="mb-4">
                    <p class="text-xs text-slate-500 mb-2">Tersedia untuk:</p>
                    <div class="flex flex-wrap gap-2">
                        <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full">PTP</span>
                        <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full">DTP</span>
                        <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full">PTD</span>
                        <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full">FCL</span>
                    </div>
                </div>
                
                <a href="#kontak" class="inline-flex items-center text-blue-600 font-semibold hover:text-blue-700">
                    Lihat Detail
                    <svg class="w-4 h-4 ml-1 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                    </svg>
                </a>
            </div>

            <!-- Inland Transport -->
            <div class="bg-slate-50 rounded-2xl p-8 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 group">
                <div class="w-16 h-16 bg-orange-100 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-orange-600 transition-colors">
                    <svg class="w-8 h-8 text-orange-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-slate-800 mb-3">Inland Transport</h3>
                <p class="text-slate-600 mb-4">Transportasi dalam kota untuk jasa door to door dengan armada trailer yang handal dan profesional.</p>
                
                <!-- Mode Availability Tags -->
                <div class="mb-4">
                    <p class="text-xs text-slate-500 mb-2">Tersedia untuk:</p>
                    <div class="flex flex-wrap gap-2">
                        <span class="text-xs bg-orange-100 text-orange-700 px-2 py-1 rounded-full">DTD</span>
                        <span class="text-xs bg-orange-100 text-orange-700 px-2 py-1 rounded-full">DTP</span>
                        <span class="text-xs bg-orange-100 text-orange-700 px-2 py-1 rounded-full">PTD</span>
                    </div>
                </div>
                
                <a href="#kontak" class="inline-flex items-center text-blue-600 font-semibold hover:text-blue-700">
                    Lihat Detail
                    <svg class="w-4 h-4 ml-1 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                    </svg>
                </a>
            </div>

            <!-- Project Logistics -->
            <div class="bg-slate-50 rounded-2xl p-8 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 group">
                <div class="w-16 h-16 bg-purple-100 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-purple-600 transition-colors">
                    <svg class="w-8 h-8 text-purple-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-slate-800 mb-3">Project Logistics</h3>
                <p class="text-slate-600 mb-4">Jasa planning, operation design, inland & sea transportation, stevedoring, dan formalitas bea cukai.</p>
                
                <!-- Mode Availability Tags -->
                <div class="mb-4">
                    <p class="text-xs text-slate-500 mb-2">Tersedia untuk:</p>
                    <div class="flex flex-wrap gap-2">
                        <span class="text-xs bg-purple-100 text-purple-700 px-2 py-1 rounded-full">DTD</span>
                        <span class="text-xs bg-purple-100 text-purple-700 px-2 py-1 rounded-full">DTP</span>
                        <span class="text-xs bg-purple-100 text-purple-700 px-2 py-1 rounded-full">PTD</span>
                        <span class="text-xs bg-purple-100 text-purple-700 px-2 py-1 rounded-full">PTP</span>
                        <span class="text-xs bg-purple-100 text-purple-700 px-2 py-1 rounded-full">Project</span>
                    </div>
                </div>
                
                <a href="#kontak" class="inline-flex items-center text-blue-600 font-semibold hover:text-blue-700">
                    Lihat Detail
                    <svg class="w-4 h-4 ml-1 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                    </svg>
                </a>
            </div>

            <!-- Container Reefer -->
            <div class="bg-slate-50 rounded-2xl p-8 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 group md:col-span-2 lg:col-span-1">
                <div class="w-16 h-16 bg-cyan-100 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-cyan-600 transition-colors">
                    <svg class="w-8 h-8 text-cyan-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-slate-800 mb-3">Container Reefer</h3>
                <p class="text-slate-600 mb-4">Pengangkutan makanan beku dengan 200+ TEUs container reefer dan genset 6 unit (20-100 KVA). Layanan domestic & internasional.</p>
                
                <!-- Mode Availability Tags -->
                <div class="mb-4">
                    <p class="text-xs text-slate-500 mb-2">Tersedia untuk:</p>
                    <div class="flex flex-wrap gap-2">
                        <span class="text-xs bg-cyan-100 text-cyan-700 px-2 py-1 rounded-full">DTD</span>
                        <span class="text-xs bg-cyan-100 text-cyan-700 px-2 py-1 rounded-full">DTP</span>
                        <span class="text-xs bg-cyan-100 text-cyan-700 px-2 py-1 rounded-full">PTD</span>
                        <span class="text-xs bg-cyan-100 text-cyan-700 px-2 py-1 rounded-full">PTP</span>
                        <span class="text-xs bg-cyan-100 text-cyan-700 px-2 py-1 rounded-full">FCL</span>
                    </div>
                </div>
                
                <a href="#kontak" class="inline-flex items-center text-blue-600 font-semibold hover:text-blue-700">
                    Lihat Detail
                    <svg class="w-4 h-4 ml-1 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Why Choose Us (replacing Vision Mission) -->
<section class="py-20 relative overflow-hidden">
    <!-- Background Image with Overlay -->
    <div class="absolute inset-0 z-0">
        <!-- Shipping/Logistics Background Image -->
        <div class="absolute inset-0 bg-cover bg-center bg-no-repeat" style="background-image: url('https://images.unsplash.com/photo-1494412574643-ff11b0a5c1c3?q=80&w=2070&auto=format&fit=crop');"></div>
        <!-- Dark Blue Gradient Overlay -->
        <div class="absolute inset-0 bg-gradient-to-br from-blue-900/90 via-blue-800/80 to-blue-600/70"></div>
    </div>
    <!-- Background Pattern -->
    <div class="absolute inset-0 opacity-10 z-0">
        <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
            <path d="M0 100 L100 0 L100 100 Z" fill="white"/>
        </svg>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">Kenapa Memilih Kami?</h2>
            <p class="text-blue-200 max-w-2xl mx-auto">Keunggulan yang membuat kami menjadi pilihan utama untuk kebutuhan logistik Anda</p>
            <div class="w-20 h-1 bg-blue-400 mx-auto mt-4"></div>
        </div>
        
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-8 border border-white/20 hover:bg-white/20 transition-all duration-300 transform hover:-translate-y-1">
                <div class="w-14 h-14 bg-white rounded-xl flex items-center justify-center mb-6">
                    <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-white mb-3">Pengalaman 30+ Tahun</h3>
                <p class="text-blue-200">Beroperasi sejak awal 1990-an dengan track record pengiriman yang terbukti handal.</p>
            </div>
            
            <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-8 border border-white/20 hover:bg-white/20 transition-all duration-300 transform hover:-translate-y-1">
                <div class="w-14 h-14 bg-white rounded-xl flex items-center justify-center mb-6">
                    <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-white mb-3">Jaringan Luas</h3>
                <p class="text-blue-200">Jangkauan pelayanan ke seluruh Indonesia dengan kantor di Jakarta, Surabaya, dan Manado.</p>
            </div>
            
            <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-8 border border-white/20 hover:bg-white/20 transition-all duration-300 transform hover:-translate-y-1">
                <div class="w-14 h-14 bg-white rounded-xl flex items-center justify-center mb-6">
                    <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-white mb-3">Tim Profesional</h3>
                <p class="text-blue-200">100+ SDM handal yang tersebar di berbagai kantor untuk pelayanan terbaik.</p>
            </div>
            
            <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-8 border border-white/20 hover:bg-white/20 transition-all duration-300 transform hover:-translate-y-1">
                <div class="w-14 h-14 bg-white rounded-xl flex items-center justify-center mb-6">
                    <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-white mb-3">Harga Kompetitif</h3>
                <p class="text-blue-200">Penawaran harga yang kompetitif tanpa mengorbankan kualitas layanan.</p>
            </div>
            
            <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-8 border border-white/20 hover:bg-white/20 transition-all duration-300 transform hover:-translate-y-1">
                <div class="w-14 h-14 bg-white rounded-xl flex items-center justify-center mb-6">
                    <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-white mb-3">Aman & Terpercaya</h3>
                <p class="text-blue-200">Sistem tracking real-time dan penanganan barang dengan standar keamanan tinggi.</p>
            </div>
            
            <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-8 border border-white/20 hover:bg-white/20 transition-all duration-300 transform hover:-translate-y-1">
                <div class="w-14 h-14 bg-white rounded-xl flex items-center justify-center mb-6">
                    <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-white mb-3">Layanan Door to Door</h3>
                <p class="text-blue-200">Layanan pengambilan dan pengantaran barang dari dan ke lokasi Anda.</p>
            </div>
        </div>
    </div>
</section>

<!-- Coverage Section with Map -->
<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <span class="text-blue-600 font-semibold text-sm uppercase tracking-wide">Jangkauan</span>
            <h2 class="text-3xl md:text-4xl font-bold text-slate-800 mt-2 mb-4">Jangkauan Pelayanan</h2>
            <p class="text-slate-600 max-w-2xl mx-auto">Kami melayani pengiriman ke seluruh wilayah Indonesia</p>
            <div class="w-20 h-1 bg-blue-600 mx-auto mt-4"></div>
        </div>

        <!-- Map Visual - Static premium enterprise network map -->
        <div class="rounded-2xl shadow-lg overflow-hidden mb-8">
            <div class="relative overflow-hidden">
                <svg id="coverage-map-svg" viewBox="0 0 1000 460" class="w-full h-auto block transition-transform duration-500 ease-out" role="img" aria-label="Peta jangkauan pelayanan PT Jaya Sakti Sejati di Indonesia">
                    <defs>
                        <linearGradient id="oceanGrad" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0" stop-color="#0C2140"/>
                            <stop offset="0.55" stop-color="#123059"/>
                            <stop offset="1" stop-color="#1B4478"/>
                        </linearGradient>
                        <linearGradient id="routeGrad" x1="0" y1="0" x2="1" y2="0">
                            <stop offset="0" stop-color="#38BDF8"/>
                            <stop offset="1" stop-color="#93C5FD"/>
                        </linearGradient>
                        <filter id="landShadow" x="-5%" y="-5%" width="110%" height="115%">
                            <feDropShadow dx="0" dy="2" stdDeviation="4" flood-color="#020617" flood-opacity="0.28"/>
                        </filter>
                    </defs>

                    <!-- Ocean -->
                    <rect width="1000" height="460" fill="url(#oceanGrad)"/>
                    <!-- Subtle graticule -->
                    <g stroke="#FFFFFF" stroke-width="1" opacity="0.05">
                        <line x1="125" y1="0" x2="125" y2="460"/><line x1="250" y1="0" x2="250" y2="460"/>
                        <line x1="375" y1="0" x2="375" y2="460"/><line x1="500" y1="0" x2="500" y2="460"/>
                        <line x1="625" y1="0" x2="625" y2="460"/><line x1="750" y1="0" x2="750" y2="460"/>
                        <line x1="875" y1="0" x2="875" y2="460"/>
                        <line x1="0" y1="115" x2="1000" y2="115"/><line x1="0" y1="230" x2="1000" y2="230"/>
                        <line x1="0" y1="345" x2="1000" y2="345"/>
                    </g>

                    <!-- Landmass: clean flat vector, light on navy -->
                    <g fill="#E2E9F2" stroke="#B9C7D8" stroke-width="1" stroke-linejoin="round" filter="url(#landShadow)">
                        <!-- Sumatera -->
                        <path d="M46,30 Q64,20 76,40 L118,84 Q160,122 196,152 Q228,178 246,214 Q258,244 264,282 Q268,300 256,307 Q244,310 234,292 Q214,254 188,224 Q160,190 132,166 Q104,142 84,112 Q62,80 48,52 Q40,38 46,30 Z"/>
                        <!-- Bangka & Belitung -->
                        <ellipse cx="286" cy="214" rx="10" ry="7"/>
                        <ellipse cx="307" cy="229" rx="8" ry="5.5"/>
                        <!-- Jawa -->
                        <path d="M250,322 Q262,312 284,309 Q318,306 348,315 Q378,323 408,327 Q430,329 440,342 Q444,352 432,355 Q404,358 372,351 Q338,344 306,338 Q276,333 258,331 Q246,328 250,322 Z"/>
                        <!-- Madura -->
                        <ellipse cx="428" cy="322" rx="14" ry="3.5"/>
                        <!-- Kalimantan -->
                        <path d="M326,130 Q332,92 352,62 Q374,28 408,10 Q432,-2 452,6 Q478,18 504,36 Q528,52 534,72 Q538,96 524,124 Q510,150 504,172 Q498,200 488,228 Q478,256 458,260 Q434,258 408,248 Q374,240 352,230 Q336,220 332,196 Q326,168 326,130 Z"/>
                        <!-- Sulawesi -->
                        <path d="M545,158 Q549,126 568,114 Q586,106 604,116 Q628,126 650,122 Q666,118 667,130 Q667,141 650,142 Q628,142 608,150 Q596,156 590,170 Q614,158 640,152 Q658,148 660,162 Q660,174 642,182 Q620,192 602,198 Q608,226 620,248 Q630,262 618,269 Q606,273 598,257 Q588,236 582,216 Q574,242 562,268 Q554,286 540,280 Q530,273 537,252 Q547,216 550,192 Q539,178 545,158 Z"/>
                        <!-- Bali & Nusa Tenggara -->
                        <ellipse cx="452" cy="364" rx="9" ry="6"/>
                        <ellipse cx="472" cy="367" rx="8" ry="6.5"/>
                        <ellipse cx="498" cy="368" rx="24" ry="8"/>
                        <ellipse cx="585" cy="367" rx="34" ry="6"/>
                        <ellipse cx="555" cy="398" rx="16" ry="7"/>
                        <ellipse cx="658" cy="384" rx="40" ry="8" transform="rotate(-25 658 384)"/>
                        <!-- Maluku -->
                        <path d="M703,118 Q713,110 719,124 Q724,136 718,148 Q726,150 733,157 Q729,166 719,160 Q712,155 708,163 Q702,172 697,161 Q693,148 699,134 Q698,124 703,118 Z"/>
                        <ellipse cx="688" cy="245" rx="14" ry="7.5"/>
                        <ellipse cx="740" cy="240" rx="28" ry="7.5"/>
                        <!-- Papua -->
                        <path d="M776,176 Q778,150 802,146 Q826,143 842,160 Q850,148 870,150 Q902,154 932,168 Q966,184 979,214 Q989,250 985,294 Q981,336 969,362 Q959,377 947,364 Q939,348 942,318 Q921,324 899,309 Q871,291 859,264 Q836,261 819,244 Q796,227 787,204 Q778,190 776,176 Z"/>
                    </g>

                    <!-- Region labels -->
                    <g font-family="Inter, sans-serif" font-size="12" fill="#94A3B8" font-weight="600" letter-spacing="3">
                        <text x="112" y="142" transform="rotate(-40 112 142)">SUMATERA</text>
                        <text x="380" y="152">KALIMANTAN</text>
                        <text x="560" y="236" font-size="10">SULAWESI</text>
                        <text x="880" y="248">PAPUA</text>
                        <text x="322" y="342" font-size="10">JAWA</text>
                    </g>
                    <!-- Sea labels -->
                    <g font-family="Inter, sans-serif" font-size="11" fill="#93C5FD" opacity="0.4" font-style="italic" letter-spacing="2">
                        <text x="352" y="288">Laut Jawa</text>
                        <text x="700" y="308">Laut Banda</text>
                    </g>

                    <!-- Routes: Jakarta → Surabaya → Makassar → Manado -->
                    <g fill="none" stroke-linecap="round">
                        <g stroke="#60A5FA" stroke-width="2.5" opacity="0.3">
                            <path d="M283,310 Q343,290 403,335"/>
                            <path d="M403,335 Q475,325 541,276"/>
                            <path d="M541,276 Q596,190 649,130"/>
                        </g>
                        <g stroke="url(#routeGrad)" stroke-width="3" opacity="0.9">
                            <path class="route-flow" d="M283,310 Q343,290 403,335"/>
                            <path class="route-flow route-flow-2" d="M403,335 Q475,325 541,276"/>
                            <path class="route-flow route-flow-3" d="M541,276 Q596,190 649,130"/>
                        </g>
                    </g>

                    <!-- Agent coverage markers (blue) -->
                    <g font-family="Inter, sans-serif">
                        @php
                            $coverageMarkers = [
                                'banda-aceh'  => [47, 34, 'Banda Aceh', 'start'],
                                'medan'       => [120, 95, 'Medan', 'start'],
                                'padang'      => [150, 187, 'Padang', 'start'],
                                'banjarmasin' => [440, 242, 'Banjarmasin', 'start'],
                                'pontianak'   => [333, 165, 'Pontianak', 'start'],
                                'samarinda'   => [493, 176, 'Samarinda', 'start'],
                                'makassar'    => [541, 276, 'Makassar', 'end'],
                                'kendari'     => [612, 252, 'Kendari', 'start'],
                                'palu'        => [548, 186, 'Palu', 'end'],
                                'gorontalo'   => [613, 152, 'Gorontalo', 'end'],
                                'ambon'       => [718, 251, 'Ambon', 'start'],
                                'ternate'     => [701, 146, 'Ternate', 'end'],
                                'jayapura'    => [974, 224, 'Jayapura', 'end'],
                                'sorong'      => [780, 185, 'Sorong', 'start'],
                                'kupang'      => [624, 404, 'Kupang', 'end'],
                                'merauke'     => [960, 364, 'Merauke', 'end'],
                            ];
                        @endphp
                        @foreach($coverageMarkers as $slug => [$x, $y, $label, $anchor])
                        <g class="map-marker" data-marker="{{ $slug }}" transform="translate({{ $x }},{{ $y }})">
                            <circle class="marker-halo" r="12" fill="#3B82F6" opacity="0"/>
                            <g class="marker-scale">
                                <circle r="7" fill="#3B82F6" opacity="0.22"/>
                                <circle r="3.8" fill="#3B82F6" stroke="#fff" stroke-width="1.5"/>
                            </g>
                            <text x="{{ $anchor === 'end' ? -10 : 10 }}" y="4" font-size="10" fill="#334155" text-anchor="{{ $anchor }}" paint-order="stroke" stroke="#E2E9F2" stroke-width="3" style="stroke-opacity:.9">{{ $label }}</text>
                        </g>
                        @endforeach

                        <!-- Office markers (red pins, focal point) -->
                        @php
                            $officeMarkers = [
                                'jakarta'  => [283, 310, 'Jakarta', 'end'],
                                'surabaya' => [403, 335, 'Surabaya', 'start'],
                                'manado'   => [649, 130, 'Manado', 'start'],
                            ];
                        @endphp
                        @foreach($officeMarkers as $slug => [$x, $y, $label, $anchor])
                        <g class="map-marker map-marker-office" data-marker="{{ $slug }}" transform="translate({{ $x }},{{ $y }})">
                            <circle class="marker-halo" r="16" fill="#EF4444" opacity="0"/>
                            <circle class="office-pulse" r="10" fill="none" stroke="#F87171" stroke-width="1.5"/>
                            <g class="marker-scale">
                                <g transform="scale(1.18)">
                                    <ellipse cx="0" cy="1.5" rx="5.5" ry="2" fill="#000" opacity="0.28"/>
                                    <path d="M0,0 C-2,-8 -9,-10 -9,-17 A9,9 0 1 1 9,-17 C9,-10 2,-8 0,0 Z" fill="#EF4444" stroke="#fff" stroke-width="1.5"/>
                                    <circle cx="0" cy="-17" r="3.2" fill="#fff"/>
                                </g>
                            </g>
                            <text x="{{ $anchor === 'end' ? -13 : 13 }}" y="-14" font-size="11" font-weight="700" fill="#1E293B" text-anchor="{{ $anchor }}" paint-order="stroke" stroke="#fff" stroke-width="3.5" style="stroke-opacity:.9">{{ $label }}</text>
                        </g>
                        @endforeach
                    </g>
                </svg>

                <!-- Data Overlay: network scale -->
                <div class="absolute top-4 right-4 hidden sm:block bg-slate-900/70 backdrop-blur-md rounded-xl border border-white/10 shadow-lg px-6 py-5">
                    <div class="flex items-center gap-7">
                        <div>
                            <p class="text-2xl font-bold text-white leading-none tabular-nums">{{ count($cities) }}</p>
                            <p class="text-[11px] text-blue-200 mt-2 uppercase tracking-wider">Kota Layanan</p>
                        </div>
                        <div class="w-px h-10 bg-white/15"></div>
                        <div>
                            <p class="text-2xl font-bold text-white leading-none tabular-nums">3</p>
                            <p class="text-[11px] text-blue-200 mt-2 uppercase tracking-wider">Kantor Cabang</p>
                        </div>
                    </div>
                    <p class="text-xs text-slate-300 mt-4 pt-3 border-t border-white/10 leading-relaxed">Didukung jaringan agent nasional</p>
                </div>

                <!-- Legend -->
                <div class="absolute bottom-4 left-4 bg-slate-900/70 backdrop-blur-md rounded-xl border border-white/10 shadow-lg px-4 py-3 space-y-2">
                    <div class="flex items-center">
                        <svg class="w-3.5 h-3.5 mr-2.5 flex-shrink-0" viewBox="0 0 20 24"><path d="M10,23 C8,15 1,13 1,9 A9,9 0 1 1 19,9 C19,13 12,15 10,23 Z" fill="#EF4444" stroke="#fff" stroke-width="1"/><circle cx="10" cy="9" r="3" fill="#fff"/></svg>
                        <span class="text-xs font-semibold text-white tracking-wide">Kantor Cabang</span>
                    </div>
                    <div class="flex items-center">
                        <span class="w-3.5 h-3.5 mr-2.5 flex items-center justify-center flex-shrink-0"><span class="w-2.5 h-2.5 bg-blue-500 rounded-full ring-2 ring-white/80"></span></span>
                        <span class="text-xs font-medium text-slate-200 tracking-wide">Kota Layanan <span class="text-slate-400">· Agent Network</span></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cities Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
            @foreach($cities as $index => $city)
            <div data-city="{{ \Illuminate\Support\Str::slug($city) }}" class="bg-slate-50 rounded-lg p-3 text-center shadow-sm hover:shadow-md transition-all duration-300 hover:bg-blue-50 cursor-default flex flex-col items-center justify-center {{ in_array($city, ['Jakarta', 'Surabaya', 'Manado']) ? 'border border-blue-200 bg-blue-50/50' : 'border border-slate-100' }}">
                <span class="text-slate-700 font-medium text-sm {{ in_array($city, ['Jakarta', 'Surabaya', 'Manado']) ? 'text-blue-700 font-bold' : '' }}">{{ $city }}</span>
                @if(in_array($city, ['Jakarta', 'Surabaya', 'Manado']))
                    <span class="block text-xs text-blue-500 mt-1">📍 Kantor</span>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</section>


<!-- Our Office Section with WhatsApp - 3 Cards Layout -->
<section id="kontak" class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <span class="text-blue-600 font-semibold text-sm uppercase tracking-wide">Kontak</span>
            <h2 class="text-3xl md:text-4xl font-bold text-slate-800 mt-2 mb-4">Kantor Kami</h2>
            <p class="text-slate-600 max-w-2xl mx-auto">Hubungi tim marketing kami di cabang terdekat via WhatsApp</p>
            <div class="w-20 h-1 bg-blue-600 mx-auto mt-4"></div>
        </div>
        
        <!-- 3 Cards Grid -->
        <div class="grid md:grid-cols-3 gap-6 items-stretch">
            <!-- Surabaya Card -->
            @php($branch = config('contact.branches.surabaya'))
            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 h-full flex flex-col">
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 p-5 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-bold">{{ $branch['short_name'] }}</h3>
                            <p class="text-blue-100 text-sm">Kantor Pusat</p>
                        </div>
                        <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="p-5 flex-grow flex flex-col">
                    <div class="mb-4 flex-grow">
                        <p class="text-slate-600 text-sm leading-relaxed">
                            {!! nl2br(e(str_replace(', ', "\n", $branch['address']))) !!}
                        </p>
                    </div>
                    <div class="border-t border-slate-100 pt-4 mb-4">
                        <p class="text-xs text-slate-500 mb-1">Marketing</p>
                        <p class="font-semibold text-slate-800 text-sm">{{ $branch['contact_person'] ?: 'Tim Marketing' }}</p>
                    </div>
                    <a href="https://wa.me/{{ $branch['whatsapp'] }}?text={{ urlencode('Selamat pagi/siang ' . $branch['contact_person'] . '. Saya ingin menanyakan informasi pengiriman dari ' . $branch['short_name'] . '. Mohon bantuannya, terima kasih.') }}" 
                       target="_blank"
                       class="inline-flex items-center justify-center w-full px-4 py-3 bg-green-500 text-white font-semibold text-sm rounded-xl hover:bg-green-600 transition-all shadow-md hover:shadow-lg mt-auto">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        Chat WhatsApp
                    </a>
                </div>
            </div>

            <!-- Jakarta Card -->
            @php($branch = config('contact.branches.jakarta'))
            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 h-full flex flex-col">
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 p-5 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-bold">{{ $branch['short_name'] }}</h3>
                            <p class="text-blue-100 text-sm">Kantor Cabang</p>
                        </div>
                        <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="p-5 flex-grow flex flex-col">
                    <div class="mb-4 flex-grow">
                        <p class="text-slate-600 text-sm leading-relaxed">
                            {!! nl2br(e(str_replace(', ', "\n", $branch['address']))) !!}
                        </p>
                    </div>
                    <div class="border-t border-slate-100 pt-4 mb-4">
                        <p class="text-xs text-slate-500 mb-1">Marketing</p>
                        <p class="font-semibold text-slate-800 text-sm">{{ $branch['contact_person'] ?: 'Tim Marketing' }}</p>
                    </div>
                    <a href="https://wa.me/{{ $branch['whatsapp'] }}?text={{ urlencode('Selamat pagi/siang ' . $branch['contact_person'] . '. Saya ingin menanyakan informasi pengiriman dari ' . $branch['short_name'] . '. Mohon bantuannya, terima kasih.') }}" 
                       target="_blank"
                       class="inline-flex items-center justify-center w-full px-4 py-3 bg-green-500 text-white font-semibold text-sm rounded-xl hover:bg-green-600 transition-all shadow-md hover:shadow-lg mt-auto">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        Chat WhatsApp
                    </a>
                </div>
            </div>

            <!-- Manado Card -->
            @php($branch = config('contact.branches.manado'))
            <div class="bg-white rounded-2xl shadow-lg border border-slate-100 overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 h-full flex flex-col">
                <div class="bg-gradient-to-r from-blue-400 to-blue-500 p-5 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-bold">{{ $branch['short_name'] }}</h3>
                            <p class="text-blue-100 text-sm">Kantor Cabang</p>
                        </div>
                        <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="p-5 flex-grow flex flex-col">
                    <div class="mb-4 flex-grow">
                        <p class="text-slate-600 text-sm leading-relaxed">
                            {!! nl2br(e(str_replace(', ', "\n", $branch['address']))) !!}
                        </p>
                    </div>
                    <div class="border-t border-slate-100 pt-4 mb-4">
                        <p class="text-xs text-slate-500 mb-1">Marketing</p>
                        <p class="font-semibold text-slate-800 text-sm">{{ $branch['contact_person'] ?: 'Tim Marketing' }}</p>
                    </div>
                    <a href="https://wa.me/{{ $branch['whatsapp'] }}?text={{ urlencode('Selamat pagi/siang ' . $branch['contact_person'] . '. Saya ingin menanyakan informasi pengiriman dari ' . $branch['short_name'] . '. Mohon bantuannya, terima kasih.') }}" 
                       target="_blank"
                       class="inline-flex items-center justify-center w-full px-4 py-3 bg-green-500 text-white font-semibold text-sm rounded-xl hover:bg-green-600 transition-all shadow-md hover:shadow-lg mt-auto">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        Chat WhatsApp
                    </a>
                </div>
            </div>
        </div>

        <!-- Jam Operasional -->
        <div class="mt-8 bg-slate-50 rounded-xl p-6 text-center">
            <div class="grid sm:grid-cols-2 gap-4 text-sm max-w-lg mx-auto">
                <div class="flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-slate-600">Senin-Jumat: 08:00-17:00 WIB</span>
                </div>
                <div class="flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-slate-600">Sabtu: 08:00-12:00 WIB</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section - Better Copy -->
<section class="py-20 relative overflow-hidden">
    <!-- Background Image with Overlay -->
    <div class="absolute inset-0 z-0">
        <!-- Container/Cargo Background Image -->
        <div class="absolute inset-0 bg-cover bg-center bg-no-repeat" style="background-image: url('https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?q=80&w=2070&auto=format&fit=crop');"></div>
        <!-- Dark Blue Gradient Overlay -->
        <div class="absolute inset-0 bg-gradient-to-br from-blue-900/95 via-blue-800/90 to-blue-600/85"></div>
    </div>
    <!-- Decorative Elements -->
    <div class="absolute top-0 left-0 w-64 h-64 bg-white/5 rounded-full -translate-x-1/2 -translate-y-1/2 z-0"></div>
    <div class="absolute bottom-0 right-0 w-96 h-96 bg-white/5 rounded-full translate-x-1/3 translate-y-1/3 z-0"></div>
    
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
        <h2 class="text-3xl md:text-5xl font-bold text-white mb-6 leading-tight">
            Butuh Pengiriman<br>
            <span class="text-blue-200">Cepat & Aman?</span>
        </h2>
        <p class="text-blue-100 text-lg mb-8 max-w-2xl mx-auto">
            Percayakan pada PT Jaya Sakti Sejati. Kami siap membantu pengiriman domestik dan internasional Anda dengan layanan terbaik.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('tracking') }}" class="inline-flex items-center justify-center px-8 py-4 bg-white text-blue-700 font-bold rounded-xl hover:bg-blue-50 transition-all shadow-xl hover:shadow-2xl transform hover:-translate-y-0.5">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Lacak Pengiriman
            </a>
            <a href="#kontak" class="inline-flex items-center justify-center px-8 py-4 border-2 border-white text-white font-bold rounded-xl hover:bg-white/10 transition-all">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
                Hubungi Kami
            </a>
        </div>
    </div>
</section>

@push('styles')
<style>
    /* Cara Kerja Pengiriman: timeline stage hover emphasis */
    #cara-kerja .group svg {
        transition: transform 0.3s ease;
    }
    #cara-kerja .group:hover svg {
        transform: scale(1.1);
    }

    /* Coverage map: marker emphasis + premium micro-interactions */
    #coverage-map-svg .marker-scale,
    #coverage-map-svg .marker-halo {
        transform-box: fill-box;
        transform-origin: center;
        transition: transform 0.3s ease, opacity 0.3s ease;
    }
    #coverage-map-svg .map-marker-office .marker-scale {
        transform-origin: center bottom;
        filter: drop-shadow(0 3px 5px rgba(239, 68, 68, 0.35));
    }
    #coverage-map-svg .map-marker:not(.map-marker-office) .marker-scale {
        filter: drop-shadow(0 0 3px rgba(96, 165, 250, 0.65));
    }
    #coverage-map-svg .map-marker.active .marker-scale {
        transform: scale(1.35);
    }
    #coverage-map-svg .map-marker.active .marker-halo {
        opacity: 0.2;
        transform: scale(1.15);
    }
    #coverage-map-svg.map-zoom {
        transform: scale(1.03);
    }
    #coverage-map-svg .office-pulse {
        transform-box: fill-box;
        transform-origin: center;
        animation: officePulse 3.2s cubic-bezier(0.25, 0.46, 0.45, 0.94) infinite;
    }
    @keyframes officePulse {
        0%   { transform: scale(0.55); opacity: 0.5; }
        75%  { transform: scale(1.7); opacity: 0; }
        100% { transform: scale(1.7); opacity: 0; }
    }
    #coverage-map-svg .route-flow {
        stroke-dasharray: 18 150;
        animation: routeFlow 4.2s linear infinite;
    }
    #coverage-map-svg .route-flow-2 { animation-delay: 1.4s; }
    #coverage-map-svg .route-flow-3 { animation-delay: 2.8s; }
    @keyframes routeFlow {
        to { stroke-dashoffset: -168; }
    }
    @media (prefers-reduced-motion: reduce) {
        #coverage-map-svg .office-pulse,
        #coverage-map-svg .route-flow {
            animation: none;
        }
        #coverage-map-svg .route-flow {
            stroke-dasharray: none;
            opacity: 0.4;
        }
        #coverage-map-svg .office-pulse {
            opacity: 0;
        }
    }
</style>
@endpush

<!-- Counter Animation Script -->
@push('scripts')
<script>
    // Coverage map: hovering a city card highlights its marker
    (function () {
        const mapSvg = document.getElementById('coverage-map-svg');
        if (!mapSvg) return;

        document.querySelectorAll('[data-city]').forEach(card => {
            const marker = mapSvg.querySelector('[data-marker="' + card.dataset.city + '"]');
            if (!marker) return;

            card.addEventListener('mouseenter', () => {
                marker.classList.add('active');
                mapSvg.classList.add('map-zoom');
            });
            card.addEventListener('mouseleave', () => {
                marker.classList.remove('active');
                mapSvg.classList.remove('map-zoom');
            });
        });
    })();

    // Counter Animation
    const counters = document.querySelectorAll('.counter');
    
    const animateCounter = (counter) => {
        const target = parseInt(counter.getAttribute('data-target'));
        const duration = 2000; // 2 seconds
        const increment = target / (duration / 16); // 60fps
        
        let current = 0;
        const updateCounter = () => {
            current += increment;
            if (current < target) {
                counter.textContent = Math.floor(current);
                requestAnimationFrame(updateCounter);
            } else {
                counter.textContent = target;
            }
        };
        
        updateCounter();
    };
    
    // Intersection Observer for triggering animation
    const observerOptions = {
        threshold: 0.5
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounter(entry.target);
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    counters.forEach(counter => observer.observe(counter));
</script>
@endpush

@endsection
