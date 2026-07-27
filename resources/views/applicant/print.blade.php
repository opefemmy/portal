<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Form - {{ $applicant->application_number }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            background: #f8f9fa;
        }
        .print-container {
            max-width: 210mm;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            position: relative;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        /* Glass-like watermark background */
        .watermark-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            opacity: 0.06;
            display: flex;
            align-items: center;
            justify-content: center;
            background-image: none;
        }
        .watermark-bg::before {
            content: '{{ \App\Models\Setting::get("institution_name", "Ekiti State College of Technology") }}';
            font-size: 120px;
            font-weight: bold;
            color: #1a237e;
            transform: rotate(-30deg);
            white-space: nowrap;
        }
        .invoice-header {
            text-align: center;
            border-bottom: 3px solid var(--primary);
            padding-bottom: 20px;
            margin-bottom: 25px;
            background: linear-gradient(to bottom, rgba(36, 125, 87, 0.05), transparent);
            padding: 20px;
            border-radius: 10px;
        }
        .invoice-header h1 {
            color: var(--primary);
            font-size: 22pt;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .invoice-header .institution-logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 10px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
        }
        .invoice-header .institution-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .invoice-header p {
            margin: 2px 0;
            color: #333;
        }
        .student-info {
            margin-bottom: 20px;
        }
        .student-info table td {
            padding: 6px 10px;
            font-size: 11pt;
        }
        .table {
            border: 1px solid #dee2e6;
        }
        .table th {
            background: linear-gradient(to bottom, var(--primary), #283593);
            color: white;
            font-weight: bold;
            padding: 8px;
            border: 1px solid var(--primary);
        }
        .table td {
            border: 1px solid #dee2e6;
        }
        .photo-frame {
            width: 140px;
            height: 160px;
            border: 3px solid var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f5f5f5;
            overflow: hidden;
            border-radius: 5px;
        }
        .photo-frame img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .signature-section {
            margin-top: 40px;
            page-break-inside: avoid;
        }
        .signature-section table td {
            padding: 25px 15px;
            border-top: 2px solid var(--primary);
        }
        .badge-status {
            padding: 8px 20px;
            font-size: 12pt;
            border-radius: 20px;
        }
        @media print {
            body {
                background: white;
            }
            .print-container {
                box-shadow: none;
                padding: 0;
            }
            .watermark-bg {
                opacity: 0.12;
            }
            .no-print {
                display: none !important;
            }
            .invoice-header {
                border-bottom-color: #000;
            }
            .table th {
                background: var(--primary) !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    <script>
        function tryNextPassport(img, path2, path3) {
            if (img.src.includes(path2) || img.dataset.tried2) {
                // Try third path
                if (img.dataset.tried2 && img.src.includes(path3)) {
                    // All paths failed, show placeholder
                    img.style.display = 'none';
                    var frame = document.getElementById('passport-frame');
                    var placeholder = document.createElement('span');
                    placeholder.className = 'text-muted';
                    placeholder.innerHTML = '<i class="fas fa-user fa-3x"></i>';
                    if (!frame.querySelector('.text-muted')) {
                        frame.appendChild(placeholder);
                    }
                    return;
                }
                img.dataset.tried2 = 'true';
                img.src = '{{ asset("") }}' + path3;
            } else {
                // Try second path
                img.src = '{{ asset("") }}' + path2;
            }
        }
    </script>
</head>
<body>
    <div class="watermark-bg"></div>

    <div class="print-container mt-4">
        <div class="no-print mb-3 text-end">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print me-2"></i>Print Form
            </button>
            <a href="{{ route('applicant.application') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back
            </a>
        </div>

        <div class="invoice-header">
            <div class="institution-logo">
                <img src="{{ asset(\App\Models\Setting::get('institution_logo', 'images/logo.png')) }}" alt="Logo" onerror="this.src='{{ asset('images/logo-placeholder.png') }}'">
            </div>
            <h1>{{ \App\Models\Setting::get('institution_name', 'Ekiti State College of Technology, Ijero-Ekiti') }}</h1>
            <p><strong>{{ \App\Models\Setting::get('institution_address', 'Ijero-Ekiti, Ekiti State, Nigeria') }}</strong></p>
            <p>Phone: {{ \App\Models\Setting::get('institution_phone', '+2348000000000') }} | Email: {{ \App\Models\Setting::get('institution_email', 'info@portal.edu') }}</p>
        </div>

        <h3 class="text-center mb-4" style="color: var(--primary);">
            <strong>ADMISSION APPLICATION FORM</strong>
        </h3>

        <div class="row mb-4">
            <div class="col-md-12 text-center">
                <div class="photo-frame mx-auto mb-3" id="passport-frame">
                    @php
                        $passportPaths = [
                            'storage/passports/' . $applicant->passport,
                            'uploads/passports/' . $applicant->passport,
                            'storage/app/public/passports/' . $applicant->passport,
                        ];
                    @endphp
                    @if($applicant->passport)
                        <img id="passport-img" src="{{ asset($passportPaths[0]) }}" alt="Passport"
                             onerror="tryNextPassport(this, '{{ $passportPaths[1] }}', '{{ $passportPaths[2] }}')"
                             style="max-width: 150px; max-height: 180px; object-fit: cover;">
                    @else
                        <span class="text-muted"><i class="fas fa-user fa-3x"></i></span>
                    @endif
                </div>
                <h4><strong>Application Number: {{ $applicant->application_number }}</strong></h4>
                <span class="badge badge-status bg-{{ $applicant->status === 'admitted' ? 'success' : ($applicant->status === 'pending' ? 'warning' : 'secondary') }}">
                    {{ strtoupper($applicant->status) }}
                </span>
            </div>
        </div>

        <div class="student-info">
            <table class="table">
                <tr>
                    <td colspan="2" class="table-primary"><strong><i class="fas fa-user me-2"></i>PERSONAL INFORMATION</strong></td>
                </tr>
                <tr>
                    <td width="50%"><strong>Surname:</strong> {{ $applicant->surname }}</td>
                    <td width="50%"><strong>First Name:</strong> {{ $applicant->first_name }}</td>
                </tr>
                @if($applicant->middle_name)
                <tr>
                    <td><strong>Middle Name:</strong> {{ $applicant->middle_name }}</td>
                    <td></td>
                </tr>
                @endif
                <tr>
                    <td><strong>Email:</strong> {{ $applicant->email }}</td>
                    <td><strong>Phone:</strong> {{ $applicant->phone }}</td>
                </tr>
                <tr>
                    <td><strong>Gender:</strong> {{ $applicant->gender }}</td>
                    <td><strong>Date of Birth:</strong> {{ $applicant->date_of_birth?->format('d M Y') ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td><strong>Place of Birth:</strong> {{ $applicant->place_of_birth ?? 'N/A' }}</td>
                    <td><strong>Religion:</strong> {{ $applicant->religion ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td><strong>Blood Group:</strong> {{ $applicant->blood_group ?? 'N/A' }}</td>
                    <td><strong>Genotype:</strong> {{ $applicant->genotype ?? 'N/A' }}</td>
                </tr>
                @if($applicant->disability && $applicant->disability !== 'none')
                <tr>
                    <td colspan="2"><strong>Disability:</strong> {{ ucfirst($applicant->disability) }} - {{ $applicant->disability_details ?? '' }}</td>
                </tr>
                @endif
            </table>

            <table class="table">
                <tr>
                    <td colspan="2" class="table-primary"><strong><i class="fas fa-map-marker-alt me-2"></i>CONTACT INFORMATION</strong></td>
                </tr>
                <tr>
                    <td width="50%"><strong>Address:</strong> {{ $applicant->address }}</td>
                    <td width="50%"><strong>State:</strong> {{ $applicant->state->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td><strong>LGA:</strong> {{ $applicant->lga->name ?? 'N/A' }}</td>
                    <td><strong>Nationality:</strong> {{ $applicant->nationality->name ?? 'N/A' }}</td>
                </tr>
            </table>

            <table class="table">
                <tr>
                    <td colspan="2" class="table-primary"><strong><i class="fas fa-graduation-cap me-2"></i>PROGRAMME SELECTION</strong></td>
                </tr>
                <tr>
                    <td width="50%"><strong>School:</strong> {{ $applicant->school->name ?? 'N/A' }}</td>
                    <td width="50%"><strong>Department:</strong> {{ $applicant->department->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td><strong>Programme:</strong> {{ $applicant->programme->name ?? 'N/A' }}</td>
                    <td><strong>Session:</strong> {{ $applicant->session->name ?? 'N/A' }}</td>
                </tr>
            </table>

            @if($applicant->olevel1_subject1)
            <table class="table">
                <tr>
                    <td colspan="4" class="table-primary"><strong><i class="fas fa-book me-2"></i>O-LEVEL RESULTS (FIRST SITTING)</strong></td>
                </tr>
                <tr>
                    <td width="25%"><strong>Exam Type:</strong> {{ $applicant->olevel1_exam_type ?? 'N/A' }}</td>
                    <td width="25%"><strong>Exam Number:</strong> {{ $applicant->olevel1_exam_number ?? 'N/A' }}</td>
                    <td width="25%"><strong>Exam Year:</strong> {{ $applicant->olevel1_exam_year ?? 'N/A' }}</td>
                    <td width="25%"></td>
                </tr>
                <tr>
                    <th>Subject</th>
                    <th>Grade</th>
                    <th>Subject</th>
                    <th>Grade</th>
                </tr>
                <tr>
                    <td>{{ $applicant->olevel1_subject1 }}</td>
                    <td>{{ $applicant->olevel1_grade1 }}</td>
                    <td>{{ $applicant->olevel1_subject2 }}</td>
                    <td>{{ $applicant->olevel1_grade2 }}</td>
                </tr>
                <tr>
                    <td>{{ $applicant->olevel1_subject3 }}</td>
                    <td>{{ $applicant->olevel1_grade3 }}</td>
                    <td>{{ $applicant->olevel1_subject4 }}</td>
                    <td>{{ $applicant->olevel1_grade4 }}</td>
                </tr>
                <tr>
                    <td>{{ $applicant->olevel1_subject5 }}</td>
                    <td>{{ $applicant->olevel1_grade5 }}</td>
                    <td colspan="2"></td>
                </tr>
            </table>
            @endif

            @if($applicant->olevel2_subject1)
            <table class="table">
                <tr>
                    <td colspan="4" class="table-primary"><strong><i class="fas fa-book me-2"></i>O-LEVEL RESULTS (SECOND SITTING)</strong></td>
                </tr>
                <tr>
                    <td width="25%"><strong>Exam Type:</strong> {{ $applicant->olevel2_exam_type ?? 'N/A' }}</td>
                    <td width="25%"><strong>Exam Number:</strong> {{ $applicant->olevel2_exam_number ?? 'N/A' }}</td>
                    <td width="25%"><strong>Exam Year:</strong> {{ $applicant->olevel2_exam_year ?? 'N/A' }}</td>
                    <td width="25%"></td>
                </tr>
                <tr>
                    <th>Subject</th>
                    <th>Grade</th>
                    <th>Subject</th>
                    <th>Grade</th>
                </tr>
                <tr>
                    <td>{{ $applicant->olevel2_subject1 }}</td>
                    <td>{{ $applicant->olevel2_grade1 }}</td>
                    <td>{{ $applicant->olevel2_subject2 }}</td>
                    <td>{{ $applicant->olevel2_grade2 }}</td>
                </tr>
                <tr>
                    <td>{{ $applicant->olevel2_subject3 }}</td>
                    <td>{{ $applicant->olevel2_grade3 }}</td>
                    <td>{{ $applicant->olevel2_subject4 }}</td>
                    <td>{{ $applicant->olevel2_grade4 }}</td>
                </tr>
                <tr>
                    <td>{{ $applicant->olevel2_subject5 }}</td>
                    <td>{{ $applicant->olevel2_grade5 }}</td>
                    <td colspan="2"></td>
                </tr>
            </table>
            @endif

            @if($applicant->guardian_name)
            <table class="table">
                <tr>
                    <td colspan="2" class="table-primary"><strong><i class="fas fa-user-shield me-2"></i>GUARDIAN / PARENT INFORMATION</strong></td>
                </tr>
                <tr>
                    <td width="50%"><strong>Name:</strong> {{ $applicant->guardian_name }}</td>
                    <td width="50%"><strong>Relationship:</strong> {{ $applicant->guardian_relationship ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td><strong>Phone:</strong> {{ $applicant->guardian_phone ?? 'N/A' }}</td>
                    <td><strong>Email:</strong> {{ $applicant->guardian_email ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td><strong>Occupation:</strong> {{ $applicant->guardian_occupation ?? 'N/A' }}</td>
                    <td><strong>Address:</strong> {{ $applicant->guardian_address ?? 'N/A' }}</td>
                </tr>
            </table>
            @endif

            @if($applicant->extra_curricular)
            <table class="table">
                <tr>
                    <td colspan="2" class="table-primary"><strong><i class="fas fa-star me-2"></i>EXTRA CURRICULAR ACTIVITIES</strong></td>
                </tr>
                <tr>
                    <td colspan="2">{{ $applicant->extra_curricular }}</td>
                </tr>
            </table>
            @endif
        </div>

        <div class="signature-section">
            <table class="table">
                <tr>
                    <td width="50%">
                        <p><strong>Applicant's Signature:</strong> _______________________</p>
                        <p class="mt-3">Date: _______________________</p>
                    </td>
                    <td width="50%">
                        <p><strong>Authorized Signature:</strong> _______________________</p>
                        <p class="mt-3">Date: _______________________</p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="text-center mt-4 text-muted" style="font-size: 10pt;">
            <p><strong>Generated on:</strong> {{ date('d M Y, h:i A') }}</p>
            <p>This is a computer-generated document. {{ \App\Models\Setting::get('institution_name', 'Ekiti State College of Technology, Ijero-Ekiti') }}</p>
        </div>
    </div>
</body>
</html>
