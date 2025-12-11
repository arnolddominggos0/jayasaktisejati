<?php

namespace App\Supports\ShippingCalendar\DTO;

class Chip
{
    public string $id;
    public string $short;
    public string $label;
    public array $voyages;
    public int $count;
    public int $plan;
    public ?int $lead;
    public ?int $lead_time;
    public string $class;
    public bool $is_urgent;
    public ?string $sla_status;
    public ?int $voyage_id;
    public ?int $schedule_id;
    public string $head;
    public string $sub;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? uniqid('chip_', true);
        $this->short = $data['short'] ?? '';
        $this->label = $data['label'] ?? '';
        $this->voyages = $data['voyages'] ?? [];
        $this->count = $data['count'] ?? 0;
        $this->plan = (int)($data['plan'] ?? 0);
        $this->lead = isset($data['lead']) ? (int)$data['lead'] : null;
        $this->lead_time = isset($data['lead_time']) ? (int)$data['lead_time'] : ($this->lead);
        $this->class = $data['class'] ?? '';
        $this->is_urgent = (bool)($data['is_urgent'] ?? false);
        $this->sla_status = $data['sla_status'] ?? null;
        $this->voyage_id = isset($data['voyage_id']) ? (int)$data['voyage_id'] : null;
        $this->schedule_id = isset($data['schedule_id']) ? (int)$data['schedule_id'] : null;
        $this->head = $data['head'] ?? '';
        $this->sub = $data['sub'] ?? '';
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'short' => $this->short,
            'label' => $this->label,
            'voyages' => $this->voyages,
            'count' => $this->count,
            'plan' => $this->plan,
            'lead' => $this->lead,
            'lead_time' => $this->lead_time,
            'class' => $this->class,
            'is_urgent' => $this->is_urgent,
            'sla_status' => $this->sla_status,
            'voyage_id' => $this->voyage_id,
            'schedule_id' => $this->schedule_id,
            'head' => $this->head,
            'sub' => $this->sub,
        ];
    }
}
