@extends('layouts.app')

@section('title', 'Edit Measurements')

@section('content')
<div class="page-header">
    <h4>Edit Student Measurements</h4>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Student: {{ $student->matric_number }}</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.students.measurements.update', $student->id) }}">
                    @csrf
                    @method('PUT')

                    <!-- Uniform Measurements -->
                    <h5 class="mb-3"><i class="fas fa-tshirt me-2"></i>Uniform Measurements</h5>
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Shirt Size</label>
                            <select class="form-select" name="uniform_shirt_size">
                                <option value="">Select Size</option>
                                <option value="XXS" {{ $student->uniform_shirt_size == 'XXS' ? 'selected' : '' }}>XXS</option>
                                <option value="XS" {{ $student->uniform_shirt_size == 'XS' ? 'selected' : '' }}>XS</option>
                                <option value="S" {{ $student->uniform_shirt_size == 'S' ? 'selected' : '' }}>S</option>
                                <option value="M" {{ $student->uniform_shirt_size == 'M' ? 'selected' : '' }}>M</option>
                                <option value="L" {{ $student->uniform_shirt_size == 'L' ? 'selected' : '' }}>L</option>
                                <option value="XL" {{ $student->uniform_shirt_size == 'XL' ? 'selected' : '' }}>XL</option>
                                <option value="XXL" {{ $student->uniform_shirt_size == 'XXL' ? 'selected' : '' }}>XXL</option>
                                <option value="3XL" {{ $student->uniform_shirt_size == '3XL' ? 'selected' : '' }}>3XL</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Pant Size</label>
                            <select class="form-select" name="uniform_pant_size">
                                <option value="">Select Size</option>
                                <option value="26" {{ $student->uniform_pant_size == '26' ? 'selected' : '' }}>26</option>
                                <option value="28" {{ $student->uniform_pant_size == '28' ? 'selected' : '' }}>28</option>
                                <option value="30" {{ $student->uniform_pant_size == '30' ? 'selected' : '' }}>30</option>
                                <option value="32" {{ $student->uniform_pant_size == '32' ? 'selected' : '' }}>32</option>
                                <option value="34" {{ $student->uniform_pant_size == '34' ? 'selected' : '' }}>34</option>
                                <option value="36" {{ $student->uniform_pant_size == '36' ? 'selected' : '' }}>36</option>
                                <option value="38" {{ $student->uniform_pant_size == '38' ? 'selected' : '' }}>38</option>
                                <option value="40" {{ $student->uniform_pant_size == '40' ? 'selected' : '' }}>40</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Shoe Size</label>
                            <select class="form-select" name="uniform_shoe_size">
                                <option value="">Select Size</option>
                                @for($i = 35; $i <= 45; $i++)
                                <option value="{{ $i }}" {{ $student->uniform_shoe_size == $i ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    <!-- Scrub Measurements -->
                    <h5 class="mb-3"><i class="fas fa-user-md me-2"></i>Scrub Measurements</h5>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Scrub Size</label>
                            <select class="form-select" name="scrub_size">
                                <option value="">Select Size</option>
                                <option value="XXS" {{ $student->scrub_size == 'XXS' ? 'selected' : '' }}>XXS</option>
                                <option value="XS" {{ $student->scrub_size == 'XS' ? 'selected' : '' }}>XS</option>
                                <option value="S" {{ $student->scrub_size == 'S' ? 'selected' : '' }}>S</option>
                                <option value="M" {{ $student->scrub_size == 'M' ? 'selected' : '' }}>M</option>
                                <option value="L" {{ $student->scrub_size == 'L' ? 'selected' : '' }}>L</option>
                                <option value="XL" {{ $student->scrub_size == 'XL' ? 'selected' : '' }}>XL</option>
                                <option value="XXL" {{ $student->scrub_size == 'XXL' ? 'selected' : '' }}>XXL</option>
                                <option value="3XL" {{ $student->scrub_size == '3XL' ? 'selected' : '' }}>3XL</option>
                                <option value="4XL" {{ $student->scrub_size == '4XL' ? 'selected' : '' }}>4XL</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Scrub Color</label>
                            <select class="form-select" name="scrub_color">
                                <option value="">Select Color</option>
                                <option value="Navy Blue" {{ $student->scrub_color == 'Navy Blue' ? 'selected' : '' }}>Navy Blue</option>
                                <option value="Ceil Blue" {{ $student->scrub_color == 'Ceil Blue' ? 'selected' : '' }}>Ceil Blue</option>
                                <option value="Green" {{ $student->scrub_color == 'Green' ? 'selected' : '' }}>Green</option>
                                <option value="White" {{ $student->scrub_color == 'White' ? 'selected' : '' }}>White</option>
                                <option value="Black" {{ $student->scrub_color == 'Black' ? 'selected' : '' }}>Black</option>
                                <option value="Grey" {{ $student->scrub_color == 'Grey' ? 'selected' : '' }}>Grey</option>
                            </select>
                        </div>
                    </div>

                    <!-- Lab Coat Measurements -->
                    <h5 class="mb-3"><i class="fas fa-notes-medical me-2"></i>Lab Coat Measurements</h5>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Lab Coat Size</label>
                            <select class="form-select" name="lab_coat_size">
                                <option value="">Select Size</option>
                                <option value="XXS" {{ $student->lab_coat_size == 'XXS' ? 'selected' : '' }}>XXS</option>
                                <option value="XS" {{ $student->lab_coat_size == 'XS' ? 'selected' : '' }}>XS</option>
                                <option value="S" {{ $student->lab_coat_size == 'S' ? 'selected' : '' }}>S</option>
                                <option value="M" {{ $student->lab_coat_size == 'M' ? 'selected' : '' }}>M</option>
                                <option value="L" {{ $student->lab_coat_size == 'L' ? 'selected' : '' }}>L</option>
                                <option value="XL" {{ $student->lab_coat_size == 'XL' ? 'selected' : '' }}>XL</option>
                                <option value="XXL" {{ $student->lab_coat_size == 'XXL' ? 'selected' : '' }}>XXL</option>
                                <option value="3XL" {{ $student->lab_coat_size == '3XL' ? 'selected' : '' }}>3XL</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Lab Coat Length</label>
                            <select class="form-select" name="lab_coat_length">
                                <option value="">Select Length</option>
                                <option value="Short (Above Knee)" {{ $student->lab_coat_length == 'Short (Above Knee)' ? 'selected' : '' }}>Short (Above Knee)</option>
                                <option value="Medium (Knee Length)" {{ $student->lab_coat_length == 'Medium (Knee Length)' ? 'selected' : '' }}>Medium (Knee Length)</option>
                                <option value="Long (Below Knee)" {{ $student->lab_coat_length == 'Long (Below Knee)' ? 'selected' : '' }}>Long (Below Knee)</option>
                                <option value="Full Length" {{ $student->lab_coat_length == 'Full Length' ? 'selected' : '' }}>Full Length</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Measurements
                        </button>
                        <a href="{{ route('admin.students.measurements', $student->id) }}" class="btn btn-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
