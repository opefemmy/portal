<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Services\Hospital\HospitalPermissions;

$nurse = User::whereHas('role', fn($q) => $q->where('slug', 'nurse'))->first();
if (! $nurse) { echo "no nurse\n"; exit(1); }

auth()->login($nurse);

$blade = file_get_contents(__DIR__ . '/resources/views/layouts/sidebar.blade.php');
$compiled = $app['view']->getEngineResolver()->resolve('blade')->getCompiler()->compileString($blade);

// Strip the @auth wrapper at the top to test render with auth
$compiled = str_replace('<?php if (auth()->guard()->check()): ?>', '', $compiled);
$compiled = str_replace('<?php endif; ?>', '', $compiled);

ob_start();
eval('?>' . $compiled);
$output = ob_get_clean();

echo "=== OUTPUT LENGTH: " . strlen($output) . " ===\n";
echo "=== Contains 'fas fa-tachometer-alt': " . (str_contains($output, 'fas fa-tachometer-alt') ? 'YES' : 'NO') . " ===\n";
echo "=== Contains 'Patients': " . (str_contains($output, 'Patients') ? 'YES' : 'NO') . " ===\n";
echo "=== Contains 'Notifications': " . (str_contains($output, 'Notifications') ? 'YES' : 'NO') . " ===\n";
echo "=== Contains 'nurse/dashboard': " . (str_contains($output, 'nurse/dashboard') ? 'YES' : 'NO') . " ===\n";
echo "=== Contains 'hospital/dashboard' (the wrong one): " . (str_contains($output, 'hospital/dashboard') ? 'YES' : 'NO') . " ===\n";

// Show the actual menu items
preg_match_all('/<a href="([^"]+)" class="nav-link[^"]*">\s*<i class="([^"]+)"><\/i>\s*([^<]+?)\s*<\/a>/', $output, $matches);
echo "\n=== NAV LINKS FOUND ===\n";
foreach ($matches[1] as $i => $href) {
    echo str_pad($matches[3][$i], 30) . " -> $href\n";
}