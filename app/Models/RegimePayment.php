<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegimePayment extends Model
{
    protected $fillable = ['name', 'student_type', 'installment', 'percentage', 'amount', 'is_active'];

    protected $casts = [
        'percentage' => 'decimal:2',
        'amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public static function getActiveRegimes()
    {
        return static::where('is_active', true)->get();
    }

    public static function getRegimeForStudentType($studentType, $installment)
    {
        return static::where('student_type', $studentType)
            ->where('installment', $installment)
            ->where('is_active', true)
            ->first();
    }

    public static function canPaySecondInstallment($student, $fee)
    {
        // Check if first installment is paid
        $firstPayment = Payment::where('student_id', $student->id)
            ->where('fee_id', $fee->id)
            ->where('installment', 'First')
            ->where('status', 'completed')
            ->first();

        return $firstPayment !== null;
    }
}