<?php

namespace App\Http\Controllers\Dean;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\EnforcesPermission;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    use EnforcesPermission;

    public function index()
    {
        $this->requirePermission('academic.departments.view');
        return view('dean.departments');
    }
}