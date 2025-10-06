@props(['contact' => null])

<div class="text-sm text-gray-700">
    @if($contact)
    <div><strong>{{ $contact['name'] ?? '-' }}</strong></div>
    <div>{{ $contact['phone'] ?? '-' }}</div>
    <div class="truncate">{{ $contact['addr'] ?? '-' }}</div>
    @else
    <div class="italic text-gray-400">Belum ada kontak</div>
    @endif
</div>