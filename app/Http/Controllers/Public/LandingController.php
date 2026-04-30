<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Shipment;
use Illuminate\Http\Request;

/**
 * Landing Page Controller
 * 
 * Handles public landing page with company profile information
 */
class LandingController extends Controller
{
    /**
     * Display landing page with stats and company information
     */
    public function index()
    {
        // Get statistics for display (with realistic fallback data)
        $stats = [
            'shipments' => '100,000+',
            'customers' => '500+',
            'experience' => '30+',
            'fleet' => '30+',
        ];

        // Services data
        $services = [
            [
                'icon' => 'ship',
                'title' => 'International Freight Forwarder',
                'description' => 'Jasa logistik domestik dan internasional dengan tim berpengalaman. Layanan cepat, aman, dan terjangkau.',
            ],
            [
                'icon' => 'warehouse',
                'title' => 'Container Depot',
                'description' => 'Penyediaan depo container dry/reefer dan peralatan handling yang lengkap.',
            ],
            [
                'icon' => 'truck',
                'title' => 'Inland Transport',
                'description' => 'Transportasi dalam kota untuk jasa door to door dengan armada trailer yang handal.',
            ],
            [
                'icon' => 'clipboard',
                'title' => 'Project Logistics',
                'description' => 'Jasa planning, operation design, inland & sea transportation, stevedoring, dan formalitas bea cukai.',
            ],
            [
                'icon' => 'snowflake',
                'title' => 'Container Reefer',
                'description' => 'Pengangkutan makanan beku dengan container reefer 200+ TEUs dan genset 6 unit (20-100 KVA).',
            ],
        ];

        // Why choose us features
        $features = [
            'On-time Delivery Guarantee',
            'Real-time Tracking System',
            'Professional & Experienced Team',
            'Competitive Pricing',
            'Wide Coverage - Seluruh Indonesia',
            'Secure & Safe Handling',
        ];

        // Coverage cities
        $cities = [
            'Banda Aceh', 'Medan', 'Padang', 'Jakarta', 'Surabaya',
            'Banjarmasin', 'Pontianak', 'Samarinda', 'Makassar', 'Manado',
            'Kendari', 'Palu', 'Gorontalo', 'Ambon', 'Ternate',
            'Jayapura', 'Sorong', 'Kupang', 'Merauke'
        ];

        return view('public.landing.index', compact(
            'stats',
            'services',
            'features',
            'cities'
        ));
    }
}
