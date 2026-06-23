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

        $analysis = $plan->analyze();
        $greeting = $this->resolveGreeting();
        $recipientName = $this->sanitizeText($plan->whatsapp_recipient_name);
        $periodLabel = $plan->period_month->translatedFormat('M Y');
        $scheduleLines = $this->buildScheduleLines($plan);
        $routeLabel = $this->buildRouteLabel($plan);
        $sopStatus = $plan->sopStatus()['label'] ?? '-';

        return "{$greeting}" . ($recipientName ? " {$recipientName}" : '') . ",\n\n"
            . "Berikut draft jadwal kapal periode {$periodLabel}" . ($routeLabel ? " rute {$routeLabel}" : '') . ":\n\n"
            . $scheduleLines . "\n\n"
            . "Analisa SOP:\n"
            . "- Max ETD Gap: " . ($analysis['max_gap'] ?? 0) . " hari\n"
            . "- Status: {$sopStatus}\n\n"
            . "Mohon konfirmasi / revisinya.\n"
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
                $shippingLine = $this->sanitizeText($item->shippingLine?->name ?? '-');
                $vessel = $this->sanitizeText($item->vessel?->name ?? '-');

                return ($index + 1) . ". {$shippingLine}\n"
                    . "Kapal : {$vessel}\n"
                    . "ETD   : {$etd}\n"
                    . "ETA   : {$eta}";
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
