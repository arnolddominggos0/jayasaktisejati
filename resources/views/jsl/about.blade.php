@extends('jsl.layouts.app', ['settings' => $settings])
@section('title', ($settings->site_name ?? 'Jaya Sakti Line') . ' - About Us')

@section('content')
<section class="jsl-gradient-hero text-white pt-32 pb-20 md:pt-40 md:pb-28">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <p class="text-sm font-semibold uppercase tracking-wider mb-4 bg-white/10 inline-block px-4 py-1.5 rounded-full border border-white/20">About Us</p>
        <h1 class="text-4xl md:text-5xl font-extrabold mb-6">Our Story</h1>
        <p class="text-lg text-slate-300 max-w-2xl">{{ $settings->tagline ?? 'Trusted maritime vessel trading and chartering partner' }}</p>
    </div>
</section>

<section class="jsl-section">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="prose prose-lg max-w-none">
            <h2 class="text-2xl font-bold text-slate-900 mb-4">About Jaya Sakti Line</h2>
            <div class="text-slate-600 leading-relaxed space-y-4">
                <p>{!! $profile->about ?? 'Jaya Sakti Line has been a trusted name in the maritime industry, specializing in vessel trading and chartering services across Indonesian waters and Southeast Asia.' !!}</p>
                <p>{!! $profile->overview ?? 'With decades of collective experience in the maritime sector, our team provides comprehensive solutions for buyers and sellers of marine vessels, ensuring transparency, expertise, and integrity in every transaction.' !!}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-12">
            <div class="bg-slate-50 rounded-2xl p-8 border border-slate-100">
                <div class="w-12 h-12 jsl-primary rounded-xl flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-3">Our Vision</h3>
                <p class="text-slate-600 leading-relaxed">{{ $profile->vision ?? 'To be the leading marine vessel trading platform in Southeast Asia, known for trust, transparency, and excellence.' }}</p>
            </div>
            <div class="bg-slate-50 rounded-2xl p-8 border border-slate-100">
                <div class="w-12 h-12 jsl-primary rounded-xl flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-3">Our Mission</h3>
                <p class="text-slate-600 leading-relaxed">{{ $profile->mission ?? 'To connect buyers and sellers with transparency, expertise, and integrity, ensuring fair and successful transactions for all parties.' }}</p>
            </div>
        </div>
    </div>
</section>

<section class="bg-slate-50 jsl-section">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
            <div>
                <p class="text-4xl font-extrabold jsl-primary-text">30+</p>
                <p class="text-slate-500 text-sm mt-2">Years Experience</p>
            </div>
            <div>
                <p class="text-4xl font-extrabold jsl-primary-text">50+</p>
                <p class="text-slate-500 text-sm mt-2">Vessels Traded</p>
            </div>
            <div>
                <p class="text-4xl font-extrabold jsl-primary-text">15+</p>
                <p class="text-slate-500 text-sm mt-2">Countries Served</p>
            </div>
            <div>
                <p class="text-4xl font-extrabold jsl-primary-text">100%</p>
                <p class="text-slate-500 text-sm mt-2">Client Satisfaction</p>
            </div>
        </div>
    </div>
</section>
@endsection
