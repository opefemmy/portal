<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "nurse relative: '" . route('hospital.nurse.dashboard', [], false) . "'\n";
echo "doctor relative: '" . route('hospital.doctor.dashboard', [], false) . "'\n";
echo "cmd relative: '" . route('hospital.dashboard', [], false) . "'\n";
echo "records index relative: '" . route('hospital.records.index', [], false) . "'\n";
