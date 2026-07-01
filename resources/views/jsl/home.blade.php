@extends('jsl.layouts.app', ['settings' => $settings])
@section('title', ($settings->site_name ?? 'Jaya Sakti Line') . ' - Home')

@section('content')
<!-- Hero Section -->
<section class="relative jsl-gradient-hero text-white pt-32 pb-20 md:pt-40 md:pb-32 overflow-hidden">
    <div class="absolute inset-0 opacity-10">
        <img src="/storage/jsl/placeholders/m2.jpg" alt="" class="w-full h-full object-cover" onerror="this.style.display='none'">
    </div>
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl">
            <p class="jsl-primary-text text-sm font-semibold uppercase tracking-wider mb-4 bg-white/10 inline-block px-4 py-1.5 rounded-full border border-white/20">
                Marine Vessel Trading & Chartering
            </p>
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold leading-tight mb-6 animate-fade-in-up">
                {{ $settings->tagline ?? 'Your Trusted Partner in Marine Vessels' }}
            </h1>
            <p class="text-lg md:text-xl text-slate-300 mb-8 leading-relaxed max-w-2xl animate-fade-in-up delay-100">
                {{ strip_tags($profile->overview ?? 'We specialize in the trading and chartering of marine vessels across Indonesian waters and Southeast Asia, connecting buyers and sellers with transparency and expertise.') }}
            </p>
            <div class="flex flex-col sm:flex-row gap-4 animate-fade-in-up delay-200">
                <a href="{{ route('jsl.trading.index') }}" class="jsl-btn-primary inline-flex items-center justify-center px-8 py-3.5 font-semibold rounded-xl text-lg">
                    View Vessels for Sale
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </a>
                <a href="{{ route('jsl.contact') }}" class="inline-flex items-center justify-center px-8 py-3.5 border-2 border-white/30 text-white font-semibold rounded-xl text-lg hover:bg-white/10 transition-all">
                    Contact Us
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Stats Bar -->
<section class="bg-white border-b border-slate-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
            <div>
                <p class="text-3xl md:text-4xl font-extrabold jsl-primary-text">30+</p>
                <p class="text-slate-500 text-sm mt-1">Years Experience</p>
            </div>
            <div>
                <p class="text-3xl md:text-4xl font-extrabold jsl-primary-text">50+</p>
                <p class="text-slate-500 text-sm mt-1">Vessels Traded</p>
            </div>
            <div>
                <p class="text-3xl md:text-4xl font-extrabold jsl-primary-text">100%</p>
                <p class="text-slate-500 text-sm mt-1">Client Satisfaction</p>
            </div>
            <div>
                <p class="text-3xl md:text-4xl font-extrabold jsl-primary-text">24/7</p>
                <p class="text-slate-500 text-sm mt-1">Support</p>
            </div>
        </div>
    </div>
</section>

<!-- About Preview -->
<section class="jsl-section">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div>
                <p class="jsl-primary-text text-sm font-semibold uppercase tracking-wider mb-3">About Us</p>
                <h2 class="text-3xl md:text-4xl font-extrabold text-slate-900 mb-6">
                    A Legacy of Maritime Excellence
                </h2>
                <div class="text-slate-600 leading-relaxed space-y-4">
                    <p>{!! $profile->about ?? 'Jaya Sakti Line has been a trusted name in the maritime industry, specializing in vessel trading and chartering services. With decades of experience, we provide comprehensive solutions for buyers and sellers of marine vessels.' !!}</p>
                </div>
                <div class="mt-8 grid grid-cols-2 gap-4">
                    <div class="bg-slate-50 rounded-xl p-5 border border-slate-100">
                        <h3 class="font-semibold text-slate-900 mb-2">Our Vision</h3>
                        <p class="text-sm text-slate-600">{{ $profile->vision ?? 'To be the leading marine vessel trading platform in Southeast Asia.' }}</p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-5 border border-slate-100">
                        <h3 class="font-semibold text-slate-900 mb-2">Our Mission</h3>
                        <p class="text-sm text-slate-600">{{ $profile->mission ?? 'To connect buyers and sellers with transparency, expertise, and integrity.' }}</p>
                    </div>
                </div>
                <a href="{{ route('jsl.about') }}" class="mt-8 inline-flex items-center jsl-link jsl-primary-text font-semibold">
                    Learn more about us
                    <svg class="w-5 h-5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </a>
            </div>
            <div class="relative">
                <div class="aspect-[4/3] rounded-2xl overflow-hidden shadow-2xl">
                    <img src="/storage/jsl/placeholders/m3.jpg" alt="Marine vessel" class="w-full h-full object-cover" onerror="this.src='{{ asset('images/logo.png') }}'">
                </div>
                <div class="absolute -bottom-6 -left-6 bg-white rounded-xl shadow-xl p-6 border border-slate-100 hidden md:block">
                    <p class="text-3xl font-extrabold jsl-primary-text">30+</p>
                    <p class="text-slate-500 text-sm">Years in Maritime</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Services Preview -->
