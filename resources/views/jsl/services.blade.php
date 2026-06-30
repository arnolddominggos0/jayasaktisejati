@extends('jsl.layouts.app', ['settings' => $settings])
@section('title', ($settings->site_name ?? 'Jaya Sakti Line') . ' - Services')

@section('content')
<section class="jsl-gradient-hero text-white pt-32 pb-20 md:pt-40 md:pb-28">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <p class="text-sm font-semibold uppercase tracking-wider mb-4 bg-white/10 inline-block px-4 py-1.5 rounded-full border border-white/20">Services</p>
        <h1 class="text-4xl md:text-5xl font-extrabold mb-6">Our Services</h1>
        <p class="text-lg text-slate-300 max-w-2xl">Comprehensive maritime solutions tailored to your needs</p>
    </div>
</section>

<section class="jsl-section">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if($services->isNotEmpty())
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($services as $service)
            <div class="jsl-card bg-white rounded-2xl p-8 border border-slate-100">
                <div class="w-14 h-14 jsl-primary rounded-2xl flex items-center justify-center mb-6">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-3">{{ $service->title }}</h3>
                <div class="text-slate-600 text-sm leading-relaxed">{!! $service->description ?? '' !!}</div>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-20">
            <svg class="w-20 h-20 text-slate-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            <p class="text-slate-400 text-lg">Services will be available soon.</p>
        </div>
        @endif
    </div>
</section>

<section class="jsl-gradient text-white py-20">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl md:text-4xl font-extrabold mb-6">Need a Custom Solution?</h2>
        <p class="text-lg text-slate-300 mb-8 max-w-2xl mx-auto">
            Contact our team to discuss your specific maritime requirements.
        </p>
        <a href="{{ route('jsl.contact') }}" class="inline-flex items-center justify-center px-8 py-3.5 bg-white text-[#0137A1] font-semibold rounded-xl hover:bg-slate-100 transition-all text-lg">
            Contact Us
        </a>
    </div>
</section>
@endsection
