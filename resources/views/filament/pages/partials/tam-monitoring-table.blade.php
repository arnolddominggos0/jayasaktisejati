<div class="space-y-8">

 @php
 $kritis = $rows->filter(fn($v) => $v->operational_status === 'delayed');

 $berlayar = $rows->filter(fn($v) => $v->operational_status === 'sailing');
 @endphp

 @if ($kritis->count())
 <div class="bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800 rounded-xl p-6 space-y-4">
 <div class="font-semibold text-red-700 dark:text-red-300 text-sm uppercase">
 Terlambat
 </div>

 @foreach ($kritis as $v)
 @include('filament.pages.partials.voyage-card', ['v' => $v])
 @endforeach
 </div>
 @endif

 @if ($berlayar->count())
 <div class="bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800 rounded-xl p-6 space-y-4">
 <div class="font-semibold text-blue-700 dark:text-blue-300 text-sm uppercase">
 Sedang Berlayar
 </div>

 @foreach ($berlayar as $v)
 @include('filament.pages.partials.voyage-card', ['v' => $v])
 @endforeach
 </div>
 @endif



 @if (!$kritis->count() && !$berlayar->count())
 <div class="bg-white dark:bg-slate-900 border rounded-xl p-8 text-center text-gray-500 dark:text-slate-400">
 Tidak ada pelayaran aktif pada periode ini.
 </div>
 @endif

</div>
