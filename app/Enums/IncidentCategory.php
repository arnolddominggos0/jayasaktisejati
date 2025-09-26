<?php

namespace App\Enums;

enum IncidentCategory: string
{
    case ADDRESS_ISSUE     = 'address_issue';
    case RECEIVER_UNAVAILABLE = 'receiver_unavailable';
    case DOC_ISSUE         = 'doc_issue';
    case PORT_ISSUE        = 'port_issue';
    case VEHICLE_ISSUE     = 'vehicle_issue';
    case WEATHER           = 'weather';
    case SAFETY_ISSUE      = 'safety_issue';
    case OTHER             = 'other';
}
