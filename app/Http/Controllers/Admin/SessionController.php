<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Session;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function index()
    {
        $sessions = Session::latest()->get();
        return view('admin.sessions.index', compact('sessions'));
    }

    public function create()
    {
        return view('admin.sessions.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:sessions',
            'semester' => 'required|in:First,Second',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        if ($request->has('is_current')) {
            Session::query()->update(['is_current' => false]);
            $validated['is_current'] = true;
        } else {
            $validated['is_current'] = false;
        }

        Session::create($validated);
        return redirect()->route('admin.sessions.index')->with('success', 'Session created');
    }

    public function edit(Session $session)
    {
        return view('admin.sessions.edit', compact('session'));
    }

    public function update(Request $request, Session $session)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:sessions,name,' . $session->id,
            'semester' => 'required|in:First,Second',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        if ($request->has('is_current')) {
            Session::query()->update(['is_current' => false]);
            $validated['is_current'] = true;
        } else {
            $validated['is_current'] = false;
        }

        $session->update($validated);
        return redirect()->route('admin.sessions.index')->with('success', 'Session updated');
    }

    public function setCurrent(Session $session)
    {
        Session::setCurrentSession($session->id);
        return back()->with('success', 'Current session set to ' . $session->name);
    }

    public function destroy(Session $session)
    {
        $session->delete();
        return back()->with('success', 'Session deleted');
    }
}