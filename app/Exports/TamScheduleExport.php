<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class TamScheduleExport implements FromArray, WithHeadings, WithTitle
{
    protected array $rows;
    protected array $headings;
    protected string $title;

    public function __construct(array $rows, array $headings, string $title)
    {
        $this->rows = $rows;
        $this->headings = $headings;
        $this->title = $title;
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function title(): string
    {
        return $this->title;
    }
}
