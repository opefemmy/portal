{{--
    Institution header partial — used by EVERY printout in the portal.

    Single source of truth. Pulls from `system_settings` via the
    canonical `SystemSetting::getInstitution*` helpers; the seeders
    populate these rows, and admin edits them through the system
    settings UI. The defaults below mirror what
    DatabaseSeeder writes — if the rows exist, the seed values
    render; if the admin edits them, the edits render; if the rows
    are missing, the **same canonical defaults** render so a printout
    never falls back to a misleading placeholder like "University
    Road, City, State".

    Pair this with `partials/print/institution-watermark` if you
    need a tiled background watermark on student-specific
    printouts (course registration, exam clearance, transcripts).
--}}
@php
    // Canonical accessor helpers — every other print template MUST
    // use these rather than `SystemSetting::get('institution_*')`.
    // The defaults match the seeded values exactly so the page
    // renders the same thing whether the row exists or not.
    $institutionName    = \App\Models\SystemSetting::getInstitutionName();
    $institutionAddress = \App\Models\SystemSetting::get(\App\Models\SystemSetting::INSTITUTION_ADDRESS);
    $institutionPhone   = \App\Models\SystemSetting::get(\App\Models\SystemSetting::INSTITUTION_PHONE);
    $institutionEmail   = \App\Models\SystemSetting::get(\App\Models\SystemSetting::INSTITUTION_EMAIL);
    $institutionWebsite = \App\Models\SystemSetting::get(\App\Models\SystemSetting::INSTITUTION_WEBSITE);
    $institutionTagline = \App\Models\SystemSetting::get(\App\Models\SystemSetting::INSTITUTION_TAGLINE);

    $logoSetting       = \App\Models\SystemSetting::getInstitutionLogo();
    $logoRelativePath  = $logoSetting ? 'storage/' . ltrim($logoSetting, '/') : null;
    $logoPublicPath    = $logoRelativePath ? public_path($logoRelativePath) : null;
    $logoPublicPath2   = $logoSetting ? public_path('uploads/' . ltrim($logoSetting, '/')) : null;
    $logoFallbackPath  = public_path('images/logo.png');
    if ($logoRelativePath && file_exists($logoPublicPath)) {
        $logoUrl = asset($logoRelativePath);
    } elseif ($logoSetting && file_exists($logoPublicPath2)) {
        $logoUrl = asset('uploads/' . ltrim($logoSetting, '/'));
    } elseif (file_exists($logoFallbackPath)) {
        $logoUrl = asset('images/logo.png');
    } else {
        $logoUrl = null;
    }
@endphp

<header class="print-institution-header text-center mb-4">
    <table class="w-100" style="border:0;">
        <tr>
            @if($logoUrl)
                <td style="width:110px; vertical-align:middle; border:0;">
                    <img src="{{ $logoUrl }}" alt="Logo"
                         style="height:90px; max-width:100px; object-fit:contain;">
                </td>
            @endif
            <td style="vertical-align:middle; text-align:center; border:0;">
                <h2 style="margin:0; font-weight:700;">{{ $institutionName }}</h2>
                @if($institutionTagline)
                    <p class="text-muted mb-1" style="font-style:italic;">{{ $institutionTagline }}</p>
                @endif
                <p style="margin:2px 0; font-size:11pt;">{{ $institutionAddress }}</p>
                <p style="margin:2px 0; font-size:10pt;">
                    @if($institutionPhone)
                        <span>Phone: {{ $institutionPhone }}</span>
                    @endif
                    @if($institutionPhone && $institutionEmail)
                        <span> | </span>
                    @endif
                    @if($institutionEmail)
                        <span>Email: {{ $institutionEmail }}</span>
                    @endif
                    @if($institutionEmail && $institutionWebsite)
                        <span> | </span>
                    @endif
                    @if($institutionWebsite)
                        <span>{{ $institutionWebsite }}</span>
                    @endif
                </p>
            </td>
            @if($logoUrl)
                <td style="width:110px; border:0;"></td>
            @endif
        </tr>
    </table>
    <hr style="border:1px solid #000; margin-top:8px;">
</header>