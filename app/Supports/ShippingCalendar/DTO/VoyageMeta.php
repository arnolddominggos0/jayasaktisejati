<?php

namespace App\Supports\ShippingCalendar\DTO;

class VoyageMeta
{
    public ?int $voyageId;
    public ?int $vesselId;
    public ?string $vesselName;
    public ?string $vesselCode;
    public ?string $vesselImo;
    public ?string $lineCode;
    public ?string $lineName;
    public ?string $voyageNo;
    public ?string $polCode;    
    public ?string $podCode;
    public ?\DateTimeImmutable $etd;
    public ?\DateTimeImmutable $eta;
    public ?\DateTimeImmutable $atd;
    public ?\DateTimeImmutable $ata;
    public int $cargoPlan;
    public bool $isUrgent;

    public function __construct(array $data = [])
    {
        $this->voyageId = $data['voyage_id'] ?? null;
        $this->vesselId = $data['vessel_id'] ?? null;
        $this->vesselName = $data['vessel_name'] ?? null;
        $this->vesselCode = $data['vessel_code'] ?? null;
        $this->vesselImo = $data['vessel_imo'] ?? null;
        $this->lineCode = $data['line_code'] ?? null;
        $this->lineName = $data['line_name'] ?? null;
        $this->voyageNo = $data['voyage_no'] ?? null;
        $this->polCode = $data['pol_code'] ?? null;
        $this->podCode = $data['pod_code'] ?? null;
        $this->etd = isset($data['etd']) ? \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $data['etd']) : null;
        $this->eta = isset($data['eta']) ? \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $data['eta']) : null;
        $this->atd = isset($data['atd']) ? \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $data['atd']) : null;
        $this->ata = isset($data['ata']) ? \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $data['ata']) : null;
        $this->cargoPlan = (int)($data['cargo_plan'] ?? 0);
        $this->isUrgent = (bool)($data['is_urgent'] ?? false);
    }
}
