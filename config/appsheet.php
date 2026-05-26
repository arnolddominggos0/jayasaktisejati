<?php

use App\Models\BriefingAttendance;
use App\Models\BriefingAttendancePpeItem;
use App\Models\BriefingChecklist;
use App\Models\EquipmentCheck;
use App\Models\LoadingFinding;
use App\Models\LoadingSession;
use App\Models\RackContainerCheck;
use App\Models\UnitCheck;

return [
    /*
    |--------------------------------------------------------------------------
    | AppSheet Integration Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk integrasi dengan AppSheet
    |
    */

    // AppSheet API Key (dari AppSheet Account)
    'api_key' => env('APPSHEET_API_KEY', ''),

    // Application Access Key dari AppSheet app
    'app_access_key' => env('APPSHEET_APP_ACCESS_KEY', ''),

    // Application ID dari AppSheet
    'application_id' => env('APPSHEET_APPLICATION_ID', ''),

    // Webhook secret untuk validasi request dari AppSheet
    'webhook_secret' => env('APPSHEET_WEBHOOK_SECRET', ''),

    // Base URL untuk AppSheet API
    'base_url' => 'https://api.appsheet.com/api/v2',

    // Tables yang tersedia di AppSheet dan mapping ke database
    'tables' => [

        // ── Briefing (sebelum loading) ──────────────────────────
        'mp_check' => [
            'appsheet_table' => 'mp_check',
            'model' => \App\Models\BriefingSession::class,
            'primary_key' => ['date', 'depot_id'],

            'fields' => [
                'date' => 'Tanggal',
                'depot_id' => 'Depot ID',
                'coordinator_user_id' => 'Koordinator ID',
                'summary_headcount' => 'Kebutuhan MP',
                'summary_solution' => 'Solusi Kekurangan',
		'notes' => 'Catatan Operasional',
                'briefing_evidence_path' => 'Foto Briefing',
            ],
        ],

        'detail_mp_check' => [
            'appsheet_table' => 'detail_mp_check',
            'model' => BriefingAttendance::class,
            'primary_key' => ['session_id', 'manpower_id'],
            'add_checked_by' => false,

            'fields' => [
                'session_id' => 'Sesi ID',
                'manpower_id' => 'MP ID',
                'attendance_status' => 'Status Kehadiran',
                'temperature' => 'Suhu',
                'bp_systolic' => 'TD Sistolik',
                'bp_diastolic' => 'TD Diastolik',
                'health_complaint' => 'Keluhan',
                'has_ppe' => 'APD Lengkap',
                'remark' => 'Catatan',
		'signature_path' => 'Tanda Tangan MP',
            ],
            'after_sync' => 'recalculate_briefing_session',
        ],

	'stok_apd_check' => [
   	     'appsheet_table' => 'stok_apd_check',
    	     'model' => StockApdCheck::class,
    	     'primary_key' => ['session_id', 'ppe_type'],
    	     'fields' => [
                'session_id' => 'Sesi ID',
                'ppe_type' => 'Jenis APD',
                'stock_available' => 'Stok Tersedia',
                'required_quantity' => 'Kebutuhan',
                'remark' => 'Catatan',
    	    ],
	],
	
	'briefing_checklists' => [
   	 'appsheet_table' => 'briefing_checklists',
   	 'model' => BriefingChecklist::class,
    	'primary_key' => ['session_id', 'item'],
    	'fields' => [
           'session_id' => 'Sesi ID',
           'item' => 'Item',
           'type' => 'Tipe',
           'status' => 'Status',
           'remark' => 'Catatan',
        ],
   ],

        'briefing_attendance_ppe_items' => [
            'appsheet_table' => 'Briefing Attendance PPE Items',
            'model' => BriefingAttendancePpeItem::class,
            'primary_key' => ['attendance_id', 'ppe_type'],
            'add_checked_by' => false,
            'fields' => [
                'attendance_id' => 'Attendance ID',
                'ppe_type' => 'Jenis APD',
                'condition' => 'Kondisi APD',
                'remark' => 'Catatan',
            ],
        ],

        'briefing_checklists' => [
            'appsheet_table' => 'Briefing Checklists',
            'model' => BriefingChecklist::class,
            'primary_key' => ['session_id', 'item'],
            'add_checked_by' => false,
            'fields' => [
                'session_id' => 'Sesi ID',
                'item' => 'Item',
                'type' => 'Tipe',
                'status' => 'Status',
                'remark' => 'Catatan',
            ],
        ],

        // ── Loading Checkpoint (saat loading/unloading) ────────
        'loading_sessions' => [
            'appsheet_table' => 'Loading Sessions',
            'model' => LoadingSession::class,
            'primary_key' => 'code',
            'fields' => [
                'code' => 'Code',
                'operation_type' => 'Jenis Operasi',
                'status' => 'Status',
                'current_step' => 'Langkah Saat Ini',
                'depot_id' => 'Depot ID',
                'coordinator_user_id' => 'Koordinator ID',
                'branch_id' => 'Branch ID',
                'shipment_id' => 'Shipment ID',
                'briefing_session_id' => 'Briefing Session ID',
                'mp_required' => 'MP Dibutuhkan',
                'mp_present' => 'MP Hadir',
                'mp_absent' => 'MP Absen',
                'mp_sick' => 'MP Sakit',
                'mp_sufficient' => 'MP Cukup',
                'mp_fit_count' => 'MP Fit',
                'mp_unfit_count' => 'MP Tidak Fit',
                'apd_complete' => 'APD Lengkap',
                'apd_clean' => 'APD Bersih',
                'equipment_safe' => 'Alat Aman',
                'rack_container_safe' => 'Rack Container Aman',
                'rack_pillars_ok' => 'Pilar OK',
                'drop_floor_ok' => 'Drop Floor OK',
                'container_structure_ok' => 'Struktur Container OK',
                'unit_measurements_ok' => 'Ukuran Unit OK',
                'stock_apd_sufficient' => 'Stok APD Cukup',
                'critical_issues_count' => 'Jumlah Isu Kritis',
                'warning_issues_count' => 'Jumlah Isu Warning',
                'final_decision_status' => 'Keputusan Final',
                'final_decision_notes' => 'Catatan Keputusan',
                'gps_latitude' => 'Latitude',
                'gps_longitude' => 'Longitude',
                'location_address' => 'Alamat Lokasi',
                'general_notes' => 'Catatan',
            ],
        ],

        'rack_container_checks' => [
            'appsheet_table' => 'Rack Container Checks',
            'model' => RackContainerCheck::class,
            'primary_key' => 'loading_session_id',
            'fields' => [
                'loading_session_id' => 'Loading Session ID',
                'pillar_a_condition' => 'Pilar A Kondisi',
                'pillar_a_pulley_hook' => 'Pilar A Pengait',
                'pillar_a_tie_status' => 'Pilar A Ikatan',
                'pillar_b_condition' => 'Pilar B Kondisi',
                'pillar_c_condition' => 'Pilar C Kondisi',
                'pillar_d_condition' => 'Pilar D Kondisi',
                'drop_floor_front_condition' => 'Drop Floor Depan Kondisi',
                'drop_floor_rear_condition' => 'Drop Floor Belakang Kondisi',
                'container_wall_status' => 'Dinding Container',
                'container_floor_status' => 'Lantai Container',
                'container_roof_status' => 'Atap Container',
            ],
        ],

        'equipment_checks' => [
            'appsheet_table' => 'Equipment Checks',
            'model' => EquipmentCheck::class,
            'primary_key' => 'loading_session_id',
            'fields' => [
                'loading_session_id' => 'Loading Session ID',
                'pulley_top_status' => 'Katrol Atas',
                'pulley_bottom_status' => 'Katrol Bawah',
                'mono_rope_condition' => 'Tali Mono',
                'chain_strength' => 'Rantai',
                'bolt_nut_status' => 'Mur/Baut',
                'bamboo_condition' => 'Bambu',
                'ladder_stability' => 'Tangga',
                'sponds_cleanliness' => 'Sponds',
            ],
        ],

        'unit_checks' => [
            'appsheet_table' => 'Unit Checks',
            'model' => UnitCheck::class,
            'primary_key' => 'loading_session_id',
            'fields' => [
                'loading_session_id' => 'Loading Session ID',
                'unit_id' => 'Unit ID',
                'unit_plate_number' => 'Nomor Plat',
                'distance_front_rh' => 'Jarak Front RH',
                'distance_rear_rh' => 'Jarak Rear RH',
                'distance_back_door' => 'Jarak Back Door',
                'distance_rear_lh' => 'Jarak Rear LH',
                'distance_front_lh' => 'Jarak Front LH',
                'drop_floor_front_height' => 'Tinggi DF Depan',
                'drop_floor_rear_height' => 'Tinggi DF Belakang',
                'container_roof_distance' => 'Jarak Atap Container',
            ],
        ],

        'loading_findings' => [
            'appsheet_table' => 'Loading Findings',
            'model' => LoadingFinding::class,
            'primary_key' => 'id',
            'fields' => [
                'loading_session_id' => 'Loading Session ID',
                'category' => 'Kategori',
                'severity' => 'Severity',
                'item_name' => 'Nama Item',
                'finding_type' => 'Tipe Temuan',
                'description' => 'Deskripsi',
                'status' => 'Status',
            ],
        ],
    ],

    // Mode sync: 'webhook' (real-time), 'polling' (scheduled), 'manual'
    'sync_mode' => env('APPSHEET_SYNC_MODE', 'webhook'),

    // Interval polling dalam menit (jika mode = polling)
    'polling_interval' => env('APPSHEET_POLLING_INTERVAL', 5),

    // Enable/disable logging
    'logging_enabled' => env('APPSHEET_LOGGING_ENABLED', true),

    // Log channel
    'log_channel' => env('APPSHEET_LOG_CHANNEL', 'appsheet'),

    // Retry attempts untuk failed sync
    'retry_attempts' => 3,

    // Timeout untuk API calls (dalam detik)
    'timeout' => 30,
];
