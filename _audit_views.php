<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

view()->share('errors', new \Illuminate\Support\ViewErrorBag());

$views = [];
$iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/resources/views'));
foreach ($iter as $f) {
    if ($f->isFile() && substr($f->getFilename(), -10) === '.blade.php') {
        $abs = $f->getPathname();
        $rel = str_replace('\\', '/', $abs);
        $rel = preg_replace('#^.*?/resources/views/#', '', $rel);
        $views[] = $rel;
    }
}
sort($views);

$ok = 0;
$bad = [];

foreach ($views as $rel) {
    try {
        $abs = __DIR__ . '/resources/views/' . str_replace('/', DIRECTORY_SEPARATOR, $rel);
        $src = file_get_contents($abs);

        // FOR directives: @if(expression) and @endif, @foreach and @endforeach, etc.
        // Inline form: @if(...) ... @else ... @endif — the inline @if(... , ...) is NOT valid Blade.
        // Self-closing: @section('x', 'value') — does NOT need @endsection.
        // Block form: @section('x') ... @endsection — needs matching close.
        $openish = preg_match_all('/@(if|foreach|for|while|unless|switch|php)\b/', $src);
        $closeish = preg_match_all('/@(endif|endforeach|endfor|endwhile|endunless|endswitch|endphp|stop)\b/', $src);
        if ($openish !== $closeish) {
            $bad[] = "[UNBALANCED] {$rel}  (open={$openish}, close={$closeish})";
            continue;
        }

        // @section block form vs inline form
        // Block form: @section('name')  → must be followed by newline or `)` not...
        // Inline form: @section('name', 'value')  → does NOT need @endsection
        $blockSection = preg_match_all('/@section\s*\(\s*[\'"][a-zA-Z0-9_]+[\'"]\s*\)/', $src);
        $inlineSection = preg_match_all('/@section\s*\(\s*[\'"][a-zA-Z0-9_]+[\'"]\s*,/', $src);
        $closeSection = preg_match_all('/@endsection\b/', $src);
        if ($blockSection !== $closeSection) {
            $bad[] = "[UNCLOSED_SECTION] {$rel}  (block={$blockSection}, close={$closeSection})";
            continue;
        }

        // @extends must point to existing file
        if (preg_match("/@extends\('([^']+)'\)/", $src, $m)) {
            $parent = $m[1];
            $parentFile = __DIR__ . '/resources/views/' . str_replace('.', DIRECTORY_SEPARATOR, $parent) . '.blade.php';
            if (!file_exists($parentFile)) {
                $bad[] = "[MISSING_PARENT] {$rel}  → '{$parent}'";
                continue;
            }
        }

        // Compile + render
        $viewName = str_replace('/', '.', preg_replace('/\.blade\.php$/', '', $rel));
        $viewName = preg_replace('/\.+/', '.', $viewName);
        try {
            view($viewName, [])->render();
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            $acceptable = (
                preg_match('/Undefined (variable|index|offset|array key)/', $msg)
                || strpos($msg, 'must be an instance of') !== false
                || strpos($msg, 'must be of type') !== false
                || strpos($msg, 'Attempt to read property') !== false
                || strpos($msg, 'Trying to get property') !== false
                || strpos($msg, 'Attempt to access') !== false
                || strpos($msg, 'array_key_exists') !== false
            );
            if (!$acceptable) {
                $bad[] = "[COMPILE_ERROR] {$rel}  → " . substr($msg, 0, 200);
                continue;
            }
        }

        $ok++;
    } catch (\Throwable $e) {
        $bad[] = "[FATAL] {$rel}  → " . substr($e->getMessage(), 0, 200);
    }
}

echo "OK: $ok / " . count($views) . PHP_EOL;
echo "BAD: " . count($bad) . PHP_EOL;
foreach ($bad as $b) echo "  $b" . PHP_EOL;
