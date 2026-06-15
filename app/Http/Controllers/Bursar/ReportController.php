<?php

namespace App\Http\Controllers\Bursar;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        return view('bursar.reports');
    }
}