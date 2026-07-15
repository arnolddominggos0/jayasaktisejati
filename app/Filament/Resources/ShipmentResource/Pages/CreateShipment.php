<?php

namespace App\Filament\Resources\ShipmentResource\Pages;

use App\Enums\RequestType;
use App\Enums\ShipmentStatus;
use App\Filament\Resources\ShipmentResource;
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
     * ini menunggu Review Summary (OCR-02) dan Apply eksplisit (OCR-03).
     * Null / empty = perilaku wizard identik dengan entri manual.
     * IntakePrefill implements Wireable — aman menyeberangi request Livewire.
     */
    public ?IntakePrefill $intakePrefill = null;

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

        // Smart Origin by Office — backend protection (always override).
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
