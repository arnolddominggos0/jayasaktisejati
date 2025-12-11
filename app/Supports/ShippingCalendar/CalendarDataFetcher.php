<?php

namespace App\Supports\ShippingCalendar;

use App\Models\ShippingSchedule;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;

class CalendarDataFetcher
{
    public function fetch(Carbon $start, Carbon $end, ?string $pol = null, ?string $pod = null): Collection
    {
        $pol = $pol ?? strtoupper((string) config('tam.route.pol_code', 'JKT'));
        $pod = $pod ?? strtoupper((string) config('tam.route.pod_code', 'MND'));
        $force = (bool) config('tam.route.force', true);

        $query = ShippingSchedule::query()
            ->with(['voyage.vessel.shippingLine', 'voyage.pol', 'voyage.pod']);

        if ($force && $pol !== '' && $pod !== '') {
            $query->whereHas('voyage.pol', function ($q) use ($pol) {
                $q->whereRaw('upper(code) = ?', [$pol]);
            })->whereHas('voyage.pod', function ($q) use ($pod) {
                $q->whereRaw('upper(code) = ?', [$pod]);
            });
        }

        $query->where(function ($q) use ($start, $end) {
            $q->whereBetween('shipping_schedules.period_month', [$start->toDateString(), $end->toDateString()])
              ->orWhereHas('voyage', function ($vq) use ($start, $end) {
                  $vq->where(function ($w) use ($start, $end) {
                      $w->whereBetween('voyages.etd', [$start, $end])
                        ->orWhereBetween('voyages.eta', [$start, $end])
                        ->orWhereBetween('voyages.atd_at', [$start, $end])
                        ->orWhereBetween('voyages.ata_at', [$start, $end])
                        ->orWhere(function ($z) use ($start, $end) {
                            $z->where('voyages.etd', '<=', $start)->where('voyages.eta', '>=', $end);
                        });
                  });
              });
        });

        $rows = $query->orderBy('voyage_id')->get()->values();

        $rows->each(function (ShippingSchedule $s) {
            $vessel = $s->voyage?->vessel ?? null;
            $code = null;

            if ($vessel) {
                $code = $vessel->code ?? null;

                if (! $code && $vessel->shippingLine?->code) {
                    $prefix = strtoupper(substr((string)$vessel->shippingLine->code, 0, 3));
                    $short = $this->makeShortName($vessel->name ?? '');
                    $code = trim(($prefix ? $prefix . '-' : '') . ($short ?: null), '-');
                }

                if (! $code) {
                    $code = $this->makeShortName($vessel->name ?? '') ?: null;
                }
            }

            $s->setAttribute('vessel_code', $code ?: null);

            $meta = $s->getAttribute('meta') ?? [];
            $meta = is_object($meta) ? (array) $meta : (is_array($meta) ? $meta : []);

            $meta['vessel_code'] = $code;
            $meta['vessel_name'] = $vessel?->name ?? null;

            $candidates = [
                'etd' => [$s->voyage?->etd ?? null, $s->etd ?? null, $meta['etd'] ?? null],
                'eta' => [$s->voyage?->eta ?? null, $s->eta ?? null, $meta['eta'] ?? null],
                'atd' => [$s->voyage?->atd_at ?? null, $s->atd ?? null, $s->actual_atd ?? null, $s->atd_actual ?? null, $meta['atd'] ?? null, $meta['actual_atd'] ?? null],
                'ata' => [$s->voyage?->ata_at ?? null, $s->ata ?? null, $s->actual_ata ?? null, $s->ata_actual ?? null, $meta['ata'] ?? null, $meta['actual_ata'] ?? null],
            ];

            foreach (['etd','eta','atd','ata'] as $k) {
                $val = null;
                foreach ($candidates[$k] as $cand) {
                    if ($cand === null) continue;
                    if ($cand instanceof \DateTime || $cand instanceof Carbon) { $val = $cand; break; }
                    if (is_string($cand) && trim($cand) !== '') { $val = $cand; break; }
                }
                if ($val instanceof \DateTime || $val instanceof Carbon) {
                    $meta[$k] = $val instanceof Carbon ? $val->toDateTimeString() : Carbon::parse($val)->toDateTimeString();
                    $s->setAttribute($k, $meta[$k]);
                } elseif (is_string($val)) {
                    try {
                        $dt = Carbon::parse($val);
                        $meta[$k] = $dt->toDateTimeString();
                        $s->setAttribute($k, $meta[$k]);
                    } catch (\Throwable $ex) {
                        $meta[$k] = null;
                        $s->setAttribute($k, null);
                    }
                } else {
                    $meta[$k] = null;
                    $s->setAttribute($k, null);
                }
            }

            $s->setAttribute('meta', $meta);
        });

        return $rows;
    }

    protected function makeShortName(string $name): ?string
    {
        $name = trim((string)$name);
        if ($name === '') return null;
        $parts = preg_split('/\s+|[^A-Za-z0-9]+/', $name);
        $pieces = [];
        foreach ($parts as $p) {
            if ($p === '') continue;
            $pieces[] = strtoupper(substr($p, 0, 2));
            if (count($pieces) >= 3) break;
        }
        $joined = implode('', $pieces);
        return $joined ? substr($joined, 0, 6) : strtoupper(substr(preg_replace('/[^A-Z0-9]/i','',$name),0,6));
    }
}
