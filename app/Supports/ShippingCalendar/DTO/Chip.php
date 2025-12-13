<?php

namespace App\Supports\ShippingCalendar\DTO;

class Chip
{
    public string $short;
    public string $label;
    public array $voyages;
    public int $count;
    public int $plan;
    public $lead;
    public string $class;
    public string $vessel_key;
    public array $color;
    public string $style;

    public function __construct(array $data = [])
    {
        $this->short = $data['short'] ?? '';
        $this->label = $data['label'] ?? '';
        $this->voyages = $data['voyages'] ?? [];
        $this->count = $data['count'] ?? 0;
        $this->plan = $data['plan'] ?? 0;
        $this->lead = $data['lead'] ?? null;
        $this->class = $data['class'] ?? '';
        $this->vessel_key = $data['vessel_key'] ?? ($data['short'] ?? '');
        $this->color = $data['color'] ?? ['bg' => '#ffffff', 'text' => '#000000', 'border' => '#dddddd'];
        $this->style = $data['style'] ?? '';
    }
}
