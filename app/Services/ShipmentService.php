<?php

namespace App\Services;

use App\Enums\ShipmentMode;
use App\Models\Customer;
use App\Models\Depot;
use App\Models\Shipment;
use App\Models\Voyage;
use Illuminate\Support\Collection;

class ShipmentService
{
    public function resolveDepotId(?int $branchId, ?string $mode, ?int $voyageId = null): ?int
    {
        if (! $branchId || ! $mode) {
            return null;
        }

        $q = Depot::query()
            ->where('branch_id', $branchId)
            ->where('mode', $mode);

        if ($mode === ShipmentMode::Sea->value && $voyageId) {
            $polId = Voyage::whereKey($voyageId)->value('pol_id');
            if ($polId) {
                $byPol = (clone $q)->where('port_id', $polId)->orderBy('name')->value('id');
                if ($byPol) {
                    return (int) $byPol;
                }
            }
        }

        return $q->orderBy('name')->value('id');
    }

    /**
     * Resolve origin branch_id, depot_id, and coordinator_id for a SEA shipment
     * from the POL (Port of Loading — origin port).
     *
     * Ownership always follows the origin depot, not the destination.
     * Returns ['branch_id' => int, 'depot_id' => int, 'coordinator_id' => int|null]
     * or null if no depot is configured for that port.
     */
    public function resolveByPol(?int $polId): ?array
    {
        if (! $polId) {
            return null;
        }

        $depot = Depot::query()
            ->where('port_id', $polId)
            ->where('mode', ShipmentMode::Sea->value)
            ->select(['id', 'branch_id', 'coordinator_user_id'])
            ->first();

        if (! $depot) {
            return null;
        }

        return [
            'branch_id'      => (int) $depot->branch_id,
            'depot_id'       => (int) $depot->id,
            'coordinator_id' => $depot->coordinator_user_id ? (int) $depot->coordinator_user_id : null,
        ];
    }

    /**
     * Resolve branch_id and depot_id for a SEA shipment from the POD (Port of Discharge).
     *
     * Kept for gate resolver and destination-depot lookups.
     * NOT used for shipment ownership — use resolveByPol() for ownership.
     * Returns ['branch_id' => int, 'depot_id' => int] or null if not found.
     */
    public function resolveByPod(?int $podId): ?array
    {
        if (! $podId) {
            return null;
        }

        $depot = Depot::query()
            ->where('port_id', $podId)
            ->where('mode', ShipmentMode::Sea->value)
            ->select(['id', 'branch_id'])
            ->first();

        if (! $depot) {
            return null;
        }

        return [
            'branch_id' => (int) $depot->branch_id,
            'depot_id'  => (int) $depot->id,
        ];
    }

    public function resolveAutoContacts(int $sourceId, string $type = 'pickup'): array
    {
        $source = Customer::query()
            ->select(['name', 'pic_name', 'phone', 'pic_phone', 'address'])
            ->find($sourceId);

        if (! $source) {
            return ['name' => null, 'phone' => null, 'address' => null];
        }

        return [
            'name' => $source->pic_name ?: $source->name,
            'phone' => $source->pic_phone ?: $source->phone,
            'address' => $source->address,
        ];
    }

    public function recalculateLclTotals(array $items, ?string $weightOverride = null): array
    {
        $sumCbm = 0.0;
        $sumPkg = 0;
        $sumItemKg = 0.0;

        foreach ($items as $r) {
            $qty = (int) ($r['qty'] ?? 0);
            $p = (float) ($r['length_cm'] ?? 0);
            $l = (float) ($r['width_cm'] ?? 0);
            $t = (float) ($r['height_cm'] ?? 0);
            $w = (float) ($r['weight_kg'] ?? 0);

            $sumCbm += ($p * $l * $t * $qty) / 1_000_000;
            $sumPkg += $qty;
            $sumItemKg += ($w * $qty);
        }

        $weightTotal = null;
        if (trim((string) $weightOverride) !== '' && is_numeric($weightOverride)) {
            $weightTotal = round((float) $weightOverride, 2);
        } elseif ($sumItemKg > 0) {
            $weightTotal = round($sumItemKg, 2);
        }

        return [
            'cbm_total' => round($sumCbm, 3),
            'packages_total' => $sumPkg,
            'weight_total' => $weightTotal,
        ];
    }

    public function fanOutContainerNo(?string $containerNo, ?string $sealNo): string
    {
        $no = trim((string) ($containerNo ?? ''));
        $seal = trim((string) ($sealNo ?? ''));

        if ($no === '' && $seal === '') {
            return '–';
        }

        return $seal !== '' ? "{$no} • {$seal}" : $no;
    }

