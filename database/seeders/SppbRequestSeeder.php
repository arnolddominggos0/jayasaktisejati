<?php

namespace Database\Seeders;

use App\Enums\CargoType;
use App\Enums\ServiceType;
use App\Enums\ShipmentMode;
use App\Enums\ShipmentStatus;
use App\Models\City;
use App\Models\Customer;
use App\Models\Depot;
use App\Models\Port;
use App\Models\Shipment;
use App\Models\Unit;
use App\Models\Voyage;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * SPPB URGENT - PT HA TERNATE
 * Create Shipment Request (DRAFT) with 3 vehicle units
 * Ready for step-by-step tracking
 */
class SppbRequestSeeder extends Seeder
{
    /**
     * SPPB Data from request
     *
     * BP. SONNY (Internal JSS) membuat permintaan pengiriman
     * PT HA TERNATE adalah customer/penerima barang di Ternate
     * Pickup dari: SEMPER, Jakarta Utara
     * Deliver ke: PT HA TERNATE, Ternate
     */
    private array $sppbData = [
        'request_by' => 'BP. SONNY',  // Internal JSS - PIC yang handle
        'request_by_phone' => '081234567890',  // No HP BP. SONNY
        'request_date' => '2026-04-14',
        'email' => 'bpsonny@jss.co.id',  // Email internal JSS
        'notes' => 'URGENT! UNIT SPK PRIORITAS KIRIM - SPPB dari Toyota',
        'service_type' => 'door_to_door',
        'etd' => '2026-04-21',
        'eta' => '2026-04-28',
        'origin' => 'SEMPER, Jakarta Utara',
        'origin_city' => 'Jakarta',
        'destination' => 'Ternate',
        'destination_city' => 'Ternate',
        'customer_name' => 'PT. HA TERNATE',  // Customer/Penerima
        'customer_phone' => '0921-123456',  // Telp customer di Ternate
        'pickup_location' => 'PDC SEMPER, JL. KEBANTENAN NO. 20 JAKARTA UTARA',
        'pickup_contact_name' => 'PDC SEMPER',  // Kontak lokasi pickup
        'pickup_contact_phone' => '021-70973056',  // Telp PDC SEMPER
        'units' => [
            [
                'model' => 'AVANZA 1.3 E M/T',
                'reg_no' => '2604-0133',
                'chassis_no' => 'MHKAA1BY8TJ014260',
                'engine_no' => '1NR-G323689',
                'color' => 'BLACK MICA',
                'do_no' => 'JKT/01/26/04/0113',
                'qty' => 1,
                'remarks' => null,
            ],
            [
                'model' => 'HILUX D-CAB 2.4 G (4x4) DSL M/T',
                'reg_no' => '2604-0143',
                'chassis_no' => 'MR0KB8CD3T1167940',
                'engine_no' => '2GD-D580477',
                'color' => 'SUPER WHITE II',
                'do_no' => 'JKT/01/26/04/0113',
                'qty' => 1,
                'remarks' => '20300-SPK260123',
            ],
            [
                'model' => 'HILUX D-CAB 2.4 G (4x4) DSL M/T',
                'reg_no' => '2604-0174',
                'chassis_no' => 'MR0KB8CD6T1167866',
                'engine_no' => '2GD-D580261',
                'color' => 'SUPER WHITE II',
                'do_no' => 'JKT/01/26/04/0113',
                'qty' => 1,
                'remarks' => '20300-SPK260131',
            ],
        ],
    ];

    public function run(): void
    {
        $this->command->info('');
        $this->command->info('========================================');
        $this->command->info('CREATING SPPB REQUEST');
        $this->command->info('========================================');

        // Get master data
        $customer = Customer::where('code', 'CUST-HA-TERNATE')->first();
        $originCity = City::where('name', 'Jakarta')->first();
        $destCity = City::where('name', 'Ternate')->first();
        $portTpri = Port::where('code', 'TPRI')->first();
        $portTernate = Port::where('code', 'TRT')->first();
        $depot = Depot::where('code', 'DEP-TPRI-01')->first();
        $voyage = Voyage::where('voyage_no', 'V-TERNATE-0426')->first();

        if (! $customer || ! $originCity || ! $destCity || ! $depot) {
            $this->command->error('Master data not found! Run MasterDataSetupSeeder first.');

            return;
        }

        // Create Shipment (DRAFT status)
        $shipment = $this->createShipment(
            $customer,
            $originCity,
            $destCity,
            $depot,
            $portTpri,
            $portTernate,
            $voyage
        );

        // Create Units
        $this->createUnits($shipment);

        $this->printSummary($shipment);
    }

