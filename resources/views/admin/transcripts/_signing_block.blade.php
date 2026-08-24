{{--
    Combined result-signing block.

    Renders six signature placeholders on a printable result sheet:
      HOD · Dean of School · Business Committee ·
      Academic Board · Registrar · Rector / President

    Layout: THREE signatures per row, TWO rows. Compact padding so
    all six cells sit tightly beside each other without wrapping the
    signature line itself. Uses `page-break-inside: avoid` so the
    block stays on a single A4 landscape strip instead of breaking
    across pages.

    Populated from system_settings (key = '<role>_name'). The role
    title is the fallback default so the printout never renders an
    empty signature box.

    Used from:
      - academic-board/results/signing-page (Part E of the multi-area plan)
      - academic-board/results/print-student (the one-roll transcript)
      - any other printable result sheet that wants the same six
        placeholders — just `@include('admin.transcripts._signing_block')`.
--}}
@php
    $signatories = [
        ['hod_name',                'Head of Department'],
        ['dean_name',               'Dean of School'],
        ['business_committee_name', 'Business Committee'],
        ['academic_board_name',     'Academic Board'],
        ['registrar_name',          'Registrar'],
        ['rector_name',             'Rector / President'],
    ];
@endphp

<div class="signing-block" style="margin-top: 14px; page-break-inside: avoid;">
    <h6 class="text-center mb-2" style="text-transform: uppercase; letter-spacing: 2px; font-size: 9pt;">
        Results Signing Page
    </h6>

    {{-- Three signatures per row, two rows. `g-1` + tight padding
         keeps the cells truly beside each other; `float:none` and a
         small height reserve the line where a wet-ink signature
         lands, with the role name + portfolio title flush to it. --}}
    <div class="row text-center" style="margin-left: -4px; margin-right: -4px;">
        @foreach($signatories as [$settingKey, $defaultTitle])
            <div class="col-4" style="padding: 0 4px; margin-bottom: 8px;">
                <div style="
                    border-top: 1px solid #000;
                    padding-top: 3px;
                    min-height: 38px;
                ">
                    <strong style="font-size: 8.5pt; line-height: 1.1;">
                        {{ \App\Models\SystemSetting::get($settingKey, $defaultTitle) }}
                    </strong>
                    <div style="font-size: 7pt; color: #555; line-height: 1.1;">
                        {{ $defaultTitle }}
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="text-center text-muted mt-1" style="font-size: 7.5pt;">
        <em>Signed at {{ \App\Models\SystemSetting::getInstitutionName() }} · {{ now()->format('d M Y') }}</em>
    </div>
</div>
