<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\IntegrationService;

echo "--- Checking Columns ---\n";
$tables = ['financial_services', 'integration_services', 'facility_services'];
foreach ($tables as $table) {
    if (Schema::hasColumn($table, 'types')) {
        echo "WARNING: Column 'types' EXISTS in table '$table'. This might cause conflicts.\n";
    } else {
        echo "OK: Column 'types' does NOT exist in table '$table'.\n";
    }
}

echo "\n--- Testing IntegrationService ID 5 ---\n";
$service = IntegrationService::find(5);
if (!$service) {
    echo "IntegrationService ID 5 not found. Creating one...\n";
    $service = IntegrationService::create([
        'name' => 'Test Integration Service',
        'price' => 5000
    ]);
    echo "Created with ID: " . $service->id . "\n";
} else {
    echo "Found IntegrationService ID: " . $service->id . "\n";
}

echo "Adding a test type...\n";
try {
    $service->types()->create([
        'title' => 'Manual Test Type',
        'description' => 'Added via script',
        'points' => ['test point 1', 'test point 2'],
    ]);
    echo "Type added successfully.\n";
} catch (\Exception $e) {
    echo "Failed to add type: " . $e->getMessage() . "\n";
}

$service->refresh();
echo "Types Count: " . $service->types->count() . "\n";
if ($service->types->count() > 0) {
    echo "First Type Title: " . $service->types->first()->title . "\n";
}
