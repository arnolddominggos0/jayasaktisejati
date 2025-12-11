<?php

namespace App\Supports\ShippingCalendar;

use Illuminate\Support\Collection;

class VoyageGroupingService
{
    protected ChipBuilder $chipBuilder;

    public function __construct(?ChipBuilder $chipBuilder = null)
    {
        $this->chipBuilder = $chipBuilder ?? new ChipBuilder();
    }

    public function group($rows, $extractor = null): array
    {
        $collection = $rows instanceof Collection ? $rows : collect($rows);

        $groups = $collection->map(function ($s) {
            $voyage = $this->prop($s, 'voyage');
            $vessel = $this->prop($voyage, 'vessel');

            $meta = $this->prop($s, 'meta') ?? [];
            if (is_object($meta)) $meta = (array) $meta;
            if (!is_array($meta)) $meta = [];

            $pick = function (array $c) {
                foreach ($c as $v) {
                    if ($v === null) continue;
                    if ($v instanceof \DateTime || $v instanceof \Illuminate\Support\Carbon) return $v;
                    if (is_string($v) && trim($v) !== '') return $v;
                    if (is_numeric($v)) return $v;
                }
                return null;
            };

            $etd = $pick([$this->prop($voyage, 'etd'), $this->prop($s, 'etd'), $meta['etd'] ?? null]);
            $eta = $pick([$this->prop($voyage, 'eta'), $this->prop($s, 'eta'), $meta['eta'] ?? null]);
            $atd = $pick([
                $this->prop($voyage, 'atd_at'),
                $this->prop($s, 'atd'),
                $this->prop($s, 'actual_atd'),
                $this->prop($s, 'atd_actual'),
                $meta['atd'] ?? null,
                $meta['actual_atd'] ?? null,
            ]);
            $ata = $pick([
                $this->prop($voyage, 'ata_at'),
                $this->prop($s, 'ata'),
                $this->prop($s, 'actual_ata'),
                $this->prop($s, 'ata_actual'),
                $meta['ata'] ?? null,
                $meta['actual_ata'] ?? null,
            ]);

            $vesselCode = $this->prop($s, 'vessel_code') ?? ($meta['vessel_code'] ?? $this->prop($vessel, 'code'));
            $vesselName = $this->prop($s, 'vessel_name') ?? ($meta['vessel_name'] ?? $this->prop($vessel, 'name'));

            // compute short display via ChipBuilder (accepts object/array/null)
            $shortDisplay = $this->chipBuilder->buildShortCode(
                // pass vessel normalized: object preferred, else array with name/code
                $vessel ?? (is_array($meta) ? $meta : (object) [
                    'code' => $vesselCode,
                    'name' => $vesselName,
                ])
            );

            $voyageNo = $this->prop($voyage, 'voyage_no') ?? $this->prop($s, 'voyage_no') ?? ($meta['voyage_no'] ?? null);

            return [
                'schedule_id' => $this->prop($s, 'id') ?? null,
                'voyage_id' => $this->prop($s, 'voyage_id') ?? $this->prop($voyage, 'id') ?? null,
                'voyage_no' => $voyageNo,
                'etd' => $etd,
                'eta' => $eta,
                'atd' => $atd,
                'ata' => $ata,
                'cargo_plan' => $this->prop($s, 'cargo_plan') ?? ($meta['cargo_plan'] ?? null),
                'vessel_code' => $vesselCode,
                'vessel_name' => $vesselName,
                'is_urgent' => (bool) ($this->prop($s, 'is_urgent') ?? ($meta['is_urgent'] ?? false)),
                'meta' => $meta, // kept but normalized to array
                'short_display' => $shortDisplay,
                'vessel_key' => $shortDisplay, // used for color hashing in blade/js
            ];
        })->values()->all();

        return $groups;
    }

    protected function prop($objOrArr, string $key)
    {
        if ($objOrArr === null) return null;
        if (is_array($objOrArr)) return $objOrArr[$key] ?? null;
        if (is_object($objOrArr)) {
            if (isset($objOrArr->{$key})) return $objOrArr->{$key};
            $arr = (array) $objOrArr;
            if (array_key_exists($key, $arr)) return $arr[$key];
            $alt = "\0*\0{$key}";
            if (array_key_exists($alt, $arr)) return $arr[$alt];
            $snake = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $key));
            if (array_key_exists($snake, $arr)) return $arr[$snake];
        }
        return null;
    }
}
