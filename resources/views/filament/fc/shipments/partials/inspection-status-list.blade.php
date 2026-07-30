{{--
    Sprint INS-04 → UX-01 → UX-02 → UX-03 (restore) — Status Inspeksi, READ-ONLY.

    Menggantikan Repeater checklist yang sebelumnya ada di sini
    (ShipmentResource::inspectionFormFields()). Modal Update TIDAK LAGI bisa
    mengedit checklist/foto/signature — hanya menampilkan Unit + tombol,
    navigasi ke InspectUnitPage (satu-satunya tempat mengedit, INS-03/04).

    UX-03: dikembalikan ke konsep sederhana INS-04 sesuai keputusan produk —
    tombol STATIS "Buka Inspection" (label dinamis UX-01 dicabut), TANPA
    badge status (penghapusan badge UX-02 tetap dipertahankan — sesuai
    contoh Scope 2 sprint ini yang juga tidak menampilkan badge apa pun),
    TANPA parameter ?return= (redirect khusus UX-01 dicabut — murni
    eksperimen UX tanpa keperluan teknis; appendTrack()'s try/catch dari
    ARCH-01/INS-03, tidak disentuh, sudah menangani kasus "belum selesai"
    dengan notifikasi yang jelas tanpa perlu redirect proaktif). Lihat
    SPRINT-UX-03-RESTORE-ORIGINAL-INSPECTION-EXPERIENCE.md untuk penjelasan
    lengkap kenapa bagian ini dicabut, bukan dipertahankan "untuk jaga-jaga".
--}}
@php
    use App\Filament\FC\Pages\InspectUnitPage;

    $units = $shipment->units()
        ->with(['inspections' => fn ($q) => $q->where('stage', $stage)])
        ->get();
@endphp

<div class="space-y-2">
    @forelse ($units as $unit)
        @php
            $url = InspectUnitPage::getUrl(['record' => $shipment->getKey(), 'unit' => $unit->getKey()]);
        @endphp
        <div class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-2">
            <span class="font-medium text-sm text-gray-900 dark:text-white min-w-0">
                {{ $unit->reg_no ?? $unit->chassis_no ?? ('Unit #' . $unit->id) }}
            </span>

            <a href="{{ $url }}"
               class="shrink-0 inline-flex items-center gap-1 rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-primary-700 dark:bg-primary-700 dark:hover:bg-primary-600 transition-colors">
                <x-heroicon-m-clipboard-document-check class="h-3.5 w-3.5" />
                Buka Inspection
            </a>
        </div>
    @empty
        <p class="text-sm text-gray-500 dark:text-gray-400">Tidak ada unit pada shipment ini.</p>
    @endforelse
</div>
