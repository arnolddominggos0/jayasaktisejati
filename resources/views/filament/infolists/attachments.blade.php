@php $items = $getState() ?? []; @endphp

@if (blank($items))
<span class="text-gray-500 dark:text-slate-400">-”</span>
@else
<ul class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
 @foreach ($items as $f)
 <li class="flex items-center gap-3 min-w-0">
 @if (! $f['exists'])
 <span class="text-red-600 dark:text-red-400 truncate"> {{ $f['name'] }} (file tidak ditemukan)</span>
 @elseif ($f['is_image'])
 <a href="{{ $f['url'] }}" target="_blank" class="flex items-center gap-3 min-w-0">
 <img src="{{ $f['url'] }}" alt="{{ $f['name'] }}"
 class="w-16 h-16 object-cover rounded border flex-none" loading="lazy">
 <span class="truncate text-primary-600 hover:underline">{{ $f['name'] }}</span>
 </a>
 @else
 <a href="{{ $f['url'] }}" target="_blank"
 class="truncate text-primary-600 hover:underline">{{ $f['name'] }}</a>
 @endif
 </li>
 @endforeach
</ul>
@endif