    public function resetFieldsForModeChange(): array
    {
        return [
            'vessel_name',
            'voyage',
            'pol',
            'pod',
            'etd',
            'eta',
            'vehicle_type',
            'vehicle_plate',
            'driver_name',
            'driver_phone',
            'pickup_date',
            'service_option',
            'voyage_id',
            'driver_id',
            'lcl_items',
            'cbm_total',
            'packages_total',
            'weight_total',
            'weight_total_input',
            'container_size',
            'container_qty',
            'container_size_vehicle',
            'container_qty_vehicle',
        ];
    }

    public function syncUnits(Shipment $shipment, array $units): array
    {
        $existing = $shipment->units()->get()->keyBy('id');
        $keepIds  = [];
        $created  = 0;
        $updated  = 0;
        $deleted  = 0;

        // Track whether at least one valid unit row was processed.
        // Guards against blank-row-only submissions (e.g. the Repeater's
        // afterStateHydrated default of [['qty'=>1]]) from nuking all
        // existing units via the diff-delete logic below.
        $hasAnyValidUnit = false;

        foreach ($units as $u) {
            // Require at least one identifying field to treat the row as a real
            // unit. Previously only `qty` was checked, and a blank row with
            // qty=1 (the afterStateHydrated default) would pass the check and
            // delete all existing units as a side-effect.
            $hasIdentifier = ! empty($u['chassis_no'])
                || ! empty($u['engine_no'])
                || ! empty($u['model_no'])
                || ! empty($u['reg_no']);

            if (! $hasIdentifier) {
                continue;
            }

            $hasAnyValidUnit = true;
            $id = $u['id'] ?? null;

            if ($id && $existing->has($id)) {
                // Preserve container_display if the form did not explicitly submit it.
                // FC assigns container_display via the Handover action — Office Admin
                // form hides the field (cargo_type=vehicle) so it must never overwrite.
                $existingUnit       = $existing->get($id);
                $containerDisplay   = array_key_exists('container_display', $u)
                    ? ($u['container_display'] ?: null)
                    : $existingUnit?->container_display;

                $shipment->units()->whereKey($id)->update([
                    'model_no'          => $u['model_no'] ?? null,
                    'reg_no'            => $u['reg_no'] ?? null,
                    'chassis_no'        => $u['chassis_no'] ?? null,
                    'engine_no'         => $u['engine_no'] ?? null,
                    'color'             => $u['color'] ?? null,
                    'do_number'         => $u['do_number'] ?? null,
                    'qty'               => isset($u['qty']) ? (int) $u['qty'] : 1,
                    'container_display' => $containerDisplay,
                    'notes'             => $u['notes'] ?? null,
                ]);
                $keepIds[] = $id;
                $updated++;
            } else {
                $new = $shipment->units()->create([
                    'model_no'          => $u['model_no'] ?? null,
                    'reg_no'            => $u['reg_no'] ?? null,
                    'chassis_no'        => $u['chassis_no'] ?? null,
                    'engine_no'         => $u['engine_no'] ?? null,
                    'color'             => $u['color'] ?? null,
                    'do_number'         => $u['do_number'] ?? null,
                    'qty'               => isset($u['qty']) ? (int) $u['qty'] : 1,
                    'container_display' => $u['container_display'] ?? null,
                    'notes'             => $u['notes'] ?? null,
                ]);
                $keepIds[] = $new->id;
                $created++;
            }
        }

        // Only run the delete diff if at least one valid unit was processed.
        // If every submitted row was blank (no identifier), preserve the
        // existing units untouched — do not destroy them.
        if ($hasAnyValidUnit) {
            $deleteIds = $existing->keys()->diff($keepIds)->toArray();
            if (! empty($deleteIds)) {
                $shipment->units()->whereKey($deleteIds)->delete();
                $deleted = count($deleteIds);
            }
        }

        return ['created' => $created, 'updated' => $updated, 'deleted' => $deleted];
    }

    public function formatVoyageLabel(Voyage $v): string
    {
        return sprintf(
            '%s / %s — %s (%s → %s)',
            $v->vessel?->name ?: '-',
            $v->voyage_no,
            $v->etd ? $v->etd->format('d M Y H:i') : '-',
            $v->pol?->code ?: $v->pol?->name ?: '-',
            $v->pod?->code ?: $v->pod?->name ?: '-',
        );
    }

    public function getVoyageOptions(?string $search = null, int $limit = 50): Collection
    {
        $q = Voyage::with(['vessel', 'pol', 'pod'])->orderByDesc('etd');

        if ($search) {
            $q->where('voyage_no', 'ilike', "%{$search}%")
                ->orWhereHas('vessel', fn ($q) => $q->where('name', 'ilike', "%{$search}%"))
                ->orWhereHas('pol', fn ($q) => $q->where('code', 'ilike', "%{$search}%")->orWhere('name', 'ilike', "%{$search}%"))
                ->orWhereHas('pod', fn ($q) => $q->where('code', 'ilike', "%{$search}%")->orWhere('name', 'ilike', "%{$search}%"));
        }

        return $q->limit($limit)->get()->mapWithKeys(fn ($v) => [
            $v->id => $this->formatVoyageLabel($v),
        ]);
    }
}
