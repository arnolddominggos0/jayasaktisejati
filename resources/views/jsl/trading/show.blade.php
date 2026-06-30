@extends('jsl.layouts.app', ['settings' => $settings])
@section('title', $vessel->public_ref_code . ' - ' . ucfirst($vessel->vessel_type))

@section('content')
<section class="jsl-gradient-hero text-white pt-32 pb-20 md:pt-36 md:pb-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="flex items-center space-x-2 text-sm text-slate-300 mb-6">
            <a href="{{ route('jsl.trading.index') }}" class="hover:text-white transition-colors">Vessels</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-white">{{ $vessel->public_ref_code }}</span>
        </nav>
        <div class="flex items-center gap-4 mb-4">
            <span class="text-sm font-semibold uppercase tracking-wider bg-white/10 px-4 py-1.5 rounded-full border border-white/20">{{ ucfirst($vessel->vessel_type) }}</span>
            <span class="text-sm text-slate-300">{{ $vessel->public_ref_code }}</span>
        </div>
        <h1 class="text-3xl md:text-4xl font-extrabold">{{ ucfirst($vessel->vessel_type) }} @if($vessel->year_built) - {{ $vessel->year_built }}@endif</h1>
    </div>
</section>

<section class="jsl-section">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
            <!-- Images & Description -->
            <div class="lg:col-span-2 space-y-8">
                @if($vessel->images->isNotEmpty())
                <div>
                    <div class="aspect-[16/9] rounded-2xl overflow-hidden bg-slate-100 mb-4">
                        @php $primaryImage = $vessel->images->first(); @endphp
                        <img src="{{ $primaryImage->mediaAsset?->url('large') ?? $primaryImage->mediaAsset?->url() }}" alt="{{ $vessel->public_ref_code }}" class="w-full h-full object-cover">
                    </div>
                    @if($vessel->images->count() > 1)
                    <div class="grid grid-cols-4 gap-3">
                        @foreach($vessel->images->skip(1) as $img)
                        <div class="aspect-square rounded-lg overflow-hidden bg-slate-100 cursor-pointer hover:opacity-80 transition-opacity">
                            <img src="{{ $img->mediaAsset?->url('thumbnail') ?? $img->mediaAsset?->url() }}" alt="{{ $img->alt_text ?? $vessel->public_ref_code }}" class="w-full h-full object-cover">
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
                @endif

                <div>
                    <h2 class="text-2xl font-bold text-slate-900 mb-4">Description</h2>
                    <div class="text-slate-600 leading-relaxed">{!! $vessel->marketing_description ?? 'No description available.' !!}</div>
                </div>
            </div>

            <!-- Specifications Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sticky top-24">
                    <h2 class="text-xl font-bold text-slate-900 mb-6">Specifications</h2>
                    <dl class="space-y-4">
                        @if($vessel->year_built)
                        <div class="flex justify-between items-center pb-3 border-b border-slate-100">
                            <dt class="text-slate-500 text-sm">Year Built</dt>
                            <dd class="font-semibold text-slate-900">{{ $vessel->year_built }}</dd>
                        </div>
                        @endif
                        @if($vessel->flag_registry)
                        <div class="flex justify-between items-center pb-3 border-b border-slate-100">
                            <dt class="text-slate-500 text-sm">Flag Registry</dt>
                            <dd class="font-semibold text-slate-900">{{ $vessel->flag_registry }}</dd>
                        </div>
                        @endif
                        @if($vessel->gross_tonnage)
                        <div class="flex justify-between items-center pb-3 border-b border-slate-100">
                            <dt class="text-slate-500 text-sm">Gross Tonnage</dt>
                            <dd class="font-semibold text-slate-900">{{ number_format($vessel->gross_tonnage, 0) }} GT</dd>
                        </div>
                        @endif
                        @if($vessel->deadweight)
                        <div class="flex justify-between items-center pb-3 border-b border-slate-100">
                            <dt class="text-slate-500 text-sm">Deadweight</dt>
                            <dd class="font-semibold text-slate-900">{{ number_format($vessel->deadweight, 0) }} DWT</dd>
                        </div>
                        @endif
                        @if($vessel->loa_length)
                        <div class="flex justify-between items-center pb-3 border-b border-slate-100">
                            <dt class="text-slate-500 text-sm">LOA</dt>
                            <dd class="font-semibold text-slate-900">{{ $vessel->loa_length }} m</dd>
                        </div>
                        @endif
                        @if($vessel->beam)
                        <div class="flex justify-between items-center pb-3 border-b border-slate-100">
                            <dt class="text-slate-500 text-sm">Beam</dt>
                            <dd class="font-semibold text-slate-900">{{ $vessel->beam }} m</dd>
                        </div>
                        @endif
                        @if($vessel->draft)
                        <div class="flex justify-between items-center pb-3 border-b border-slate-100">
                            <dt class="text-slate-500 text-sm">Draft</dt>
                            <dd class="font-semibold text-slate-900">{{ $vessel->draft }} m</dd>
                        </div>
                        @endif
                        @if($vessel->engine_power)
                        <div class="flex justify-between items-center pb-3 border-b border-slate-100">
                            <dt class="text-slate-500 text-sm">Engine Power</dt>
                            <dd class="font-semibold text-slate-900">{{ $vessel->engine_power }}</dd>
                        </div>
                        @endif
                        @if($vessel->trading_area)
                        <div class="pb-3 border-b border-slate-100">
                            <dt class="text-slate-500 text-sm mb-1">Trading Area</dt>
                            <dd class="font-semibold text-slate-900">{{ $vessel->trading_area }}</dd>
                        </div>
                        @endif
                    </dl>

                    <div class="mt-8 space-y-3">
                        <a href="{{ route('jsl.contact') }}?vessel={{ $vessel->public_ref_code }}" class="jsl-btn-primary w-full inline-flex items-center justify-center px-6 py-3.5 font-semibold rounded-xl">
                            Inquire About This Vessel
                        </a>
                        @if($settings->broker_whatsapp)
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $settings->broker_whatsapp) }}?text=I%20am%20interested%20in%20{{ $vessel->public_ref_code }}" target="_blank" rel="noopener" class="w-full inline-flex items-center justify-center px-6 py-3.5 border-2 border-slate-200 text-slate-700 font-semibold rounded-xl hover:border-slate-300 transition-all">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                            WhatsApp Broker
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
