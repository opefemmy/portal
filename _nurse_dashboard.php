<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$nurse = User::whereHas('role', fn($q) => $q->where('slug', 'nurse'))->first();
if (! $nurse) { echo "no nurse\n"; exit(1); }

// Simulate an actual HTTP request to /hospital/dashboard as a nurse
$kernel->handle(
    Illuminate\Http\Request::create('/hospital/dashboard', 'GET'),
    function ($request) use ($nurse) {
        auth()->login($nurse);
        // Force render the dashboard view directly
        $widgets = [];
        $todayAppointments = [];
        $recentPatients = [];
        try {
            $html = view('hospital.dashboard', compact('widgets','todayAppointments','recentPatients'))->render();
            echo "DASHBOARD HTML LENGTH: " . strlen($html) . "\n";
            echo "Contains 'Patients': " . (str_contains($html, 'Patients') ? 'YES' : 'NO') . "\n";
            echo "Contains 'Appointments': " . (str_contains($html, 'Appointments') ? 'YES' : 'NO') . "\n";
            echo "Contains 'Notifications': " . (str_contains($html, 'Notifications') ? 'YES' : 'NO') . "\n";
            echo "Contains 'nurse/dashboard': " . (str_contains($html, 'nurse/dashboard') ? 'YES' : 'NO') . "\n";

            preg_match_all('/<a href="([^"]+)" class="nav-link[^"]*">\s*<i class="([^"]+)"><\/i>\s*([^<]+?)\s*<\/a>/', $html, $matches);
            echo "\n=== NAV LINKS FOUND ===\n";
            foreach ($matches[1] as $i => $href) {
                echo str_pad($matches[3][$i], 30) . " -> $href\n";
            }
        } catch (\Throwable $e) {
            echo "ERROR: " . $e->getMessage() . "\n";
            echo "FILE: " . $e->getFile() . ":" . $e->getLine() . "\n";
        }
    }
);
