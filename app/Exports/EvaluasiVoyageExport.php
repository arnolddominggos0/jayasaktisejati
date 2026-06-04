<?php

namespace App\Exports;

use App\Services\LeadTimeAnalysisService;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EvaluasiVoyageExport implements FromArray, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    public function __construct(
        private Carbon  $start,
        private Carbon  $end,
        private ?string $voyageSearch = null
    ) {}

    public function title(): string
    {
        return 'Evaluasi Voyage';
    }

    public function headings(): array
    {
        return [
            'Voyage',
            'No Voyage',
            'Bulan',
            'Qty Unit',
            'Avg Dwelling (hari)',
            'Avg Sailing (hari)',
            'Avg Dooring (hari)',
            'Avg LT Total (hari)',
            'OK',
            'NG',
            'ETD',
            'ATA',
        ];
    }

    public function array(): array
    {
        /** @var LeadTimeAnalysisService $svc */
        $svc  = app(LeadTimeAnalysisService::class);
        $rows = $svc->getVoyageSummaries($this->start, $this->end, $this->voyageSearch);

        return $rows->map(fn ($r) => [
            $r['vessel_name'],
            $r['voyage_no'],
            $r['period_label'],
            $r['qty_unit'],
            $r['avg_dwelling'],
            $r['avg_sailing'],
            $r['avg_dooring'],
            $r['avg_lt'],
            $r['ok_count'],
            $r['ng_count'],
            $r['etd'] ? Carbon::parse($r['etd'])->format('d/m/Y') : '',
            $r['ata'] ? Carbon::parse($r['ata'])->format('d/m/Y') : '',
        ])->all();
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
