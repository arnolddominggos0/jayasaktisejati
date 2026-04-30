@extends('public.layouts.public')

@section('title', 'Lacak Pengiriman - PT Jaya Sakti Sejati')

@section('content')

<!-- Hero Section -->
<section class="relative pt-24 pb-16 gradient-blue hero-pattern">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-3xl md:text-4xl font-bold text-white mb-4">Lacak Pengiriman Anda</h1>
        <p class="text-blue-100 max-w-2xl mx-auto">Masukkan nomor resi untuk melihat status dan posisi pengiriman terkini.</p>
    </div>
</section>

<!-- Tracking Search Section -->
<section class="py-12 bg-slate-50 -mt-8">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-2xl shadow-lg p-8">
            <form action="{{ route('tracking.search') }}" method="POST" class="space-y-4">
                @csrf
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="flex-1">
                        <label for="tracking_number" class="sr-only">Nomor Resi</label>
                        <input type="text" name="tracking_number" id="tracking_number" 
                            class="w-full px-4 py-4 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg"
                            placeholder="Masukkan nomor resi (Contoh: JSS-2026-001234)"
                            value="{{ request('tracking_number') }}"
                            required>
                    </div>
                    <button type="submit" class="px-8 py-4 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        Cari
                    </button>
                </div>
                @error('tracking_number')
                    <p class="text-red-500 text-sm">{{ $message }}</p>
                @enderror
            </form>

            @if(session('error'))
                <div class="mt-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-red-700">{{ session('error') }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</section>

<!-- Tracking Result Section -->
@if(isset($result))
<section class="py-12 bg-slate-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <!-- Header -->
            <div class="bg-blue-600 px-8 py-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-blue-200 text-sm mb-1">Nomor Resi</p>
                        <h2 class="text-2xl font-bold text-white">{{ $result['code'] }}</h2>
                    </div>
                    <div class="mt-4 md:mt-0">
                        <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold
                            @if($result['status'] == 'Delivered') bg-green-100 text-green-800
                            @elseif($result['status'] == 'Transit') bg-yellow-100 text-yellow-800
                            @elseif($result['status'] == 'Pickup') bg-blue-100 text-blue-800
                            @else bg-gray-100 text-gray-800 @endif">
                            {{ $result['status'] }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Shipment Info -->
            <div class="px-8 py-6 border-b border-slate-200">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div>
                        <p class="text-slate-500 text-sm mb-1">Layanan</p>
                        <p class="font-semibold text-slate-800">{{ $result['service_type'] ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-slate-500 text-sm mb-1">Penerima</p>
                        <p class="font-semibold text-slate-800">{{ $result['receiver'] ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-slate-500 text-sm mb-1">Tujuan</p>
                        <p class="font-semibold text-slate-800">{{ $result['destination'] ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-slate-500 text-sm mb-1">ETA</p>
                        <p class="font-semibold text-slate-800">{{ $result['eta'] ?? '-' }}</p>
                    </div>
                </div>
            </div>

            <!-- Tracking Timeline -->
            <div class="px-8 py-6">
                <h3 class="text-lg font-bold text-slate-800 mb-6">Riwayat Tracking</h3>
                @if(!empty($result['tracks']))
                    <div class="space-y-6">
                        @foreach($result['tracks'] as $index => $track)
                            <div class="flex items-start">
                                <div class="flex-shrink-0 mr-4">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center
                                        @if($index == 0) bg-blue-600 text-white
                                        @else bg-slate-200 text-slate-600 @endif">
                                        @if($index == 0)
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        @else
                                            <span class="text-sm font-semibold">{{ count($result['tracks']) - $index }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex-1 pb-6 @if(!$loop->last) border-l-2 border-slate-200 ml-5 -mt-2 @endif">
                                    <div class="ml-6">
                                        <p class="font-semibold text-slate-800">{{ $track['status'] }}</p>
                                        @if($track['location'])
                                            <p class="text-slate-600 text-sm">{{ $track['location'] }}</p>
                                        @endif
                                        @if($track['occurred_at'])
                                            <p class="text-slate-500 text-sm mt-1">{{ $track['occurred_at'] }}</p>
                                        @endif
                                        @if($track['notes'])
                                            <p class="text-slate-600 text-sm mt-2">{{ $track['notes'] }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-slate-500 text-center py-8">Belum ada riwayat tracking.</p>
                @endif
            </div>

            <!-- Footer -->
            <div class="px-8 py-4 bg-slate-50 border-t border-slate-200">
                <p class="text-sm text-slate-600 text-center">
                    Untuk informasi lebih detail, silakan 
                    <a href="{{ url('/portal') }}" class="text-blue-600 hover:underline font-medium">login ke portal customer</a>
                </p>
            </div>
        </div>
    </div>
</section>
@endif

<!-- How to Track Section -->
@if(!isset($result))
<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-2xl md:text-3xl font-bold text-slate-800 mb-4">Cara Melacak Pengiriman</h2>
            <div class="w-20 h-1 bg-blue-600 mx-auto"></div>
        </div>
        <div class="grid md:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="text-2xl font-bold text-blue-600">1</span>
                </div>
                <h3 class="font-semibold text-slate-800 mb-2">Masukkan Nomor Resi</h3>
                <p class="text-slate-600 text-sm">Ketik nomor resi yang Anda dapatkan saat pengiriman.</p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="text-2xl font-bold text-blue-600">2</span>
                </div>
                <h3 class="font-semibold text-slate-800 mb-2">Klik Tombol Cari</h3>
                <p class="text-slate-600 text-sm">Tekan tombol Cari untuk mencari data pengiriman.</p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="text-2xl font-bold text-blue-600">3</span>
                </div>
                <h3 class="font-semibold text-slate-800 mb-2">Lihat Status</h3>
                <p class="text-slate-600 text-sm">Informasi status dan posisi pengiriman akan ditampilkan.</p>
            </div>
        </div>
    </div>
</section>
@endif

@endsection
