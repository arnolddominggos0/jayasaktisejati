<?php

namespace App\Exports;

use App\Services\LeadTimeAnalysisService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EvaluasiVoyageDetailExport implements FromArray, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    public function __construct(private int $voyageId) {}

    public function title(): string
    {
        return 'Detail Voyage';
    }

    public function headings(): array
    {
        return [
            'No Rangka',
            'No Mesin',
            'Voyage',
            'Dwelling (hari)',
            'Status Dwelling',
            'Sailing (hari)',
            'Status Sailing',
            'Dooring (hari)',
            'Status Dooring',
            'LT Total (hari)',
            'Status LT',
        ];
    }

    public function array(): array
    {
        /** @var LeadTimeAnalysisService $svc */
        $svc       = app(LeadTimeAnalysisService::class);
        $units     = $svc->getVoyageUnits($this->voyageId);
        $voyageInfo = $svc->getVoyageInfo($this->voyageId);
        $voyageLabel = $voyageInfo['label'] ?? '';

        return $units->map(fn ($u) => [
            $u['chassis_no'],
            $u['engine_no'],
            $voyageLabel,
            $u['dwelling'],
            $this->stLabel($u['dwelling_st']),
            $u['sailing'],
            $this->stLabel($u['sailing_st']),
            $u['dooring'],
            $this->stLabel($u['dooring_st']),
            $u['lt_total'],
            $this->stLabel($u['lt_status']),
        ])->all();
    }

    private function stLabel(string $st): string
    {
        return match ($st) {
            'OK'   => 'OK',
            'LATE' => 'NG',
            default => '-',
        };
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
