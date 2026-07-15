<?php

namespace App\Filament\Resources\VesselPlanResource\Widgets;

use App\Models\VesselPlan;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

class VesselPlanAnalysis extends StatsOverviewWidget
{
    public ?VesselPlan $record = null;

    protected function getStats(): array
    {
        if (! $this->record) {
            return [];
        }

        $itemCount = $this->record->items->count();
        $unitSuffix = fn(string $unit) => '<span class="text-base font-normal text-gray-500"> ' . $unit . '</span>';

        // Belum ada jadwal = belum ada apa pun untuk dianalisis. Tampilkan
        // placeholder netral, bukan hasil analyze() (yang defaultnya
        // gap_ok=true/max_gap=0 untuk plan kosong) — itu memberi kesan
        // palsu bahwa evaluasi sudah dilakukan dan semuanya "OK".
        if ($itemCount === 0) {
            return [
                Stat::make('Jadwal', 0)
                    ->description('Jumlah jadwal yang direncanakan')
                    ->descriptionColor('gray'),

                Stat::make('Rencana Muatan', '—')
                    ->description('Belum ada data')
                    ->descriptionColor('gray'),

                Stat::make('ETD Gap', '—')
                    ->description('Belum ada data')
                    ->descriptionColor('gray'),

                Stat::make('Gap Status', 'Belum dievaluasi')
                    ->description('Tambahkan jadwal untuk mulai evaluasi')
                    ->descriptionColor('gray'),
            ];
        }

        $analysis = $this->record->analyze();

        $maxGap = $analysis['max_gap'] ?? 0;
        $gapLimit = $analysis['gap_limit'] ?? 6;
        $gapOk = $analysis['gap_ok'] ?? false;

        return [
            Stat::make('Jadwal', $itemCount)
                ->description('Jumlah jadwal yang direncanakan')
                ->descriptionColor('gray'),

            Stat::make(
                'Rencana Muatan',
                new HtmlString(
                    $this->record->items->sum('cargo_plan') . $unitSuffix('Unit')
                )
            )
                ->description('Total rencana muatan')
                ->descriptionColor('gray'),

            Stat::make(
                'ETD Gap',
                new HtmlString(
                    $maxGap . $unitSuffix('Hari')
                )
            )
                ->description("Target ≤ {$gapLimit} Hari")
                ->descriptionColor('gray'),

            Stat::make('Gap OK', $gapOk ? 'Ya' : 'Tidak')
                ->description($gapOk ? 'Semua jadwal sesuai target' : 'Ada jadwal yang melebihi target')
                ->descriptionColor($gapOk ? 'success' : 'danger'),
        ];
    }
}
