<?php

namespace App\Http\Controllers\HOD;

use App\Http\Controllers\Controller;
use App\Models\Result;
use Illuminate\Http\Request;

class ResultController extends Controller
{
    public function index()
    {
        return view('hod.results');
    }

    public function approve(Result $result, Request $request)
    {
        $result->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'remarks' => $request->remarks,
        ]);
        return back()->with('success', 'Result approved');
    }

    public function reject(Result $result, Request $request)
    {
        $result->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'remarks' => $request->remarks,
        ]);
        return back()->with('success', 'Result rejected');
    }
}