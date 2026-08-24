{{--
    Stat tile rendered by DashboardResolver.

    Expected `$data` shape:
        value:    number
        format:   'number' | 'currency'
        color:    tailwind-like colour name (default 'primary')
        icon:     font-awesome class (default 'fas fa-chart-bar')
        href:     optional URL — when present the title becomes a link
        cta:      optional array describing an in-tile action button:
                       ['label' => string,
                        'icon'  => font-awesome class,
                        'href'  => URL,
                        'color' => tailwind-like colour (defaults to $color)]
                  When set, renders a small outline-coloured button
                  below the value. The button gets `z-index: 2` if
                  a title href is also present so it stays clickable
                  above the stretched-link overlay.
--}}
@php
    $value  = $data['value']  ?? 0;
    $format = $data['format'] ?? 'number';
    $color  = $data['color']  ?? 'primary';
    $icon   = $data['icon']   ?? 'fas fa-chart-bar';
    $href   = $data['href']   ?? null;
    $cta    = $data['cta']    ?? null;
    $labelExtra = $data['label_extra'] ?? null;

    if ($format === 'currency') {
        $display = '₦' . number_format((float) $value, 0);
    } else {
        $display = number_format((float) $value);
    }
@endphp
<div class="col-md-6 col-xl-3 mb-3">
    <div class="card stat-card {{ $color }} h-100">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h6 class="text-muted mb-2">
                        @if($href)
                            <a href="{{ $href }}" class="text-decoration-none text-muted stretched-link">{{ $label ?? '' }}</a>
                        @else
                            {{ $label ?? '' }}
                        @endif
                    </h6>
                    <h2 class="mb-0">{{ $display }}</h2>
                    @if($labelExtra)
                        <small class="text-muted d-block mt-1">{{ $labelExtra }}</small>
                    @endif
                    @if($cta)
                        <a href="{{ $cta['href'] }}"
                           class="btn btn-sm btn-outline-{{ $cta['color'] ?? $color }} mt-2 {{ $href ? 'position-relative' : '' }}"
                           style="{{ $href ? 'z-index: 2' : '' }}">
                            <i class="{{ $cta['icon'] ?? 'fas fa-arrow-right' }} me-1"></i>{{ $cta['label'] }}
                        </a>
                    @endif
                </div>
                <div class="icon text-{{ $color }}">
                    <i class="{{ $icon }}"></i>
                </div>
            </div>
        </div>
    </div>
</div>