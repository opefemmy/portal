{{--
    Reusable O'level subject dropdown.

    Renders one <select> for a single subject slot. Subject slots 1 and 2
    are locked to English and Mathematics respectively (the two compulsory
    subjects required by the institution); the remaining slots are
    optional dropdowns.

    Required variables:
        $position     — 1..5 (subject slot number)
        $name         — field name, e.g. 'olevel1_subject3'
        $subjects     — array of subject strings (the full list)
        $lockedValue  — string to use when position is 1 or 2
                        ('English' or 'Mathematics')
        $value        — current value for the select (for edit / old())
                        — optional; falls back to $lockedValue when blank

    The partial always emits a hidden companion <input type="hidden">
    with the same name so the locked value is still submitted even though
    the visible <select> is disabled. Without this, disabled selects are
    dropped from form submission and the controller would receive an
    empty olevel1_subject1 / olevel1_subject2.
--}}
@php
    $isLocked = $position === 1 || $position === 2;
    $currentValue = $value ?? request()->old($name, $lockedValue ?? '');
    if ($isLocked && $currentValue === '') {
        $currentValue = $lockedValue;
    }
@endphp

@if ($isLocked)
    {{-- Locked slot: hidden field carries the value, visible select is disabled so the user sees which subject is mandatory. --}}
    <input type="hidden" name="{{ $name }}" value="{{ $lockedValue }}">
    <select class="form-select" disabled>
        <option value="{{ $lockedValue }}" selected>{{ $lockedValue }} (Compulsory)</option>
    </select>
@else
    <select name="{{ $name }}" class="form-select">
        <option value="">Select Subject</option>
        @foreach ($subjects as $subject)
            <option value="{{ $subject }}" {{ $currentValue === $subject ? 'selected' : '' }}>{{ $subject }}</option>
        @endforeach
    </select>
@endif
