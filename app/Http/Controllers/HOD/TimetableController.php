<?php

namespace App\Http\Controllers\HOD;

use App\Http\Controllers\Controller;
use App\Models\Timetable;
use Illuminate\Http\Request;

class TimetableController extends Controller
{
    public function index()
    {
        return view('hod.timetable');
    }

    public function approve(Timetable $timetable)
    {
        $timetable->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);
        return back()->with('success', 'Timetable approved');
    }

    public function reject(Timetable $timetable)
    {
        $timetable->update(['status' => 'rejected']);
        return back()->with('success', 'Timetable rejected');
    }
}