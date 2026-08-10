<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        return match ($user->role->slug) {
            'super_admin', 'admin', 'ict_admin', 'staff' => redirect('/admin/dashboard'),
            'student' => redirect('/student/dashboard'),
            'lecturer' => redirect('/lecturer/dashboard'),
            'hod' => redirect('/hod/dashboard'),
            'dean' => redirect('/dean/dashboard'),
            'registrar' => redirect('/registrar/dashboard'),
            'bursar', 'bursary_officer', 'fees_officer', 'payment_officer' => redirect('/bursar/dashboard'),
            'applicant' => redirect('/applicant/dashboard'),
            default => redirect('/dashboard'),
        };
    }
}