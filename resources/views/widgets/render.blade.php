{{--
    Shared dashboard widget renderer.

    Accepts a `$widgets` array (the shape returned by
    `DashboardResolver::widgetsForUser()` — each element is a
    `[definition, data]` tuple) and emits the grid:

      - stat widgets       → 4-per-row .row chunks (stat-card partial uses col-xl-3)
      - table widgets      → one 2-per-row .row (table-card uses col-lg-6)
      - other widgets      → one .row per widget, full-width

    A view that wants its dashboard rendered through the registry
    just does:

        @include('widgets.render', ['widgets' => $widgets])

    Filter forms, paginators, welcome banners, Quick Actions blocks,
    and any other chrome belong in the calling view, not here. This
    partial only renders data widgets.

    The empty-state card at the bottom only shows the "no widgets
    enabled" message. Per-audience empty-state CTAs (e.g. the
    super_admin "Customize Dashboard" link) stay in the calling
    view's chrome.
--}}
@php
    $statWidgets  = [];
    $tableWidgets = [];
    $otherWidgets = [];
    foreach ($widgets as $entry) {
        $type = $entry['definition']->type;
        if ($type === 'stat') {
            $statWidgets[] = $entry;
        } elseif ($type === 'table') {
            $tableWidgets[] = $entry;
        } else {
            $otherWidgets[] = $entry;
        }
    }
@endphp

{{-- Stat tiles: render in groups of 4 per row --}}
@if(!empty($statWidgets))
    @foreach(array_chunk($statWidgets, 4) as $rowGroup)
        <div class="row mb-4">
            @foreach($rowGroup as $w)
                @include($w['definition']->partial, ['data' => $w['data'], 'label' => $w['definition']->label])
            @endforeach
        </div>
    @endforeach
@endif

{{-- Tables: render in one wide row, two-per-row via the partial's col-lg-6 --}}
@if(!empty($tableWidgets))
    <div class="row">
        @foreach($tableWidgets as $w)
            @include($w['definition']->partial, ['data' => $w['data']])
        @endforeach
    </div>
@endif

{{-- Anything else (future widget types) renders in its own row --}}
@if(!empty($otherWidgets))
    <div class="row mb-4">
        @foreach($otherWidgets as $w)
            @includeIf($w['definition']->partial, ['data' => $w['data'], 'label' => $w['definition']->label])
        @endforeach
    </div>
@endif

@if(empty($widgets))
    <div class="card">
        <div class="card-body text-center text-muted py-5">
            <i class="fas fa-sliders-h fa-2x mb-3 d-block"></i>
            <p class="mb-2">No widgets are enabled for your dashboard yet.</p>
        </div>
    </div>
@endif