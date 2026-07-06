<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Form - {{ $applicant->application_number }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
        }
        .invoice-header {
            text-align: center;
            border-bottom: 2px solid #1a237e;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .invoice-header h1 {
            color: #1a237e;
            font-size: 24pt;
            margin-bottom: 5px;
        }
        .student-info {
            margin-bottom: 30px;
        }
        .student-info table td {
            padding: 5px 10px;
        }
        .signature-section {
            margin-top: 50px;
            page-break-inside: avoid;
        }
        .signature-section table td {
            padding: 30px 10px;
            border-top: 1px solid #ccc;
        }
        .photo-frame {
            width: 150px;
            height: 150px;
            border: 2px solid #1a237e;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f5f5f5;
        }
        @media print {
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="no-print mb-3">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print me-2"></i>Print Form
            </button>
            <a href="{{ route('applicant.application') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back
            </a>
        </div>

        <div class="invoice-header">
            <h1>{{ \App\Models\Setting::get('institution_name', 'Institution Management Portal') }}</h1>
            <p>{{ \App\Models\Setting::get('institution_address', 'University Road, City, State') }}</p>
            <p>Phone: {{ \App\Models\Setting::get('institution_phone', '+2348000000000') }} | Email: {{ \App\Models\Setting::get('institution_email', 'info@portal.edu') }}</p>
        </div>

        <h3 class="text-center mb-4">APPLICATION FORM</h3>

        <div class="row mb-4">
            <div class="col-md-12 text-center">
                <div class="photo-frame mx-auto mb-3">
                    @if($applicant->passport)
                        <img src="{{ asset('storage/passports/' . $applicant->passport) }}" alt="Passport" style="width: 100%; height: 100%; object-fit: cover;">
                    @else
                        <span class="text-muted">No Photo</span>
                    @endif
                </div>
                <h4><strong>Application Number: {{ $applicant->application_number }}</strong></h4>
                <span class="badge bg-{{ $applicant->status === 'admitted' ? 'success' : ($applicant->status === 'pending' ? 'warning' : 'secondary') }}">
                    {{ strtoupper($applicant->status) }}
                </span>
            </div>
        </div>

        <div class="student-info">
            <table class="table table-bordered">
                <tr>
                    <td colspan="2" class="table-primary"><strong>PERSONAL INFORMATION</strong></td>
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
                @if($applicant->religion)
                <tr>
                    <td><strong>Religion:</strong> {{ $applicant->religion }}</td>
                    <td><strong>Blood Group:</strong> {{ $applicant->blood_group ?? 'N/A' }}</td>
                </tr>
                @endif
            </table>

            <table class="table table-bordered">
                <tr>
                    <td colspan="2" class="table-primary"><strong>CONTACT INFORMATION</strong></td>
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

            <table class="table table-bordered">
                <tr>
                    <td colspan="2" class="table-primary"><strong>PROGRAMME SELECTION</strong></td>
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
            <table class="table table-bordered">
                <tr>
                    <td colspan="4" class="table-primary"><strong>O-LEVEL RESULTS</strong></td>
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
                    <td colspan="2"><strong>Exam Year:</strong> {{ $applicant->olevel1_exam_year }}</td>
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

        <div class="text-center mt-4 text-muted">
            <p>Generated on: {{ date('d M Y, h:i A') }}</p>
            <p class="no-print">This is a computer-generated document.</p>
        </div>
    </div>
</body>
</html>