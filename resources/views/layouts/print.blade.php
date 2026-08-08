{{--
    Minimal layout used by PDF endpoints (DOMPDF). Deliberately strips
    Bootstrap, sidebar, topbar, and the rest of the portal chrome —
    PDFs should be a clean A4 sheet, nothing else.
--}}
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', config('app.name'))</title>
    <style>
        @page { size: A4 portrait; margin: 12mm; }
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #111; margin: 0; padding: 0; background: #fff; }
        @stack('styles')
    </style>
    @stack('head')
</head>
<body>
    @yield('content')
</body>
</html>
