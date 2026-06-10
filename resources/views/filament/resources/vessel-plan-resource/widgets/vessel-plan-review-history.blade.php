<div class="rounded-xl border bg-white px-6 py-5 mb-6"
     x-data="{ open: false, entry: null }">

    <h3 class="text-base font-semibold mb-1">Riwayat Review</h3>
    <p class="text-sm text-gray-500 mb-4">Jejak pengiriman draft, revisi, dan persetujuan final vessel plan.</p>

    @if (empty($entries))
        <div class="text-sm text-gray-400">Belum ada riwayat review.</div>
    @else
        <div class="overflow-x-auto rounded-lg border">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3 text-left">Tanggal</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">User</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach ($entries as $entry)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $entry['acted_at'] }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium {{ $entry['badge_color'] }}">
                                    {{ $entry['action'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ $entry['actor'] }}</td>
                            <td class="px-4 py-3 text-center">
                                @if (!empty($entry['note']) || !empty($entry['meta']))
                                    <button
                                        type="button"
                                        class="text-xs text-blue-600 hover:underline"
                                        @click="entry = {{ json_encode($entry) }}; open = true"
                                    >
                                        Detail
                                    </button>
                                @else
                                    <span class="text-xs text-gray-300">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Detail modal --}}
    <div
        x-show="open"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="display: none;"
    >
        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-black/40" @click="open = false"></div>

        {{-- Panel --}}
        <div class="relative z-10 w-full max-w-lg rounded-xl bg-white shadow-xl">
            <div class="flex items-center justify-between border-b px-5 py-4">
                <div>
                    <div class="font-semibold text-gray-800" x-text="entry?.action"></div>
                    <div class="text-xs text-gray-400 mt-0.5">
                        <span x-text="entry?.actor"></span> &bull; <span x-text="entry?.acted_at"></span>
                    </div>
                </div>
                <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            <div class="px-5 py-4 space-y-4 max-h-[60vh] overflow-y-auto">

                {{-- Note --}}
                <template x-if="entry?.note">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Catatan</div>
                        <div class="rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-800" x-text="entry.note"></div>
                    </div>
                </template>

                {{-- Meta --}}
                <template x-if="entry?.meta && Object.keys(entry.meta).length > 0">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Detail</div>
                        <dl class="divide-y rounded-lg border overflow-hidden">
                            <template x-for="[key, val] in Object.entries(entry.meta)" :key="key">
                                <div class="flex justify-between gap-4 px-3 py-2 text-xs">
                                    <dt class="text-gray-500 capitalize" x-text="key.replace(/_/g, ' ')"></dt>
                                    <dd class="text-gray-800 font-medium text-right break-all"
                                        x-text="typeof val === 'object' ? JSON.stringify(val) : val"></dd>
                                </div>
                            </template>
                        </dl>
                    </div>
                </template>

            </div>

            <div class="border-t px-5 py-3 flex justify-end">
                <button type="button" @click="open = false"
                    class="rounded-lg border px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
                    Tutup
                </button>
            </div>
        </div>
    </div>

</div>
