@php
    $t = $timetable ?? null;
@endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Course</label>
        <select name="course_id" class="form-select" required>
            <option value="">-- Select course --</option>
            @foreach($courses as $c)
                <option value="{{ $c->id }}" {{ old('course_id', $t?->course_id) == $c->id ? 'selected' : '' }}>
                    {{ $c->code }} — {{ $c->title }}
                </option>
            @endforeach
        </select>
        @error('course_id') <small class="text-danger">{{ $message }}</small> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Session</label>
        <select name="session_id" class="form-select" required>
            @foreach($sessions as $s)
                <option value="{{ $s->id }}" {{ old('session_id', $t?->session_id) == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
            @endforeach
        </select>
        @error('session_id') <small class="text-danger">{{ $message }}</small> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Semester</label>
        <select name="semester" class="form-select" required>
            <option value="First" {{ old('semester', $t?->semester) == 'First' ? 'selected' : '' }}>First</option>
            <option value="Second" {{ old('semester', $t?->semester) == 'Second' ? 'selected' : '' }}>Second</option>
        </select>
        @error('semester') <small class="text-danger">{{ $message }}</small> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Exam Date</label>
        <input type="date" name="exam_date" class="form-control" value="{{ old('exam_date', optional($t?->exam_date)->format('Y-m-d')) }}" required>
        @error('exam_date') <small class="text-danger">{{ $message }}</small> @enderror
    </div>
    <div class="col-md-2">
        <label class="form-label">Start Time</label>
        <input type="time" name="start_time" class="form-control" value="{{ old('start_time', $t?->start_time ? \Carbon\Carbon::parse($t->start_time)->format('H:i') : '') }}" required>
    </div>
    <div class="col-md-2">
        <label class="form-label">End Time</label>
        <input type="time" name="end_time" class="form-control" value="{{ old('end_time', $t?->end_time ? \Carbon\Carbon::parse($t->end_time)->format('H:i') : '') }}" required>
        @error('end_time') <small class="text-danger">{{ $message }}</small> @enderror
    </div>
    <div class="col-md-5">
        <label class="form-label">Venue</label>
        <input type="text" name="venue" class="form-control" value="{{ old('venue', $t?->venue) }}" placeholder="e.g. Hall A">
    </div>
</div>
