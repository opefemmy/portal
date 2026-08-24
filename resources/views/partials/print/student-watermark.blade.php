{{--
    Tiled anti-fraud watermark for student-specific printouts.

    Pass `$student` (and optionally `$session`) so the watermark
    tiles the student's name, department, level, and matric number
    across the page. Pairs with `@include('partials.print.institution-header')`
    for the visible header.

    Usage:
      <style> ... include watermark CSS ... </style>
      <div class="print-page">
          @include('partials.print.student-watermark')
          @include('partials.print.institution-header')
          ... rest of printable content ...
      </div>

    The companion stylesheet is documented inline below — copy
    the .print-watermark block into the consumer view's <style>.
--}}
@php
    $studentName  = $student->user->name ?? ($student->full_name ?? ($student->name ?? ''));
    $studentDept  = $student->department->name ?? '';
    $studentLevel = $student->level_display ?? ($student->level ?? '');
    $studentMatric = $student->matric_number ?? ($student->matric_no ?? '');
    $wmCells = array_fill(0, 12, true); // 4x3 grid — denser coverage for A4
@endphp

<style>
    .print-watermark {
        position: fixed;
        inset: 0;
        pointer-events: none;
        z-index: 0;
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        grid-auto-rows: 1fr;
    }
    .print-watermark .wm-cell {
        display: flex;
        align-items: center;
        justify-content: center;
        transform: rotate(-30deg);
        text-align: center;
        font-family: 'Segoe UI', Arial, sans-serif;
        text-transform: uppercase;
        color: #1a237e;
        opacity: 0.06;
        line-height: 1.15;
        padding: 0 4px;
    }
    .print-watermark .wm-cell .wm-name { font-size: 14pt; font-weight: 700; }
    .print-watermark .wm-cell .wm-line { font-size: 10pt; font-weight: 600; }
    .print-watermark + * { position: relative; z-index: 1; }
    @media print {
        .print-watermark .wm-cell { opacity: 0.08 !important; }
    }
</style>
<div class="print-watermark" aria-hidden="true">
    @foreach($wmCells as $_)
        <div class="wm-cell">
            <div>
                <div class="wm-name">{{ $studentName }}</div>
                @if($studentDept)<div class="wm-line">{{ $studentDept }}</div>@endif
                @if($studentLevel)<div class="wm-line">{{ $studentLevel }}</div>@endif
                @if($studentMatric)<div class="wm-line">{{ $studentMatric }}</div>@endif
            </div>
        </div>
    @endforeach
</div>