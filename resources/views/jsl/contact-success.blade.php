@extends('jsl.layouts.app', ['settings' => $settings])
@section('title', 'Inquiry Submitted - ' . ($settings->site_name ?? 'Jaya Sakti Line'))

@section('content')
<section class="jsl-gradient-hero text-white pt-32 pb-20 md:pt-40 md:pb-32">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="w-20 h-20 bg-white/10 rounded-full flex items-center justify-center mx-auto mb-8 border border-white/20">
            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h1 class="text-3xl md:text-4xl font-extrabold mb-6">Thank You!</h1>
        <p class="text-lg text-slate-300 mb-8 max-w-xl mx-auto">
            Your inquiry has been submitted successfully. Our team will contact you shortly.
        </p>
        <a href="{{ route('jsl.home') }}" class="inline-flex items-center justify-center px-8 py-3.5 bg-white text-[#0137A1] font-semibold rounded-xl hover:bg-slate-100 transition-all text-lg">
            Back to Home
        </a>
    </div>
</section>
@endsection
