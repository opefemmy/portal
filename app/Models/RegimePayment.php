<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegimePayment extends Model
{
    protected $fillable = [
        'name', 'student_type', 'installment', 'percentage', 'amount', 'is_active',
        'payment_type', 'school_id', 'department_id', 'programme_id',
        'session_id', 'semester', 'level', 'level_operator',
        'portal_charge', 'include_portal_charge', 'payment_config'
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
        'amount' => 'decimal:2',
        'portal_charge' => 'decimal:2',
        'is_active' => 'boolean',
        'include_portal_charge' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function programme(): BelongsTo
    {
        return $this->belongsTo(Programme::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public static function getActiveRegimes()
    {
        return static::where('is_active', true)->get();
    }

    public static function getRegimeForStudent($student, $paymentType = 'school_fee')
    {
        $query = static::where('is_active', true)
            ->where('payment_type', $paymentType)
            ->where('student_type', $student->student_type ?? 'Non-Indigene');

        // Filter by school if set
        $query->where(function($q) use ($student) {
            $q->whereNull('school_id')
              ->orWhere('school_id', $student->school_id);
        });

        // Filter by department if set
        $query->where(function($q) use ($student) {
            $q->whereNull('department_id')
              ->orWhere('department_id', $student->department_id);
        });

        // Filter by programme if set
        $query->where(function($q) use ($student) {
            $q->whereNull('programme_id')
              ->orWhere('programme_id', $student->programme_id);
        });

        // Filter by session
        $query->where(function($q) use ($student) {
            $q->whereNull('session_id')
              ->orWhere('session_id', $student->session_id);
        });

        // Filter by level
        $query->where(function($q) use ($student) {
            $q->whereNull('level')
              ->orWhere('level', $student->level);
        });

        return $query->get();
    }

    public static function getPaymentConfigOptions()
    {
        return [
            'full' => 'Full Payment (100%)',
            '60_40' => '60% First Installment, 40% Second Installment',
            '50_50' => '50% First Installment, 50% Second Installment',
            '70_30' => '70% First Installment, 30% Second Installment',
        ];
    }

    public static function getPaymentTypeOptions()
    {
        return [
            'school_fee' => 'School Fee',
            'accommodation' => 'Accommodation',
            'acceptance_fee' => 'Acceptance Fee',
            'other' => 'Other Fee',
        ];
    }

    public static function getSemesterOptions()
    {
        return [
            'first' => 'First Semester',
            'second' => 'Second Semester',
            'both' => 'Both Semesters',
        ];
    }

    public function calculateAmount($baseAmount)
    {
        $amount = ($baseAmount * $this->percentage) / 100;

        if ($this->include_portal_charge && $this->portal_charge > 0) {
            $amount += $this->portal_charge;
        }

        return round($amount, 2);
    }

    public static function canPaySecondInstallment($student, $fee)
    {
        $firstPayment = Payment::where('student_id', $student->id)
            ->where('fee_id', $fee->id)
            ->where('installment', 'First')
            ->where('status', 'completed')
            ->first();

        return $firstPayment !== null;
    }
}