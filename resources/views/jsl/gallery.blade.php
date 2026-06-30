@extends('jsl.layouts.app', ['settings' => $settings])
@section('title', ($settings->site_name ?? 'Jaya Sakti Line') . ' - Gallery')

@section('content')
<section class="jsl-gradient-hero text-white pt-32 pb-20 md:pt-40 md:pb-28">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <p class="text-sm font-semibold uppercase tracking-wider mb-4 bg-white/10 inline-block px-4 py-1.5 rounded-full border border-white/20">Gallery</p>
        <h1 class="text-4xl md:text-5xl font-extrabold mb-6">Gallery</h1>
        <p class="text-lg text-slate-300 max-w-2xl">Explore our fleet and operations</p>
    </div>
</section>

<section class="jsl-section">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if($categories->isNotEmpty())
        <div class="flex flex-wrap gap-2 mb-8">
            <button class="gallery-filter px-4 py-2 rounded-lg text-sm font-medium bg-[#0137A1] text-white" data-category="all">All</button>
            @foreach($categories as $category)
            <button class="gallery-filter px-4 py-2 rounded-lg text-sm font-medium bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors" data-category="{{ $category }}">{{ $category }}</button>
            @endforeach
        </div>
        @endif

        @if($items->isNotEmpty())
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach($items as $item)
            <div class="gallery-item group relative aspect-square rounded-xl overflow-hidden bg-slate-100 cursor-pointer" data-category="{{ $item->category ?? '' }}">
                @if($item->mediaAsset)
                    <img src="{{ $item->mediaAsset->url('medium') ?? $item->mediaAsset->url() }}" alt="{{ $item->caption ?? '' }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                @else
                    <div class="w-full h-full flex items-center justify-center">
                        <svg class="w-16 h-16 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                @endif
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-4">
                    <p class="text-white text-sm font-medium">{{ $item->caption ?? '' }}</p>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-20">
            <svg class="w-20 h-20 text-slate-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <p class="text-slate-400 text-lg">Gallery content will be available soon.</p>
        </div>
        @endif
    </div>
</section>

@push('scripts')
<script>
document.querySelectorAll('.gallery-filter').forEach(btn => {
    btn.addEventListener('click', function() {
        const category = this.dataset.category;
        document.querySelectorAll('.gallery-filter').forEach(b => {
            b.classList.remove('bg-[#0137A1]', 'text-white');
            b.classList.add('bg-slate-100', 'text-slate-600');
        });
        this.classList.add('bg-[#0137A1]', 'text-white');
        this.classList.remove('bg-slate-100', 'text-slate-600');

        document.querySelectorAll('.gallery-item').forEach(item => {
            if (category === 'all' || item.dataset.category === category) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });
});
</script>
@endpush
@endsection
