@php
    $url = $getRecord()->briefing_evidence_path
        ? \Illuminate\Support\Facades\Storage::disk('public')
            ->url($getRecord()->briefing_evidence_path)
        : null;
@endphp

@if ($url)
    <div class="p-2">
        <a href="{{ $url }}" target="_blank">
            <img
                src="{{ $url }}"
                alt="Bukti Briefing"
                class="rounded-xl max-h-96 object-contain cursor-pointer"
            >
        </a>
    </div>
@endif