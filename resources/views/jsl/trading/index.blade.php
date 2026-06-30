@extends('jsl.layouts.app', ['settings' => $settings])
@section('title', ($settings->site_name ?? 'Jaya Sakti Line') . ' - Vessels for Sale')

@section('content')
<section class="jsl-gradient-hero text-white pt-32 pb-20 md:pt-40 md:pb-28">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <p class="text-sm font-semibold uppercase tracking-wider mb-4 bg-white/10 inline-block px-4 py-1.5 rounded-full border border-white/20">Vessel Listings</p>
        <h1 class="text-4xl md:text-5xl font-extrabold mb-6">Vessels for Sale</h1>
        <p class="text-lg text-slate-300 max-w-2xl">Browse our available vessels for trading and chartering</p>
    </div>
</section>

<section class="jsl-section">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if($vessels->isNotEmpty())
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
                        @if($vessel->deadweight)<div><span class="text-slate-400">DWT:</span> {{ number_format($vessel->deadweight, 0) }}</div>@endif
                        @if($vessel->trading_area)<div class="col-span-2"><span class="text-slate-400">Trading Area:</span> {{ $vessel->trading_area }}</div>@endif
                    </div>
                    <div class="mt-5 pt-5 border-t border-slate-100">
                        <span class="jsl-primary-text font-semibold text-sm inline-flex items-center group-hover:underline">
                            View Details
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                        </span>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
        @else
        <div class="text-center py-20">
            <svg class="w-20 h-20 text-slate-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>
            <p class="text-slate-400 text-lg">No vessels currently available for sale.</p>
            <p class="text-slate-400 text-sm mt-2">Please check back later or contact us directly.</p>
            <a href="{{ route('jsl.contact') }}" class="mt-6 jl-btn-outline inline-flex items-center px-6 py-3 font-semibold rounded-xl">Contact Us</a>
        </div>
        @endif
    </div>
</section>
@endsection
