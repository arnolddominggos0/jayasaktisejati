<?php

namespace App\Enums;

enum DepotMetric: string
{
    case FleetReady       = 'fleet_ready';        
    case FleetBreakdown   = 'fleet_breakdown';   
    case DriversPresent   = 'drivers_present';    
    case DriversAbsent    = 'drivers_absent';     
    case LoadsOutbound    = 'loads_outbound';     
    case LoadsInbound     = 'loads_inbound';      
    case IssuesReported   = 'issues_reported'; 

    public function label(): string
    {
        return match($this) {
            self::FleetReady      => 'Armada Siap Jalan',
            self::FleetBreakdown  => 'Armada Rusak',
            self::DriversPresent  => 'Sopir Hadir',
            self::DriversAbsent   => 'Sopir Absen',
            self::LoadsOutbound   => 'Muatan Keluar',
            self::LoadsInbound    => 'Muatan Masuk',
            self::IssuesReported  => 'Jumlah Insiden',
        };
    }
}

