<?php

namespace App\Supports\ShippingCalendar\DTO;

class KpiResult
{
    public int $total;
    public int $onTime;
    public int $late;
    public int $urgent;
    public ?float $avgLeadTime;
    public int $completion;

    public function __construct(array $data = [])
    {
        $this->total = $data['total'] ?? 0;
        $this->onTime = $data['on_time'] ?? 0;
        $this->late = $data['late'] ?? 0;
        $this->urgent = $data['urgent'] ?? 0;
        $this->avgLeadTime = isset($data['avg_lead_time']) ? (float)$data['avg_lead_time'] : null;
        $this->completion = $data['completion'] ?? 0;
    }

    public function toArray(): array
    {
        return [
            'total' => $this->total,
            'on_time' => $this->onTime,
            'late' => $this->late,
            'urgent' => $this->urgent,
            'avg_lead_time' => $this->avgLeadTime,
            'completion' => $this->completion,
        ];
    }
}
