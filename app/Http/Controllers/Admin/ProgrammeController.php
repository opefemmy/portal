<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Programme;
use Illuminate\Http\Request;

class ProgrammeController extends Controller
{
    public function index()
    {
        $programmes = Programme::all();
        return view('admin.programmes.index', compact('programmes'));
    }

    public function create()
    {
        return view('admin.programmes.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:programmes',
            'type' => 'required|in:ND,HND,Degree,PGD,Masters,PhD',
        ]);

        Programme::create($validated);
        return redirect()->route('admin.programmes.index')->with('success', 'Programme created');
    }

    public function edit(Programme $programme)
    {
        return view('admin.programmes.edit', compact('programme'));
    }

    public function update(Request $request, Programme $programme)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:programmes,code,' . $programme->id,
            'type' => 'required|in:ND,HND,Degree,PGD,Masters,PhD',
        ]);

        $programme->update($validated);
        return redirect()->route('admin.programmes.index')->with('success', 'Programme updated');
    }

    public function destroy(Programme $programme)
    {
        $programme->delete();
        return back()->with('success', 'Programme deleted');
    }
}