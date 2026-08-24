<?php

namespace App\Http\Controllers\Dean;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\EnforcesPermission;
use App\Models\Department;
use App\Models\Programme;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    use EnforcesPermission;

    /**
     * List the students in the Dean's school, filterable by
     * department and programme. The list is server-paginated and
     * scoped to `users.school_id` so a Dean can never see another
     * school's roster.
     */
    public function index(Request $request)
    {
        $this->requirePermission('academic.students.view');

        $user = auth()->user();
        $schoolId = $user?->school_id;

        if (!$schoolId) {
            return view('dean.students', [
                'students' => collect()->paginate(20),
                'departments' => collect(),
                'programmes' => collect(),
                'filters' => [],
            ]);
        }

        $departmentIds = Department::where('school_id', $schoolId)->orderBy('name')->pluck('id', 'name');

        $query = Student::query()
            ->where('school_id', $schoolId)
            ->with(['user', 'department', 'programme']);

        $filters = [];

        if ($departmentId = $request->integer('department_id')) {
            if (Department::where('school_id', $schoolId)->whereKey($departmentId)->exists()) {
                $query->where('department_id', $departmentId);
                $filters['department_id'] = $departmentId;
            }
        }

        if ($programmeId = $request->integer('programme_id')) {
            // Scope programme filter to the school's departments so
            // a Dean can't probe a programme from another school by
            // guessing its id.
            $validProgrammeIds = Programme::whereIn('department_id', $departmentIds->values())->pluck('id');
            if ($validProgrammeIds->contains($programmeId)) {
                $query->where('programme_id', $programmeId);
                $filters['programme_id'] = $programmeId;
            }
        }

        if ($level = $request->integer('level')) {
            $query->where('level', $level);
            $filters['level'] = $level;
        }

        if ($search = trim((string) $request->input('search'))) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })->orWhere('matric_number', 'like', "%{$search}%");
            $filters['search'] = $search;
        }

        $students = $query->orderBy('matric_number')->paginate(20)->withQueryString();

        $programmes = collect();
        if ($filters['department_id'] ?? null) {
            $programmes = Programme::where('department_id', $filters['department_id'])
                ->orderBy('name')->pluck('name', 'id');
        }

        return view('dean.students', [
            'students' => $students,
            'departments' => $departmentIds,
            'programmes' => $programmes,
            'filters' => $filters,
        ]);
    }
}