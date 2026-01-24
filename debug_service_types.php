<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\FinancialService;
use App\Models\ServiceType;

// Enable query logging
DB::enableQueryLog();

try {
    echo "--- Debugging Service Types ---\n";

    // 1. Get or Create a Financial Service
    $service = FinancialService::first();
    if (!$service) {
        $service = FinancialService::create([
            'name' => 'Debug Service',
            'price' => 1000,
        ]);
        echo "Created new FinancialService ID: " . $service->id . "\n";
    } else {
        echo "Using existing FinancialService ID: " . $service->id . "\n";
    }

    // 2. Try to add a type via relationship
    echo "Attempting to create ServiceType via relationship...\n";
    $type = $service->types()->create([
        'title' => 'Debug Type ' . time(),
        'description' => 'Test Description',
    ]);
    
    echo "ServiceType created. ID: " . $type->id . "\n";
    echo "Serviceable Type: " . $type->serviceable_type . "\n";
    echo "Serviceable ID: " . $type->serviceable_id . "\n";

    // 3. Refresh and check count
    $service->refresh();
    echo "FinancialService Types Count: " . $service->types->count() . "\n";

    // 4. Check database directly
    $dbCount = DB::table('service_types')->where('serviceable_type', FinancialService::class)->where('serviceable_id', $service->id)->count();
    echo "Database query count: " . $dbCount . "\n";

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

// dump query log
// print_r(DB::getQueryLog());
