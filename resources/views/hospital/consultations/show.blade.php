@extends('layouts.app')

@section('title', 'Consultation #' . $consultation->id)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h3 class="mb-0"><i class="fas fa-stethoscope me-2"></i>Consultation #{{ $consultation->id }}</h3>
        <a href="{{ route('hospital.consultations.index') }}" class="btn btn-outline-secondary" title="Back to consultations">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-5">
            <!-- Patient + Consultation summary -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-user me-2"></i>Patient</h5>
                </div>
                <div class="card-body">
                    @if($consultation->patient)
                        <p class="mb-1"><strong>{{ $consultation->patient->first_name }} {{ $consultation->patient->last_name }}</strong></p>
                        <p class="text-muted mb-1">Patient No: {{ $consultation->patient->patient_number }}</p>
                        <p class="text-muted mb-0">Phone: {{ $consultation->patient->phone }}</p>
                    @else
                        <p class="text-muted mb-0">No patient linked.</p>
                    @endif
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-notes-medical me-2"></i>Notes</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Doctor</dt>
                        <dd class="col-sm-8">
                            @if($consultation->doctor)
                                Dr. {{ $consultation->doctor->first_name }} {{ $consultation->doctor->last_name }}
                            @else
                                <span class="text-muted">Unassigned</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4">Date</dt>
                        <dd class="col-sm-8">{{ optional($consultation->consultation_date)->format('d M Y, h:i A') ?? 'N/A' }}</dd>

                        <dt class="col-sm-4">Visit Type</dt>
                        <dd class="col-sm-8">{{ ucfirst(str_replace('_',' ', $consultation->visit_type ?? '')) }}</dd>

                        <dt class="col-sm-4">Complaint</dt>
                        <dd class="col-sm-8">{{ $consultation->chief_complaint ?: '—' }}</dd>

                        <dt class="col-sm-4">Symptoms</dt>
                        <dd class="col-sm-8">{{ $consultation->symptoms ?: '—' }}</dd>

                        <dt class="col-sm-4">Examination</dt>
                        <dd class="col-sm-8">{{ $consultation->examination_findings ?: '—' }}</dd>

                        <dt class="col-sm-4">Doctor Notes</dt>
                        <dd class="col-sm-8">{{ $consultation->doctor_notes ?: '—' }}</dd>

                        <dt class="col-sm-4">Treatment Plan</dt>
                        <dd class="col-sm-8">{{ $consultation->treatment_plan ?: '—' }}</dd>
                    </dl>
                </div>
            </div>

            @if($consultation->diagnoses && $consultation->diagnoses->count())
                <div class="card mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-diagnoses me-2"></i>Diagnoses</h5>
                    </div>
                    <ul class="list-group list-group-flush">
                        @foreach($consultation->diagnoses as $dx)
                            <li class="list-group-item">
                                <strong>{{ $dx->diagnosis }}</strong>
                                @if($dx->icd_code) <span class="badge bg-light text-dark ms-1">{{ $dx->icd_code }}</span> @endif
                                <small class="text-muted d-block">{{ ucfirst($dx->type ?? 'primary') }}{{ $dx->severity ? ' • ' . $dx->severity : '' }}</small>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <div class="col-lg-7">
            <!-- Prescribe drugs -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-pills me-2"></i>Prescribe Drugs</h5>
                </div>
                <div class="card-body">
                    @permission('prescriptions.create')
                    <form method="POST" action="{{ route('hospital.consultations.prescriptions.store', $consultation) }}" id="prescribeForm">
                        @csrf
                        <div id="prescriptionItems">
                            <div class="prescription-row row g-2 mb-2">
                                <div class="col-md-4">
                                    <select name="items[0][drug_id]" class="form-select" required>
                                        <option value="">Select Drug</option>
                                        @foreach(\App\Models\Hospital\HospitalDrug::where('is_active', true)->orderBy('name')->get() as $drug)
                                            <option value="{{ $drug->id }}" data-price="{{ $drug->selling_price }}">{{ $drug->name }} ({{ $drug->strength }}) — ₦{{ number_format($drug->selling_price, 2) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-1">
                                    <input type="number" name="items[0][quantity]" class="form-control" placeholder="Qty" min="1" value="1" required>
                                </div>
                                <div class="col-md-2">
                                    <input type="text" name="items[0][dosage]" class="form-control" placeholder="Dosage" required>
                                </div>
                                <div class="col-md-2">
                                    <input type="text" name="items[0][frequency]" class="form-control" placeholder="e.g. TDS" required>
                                </div>
                                <div class="col-md-2">
                                    <input type="text" name="items[0][duration]" class="form-control" placeholder="e.g. 5 days" required>
                                </div>
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-row" title="Remove row">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="mb-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="addPrescriptionRow" title="Add another drug">
                                <i class="fas fa-plus"></i> Add Another Drug
                            </button>
                        </div>
                        <div class="mb-2">
                            <textarea name="notes" class="form-control" rows="2" placeholder="Optional prescription notes"></textarea>
                        </div>
                        <button type="submit" class="btn btn-success" title="Send prescription to patient for payment">
                            <i class="fas fa-paper-plane me-2"></i>Send to Patient for Payment
                        </button>
                    </form>
                    @else
                        <p class="text-muted mb-0">You don't have permission to prescribe drugs.</p>
                    @endpermission
                </div>
            </div>

            <!-- Suggest lab / x-ray -->
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-vial me-2"></i>Suggest Test / X-ray</h5>
                </div>
                <div class="card-body">
                    @permission('lab.create')
                    <form method="POST" action="{{ route('hospital.consultations.lab-requests.store', $consultation) }}">
                        @csrf
                        <div class="row g-2">
                            <div class="col-md-7 mb-2">
                                <label class="form-label small">Test Type *</label>
                                <select name="test_type" class="form-select" required>
                                    <option value="">Select Test</option>
                                    @foreach(\App\Models\Hospital\HospitalServiceType::where('category', 'Laboratory')->where('is_active', true)->orderBy('name')->get() as $svc)
                                        <option value="{{ $svc->name }}" data-amount="{{ $svc->amount }}">{{ $svc->name }} — ₦{{ number_format($svc->amount, 2) }}</option>
                                    @endforeach
                                    <option value="X-Ray">X-Ray</option>
                                    <option value="Ultrasound Scan">Ultrasound Scan</option>
                                    <option value="MRI">MRI</option>
                                    <option value="CT Scan">CT Scan</option>
                                </select>
                            </div>
                            <div class="col-md-5 mb-2">
                                <label class="form-label small">Amount (₦) *</label>
                                <input type="number" name="amount" id="labAmount" class="form-control" min="0" step="0.01" required>
                            </div>
                            <div class="col-md-12 mb-2">
                                <label class="form-label small">Clinical Notes</label>
                                <textarea name="clinical_notes" class="form-control" rows="2" placeholder="Why this test is needed"></textarea>
                            </div>
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-warning" title="Send test suggestion to patient for payment">
                                    <i class="fas fa-paper-plane me-2"></i>Send to Patient for Payment
                                </button>
                            </div>
                        </div>
                    </form>
                    @else
                        <p class="text-muted mb-0">You don't have permission to suggest tests.</p>
                    @endpermission
                </div>
            </div>

            <!-- Recent orders for this patient -->
            @php
                $orders = \App\Models\Hospital\HospitalOrderItem::query()
                    ->where('patient_id', $consultation->patient_id)
                    ->with('orderable')
                    ->orderBy('created_at', 'desc')
                    ->limit(20)
                    ->get();
            @endphp
            <div class="card mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-receipt me-2"></i>Recent Orders for This Patient</h5>
                </div>
                <div class="card-body">
                    @if($orders->count())
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Type</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($orders as $o)
                                        <tr>
                                            <td>{{ $o->item_name }}</td>
                                            <td>
                                                @if($o->orderable_type === \App\Models\Hospital\HospitalPrescriptionItem::class)
                                                    <span class="badge bg-primary">Prescription</span>
                                                @else
                                                    <span class="badge bg-info">Lab / X-ray</span>
                                                @endif
                                            </td>
                                            <td>₦{{ number_format((float) $o->amount, 2) }}</td>
                                            <td>
                                                @if($o->status === 'paid')
                                                    <span class="badge bg-success">Paid</span>
                                                @elseif($o->status === 'awaiting_payment')
                                                    <span class="badge bg-warning">Awaiting Payment</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ ucfirst($o->status) }}</span>
                                                @endif
                                            </td>
                                            <td>{{ $o->created_at->format('d M Y, h:i A') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">No prescribed items yet for this patient.</p>
                    @endif
                </div>
            </div>

            <!-- Existing prescriptions and lab requests on this consultation -->
            @if($consultation->prescriptions->count())
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-prescription me-2"></i>Prescriptions on this Consultation</h5>
                    </div>
                    <ul class="list-group list-group-flush">
                        @foreach($consultation->prescriptions as $rx)
                            <li class="list-group-item">
                                <strong>{{ ucfirst($rx->status) }}</strong> — created {{ $rx->created_at->format('d M Y') }}
                                <small class="text-muted d-block">{{ $rx->notes }}</small>
                                @if($rx->items && $rx->items->count())
                                    <ul class="mb-0">
                                        @foreach($rx->items as $it)
                                            <li>{{ $it->drug_name }} × {{ $it->quantity }} — {{ $it->dosage }} {{ $it->frequency }} ({{ $it->duration }})</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($consultation->labRequests->count())
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-vial me-2"></i>Lab / Scan Requests on this Consultation</h5>
                    </div>
                    <ul class="list-group list-group-flush">
                        @foreach($consultation->labRequests as $lr)
                            <li class="list-group-item">
                                <strong>{{ $lr->test_type }}</strong>
                                <span class="badge bg-{{ $lr->status === 'completed' ? 'success' : 'warning' }}">{{ ucfirst(str_replace('_',' ',$lr->status)) }}</span>
                                @if($lr->clinical_notes)
                                    <small class="text-muted d-block">{{ $lr->clinical_notes }}</small>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var rowIdx = 1;
    var itemsContainer = document.getElementById('prescriptionItems');
    var addBtn = document.getElementById('addPrescriptionRow');
    if (addBtn && itemsContainer) {
        addBtn.addEventListener('click', function() {
            var firstRow = itemsContainer.querySelector('.prescription-row');
            var clone = firstRow.cloneNode(true);
            clone.querySelectorAll('input, select').forEach(function(el) {
                if (el.name) {
                    el.name = el.name.replace(/items\[\d+\]/, 'items[' + rowIdx + ']');
                    if (el.tagName === 'SELECT') el.selectedIndex = 0;
                    if (el.tagName === 'INPUT' && el.type === 'number') el.value = 1;
                    if (el.tagName === 'INPUT' && el.type === 'text') el.value = '';
                }
            });
            itemsContainer.appendChild(clone);
            rowIdx++;
        });
        itemsContainer.addEventListener('click', function(e) {
            if (e.target.closest('.remove-row')) {
                var rows = itemsContainer.querySelectorAll('.prescription-row');
                if (rows.length > 1) {
                    e.target.closest('.prescription-row').remove();
                }
            }
        });
    }

    // Auto-fill lab amount when a service is selected
    var labForm = document.querySelector('form[action*="lab-requests"]');
    if (labForm) {
        var select = labForm.querySelector('select[name="test_type"]');
        var amount = labForm.querySelector('#labAmount');
        if (select && amount) {
            select.addEventListener('change', function() {
                var opt = select.options[select.selectedIndex];
                var amt = opt.getAttribute('data-amount');
                if (amt) { amount.value = amt; }
            });
        }
    }
});
</script>
@endpush
@endsection