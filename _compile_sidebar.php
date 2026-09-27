<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$blade = file_get_contents(__DIR__ . '/resources/views/layouts/sidebar.blade.php');
$compiler = $app['view']->getEngineResolver()->resolve('blade')->getCompiler();

try {
    $compiled = $compiler->compileString($blade);
    echo "compiled OK, length: " . strlen($compiled) . "\n";
    file_put_contents(__DIR__ . '/_sidebar_compiled.php', $compiled);
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
