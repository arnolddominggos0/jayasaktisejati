<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Shipment;

echo "=== EXISTING DATA CHECK ===\n\n";

echo "BRANCHES:\n";
$branches = Branch::all(['id', 'code', 'name']);
if ($branches->isEmpty()) {
    echo "  (none)\n";
} else {
    foreach ($branches as $b) {
        echo "  ID={$b->id}, Code={$b->code}, Name={$b->name}\n";
    }
}

echo "\nCUSTOMERS:\n";
$customers = Customer::all(['id', 'name']);
foreach ($customers as $c) {
    echo "  ID={$c->id}, Name={$c->name}\n";
}

echo "\nSHIPMENT COUNT: " . Shipment::count() . "\n";
echo "SHIPMENT ID=3 customer_id: " . Shipment::find(3)?->customer_id . "\n";

echo "\n=== END ===\n";
