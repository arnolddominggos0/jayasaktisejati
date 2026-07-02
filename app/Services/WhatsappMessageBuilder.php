<?php

namespace App\Services;

use App\Models\VesselPlan;
use App\Supports\BusinessRouteResolver;
use Illuminate\Support\Carbon;

class WhatsappMessageBuilder
{
    public function buildFullMessage(VesselPlan $plan): string
    {
        $plan->loadMissing(['customer', 'items.shippingLine', 'items.vessel', 'pol', 'pod']);

        $greeting = $this->resolveGreeting();
        $recipientName = $this->sanitizeText($plan->whatsapp_recipient_name);
        $periodLabel = $plan->period_month->translatedFormat('F Y');
        $scheduleLines = $this->buildScheduleLines($plan);
        $routeLabel = $this->buildRouteLabel($plan);

        return "{$greeting}" . ($recipientName ? " {$recipientName}" : '') . ",\n\n"
            . "Berikut kami sampaikan Draft Jadwal Kapal:\n\n"
            . "Periode : {$periodLabel}"
            . ($routeLabel ? "\nRute    : {$routeLabel}" : '') . "\n\n"
            . $scheduleLines . "\n\n"
            . "Jadwal masih berupa estimasi dan dapat berubah sewaktu-waktu.\n\n"
            . "Terima kasih.";
    }


    protected function buildScheduleLines(VesselPlan $plan): string
    {
        if ($plan->items->isEmpty()) {
            return '- Belum ada detail jadwal kapal.';
        }

        return $plan->items
            ->sortBy('planned_etd')
            ->values()
            ->map(function ($item, int $index) {
                $etd = $item->planned_etd?->translatedFormat('d M Y');
                $eta = $item->planned_eta?->translatedFormat('d M Y');
                $vessel = $this->sanitizeText($item->vessel?->name ?? '-');
                $voyage = $this->sanitizeText($item->voyage_no ?: '-');

                return ($index + 1) . ". {$vessel} V.{$voyage}\n"
                    . "   ETD : {$etd}\n"
                    . "   ETA : {$eta}";
            })
            ->implode("\n\n");
    }

    protected function buildRouteLabel(VesselPlan $plan): ?string
    {
        $label = BusinessRouteResolver::forPlan($plan);

        return $label !== '—' ? $this->sanitizeText($label) : null;
    }

    protected function sanitizeText(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $sanitized = str_replace(['*', '_', '`'], '', $value);
        $sanitized = preg_replace('/\s+/', ' ', $sanitized);

        return trim((string) $sanitized);
    }

    protected function resolveGreeting(): string
    {
        $hour = Carbon::now(config('app.timezone'))->hour;

        return match (true) {
            $hour < 10 => 'Selamat pagi',
            $hour < 14 => 'Selamat siang',
            $hour < 18 => 'Selamat sore',
            default => 'Selamat malam',
        };
    }
}
