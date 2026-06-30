@extends('jsl.layouts.app', ['settings' => $settings])
@section('title', ($settings->site_name ?? 'Jaya Sakti Line') . ' - Contact')

@section('content')
<section class="jsl-gradient-hero text-white pt-32 pb-20 md:pt-40 md:pb-28">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <p class="text-sm font-semibold uppercase tracking-wider mb-4 bg-white/10 inline-block px-4 py-1.5 rounded-full border border-white/20">Contact</p>
        <h1 class="text-4xl md:text-5xl font-extrabold mb-6">Get in Touch</h1>
        <p class="text-lg text-slate-300 max-w-2xl">Have a question or want to inquire about a vessel? Send us a message.</p>
    </div>
</section>

<section class="jsl-section">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
            <!-- Contact Info -->
            <div>
                <h2 class="text-2xl font-bold text-slate-900 mb-6">Contact Information</h2>
                <div class="space-y-6">
                    @if($settings->contact_address)
                    <div class="flex items-start space-x-4">
                        <div class="w-12 h-12 jsl-primary rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-slate-900 mb-1">Address</h3>
                            <p class="text-slate-600 text-sm">{{ $settings->contact_address }}</p>
                        </div>
                    </div>
                    @endif
                    @if($settings->contact_phone_display)
                    <div class="flex items-start space-x-4">
                        <div class="w-12 h-12 jsl-primary rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-slate-900 mb-1">Phone</h3>
                            <p class="text-slate-600 text-sm">{{ $settings->contact_phone_display }}</p>
                        </div>
                    </div>
                    @endif
                    @if($settings->contact_email_display)
                    <div class="flex items-start space-x-4">
                        <div class="w-12 h-12 jsl-primary rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-slate-900 mb-1">Email</h3>
                            <p class="text-slate-600 text-sm">{{ $settings->contact_email_display }}</p>
                        </div>
                    </div>
                    @endif
                    @if($settings->broker_whatsapp)
                    <div class="flex items-start space-x-4">
                        <div class="w-12 h-12 jsl-primary rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-slate-900 mb-1">WhatsApp</h3>
                            <p class="text-slate-600 text-sm">{{ $settings->broker_whatsapp }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Contact Form -->
            <div>
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-8">
                    <h2 class="text-2xl font-bold text-slate-900 mb-6">Send Us a Message</h2>

                    @if(session('success'))
                    <div class="mb-6 bg-green-50 border border-green-200 text-green-800 rounded-xl p-4 text-sm">
                        {{ session('success') }}
                    </div>
                    @endif

                    @if(session('error'))
                    <div class="mb-6 bg-red-50 border border-red-200 text-red-800 rounded-xl p-4 text-sm">
                        {{ session('error') }}
                    </div>
                    @endif

                    <form action="{{ route('jsl.contact.store') }}" method="POST" class="space-y-5">
                        @csrf
                        <div>
                            <label for="name" class="block text-sm font-medium text-slate-700 mb-1.5">Name <span class="text-red-500">*</span></label>
                            <input type="text" id="name" name="name" required value="{{ old('name') }}" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-slate-900 focus:border-[#0137A1] focus:ring-2 focus:ring-[#0137A1]/20 outline-none transition-all @error('name') border-red-300 @enderror">
                            @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="company" class="block text-sm font-medium text-slate-700 mb-1.5">Company</label>
                            <input type="text" id="company" name="company" value="{{ old('company') }}" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-slate-900 focus:border-[#0137A1] focus:ring-2 focus:ring-[#0137A1]/20 outline-none transition-all">
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                                <input type="email" id="email" name="email" value="{{ old('email') }}" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-slate-900 focus:border-[#0137A1] focus:ring-2 focus:ring-[#0137A1]/20 outline-none transition-all">
                            </div>
                            <div>
                                <label for="phone" class="block text-sm font-medium text-slate-700 mb-1.5">Phone</label>
                                <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-slate-900 focus:border-[#0137A1] focus:ring-2 focus:ring-[#0137A1]/20 outline-none transition-all">
                            </div>
                        </div>
                        <div>
                            <label for="message" class="block text-sm font-medium text-slate-700 mb-1.5">Message <span class="text-red-500">*</span></label>
                            <textarea id="message" name="message" required rows="5" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-slate-900 focus:border-[#0137A1] focus:ring-2 focus:ring-[#0137A1]/20 outline-none transition-all @error('message') border-red-300 @enderror">{{ old('message') }}</textarea>
                            @error('message')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div class="flex items-start space-x-3">
                            <input type="checkbox" id="consent" name="consent_given" required class="mt-1 rounded border-slate-300 text-[#0137A1] focus:ring-[#0137A1]">
                            <label for="consent" class="text-sm text-slate-600">I consent to having this website store my submitted information so they can respond to my inquiry. <span class="text-red-500">*</span></label>
                        </div>
                        <button type="submit" class="jsl-btn-primary w-full px-6 py-3.5 font-semibold rounded-xl text-lg">
                            Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
