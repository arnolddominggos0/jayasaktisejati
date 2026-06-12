<?php

namespace App\Services;

use App\Models\Unit;
use App\Models\UnitInspection;
use App\Models\UnitInspectionItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * UnitInspectionGenerator
 *
 * Membuat inspection history otomatis untuk satu atau banyak unit.
 * Digunakan oleh:
 *   - artisan inspections:generate-historical
 *   - Tinker manual: $generator->generateHistorical($unit, [...stages])
 *
 * Default behavior untuk historical_import:
 *   - Semua item result = 'ok', finding_type = null
 *   - Status header = 'passed', gate_decision = 'accept'
 *   - Source = 'historical_import'
 *   - Skip stage yang sudah ada (idempotent)
 */
class UnitInspectionGenerator
{
    private array $templates;

    public function __construct()
    {
        $this->templates = config('unit_inspection_templates', []);
    }

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Generate historical inspection untuk satu unit, untuk stages yang diminta.
     *
     * @param  Unit       $unit
     * @param  string[]   $stages
     * @param  Carbon|null $checkedAt
     * @return array { unit_id, inspections_created, items_created, skipped_stages }
     */
    public function generateHistorical(
        Unit $unit,
        array $stages,
        ?Carbon $checkedAt = null
    ): array {
        $checkedAt = $checkedAt ?? ($unit->created_at ? Carbon::parse($unit->created_at) : now());

        $inspectionsCreated = 0;
        $itemsCreated       = 0;
        $skipped            = [];

        $validStages   = array_intersect($stages, UnitInspection::STAGES);
        $invalidStages = array_diff($stages, UnitInspection::STAGES);

        if ($invalidStages) {
            Log::warning('UnitInspectionGenerator: invalid stages', [
                'unit_id' => $unit->id,
                'invalid' => $invalidStages,
            ]);
        }

        $existingStages = UnitInspection::where('unit_id', $unit->id)
            ->pluck('stage')
            ->toArray();

        DB::transaction(function () use (
            $unit, $validStages, $checkedAt, $existingStages,
            &$inspectionsCreated, &$itemsCreated, &$skipped
        ) {
            foreach ($validStages as $stage) {
                if (in_array($stage, $existingStages)) {
                    $skipped[] = $stage;
                    continue;
                }

                $inspection = UnitInspection::create([
                    'unit_id'       => $unit->id,
                    'stage'         => $stage,
                    'status'        => UnitInspection::STATUS_PASSED,
                    'source'        => UnitInspection::SOURCE_HISTORICAL_IMPORT,
                    'gate_decision' => UnitInspection::GATE_ACCEPT,
                    'checked_at'    => $checkedAt,
                    'submitted_at'  => $checkedAt,
                    'notes'         => 'Historical sample import',
                ]);

                $inspectionsCreated++;

                foreach ($this->buildItems($stage) as $item) {
                    UnitInspectionItem::create([
                        'unit_inspection_id' => $inspection->id,
                        'category'           => $item['category'],
                        'item_name'          => $item['item_name'],
                        'result'             => UnitInspectionItem::RESULT_OK,
                        'finding_type'       => null,
                        'notes'              => null,
                    ]);
                    $itemsCreated++;
                }
            }
        });

        return [
            'unit_id'             => $unit->id,
            'inspections_created' => $inspectionsCreated,
            'items_created'       => $itemsCreated,
            'skipped_stages'      => $skipped,
        ];
    }

    /**
     * Generate untuk banyak unit sekaligus.
     */
    public function generateHistoricalBatch(
        iterable $units,
        array $stages,
        ?Carbon $checkedAt = null
    ): array {
        $totalUnits       = 0;
        $totalInspections = 0;
        $totalItems       = 0;
        $totalSkipped     = 0;

        foreach ($units as $unit) {
            $result = $this->generateHistorical($unit, $stages, $checkedAt);
            $totalUnits++;
            $totalInspections += $result['inspections_created'];
            $totalItems       += $result['items_created'];
            $totalSkipped     += count($result['skipped_stages']);
        }

        return [
            'units_processed'     => $totalUnits,
            'inspections_created' => $totalInspections,
            'items_created'       => $totalItems,
            'skipped_total'       => $totalSkipped,
        ];
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Bangun flat list items [{category, item_name, type}] untuk stage tertentu.
     * handover_depot → inherit template dari pickup.
     */
    private function buildItems(string $stage): array
    {
        $templateKey = ($stage === 'handover_depot') ? 'pickup' : $stage;
        $template    = $this->templates[$templateKey] ?? [];

        if ($template === null) {
            $template = $this->templates['pickup'] ?? [];
        }

        $items = [];
        foreach ($template as $category => $categoryItems) {
            foreach ($categoryItems as $item) {
                $items[] = [
                    'category'  => $category,
                    'item_name' => $item['name'],
                    'type'      => $item['type'],
                ];
            }
        }

        return $items;
    }

    /**
     * Hitung total items yang akan dihasilkan per stage (dry-run / preview).
     */
    public function countItemsForStage(string $stage): int
    {
        return count($this->buildItems($stage));
    }

    /**
     * Preview — berapa inspection & items yang akan dibuat untuk unit tertentu.
     * Tidak membuat data, hanya hitung.
     */
    public function preview(Unit $unit, array $stages): array
    {
        $existing = UnitInspection::where('unit_id', $unit->id)->pluck('stage')->toArray();
        $preview  = [];

        foreach ($stages as $stage) {
            if (!in_array($stage, UnitInspection::STAGES)) {
                $preview[$stage] = ['status' => 'invalid_stage', 'items' => 0];
                continue;
            }
            if (in_array($stage, $existing)) {
                $preview[$stage] = ['status' => 'already_exists', 'items' => 0];
                continue;
            }
            $preview[$stage] = [
                'status' => 'will_create',
                'items'  => $this->countItemsForStage($stage),
            ];
        }

        return $preview;
    }
}
