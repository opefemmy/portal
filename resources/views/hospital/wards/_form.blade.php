{{-- Shared ward form used by create + edit views. --}}
<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Name</label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $ward->name ?? '') }}" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Type</label>
        <select name="type" class="form-select @error('type') is-invalid @enderror" required>
            @php $types = ['general','private','emergency','maternity','pediatric','icu','isolation']; @endphp
            @foreach($types as $type)
                <option value="{{ $type }}" @selected(old('type', $ward->type ?? 'general') === $type)>
                    {{ ucfirst($type) }}
                </option>
            @endforeach
        </select>
        @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Total beds</label>
        <input type="number" name="total_beds" min="1" max="500" class="form-control"
               value="{{ old('total_beds', $ward->total_beds ?? 1) }}" required>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Daily rate (₦)</label>
        <input type="number" name="daily_rate" step="0.01" min="0" class="form-control"
               value="{{ old('daily_rate', $ward->daily_rate ?? 0) }}" required>
    </div>
    <div class="col-md-4 mb-3 d-flex align-items-end">
        <div class="form-check">
            <input type="checkbox" name="is_active" value="1" class="form-check-input"
                   id="is_active" @checked(old('is_active', $ward->is_active ?? true))>
            <label class="form-check-label" for="is_active">Active</label>
        </div>
    </div>
    <div class="col-12 mb-3">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="3">{{ old('description', $ward->description ?? '') }}</textarea>
    </div>
</div>