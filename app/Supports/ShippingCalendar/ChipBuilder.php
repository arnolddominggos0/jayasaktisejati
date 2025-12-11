<?php

namespace App\Supports\ShippingCalendar;

use App\Supports\ShippingCalendar\DTO\Chip;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class ChipBuilder
{
    public function buildFromSchedule($schedule): Chip
    {
        $v = $schedule->voyage ?? null;
        $vs = $v?->vessel ?? null;
        $voyageNo = (string) ($v?->voyage_no ?? ($schedule->voyage_no ?? '-'));

        $short = $this->buildShortCode($vs);

        $label = ($vs?->code ?? (is_array($vs) ? ($vs['code'] ?? null) : null) ?? Str::upper((string)($vs?->name ?? ($schedule->vessel_name ?? '')))) . ' · ' . $voyageNo;

        $etd = $v?->etd ? Carbon::parse($v->etd) : null;
        $ata = $v?->ata_at ? Carbon::parse($v->ata_at) : null;
        $lead = null;
        if ($etd && $ata) {
            $lead = $etd->diffInDays($ata);
        }

        $class = $this->classFor($lead, (bool)($schedule->is_urgent ?? false));

        return new Chip([
            'short' => $short,
            'label' => $label,
            'voyages' => [$voyageNo],
            'count' => 0,
            'plan' => (int) ($schedule->cargo_plan ?? ($schedule->plan ?? 0)),
            'lead' => $lead,
            'class' => $class,
        ]);
    }

    public function buildShortCode($vessel): string
    {
        $overrides = config('vessel.overrides', []);
        $vCode = null;
        $vName = '';

        if (is_object($vessel)) {
            $vCode = $vessel->code ?? null;
            $vName = $vessel->name ?? '';
        } elseif (is_array($vessel)) {
            $vCode = $vessel['code'] ?? null;
            $vName = $vessel['name'] ?? ($vessel['vessel_name'] ?? '');
        } else {
            $vName = (string)($vessel ?? '');
        }

        $name = trim(strtoupper((string)$vName));

        foreach ($overrides as $k => $v) {
            if ($k === '') continue;
            if ($name !== '' && stripos($name, (string) $k) !== false) {
                return 'TT' . strtoupper((string)$v);
            }
        }

        if (! empty($vCode)) {
            $clean = strtoupper((string)$vCode);
            $clean = preg_replace('/[^A-Z0-9]/', '', $clean);
            $clean = Str::substr($clean, 0, 4);
            if ($clean !== '') {
                return 'TT' . $clean;
            }
        }

        $parts = preg_split('/[\s\-\/\_]+/', $name, -1, PREG_SPLIT_NO_EMPTY);
        $mapped = [];
        foreach ($parts as $i => $p) {
            if ($p === '') continue;
            $len = strlen($p);
            if ($i === 0) {
                $mapped[] = substr($p, 0, 3);
            } else {
                $mapped[] = substr($p, 0, 2);
            }
            if (strlen(implode('', $mapped)) >= 4) break;
        }

        $code = implode('', $mapped);
        $code = preg_replace('/[^A-Z0-9]/', '', $code);
        $code = Str::substr($code, 0, 4);
        if ($code === '') {
            $code = strtoupper(substr(preg_replace('/[^A-Z0-9]/', '', $name), 0, 4)) ?: 'VS';
        }

        return 'TT' . $code;
    }

    protected function classFor(?int $lead, bool $urgent): string
    {
        if ($lead === null) return 'bg-gray-50 text-gray-700 border border-gray-200';
        if ($urgent) return 'bg-red-100 text-red-800 border border-red-600';
        if ($lead > 11) return 'bg-rose-100 text-rose-800 border border-rose-300';
        return 'bg-emerald-100 text-emerald-800 border border-emerald-300';
    }

    public function merge(array $chips): Chip
    {
        $first = array_shift($chips);
        $voyages = $first->voyages;
        $plan = $first->plan;
        $lead = $first->lead;
        $count = 1;
        foreach ($chips as $c) {
            $voyages = array_merge($voyages, $c->voyages);
            $plan += $c->plan;
            if ($c->lead !== null) {
                $lead = $lead === null ? $c->lead : min($lead, $c->lead);
            }
            $count++;
        }
        $first->voyages = array_values(array_unique($voyages));
        $first->count = $count;
        $first->plan = $plan;
        $first->lead = $lead;
        return $first;
    }
}
