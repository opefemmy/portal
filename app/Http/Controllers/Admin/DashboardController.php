<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Session;
use App\Services\Dashboard\DashboardResolver;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // The configurator (when present) dictates which widgets this
        // user sees. When no `dashboard_widgets` row exists for the
        // user, the resolver falls back to the role default — so an
        // unconfigured user sees the same dashboard as before.
        $widgets      = DashboardResolver::widgetsForUser($request->user());
        $currentSession = Session::getCurrentSession();

        return view('admin.dashboard', [
            'widgets'        => $widgets,
            'currentSession' => $currentSession,
        ]);
    }
}