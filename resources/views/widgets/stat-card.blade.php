{{--
    Stat tile rendered by DashboardResolver.

    Expected `$data` shape:
        value: number
        format: 'number' | 'currency'
        color: tailwind-like colour name (default 'primary')
        icon:  font-awesome class (default 'fas fa-chart-bar')
        href:  optional URL — when present the title becomes a link
--}}
@php
    $value  = $data['value']  ?? 0;
    $format = $data['format'] ?? 'number';
    $color  = $data['color']  ?? 'primary';
    $icon   = $data['icon']   ?? 'fas fa-chart-bar';
    $href   = $data['href']   ?? null;

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
                </div>
                <div class="icon text-{{ $color }}">
                    <i class="{{ $icon }}"></i>
                </div>
            </div>
        </div>
    </div>
</div>
