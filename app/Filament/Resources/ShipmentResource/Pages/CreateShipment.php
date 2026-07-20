<?php

namespace App\Filament\Resources\ShipmentResource\Pages;

use App\Enums\CargoType;
use App\Enums\RequestType;
use App\Enums\ShipmentMode;
use App\Enums\ShipmentStatus;
use App\Filament\Resources\ShipmentResource;
use App\Models\Customer;
use App\Models\Shipment;
use App\Models\Voyage;
use App\Services\ShipmentService;
use App\Support\Intake\IntakePrefill;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateShipment extends CreateRecord
{
    protected static string $resource = ShipmentResource::class;

    protected static bool $canCreateAnother = false;

    /**
     * OCR-01 — Review layer holder. Diisi oleh FileUpload::afterStateUpdated
     * (via SppbAssistService::assist) TANPA menyentuh form state. Envelope
     * ini menunggu keputusan eksplisit Office Admin di Extraction Summary.
     * Null / empty = perilaku wizard identik dengan entri manual.
     * IntakePrefill implements Wireable — aman menyeberangi request Livewire.
     */
    public ?IntakePrefill $intakePrefill = null;

    /** OCR-02 — true setelah [Terapkan ke Formulir]; summary jadi ringkas. */
    public bool $intakeApplied = false;

    /**
     * UX-RECOMPOSE-01 — Hero subheading. Judul "Permintaan Pengiriman"
     * berasal dari resource label; ini melengkapinya dengan penjelasan
     * konteks operasional + status draft. Presentasi murni — tidak ada
     * perubahan workflow/validasi/data.
     */
    public function getSubheading(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        // UX v2.1: hero ringkas — satu kalimat + badge status kecil.
        return new \Illuminate\Support\HtmlString(
            '<span class="jss-hero-lead">Buat permintaan baru berdasarkan <strong>SPPB</strong> atau '
            . '<strong>Delivery Order</strong>.</span>'
            . '<span class="jss-hero-status">🟢 Draft Baru</span>'
        );
    }

    /**
     * UX v2.1 — Primary action lebih dominan ("Buat Permintaan", size lg),
     * "Batal" sekunder. Hanya label & ukuran; submit handler bawaan
     * CreateRecord tidak diubah.
     */
    protected function getCreateFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateFormAction()
            ->label('Buat Permintaan')
            ->icon('heroicon-m-paper-airplane')
            ->size(\Filament\Support\Enums\ActionSize::Large);
    }

    protected function getCancelFormAction(): \Filament\Actions\Action
    {
        return parent::getCancelFormAction()
            ->label('Batal');
    }

    /*
    |--------------------------------------------------------------------------
    | OCR-02 — Review → Apply
    |
    | Aturan arsitektur: form TIDAK PERNAH berubah karena upload. Envelope
    | pindah ke form state HANYA lewat aksi eksplisit di bawah. Voyage tidak
    | pernah di-assign dari ekstraksi — hint tinggal di summary.
    |--------------------------------------------------------------------------
    */

    /**
     * [Terapkan ke Formulir] — pindahkan IntakePrefill ke form state.
     * Default: tidak menimpa field yang sudah diisi manual oleh admin.
     * $force = true ([Terapkan ulang]): nilai ekstraksi menimpa isi field.
     */
    public function applyIntakePrefill(bool $force = false): void
    {
        $prefill = $this->intakePrefill;

        if ($prefill === null || $prefill->isEmpty()) {
            return;
        }

        $appliedPaths = [];

        $apply = function (string $key, mixed $value) use ($force, &$appliedPaths): void {
            if ($value === null || $value === '') {
                return;
            }
            if (! $force && ! empty($this->data[$key])) {
                return; // hormati isian manual admin
            }
            $this->data[$key] = $value;
            $appliedPaths[] = "data.{$key}";
        };

        // Document
        $apply('doc_number', $prefill->document['number'] ?? null);

        // requested_at ber-default "hari ini" dari form — default bukan
        // isian manual, jadi tanggal dokumen boleh menggantikannya.
        $docDate = $prefill->document['date'] ?? null;
        if ($docDate !== null && ($this->data['requested_at'] ?? null) === now()->format('Y-m-d')) {
            $this->data['requested_at'] = $docDate;
            $appliedPaths[] = 'data.requested_at';
        } else {
            $apply('requested_at', $docDate);
        }

        // Copy fields (klaim scalar)
        $apply('delivery_scope', $prefill->copyFields['delivery_scope']['value'] ?? null);
        $apply('notes', $prefill->copyFields['notes']['value'] ?? null);

        // OCR-02A Rule 2 & 3 — fakta dokumen, bukan keputusan user:
        // SPPB adalah dokumen pengiriman kendaraan via laut ⇒ moda = Laut;
        // manifest ber-VIN/engine/model ⇒ jenis muatan = Unit Kendaraan.
        if (($prefill->source['channel'] ?? null) === RequestType::SPPB_DO->value) {
            $apply('mode', ShipmentMode::Sea->value);
        }
        if (($prefill->manifest['detected_count'] ?? 0) > 0) {
            $apply('cargo_type', CargoType::Vehicle->value);
        }

        // DOMAIN-02 — Customer TIDAK berasal dari OCR. Kop dokumen adalah
        // DEALER: resolve ke master Dealer → dealer_id, lalu customer_id
        // DITURUNKAN dari dealer.customer_id. Dealer tak ditemukan →
        // keduanya kosong, Office Admin memilih manual.
        $dealerSuggestion = $prefill->suggestionFor('dealer_id');
        if ($dealerSuggestion !== null) {
            $apply('dealer_id', $dealerSuggestion['value'] ?? null);
            $apply('customer_id', $dealerSuggestion['customer_id'] ?? null);
        }

        // Snapshot lokasi jemput dari dokumen (kolom baru DOMAIN-02).
        $apply('pickup_location', $prefill->copyFields['pickup_location']['value'] ?? null);

        // Kompatibilitas — receiver tetap seperti sebelumnya (bukan scope sprint).
        $apply('receiver_id', $prefill->suggestionFor('receiver_id')['value'] ?? null);

        // Rule 6 — Destination City dari RELASI MASTER DATA receiver
        // (bukan hasil OCR kota). Hanya bila receiver ter-resolve dan
        // punya city_id; selain itu biarkan admin memilih.
        $receiverId = $this->data['receiver_id'] ?? null;
        if (! empty($receiverId)) {
            $receiverCityId = Customer::whereKey($receiverId)->value('city_id');
            $apply('destination_city_id', $receiverCityId);
        }

        // OCR-02B — fallback: destination_city_hint → lookup Master City.
        // Generik (hint diturunkan dari pola dokumen, bukan daftar kota).
        // Diisi HANYA bila lookup menghasilkan TEPAT SATU kota; tidak
        // ditemukan / ambigu → biarkan kosong, admin memilih manual.
        if (empty($this->data['destination_city_id'])) {
            $cityHint = $prefill->copyFields['destination_city_hint']['value'] ?? null;
            $cityId   = $this->resolveCityIdFromHint($cityHint);
            $apply('destination_city_id', $cityId);
        }

        // Rule 7 — pickup_location OCR hanya informasi di summary; Origin
        // (Cabang Asal) tetap mengikuti Smart Origin by Branch yang ada
        // (tidak disentuh).

        // Manifest → repeater units. Semua row tetap editable, tanpa lock.
        $manifestUnits = $prefill->manifest['units'] ?? [];
        if ($manifestUnits !== []) {
            $existing = array_filter(
                $this->data['units'] ?? [],
                function ($row) {
                    foreach (['model_no', 'reg_no', 'chassis_no', 'engine_no', 'color', 'do_number'] as $f) {
                        if (! empty($row[$f])) {
                            return true;
                        }
                    }

                    return false;
                }
            );

            if ($force || $existing === []) {
                $rows = [];
                foreach ($manifestUnits as $unit) {
                    $rows[(string) \Illuminate\Support\Str::uuid()] = [
                        'model_no'          => $unit['model'] ?? null,
                        'reg_no'            => $unit['reg_no'] ?? null,
                        'chassis_no'        => $unit['vin'] ?? null,
                        'engine_no'         => $unit['engine'] ?? null,
                        'color'             => $unit['color'] ?? null,
                        'do_number'         => $unit['do_number'] ?? null,
                        'qty'               => $unit['qty'] ?? 1,
                        'notes'             => null,
                        'container_display' => null,
                    ];
                }
                $this->data['units'] = $rows;
            }
        }

        // Voyage: SENGAJA tidak di-set — hint tetap di summary (frozen rule).

        $this->intakeApplied = true;

        // §6 — highlight halus pada field hasil ekstraksi (hilang saat diedit).
        // UX-02: customer_id dikecualikan — status readonly-nya (terkunci saat
        // dealer terisi) sudah menjadi indikator "sistem telah memahami";
        // dua sinyal untuk satu pesan jadi redundan.
        $this->dispatch('intake-prefill-applied', fields: array_values(array_filter(
            $appliedPaths,
            fn (string $path) => $path !== 'data.customer_id',
        )));

        \Filament\Notifications\Notification::make()
            ->title('Hasil ekstraksi diterapkan ke formulir')
            ->body(count($appliedPaths) . ' field terisi' . ($manifestUnits !== [] ? ', ' . count($manifestUnits) . ' unit masuk ke daftar.' : '.'))
            ->success()
            ->send();
    }

    /**
     * OCR-02B — hint kota → city_id, hanya bila TEPAT SATU kota cocok.
     * Mencoba hint utuh dulu; bila hint multi-kata dan gagal unik, coba
     * kata terakhirnya (nama kota lazim berada di ekor teks tujuan).
     * Tetap generik: tidak ada aturan per-kota.
     */
    protected function resolveCityIdFromHint(?string $hint): ?int
    {
        if ($hint === null || trim($hint) === '') {
            return null;
        }

        $lookup = function (string $candidate): ?int {
            $matches = \App\Models\City::query()
                ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower(trim($candidate)) . '%'])
                ->where('is_active', true)
                ->limit(2)
                ->pluck('id');

            return $matches->count() === 1 ? (int) $matches->first() : null;
        };

        $cityId = $lookup($hint);
        if ($cityId !== null) {
            return $cityId;
        }

        $words = preg_split('/\s+/', trim($hint)) ?: [];
        if (count($words) > 1) {
            $last = end($words);
            if (is_string($last) && mb_strlen($last) >= 4) {
                return $lookup($last);
            }
        }

        return null;
    }

    /** [Abaikan] — buang envelope; form tetap kosong, isi manual. */
    public function ignoreIntakePrefill(): void
    {
        $this->intakePrefill = null;
        $this->intakeApplied = false;
    }

    protected function getRedirectUrl(): string
    {
        return ShipmentResource::getUrl('index');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()->label('Buat Permintaan'),
            $this->getCancelFormAction()->label('Batal')->url(ShipmentResource::getUrl('index')),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $mode = $data['mode'] ?? null;

        // ── Branch + Depot resolution ──────────────────────────────────────
        // Source of truth for SEA: POL (Port of Loading — origin port) from the Voyage.
        // Source of truth for LAND: the logged-in user's effective branch.
        // Never derive branch from the admin user's account for SEA shipments.

        if ($mode === 'sea') {
            $polId = null;

            // Snapshot voyage fields into shipment — voyage is the source of truth,
            // shipment must carry its own copy so gate resolution never depends on voyage FK.
            if (! empty($data['voyage_id'])) {
                $voyage = Voyage::whereKey($data['voyage_id'])->first(['pol_id', 'pod_id']);
                if ($voyage) {
                    $data['pol_id'] = !empty($data['pol_id'])
                        ? $data['pol_id']
                        : $voyage->pol_id;

                    $data['pod_id'] = !empty($data['pod_id'])
                        ? $data['pod_id']
                        : $voyage->pod_id;
                    $polId = $voyage->pol_id;
                }
            }

            if ($polId) {
                // Ownership follows origin depot (POL), not destination.
                $resolved = app(ShipmentService::class)->resolveByPol($polId);

                if ($resolved) {
                    // Always override — form pre-fill may have used POD (wrong).
                    // Server-side POL resolution is the canonical source of truth.
                    $data['branch_id']         = $resolved['branch_id'];
                    $data['assigned_depot_id'] = $resolved['depot_id'];

                    if (empty($data['coordinator_id']) && $resolved['coordinator_id']) {
                        $data['coordinator_id'] = $resolved['coordinator_id'];
                    }
                }
            }

            if (empty($data['assigned_depot_id'])) {
                throw ValidationException::withMessages([
                    'assigned_depot_id' => 'Depo tidak ditemukan untuk rute ini. Pastikan POL voyage sudah dikonfigurasi di menu Depo.',
                ]);
            }
        } else {
            // LAND: branch dari user yang login
            if (empty($data['branch_id'])) {
                $data['branch_id'] = Auth::user()?->effectiveBranchId();
            }
        }

        if (empty($data['branch_id'])) {
            throw ValidationException::withMessages([
                'branch_id' => 'Cabang tidak dapat ditentukan. Untuk SEA: pastikan voyage memiliki POD dengan depo terkonfigurasi.',
            ]);
        }

        // Smart Origin by Branch — backend protection (always override).
        // Office is no longer involved in this flow (migrated 2026-07-20,
        // see docs/master-office/SMART-ORIGIN-MIGRATION-BLOCKED-SCHEMA-GAP.md).
        // Primary: resolve from authenticated user's branch.
        // Fallback: resolve from the shipment's final branch_id (e.g. super admin SEA where branch came from POL).
        $resolvedOrigin = ShipmentResource::resolveOriginCityFromUser();
        if (! $resolvedOrigin['city_id'] && ! empty($data['branch_id'])) {
            $resolvedOrigin = ShipmentResource::resolveOriginCityFromUser((int) $data['branch_id']);
        }
        if ($resolvedOrigin['city_id']) {
            $data['origin_city_id'] = $resolvedOrigin['city_id'];
        }

        // ── Code ───────────────────────────────────────────────────────────
        if (empty($data['code'])) {
            $data['code'] = Shipment::generateCode($data['mode'] ?? null);
        }

        $requestType = (string)($data['request_type'] ?? '');
        $docNumber   = isset($data['doc_number']) ? trim((string)$data['doc_number']) : null;
        $data['doc_number'] = $docNumber ?: null;

        if ($requestType === RequestType::SPPB_DO->value && empty($data['doc_number'])) {
            throw ValidationException::withMessages([
                'doc_number' => 'No. Dokumen SPPB/DO wajib diisi.',
            ]);
        }

        if ($requestType === RequestType::WALK_IN->value && empty($data['doc_number'])) {
            $data['doc_number'] = 'JSS-' . now()->format('Ymd-His');
        }

        $base = null;
        if (!empty($data['etd'])) {
            try {
                $base = Carbon::parse($data['etd']);
            } catch (\Throwable) {
            }
        }
        if (!$base && !empty($data['requested_at'])) {
            try {
                $base = Carbon::parse($data['requested_at']);
            } catch (\Throwable) {
            }
        }

        $modeCode = match (strtolower((string)($data['mode'] ?? 'land'))) {
            'sea', 'sea_freight' => 'SH',
            default              => 'TC',
        };

        $priority = strtolower((string)($data['priority'] ?? 'normal'));
        $priority = in_array($priority, ['normal', 'urgent'], true) ? $priority : 'normal';

        $data['eta'] = Shipment::computeEta($modeCode, $priority, $base)->toDateTimeString();

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $units = $data['units'] ?? null;

        unset($data['units']);

        $shipment = Shipment::create($data);

        if (is_array($units) && count($units) > 0) {
            $toInsert = [];
            foreach ($units as $u) {
                $toInsert[] = [
                    'model_no'          => $u['model_no'] ?? null,
                    'reg_no'            => $u['reg_no'] ?? null,
                    'chassis_no'        => $u['chassis_no'] ?? null,
                    'engine_no'         => $u['engine_no'] ?? null,
                    'color'             => $u['color'] ?? null,
                    'do_number'         => $u['do_number'] ?? null,
                    'qty'               => isset($u['qty']) ? (int)$u['qty'] : 1,
                    'container_display' => $u['container_display'] ?? null,
                    'notes'             => $u['notes'] ?? null,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ];
            }

            $shipment->units()->createMany($toInsert);
        }

        $shipment->forceFill(['status' => ShipmentStatus::Pending])->saveQuietly();

        return $shipment;
    }
}
