<?php

namespace App\Support;

use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class TamFinalTableParser
{
    /**
     * Parse tabel final TAM (paste dari email/Excel).
     * Output array baris untuk shipping_schedule_items.
     *
     * @return array<int, array{
     *   etd:?string, eta:?string, cargo_plan:?int, vessel_name:?string,
     *   vessel_capacity:?int, voyage_no:?string, jss:?string, lts:?string, dwelling:?int
     * }>
     */
    public static function parse(string $raw): array
    {
        $raw = trim(str_replace("\r", "\n", $raw));
        $lines = array_values(array_filter(array_map('trim', explode("\n", $raw))));

        $dataLines = array_filter($lines, function ($l) {
            $u = Str::upper($l);
            if ($u === '' || Str::contains($u, ['MANADO', 'ROUTE', 'TOTAL', 'ARRANGEMENT', 'MDP TAM'])) {
                return false;
            }
            if (Str::contains($u, ['NO', 'ETD', 'ETA', 'CARGO', 'VESSEL', 'CAPACITY', 'VOYAGE', 'JSS', 'LTS', 'DWELLING'])) {
                return false;
            }
            return true;
        });

        $rows = [];
        foreach ($dataLines as $l) {
            $parts = preg_split('/\t|;|,|\s{2,}/', $l);
            $parts = array_values(array_filter(array_map('trim', $parts), fn($v) => $v !== ''));

            if (count($parts) === 10) array_shift($parts);
            if (count($parts) < 9) continue;

            [$etd, $eta, $cargo, $vessel, $cap, $voy, $jss, $lts, $dw] = array_slice($parts, 0, 9);

            $rows[] = [
                'etd'             => self::toIsoDate($etd),
                'eta'             => self::toIsoDate($eta),
                'cargo_plan'      => self::toInt($cargo),
                'vessel_name'     => $vessel ?: null,
                'vessel_capacity' => self::toInt($cap),
                'voyage_no'       => $voy ?: null,
                'jss'             => $jss ?: null,
                'lts'             => $lts ?: null,
                'dwelling'        => self::toInt($dw),
            ];
        }

        return $rows;
    }

    protected static function toIsoDate(?string $v): ?string
    {
        if (!$v) return null;
        $v = trim($v);
        $guessYear = (int) now()->format('Y');
        foreach ([$guessYear, $guessYear + 1, $guessYear - 1] as $y) {
            try {
                $c = Carbon::parse("$v-$y")->startOfDay();
                return $c->toIso8601String();
            } catch (\Throwable) {
            }
        }
        return null;
    }

    protected static function toInt($v): ?int
    {
        if ($v === null || $v === '') return null;
        $v = preg_replace('/[^\d]/', '', (string) $v);
        return $v === '' ? null : (int) $v;
    }
}
