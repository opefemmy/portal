<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Role;
use App\Services\Hospital\HospitalPermissions;

// Find the first nurse user
$nurse = User::whereHas('role', fn($q) => $q->where('slug', 'nurse'))->first();
if (! $nurse) {
    echo "No nurse user found\n";
    exit(1);
}

auth()->login($nurse);
echo "Current role: " . HospitalPermissions::currentRole() . "\n";
echo "isHospitalStaff: " . (HospitalPermissions::isHospitalStaff() ? 'YES' : 'NO') . "\n";
echo "dashboardFor: " . HospitalPermissions::dashboardFor() . "\n";
echo "menuFor items: " . count(HospitalPermissions::menuFor()) . "\n";
foreach (HospitalPermissions::menuFor() as $i => $item) {
    echo "  [$i] route={$item[0]} icon={$item[1]} label={$item[2]}\n";
}
