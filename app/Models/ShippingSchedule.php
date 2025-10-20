<?php

namespace App\Models;

use App\Enums\ScheduleState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\Models\Customer;
use App\Models\Port;
use App\Models\ShippingScheduleItem;
use App\Models\ShippingLine;
use App\Models\Vessel;

class ShippingSchedule extends Model
{
    protected $fillable = [
        'customer_id',
        'pol_id',
        'pod_id',
        'period_ym',
        'state',
        'title',
        'notes',
        'created_by',
        'finalized_at',
        'final_source',
        'final_attachment',
        'final_note',
        'approved_by_name',
        'approved_at',
    ];

    protected $casts = [
        'finalized_at' => 'datetime',
        'approved_at'  => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function pol(): BelongsTo
    {
        return $this->belongsTo(Port::class, 'pol_id');
    }

    public function pod(): BelongsTo
    {
        return $this->belongsTo(Port::class, 'pod_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShippingScheduleItem::class, 'schedule_id');
    }

    public function isFinal(): bool
    {
        return $this->state === ScheduleState::Final->value;
    }

    protected function parseDateWithPeriod(?string $value): ?Carbon
    {
        $value = trim((string)$value);
        if ($value === '') return null;
        $dt = self::parseDateTime($value);
        if ($dt) return $dt;
        $year = null;
        if (preg_match('/^\d{4}\-\d{2}$/', (string)$this->period_ym)) {
            [$y] = explode('-', $this->period_ym);
            $year = (int)$y;
        } else {
            $year = (int)date('Y');
        }
        $try = ['d-M', 'd/M', 'd M', 'M d'];
        foreach ($try as $fmt) {
            try {
                $base = Carbon::createFromFormat($fmt, $value);
                if ($base !== false) {
                    return Carbon::create($year, $base->month, $base->day, 0, 0, 0);
                }
            } catch (\Throwable $e) {
            }
        }
        $ts = strtotime($value . ' ' . $year);
        return $ts ? Carbon::parse(date('Y-m-d H:i:s', $ts)) : null;
    }

    public function finalizeFromWhatsapp(
        string $tableText,
        ?string $finalNote = null,
        ?string $attachmentPath = null,
        ?string $approvedByName = null,
        ?int $userId = null,
        ?string $lineHint = null
    ): array {
        if ($this->isFinal()) return ['rows' => 0, 'voyages' => 0, 'items' => 0];

        $rows = self::parseDelimited($tableText);
        $createdItems = 0;

        foreach ($rows as $r) {
            $lineName = trim((string)($r['line'] ?? $r['shipping_line'] ?? $lineHint ?? ''));
            $vessel   = trim((string)($r['vessel'] ?? ''));
            $vesselCapacity = trim((string)($r['vessel_capacity'] ?? $r['vessel_2'] ?? ''));

            $voyNo    = trim((string)($r['voyage_no'] ?? $r['voyage'] ?? ''));
            $polVal   = trim((string)($r['pol'] ?? ''));
            $podVal   = trim((string)($r['pod'] ?? ''));
            $service  = trim((string)($r['service'] ?? ''));

            $directRaw   = trim((string)($r['direct'] ?? ''));
            $transitRaw  = trim((string)($r['transit'] ?? ''));

            $etdStr   = trim((string)($r['etd'] ?? ''));
            $etaStr   = trim((string)($r['eta'] ?? ''));

            $vh = strtolower($vessel);
            if ($vessel === '' && $voyNo === '' && $etdStr === '') continue;
            if (in_array($vh, ['capacity', 'vessel capacity', 'dwelling', 'cargo plan', 'plan'], true)) continue;
            if (array_key_exists('lts', $r) && !empty($r['lts']) && empty($r['jss'])) continue;

            $shippingLineId = self::resolveShippingLineId($lineName);
            $vesselId = self::resolveVesselId($vessel, $shippingLineId);
            $polId = self::resolvePortId($polVal) ?: $this->pol_id;
            $podId = self::resolvePortId($podVal) ?: $this->pod_id;

            $etd = $this->parseDateWithPeriod($etdStr);
            $eta = $this->parseDateWithPeriod($etaStr);

            $direct = null;
            if ($directRaw !== '') $direct = self::toBool($directRaw);
            elseif ($transitRaw !== '') $direct = self::toBool($transitRaw) === false ? false : null;
            if ($direct === null) $direct = self::inferDirectFromService($service);

            $extra = array_filter([
                'cargo_plan'       => $r['cargo_plan'] ?? null,
                'capacity'         => $r['capacity'] ?? null,
                'vessel_capacity'  => $vesselCapacity ?: null,
                'dwelling'         => $r['dwelling'] ?? null,
                'jss'              => $r['jss'] ?? null,
                'direct'           => $direct,
            ], fn($v) => $v !== null && $v !== '');

            $this->items()->updateOrCreate(
                [
                    'vessel_id'        => $vesselId,
                    'voyage_no'        => $voyNo ?: null,
                    'pol_id'           => $polId,
                    'pod_id'           => $podId,
                    'etd'              => $etd?->toDateTimeString(),
                ],
                [
                    'schedule_id'      => $this->id,
                    'shipping_line_id' => $shippingLineId,
                    'eta'              => $eta?->toDateTimeString(),
                    'service'          => $service ?: null,
                    'extra'            => $extra,
                ]
            );

            $createdItems++;
        }

        $this->state            = \App\Enums\ScheduleState::Final->value;
        $this->finalized_at     = now();
        $this->final_source     = 'customer_email';
        $this->final_attachment = $attachmentPath;
        $this->final_note       = $finalNote;
        $this->approved_by_name = $approvedByName;
        $this->approved_at      = now();
        $this->save();

        return ['rows' => count($rows), 'voyages' => 0, 'items' => $createdItems];
    }

    public static function storeAttachment(?\Illuminate\Http\UploadedFile $file = null): ?string
    {
        if (!$file) return null;
        $dir = 'schedules/' . now()->format('Y/m');
        return $file->store($dir, ['disk' => 'public']);
    }

    protected static function parseDelimited(string $text): array
    {
        $text = trim($text);
        if ($text === '') return [];
        $delimiter = self::detectDelimiter($text);
        $lines = preg_split("/\r\n|\n|\r/", $text);
        $lines = array_values(array_filter($lines, fn($l) => trim($l) !== ''));
        if (empty($lines)) return [];

        $headersRaw = str_getcsv(array_shift($lines), $delimiter);
        $headers = array_map(fn($h) => self::normalizeHeader($h), $headersRaw);

        $seen = [];
        foreach ($headers as $i => $key) {
            if (!isset($seen[$key])) {
                $seen[$key] = 1;
            } else {
                $seen[$key]++;
                $headers[$i] = $key . '_' . $seen[$key];
            }
        }

        foreach ($headers as $i => $key) {
            if ($key === 'vessel_2') $headers[$i] = 'vessel_capacity';
        }

        $rows = [];
        foreach ($lines as $line) {
            $cols = str_getcsv($line, $delimiter);
            $assoc = [];
            foreach ($headers as $i => $key) {
                $assoc[$key] = $cols[$i] ?? null;
            }
            $rows[] = $assoc;
        }
        return $rows;
    }

    protected static function detectDelimiter(string $text): string
    {
        $c = substr_count($text, ',');
        $t = substr_count($text, "\t");
        $s = substr_count($text, ';');
        if ($t >= $c && $t >= $s) return "\t";
        if ($c >= $t && $c >= $s) return ',';
        return ';';
    }

    protected static function normalizeHeader(string $h): string
    {
        $h = strtolower(trim($h));
        $h = str_replace(['  ', ' ', '-', '__'], [' ', '_', '_', '_'], $h);

        $map = [
            'shipping_line'   => 'line',
            'line'            => 'line',
            'vessel'          => 'vessel',
            'vessel_name'     => 'vessel',
            'kapal'           => 'vessel',
            'voy'             => 'voyage_no',
            'voyage'          => 'voyage_no',
            'voyage_no'       => 'voyage_no',
            'pol_code'        => 'pol',
            'pod_code'        => 'pod',
            'pol'             => 'pol',
            'pod'             => 'pod',
            'eta'             => 'eta',
            'etd'             => 'etd',
            'service'         => 'service',
            'cargo_plan'      => 'cargo_plan',
            'dwelling'        => 'dwelling',
            'jss'             => 'jss',
            'lts'             => 'lts',
            'vessel_capacity' => 'vessel_capacity',
            'capacity'        => 'capacity',
            'direct'          => 'direct',
            'transit'         => 'transit',
        ];

        return $map[$h] ?? $h;
    }

    protected static function parseDateTime(string $value): ?Carbon
    {
        $value = trim($value);
        if ($value === '') return null;

        $formats = [
            'Y-m-d H:i',
            'Y-m-d H:i:s',
            'd-m-Y H:i',
            'd/m/Y H:i',
            'd-M-Y H:i',
            'M d Y H:i',
            'Y-m-d',
            'd-m-Y',
            'd/m/Y',
        ];

        foreach ($formats as $fmt) {
            try {
                $dt = Carbon::createFromFormat($fmt, $value);
                if ($dt !== false) return $dt;
            } catch (\Throwable $e) {
            }
        }

        $ts = strtotime($value);
        return $ts ? Carbon::parse(date('Y-m-d H:i:s', $ts)) : null;
    }

    protected static function resolveShippingLineId(?string $name): ?int
    {
        $name = trim((string) $name);
        if ($name === '') $name = 'Unknown Line';
        $existing = ShippingLine::whereRaw('lower(name) = ?', [strtolower($name)])->first();
        if ($existing) return $existing->id;
        $code = self::makeLineCode($name);
        $base = $code;
        $i = 1;
        while (ShippingLine::where('code', $code)->exists()) {
            $code = $base . $i;
            $i++;
        }
        $m = ShippingLine::create([
            'code' => $code,
            'name' => $name,
        ]);
        return $m->id;
    }

    protected static function makeLineCode(string $name): string
    {
        $u = strtoupper(trim($name));
        $words = preg_split('/\s+/', $u, -1, PREG_SPLIT_NO_EMPTY);
        $initials = implode('', array_map(fn($w) => mb_substr($w, 0, 1), $words));
        $initials = preg_replace('/[^A-Z0-9]/', '', $initials);
        $fallback = preg_replace('/[^A-Z0-9]/', '', $u);
        $candidate = $initials ?: $fallback ?: 'LINE';
        $candidate = substr($candidate, 0, 5);
        return $candidate !== '' ? $candidate : 'LINE';
    }

    protected static function resolveVesselId(?string $name, ?int $shippingLineId = null): ?int
    {
        $name = trim((string)$name);
        if ($name === '') return null;
        if (preg_match('/^\d+$/', $name)) return null;
        $existing = Vessel::where('name', $name)->first();
        if ($existing) return $existing->id;
        if (!$shippingLineId) {
            $shippingLineId = self::resolveShippingLineId('Unknown Line');
        }
        $m = Vessel::create([
            'name'             => $name,
            'shipping_line_id' => $shippingLineId,
        ]);
        return $m->id;
    }

    protected static function resolvePortId(?string $codeOrName): ?int
    {
        $v = trim((string)$codeOrName);
        if ($v === '') return null;
        $byCode = Port::where('code', strtoupper($v))->first();
        if ($byCode) return $byCode->id;
        $byName = Port::whereRaw('lower(name) = ?', [strtolower($v)])->first();
        if ($byName) return $byName->id;
        $genCode = strtoupper(Str::limit(preg_replace('/[^A-Z0-9]/', '', strtoupper($v)), 5, '')) ?: null;
        $m = Port::create(['code' => $genCode, 'name' => $v]);
        return $m->id;
    }

    protected static function toBool(?string $v): ?bool
    {
        $v = strtolower(trim((string)$v));
        if ($v === '') return null;
        $true = ['1', 'true', 'yes', 'y', 'direct', 'd'];
        $false = ['0', 'false', 'no', 'n', 'transit', 't'];
        if (in_array($v, $true, true)) return true;
        if (in_array($v, $false, true)) return false;
        return null;
    }

    protected static function inferDirectFromService(?string $service): ?bool
    {
        $s = strtolower(trim((string)$service));
        if ($s === '') return null;
        if (str_contains($s, 'direct')) return true;
        if (str_contains($s, 'via') || str_contains($s, 'transit')) return false;
        return null;
    }
}
