<?php

namespace App\Services;

use App\Models\UnitInspection;
use App\Models\UnitInspectionItem;

/**
 * InspectionGateEvaluator
 *
 * Menentukan gate_decision berdasarkan items NG dalam satu UnitInspection.
 *
 * Rules (SOP TAM):
 *   major_damage NG      → return_to_pdc
 *   minor_missing NG     → allow_with_remark
 *   information_only NG  → tidak berpengaruh ke gate
 *   semua OK             → accept
 */
class InspectionGateEvaluator
{
    public function evaluate(UnitInspection $inspection): string
    {
        $ngItems = $inspection->items()
            ->where('result', UnitInspectionItem::RESULT_NG)
            ->pluck('finding_type');

        if ($ngItems->isEmpty()) {
            return UnitInspection::GATE_ACCEPT;
        }

        if ($ngItems->contains(UnitInspectionItem::FINDING_MAJOR_DAMAGE)) {
            return UnitInspection::GATE_RETURN_TO_PDC;
        }

        if ($ngItems->contains(UnitInspectionItem::FINDING_MINOR_MISSING)) {
            return UnitInspection::GATE_ALLOW_WITH_REMARK;
        }

        // Semua NG adalah information_only — tidak mempengaruhi gate
        return UnitInspection::GATE_ACCEPT;
    }
}