@if($services->isNotEmpty())
<section class="bg-slate-50 jsl-section">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-12">
            <p class="jsl-primary-text text-sm font-semibold uppercase tracking-wider mb-3">Our Services</p>
            <h2 class="text-3xl md:text-4xl font-extrabold text-slate-900">What We Offer</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach($services->take(3) as $service)
            <div class="jsl-card bg-white rounded-2xl p-8 border border-slate-100">
                <div class="w-12 h-12 jsl-primary rounded-xl flex items-center justify-center mb-5">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-3">{{ $service->title }}</h3>
                <p class="text-slate-600 text-sm leading-relaxed">{!! strip_tags($service->description ?? '') !!}</p>
            </div>
            @endforeach
        </div>
        <div class="text-center mt-10">
            <a href="{{ route('jsl.services') }}" class="jsl-btn-outline inline-flex items-center px-6 py-3 font-semibold rounded-xl">
                View All Services
            </a>
        </div>
    </div>
</section>
@endif

<!-- Featured Vessels -->
@if($vessels->isNotEmpty())
<section class="jsl-section">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-end justify-between mb-10">
            <div>
                <p class="jsl-primary-text text-sm font-semibold uppercase tracking-wider mb-3">Featured Listings</p>
                <h2 class="text-3xl md:text-4xl font-extrabold text-slate-900">Vessels for Sale</h2>
            </div>
            <a href="{{ route('jsl.trading.index') }}" class="mt-4 md:mt-0 jsl-link jsl-primary-text font-semibold inline-flex items-center">
                View All Vessels
                <svg class="w-5 h-5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($vessels as $vessel)
            <a href="{{ route('jsl.trading.show', $vessel->public_ref_code) }}" class="jsl-card bg-white rounded-2xl overflow-hidden border border-slate-100 group">
                <div class="aspect-[4/3] bg-slate-100 overflow-hidden">
                    @if($vessel->images->isNotEmpty() && $vessel->images->first()->mediaAsset)
                        <img src="{{ $vessel->images->first()->mediaAsset->url('medium') ?? $vessel->images->first()->mediaAsset->url() }}" alt="{{ $vessel->public_ref_code }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    @else
                        <div class="w-full h-full flex items-center justify-center">
                            <svg class="w-20 h-20 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                    @endif
                </div>
                <div class="p-6">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold uppercase tracking-wider jsl-primary-text bg-blue-50 px-3 py-1 rounded-full">{{ ucfirst($vessel->vessel_type) }}</span>
                        <span class="text-xs text-slate-400">{{ $vessel->public_ref_code }}</span>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">{{ ucfirst($vessel->vessel_type) }} - {{ $vessel->year_built ?? 'N/A' }}</h3>
                    <div class="grid grid-cols-2 gap-2 text-sm text-slate-500 mt-4">
                        @if($vessel->flag_registry)<div><span class="text-slate-400">Flag:</span> {{ $vessel->flag_registry }}</div>@endif
                        @if($vessel->gross_tonnage)<div><span class="text-slate-400">GT:</span> {{ number_format($vessel->gross_tonnage, 0) }}</div>@endif
                        @if($vessel->loa_length)<div><span class="text-slate-400">LOA:</span> {{ $vessel->loa_length }}m</div>@endif
                        @if($vessel->trading_area)<div><span class="text-slate-400">Area:</span> {{ $vessel->trading_area }}</div>@endif
                    </div>
                </div>
            </a>
            @endforeach
        </div>
    </div>
</section>
@endif

<!-- CTA Section -->
<section class="jsl-gradient text-white py-20">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl md:text-4xl font-extrabold mb-6">Ready to Find Your Next Vessel?</h2>
        <p class="text-lg text-slate-300 mb-8 max-w-2xl mx-auto">
            Our experienced broker team is ready to help you find the right vessel or connect you with qualified buyers.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('jsl.contact') }}" class="inline-flex items-center justify-center px-8 py-3.5 bg-white text-[#0137A1] font-semibold rounded-xl hover:bg-slate-100 transition-all text-lg">
                Get in Touch
            </a>
            <a href="{{ route('jsl.trading.index') }}" class="inline-flex items-center justify-center px-8 py-3.5 border-2 border-white/30 text-white font-semibold rounded-xl hover:bg-white/10 transition-all text-lg">
                Browse Listings
            </a>
        </div>
    </div>
</section>
@endsection