    private function createShipment(
        Customer $customer,
        City $originCity,
        City $destCity,
        Depot $depot,
        Port $portTpri,
        Port $portTernate,
        Voyage $voyage
    ): Shipment {

        $shipment = Shipment::create([
            'customer_id' => $customer->id,
            'origin_city_id' => $originCity->id,
            'destination_city_id' => $destCity->id,
            'branch_id' => $depot->branch_id,
            'assigned_depot_id' => $depot->id,
            'mode' => ShipmentMode::Sea,
            'status' => ShipmentStatus::Draft,
            'service_type' => ServiceType::SeaFreight,
            'service_option' => 'fcl',
            'cargo_type' => CargoType::Vehicle,
            'delivery_scope' => 'door_to_door',
            'container_size' => '40ft',
            'container_qty' => 1,
            'packages_total' => 3,
            'cbm_total' => 45.00,
            'weight_total' => 4500.00,
            'pol_id' => $portTpri->id,
            'pod_id' => $portTernate->id,
            'vessel_name' => $voyage->vessel->name,
            'voyage' => $voyage->voyage_no,
            'voyage_id' => $voyage->id,
            'etd' => Carbon::parse($this->sppbData['etd']),
            'eta' => Carbon::parse($this->sppbData['eta']),
            'pic_name' => $this->sppbData['request_by'],
            'pic_phone' => $this->sppbData['request_by_phone'],
            'pickup_contact_name' => $this->sppbData['pickup_contact_name'],
            'pickup_contact_phone' => $this->sppbData['pickup_contact_phone'],
            'delivery_contact_name' => $this->sppbData['customer_name'],
            'delivery_contact_phone' => $this->sppbData['customer_phone'],
            'priority' => 'urgent',
            'notes' => $this->sppbData['notes'],
            'requested_at' => Carbon::parse($this->sppbData['request_date']),
        ]);

        $this->command->info("✓ Shipment created: {$shipment->code}");
        $this->command->info('  Status: DRAFT (ready for tracking)');

        return $shipment;
    }

    private function createUnits(Shipment $shipment): void
    {
        foreach ($this->sppbData['units'] as $index => $unitData) {
            Unit::create([
                'shipment_id' => $shipment->id,
                'model_no' => $unitData['model'],
                'reg_no' => $unitData['reg_no'],
                'chassis_no' => $unitData['chassis_no'],
                'engine_no' => $unitData['engine_no'],
                'color' => $unitData['color'],
                'do_number' => $unitData['do_no'],
                'qty' => $unitData['qty'],
                'notes' => $unitData['remarks'],
            ]);

            $this->command->info('✓ Unit '.($index + 1).": {$unitData['model']}");
        }
    }

    private function printSummary(Shipment $shipment): void
    {
        $this->command->info('');
        $this->command->info('========================================');
        $this->command->info('SPPB REQUEST READY');
        $this->command->info('========================================');
        $this->command->info("Shipment Code: {$shipment->code}");
        $this->command->info('');
        $this->command->info('REQUEST DETAIL:');
        $this->command->info("  Requested By: {$this->sppbData['request_by']} (Internal JSS)");
        $this->command->info("  Request Date: {$this->sppbData['request_date']}");
        $this->command->info('');
        $this->command->info('CUSTOMER (Penerima):');
        $this->command->info("  Name: {$shipment->customer->name}");
        $this->command->info("  City: {$shipment->destinationCity->name}");
        $this->command->info("  Phone: {$this->sppbData['customer_phone']}");
        $this->command->info('');
        $this->command->info('PICKUP (Pengambilan):');
        $this->command->info("  Location: {$this->sppbData['pickup_location']}");
        $this->command->info("  Contact: {$this->sppbData['pickup_contact_name']}");
        $this->command->info("  Phone: {$this->sppbData['pickup_contact_phone']}");
        $this->command->info('');
        $this->command->info('ROUTE:');
        $this->command->info("  {$shipment->route_summary}");
        $this->command->info("  Vessel: {$shipment->vessel_name}");
        $this->command->info("  ETD: {$shipment->etd->format('d M Y')}");
        $this->command->info("  ETA: {$shipment->eta->format('d M Y')}");
        $this->command->info('');
        $this->command->info('Service: Door to Door (URGENT)');
        $this->command->info('Status: DRAFT');
        $this->command->info('');
        $this->command->info('NEXT STEPS:');
        $this->command->info("1. Send to FC: php artisan shipment:send-to-fc {$shipment->code}");
        $this->command->info("2. View status: php artisan shipment:status {$shipment->code}");
    }
}
