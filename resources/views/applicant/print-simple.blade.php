<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Form - {{ $applicant->application_number }}</title>
    @php
        $institutionName = \App\Models\SystemSetting::getInstitutionName() ?? 'Ekiti State College of Technology';
        $institutionAddress = \App\Models\SystemSetting::get(\App\Models\SystemSetting::INSTITUTION_ADDRESS, 'Ijero-Ekiti, Ekiti State, Nigeria');
        $institutionPhone = \App\Models\SystemSetting::get(\App\Models\SystemSetting::INSTITUTION_PHONE, '08061234567');
        $institutionEmail = \App\Models\SystemSetting::get(\App\Models\SystemSetting::INSTITUTION_EMAIL, 'admissions@eksu.edu.ng');

        // Check passport - check multiple locations for backward compatibility
        $passportPath = '';
        $passportFile = $applicant->passport ?? '';

        if ($passportFile) {
            // Check uploads/passports (old location)
            if (file_exists(public_path('uploads/passports/' . $passportFile))) {
                $passportPath = 'uploads/passports/' . $passportFile;
            }
            // Check storage/passports (new location)
            elseif (file_exists(public_path('storage/passports/' . $passportFile))) {
                $passportPath = 'storage/passports/' . $passportFile;
            }
            // Check public/passports
            elseif (file_exists(public_path('passports/' . $passportFile))) {
                $passportPath = 'passports/' . $passportFile;
            }
        }

        // Also check for user passport if applicant passport is empty
        if (empty($passportPath) && $applicant->user) {
            $userPassport = $applicant->user->passport ?? '';
            if ($userPassport) {
                if (file_exists(public_path('uploads/passports/' . $userPassport))) {
                    $passportPath = 'uploads/passports/' . $userPassport;
                } elseif (file_exists(public_path('storage/passports/' . $userPassport))) {
                    $passportPath = 'storage/passports/' . $userPassport;
                }
            }
        }

        // Logo - check multiple locations
        $logoPath = '';
        if (file_exists(public_path('images/logo.png'))) {
            $logoPath = 'images/logo.png';
        } elseif (file_exists(public_path('images/logo.jpg'))) {
            $logoPath = 'images/logo.jpg';
        } elseif (file_exists(public_path('images/logo.jpeg'))) {
            $logoPath = 'images/logo.jpeg';
        } elseif (file_exists(public_path('images/logo.gif'))) {
            $logoPath = 'images/logo.gif';
        } elseif (file_exists(public_path('storage/logos/logo.png'))) {
            $logoPath = 'storage/logos/logo.png';
        }

    @endphp
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12pt; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; border: 2px solid #000; padding: 20px; position: relative; }
        .watermark { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-30deg); font-size: 60px; font-weight: bold; color: #ccc; opacity: 0.08; z-index: -1; white-space: nowrap; pointer-events: none; }
        h1 { text-align: center; color: #333; margin-bottom: 5px; }
        h2 { color: #333; border-bottom: 1px solid #ccc; padding-bottom: 5px; margin: 20px 0 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        td, th { padding: 8px; border: 1px solid #ddd; }
        .text-center { text-align: center; }
        .bold { font-weight: bold; }
        .header { text-align: center; margin-bottom: 30px; }
        .logo { width: 80px; height: 80px; display: block; margin: 0 auto 10px; }
        .passport { width: 120px; height: 140px; border: 1px solid #000; object-fit: cover; }
        .passport-box { width: 120px; height: 140px; border: 1px solid #000; display: flex; align-items: center; justify-content: center; background: #f0f0f0; }
    </style>
</head>
<body>
    @if($logoPath)
    <img src="{{ asset($logoPath) }}" class="watermark" alt="Watermark">
    @else
    <div class="watermark">{{ $institutionName }}</div>
    @endif
    <div class="container">
        <div class="header">
            @if($logoPath)
            <img src="{{ asset($logoPath) }}" class="logo" alt="Logo">
            @endif
            <h1>{{ $institutionName }}</h1>
            <p><strong>{{ $institutionAddress }}</strong></p>
            <p>Phone: {{ $institutionPhone ?: '08061234567' }} | Email: {{ $institutionEmail ?: 'admissions@eksu.edu.ng' }}</p>
        </div>

        <p><strong>Application Number:</strong> {{ $applicant->application_number ?? 'N/A' }}</p>
        <p><strong>Status:</strong> {{ strtoupper($applicant->status ?? 'pending') }}</p>

        <h2>PERSONAL INFORMATION</h2>
        <table>
            <tr>
                <td>
                    <strong>Passport:</strong><br>
                    @if($passportPath)
                    <img src="{{ asset($passportPath) }}" class="passport" alt="Passport">
                    @else
                    <div class="passport-box">No Photo</div>
                    @endif
                </td>
                <td>
                    <strong>Surname:</strong> {{ $applicant->surname ?? ($applicant->user->name ?? 'N/A') }}<br>
                    <strong>First Name:</strong> {{ $applicant->first_name ?? 'N/A' }}<br>
                    <strong>Middle Name:</strong> {{ $applicant->middle_name ?? 'N/A' }}<br>
                    <strong>Gender:</strong> {{ $applicant->gender ?? ($applicant->user->gender ?? 'N/A') }}
                </td>
            </tr>
            <tr>
                <td><strong>Date of Birth:</strong> {{ $applicant->date_of_birth ? \Carbon\Carbon::parse($applicant->date_of_birth)->format('d M, Y') : 'N/A' }}</td>
                <td><strong>Place of Birth:</strong> {{ $applicant->place_of_birth ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>Religion:</strong> {{ $applicant->religion ?? 'N/A' }}</td>
                <td><strong>Phone:</strong> {{ $applicant->phone ?? ($applicant->user->phone ?? 'N/A') }}</td>
            </tr>
            <tr>
                <td colspan="2"><strong>Address:</strong> {{ $applicant->address ?? ($applicant->user->address ?? 'N/A') }}</td>
            </tr>
        </table>

        <h2>PROGRAMME SELECTION</h2>
        <table>
            <tr>
                <td><strong>School:</strong> {{ $applicant->school->name ?? 'N/A' }}</td>
                <td><strong>Department:</strong> {{ $applicant->department->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>Programme:</strong> {{ $applicant->programme->name ?? 'N/A' }}</td>
                <td><strong>Session:</strong> {{ $applicant->session->name ?? 'N/A' }}</td>
            </tr>
        </table>

        <h2>PAYMENT INFORMATION</h2>
        <table>
            <tr>
                <td><strong>Payment Reference:</strong> {{ $applicant->payment_ref ?? $applicant->payment_transaction_id ?? 'N/A' }}</td>
                <td><strong>Amount:</strong> ₦{{ number_format($applicant->payment_amount ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td colspan="2"><strong>Date Paid:</strong> {{ $applicant->payment_date ? \Carbon\Carbon::parse($applicant->payment_date)->format('d M, Y') : 'N/A' }}</td>
            </tr>
        </table>

        <h2>GUARDIAN INFORMATION</h2>
        <table>
            <tr>
                <td><strong>Name:</strong> {{ $applicant->guardian_name ?? 'N/A' }}</td>
                <td><strong>Relationship:</strong> {{ $applicant->guardian_relationship ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>Phone:</strong> {{ $applicant->guardian_phone ?? 'N/A' }}</td>
                <td><strong>Email:</strong> {{ $applicant->guardian_email ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td colspan="2"><strong>Address:</strong> {{ $applicant->guardian_address ?? 'N/A' }}</td>
            </tr>
        </table>

        <div style="margin-top: 30px; text-align: center;">
            <button onclick="window.print()" style="padding: 10px 30px; font-size: 14px; cursor: pointer;">PRINT</button>
            <a href="/applicant/application" style="display: inline-block; padding: 10px 30px; font-size: 14px; margin-left: 10px; text-decoration: none; background: #ccc; color: #000;">BACK</a>
        </div>
    </div>
</body>
</html>
