<?php

namespace App\Supports\ShippingCalendar;

use Illuminate\Support\Carbon;

class CalendarLaneBuilder
{
    public function build(array $groups, Carbon $start, Carbon $end): array
    {
        $daysCount = $start->daysInMonth;
        $lanes = [
            'plan_etd' => 'ETD (Plan)',
            'plan_eta' => 'ETA (Plan)',
            'act_atd' => 'ATD (Aktual)',
            'act_ata' => 'ATA (Aktual)',
            'sum_atd' => 'Vol. ATD (Total)',
        ];
        $bucket = [];
        foreach (array_keys($lanes) as $k) {
            $bucket[$k] = array_fill(1, $daysCount, []);
        }
        $bars = [];
        $sailingBg = [];
        $chipBuilder = app(ChipBuilder::class);
        foreach ($groups as $g) {
            $voyageId   = $g['voyage_id'] ?? null;
            $scheduleId = $g['schedule_id'] ?? null;
            $etd        = $g['etd'] ?? ($g['meta']['etd'] ?? null);
            $eta        = $g['eta'] ?? ($g['meta']['eta'] ?? null);
            $atd        = $g['atd'] ?? ($g['meta']['atd'] ?? null);
            $ata        = $g['ata'] ?? ($g['meta']['ata'] ?? null);
            $plan       = (int) ($g['cargo_plan'] ?? 0);
            $vesselCode = $g['vessel_code'] ?? ($g['meta']['vessel_code'] ?? null);
            $voyageNo   = $g['voyage_no'] ?? ($g['meta']['voyage_no'] ?? null);
            $vesselName = $g['vessel_name'] ?? ($g['meta']['vessel_name'] ?? null);
            $isUrgent   = (bool) ($g['is_urgent'] ?? ($g['meta']['is_urgent'] ?? false));
            if ($etd && ! ($etd instanceof Carbon)) {
                try { $etd = Carbon::parse($etd); } catch (\Throwable $ex) { $etd = null; }
            }
            if ($eta && ! ($eta instanceof Carbon)) {
                try { $eta = Carbon::parse($eta); } catch (\Throwable $ex) { $eta = null; }
            }
            if ($atd && ! ($atd instanceof Carbon)) {
                try { $atd = Carbon::parse($atd); } catch (\Throwable $ex) { $atd = null; }
            }
            if ($ata && ! ($ata instanceof Carbon)) {
                try { $ata = Carbon::parse($ata); } catch (\Throwable $ex) { $ata = null; }
            }
            if (! $etd && ! $eta && ! $atd && ! $ata) {
                continue;
            }
            $vesselObj = (object)['name' => $vesselName, 'code' => $vesselCode];
            $shortLabel = $chipBuilder->buildShortCode($vesselObj);
            $makeChip = function ($shortLabel, $voyageNo, $plan, $meta, $isEta = false, $isUrgent = false) use ($chipBuilder) {
                $label = trim($shortLabel . ' • ' . (string)$voyageNo);
                $color = $chipBuilder->colorFor($shortLabel);
                $style = "background: {$color['bg']}; color: {$color['text']}; border: 1px solid {$color['border']};";
                $classBase = $isUrgent ? 'urgent' : ($isEta ? 'eta' : 'plan');
                return [
                    'label' => $label,
                    'short' => $shortLabel,
                    'voyage_no' => $voyageNo,
                    'plan' => $plan,
                    'meta' => $meta,
                    'voyages' => $meta['voyage_id'] ? [$meta['voyage_id']] : ($meta['voyages'] ?? []),
                    'class' => $classBase,
                    'vessel_key' => $shortLabel,
                    'color' => $color,
                    'style' => $style,
                ];
            };
            $metaCommon = [
                'voyage_no' => $voyageNo,
                'schedule_id' => $scheduleId,
                'voyage_id' => $voyageId,
                'etd' => $etd?->toDateTimeString() ?? null,
                'eta' => $eta?->toDateTimeString() ?? null,
                'atd' => $atd?->toDateTimeString() ?? null,
                'ata' => $ata?->toDateTimeString() ?? null,
                'vessel_code' => $vesselCode ?? null,
                'vessel_name' => $vesselName ?? null,
                'is_urgent' => $isUrgent,
                'sla_status' => $g['meta']['sla_status'] ?? null,
            ];
            if ($etd) {
                $fromDay = max(1, min((int)$etd->day, $daysCount));
                $chip = $makeChip($shortLabel, $voyageNo, $plan, $metaCommon, false, $isUrgent);
                $bucket['plan_etd'][$fromDay][] = $chip;
                $bars[] = [
                    'from' => $fromDay,
                    'span' => 1,
                    'lane' => 'plan_etd',
                    'label' => $chip['label'],
                    'short' => $chip['short'],
                    'voyage_no' => $voyageNo,
                    'schedule_id' => $scheduleId,
                    'vessel_code' => $vesselCode,
                    'etd' => $etd->toDateTimeString(),
                    'eta' => $eta?->toDateTimeString() ?? null,
                    'plan' => $plan,
                    'class' => $chip['class'],
                    'style' => $chip['style'],
                    'lead_time' => null,
                    'meta' => $metaCommon,
                ];
            }
            if ($eta) {
                $etaDay = max(1, min((int)$eta->day, $daysCount));
                $shouldPushEtaChip = true;
                if ($etd && $etd->day === $eta->day) {
                    $shouldPushEtaChip = false;
                }
                if ($shouldPushEtaChip) {
                    $chipEta = $makeChip($shortLabel, $voyageNo, $plan, $metaCommon, true, $isUrgent);
                    $bucket['plan_eta'][$etaDay][] = $chipEta;
                    $bars[] = [
                        'from' => $etaDay,
                        'span' => 1,
                        'lane' => 'plan_eta',
                        'label' => $chipEta['label'],
                        'short' => $chipEta['short'],
                        'voyage_no' => $voyageNo,
                        'schedule_id' => $scheduleId,
                        'vessel_code' => $vesselCode,
                        'etd' => $etd?->toDateTimeString() ?? null,
                        'eta' => $eta->toDateTimeString(),
                        'plan' => $plan,
                        'class' => $chipEta['class'],
                        'style' => $chipEta['style'],
                        'lead_time' => null,
                        'meta' => $metaCommon,
                    ];
                }
            }
            if ($atd) {
                $atdDay = max(1, min((int)$atd->day, $daysCount));
                if ($atdDay >= 1 && $atdDay <= $daysCount) {
                    $chipAtd = $makeChip($shortLabel, $voyageNo, $plan, $metaCommon, false, $isUrgent);
                    $bucket['act_atd'][$atdDay][] = $chipAtd;
                    $bars[] = [
                        'from' => $atdDay,
                        'span' => 1,
                        'lane' => 'act_atd',
                        'label' => $chipAtd['label'],
                        'short' => $chipAtd['short'],
                        'voyage_id' => $voyageId,
                        'schedule_id' => $scheduleId,
                        'vessel_code' => $vesselCode,
                        'etd' => $etd?->toDateTimeString() ?? null,
                        'eta' => $eta?->toDateTimeString() ?? null,
                        'atd' => $atd->toDateTimeString(),
                        'plan' => $plan,
                        'class' => $chipAtd['class'],
                        'style' => $chipAtd['style'],
                        'lead_time' => null,
                        'meta' => $metaCommon,
                    ];
                    $sailingBg[$atdDay] = true;
                }
            }
            if ($ata) {
                $ataDay = max(1, min((int)$ata->day, $daysCount));
                $shouldPushAta = true;
                if ($atd && $atd->day === $ata->day) {
                    $shouldPushAta = false;
                }
                if ($shouldPushAta) {
                    $chipAta = $makeChip($shortLabel, $voyageNo, $plan, $metaCommon, false, $isUrgent);
                    $bucket['act_ata'][$ataDay][] = $chipAta;
                    $bars[] = [
                        'from' => $ataDay,
                        'span' => 1,
                        'lane' => 'act_ata',
                        'label' => $chipAta['label'],
                        'short' => $chipAta['short'],
                        'voyage_id' => $voyageId,
                        'schedule_id' => $scheduleId,
                        'vessel_code' => $vesselCode,
                        'ata' => $ata->toDateTimeString(),
                        'plan' => $plan,
                        'class' => $chipAta['class'],
                        'style' => $chipAta['style'],
                        'lead_time' => null,
                        'meta' => $metaCommon,
                    ];
                }
            }
        }
        return [
            'days_count' => $daysCount,
            'lanes' => $lanes,
            'lane_types' => [
                'plan_etd' => 'plan',
                'plan_eta' => 'plan',
                'act_atd' => 'actual',
                'act_ata' => 'actual',
                'sum_atd' => 'summary',
            ],
            'bucket' => $bucket,
            'sailing_bg' => $sailingBg,
            'bars' => $bars,
        ];
    }
}
