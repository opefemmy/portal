<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\FinancePayroll;
use App\Models\Finance\FinanceAllowance;
use App\Models\Finance\FinanceDeduction;
use App\Models\Finance\FinanceStaffAllowance;
use App\Models\Finance\FinanceStaffDeduction;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        $query = FinancePayroll::with('staff');

        if ($request->month && $request->year) {
            $query->where('month', $request->month)->where('year', $request->year);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $payrolls = $query->orderBy('year', 'desc')->orderBy('month', 'desc')->paginate(20);

        return view('finance.payroll.index', compact('payrolls'));
    }

    public function create()
    {
        $staff = User::whereHas('role', function($q) {
            $q->whereIn('slug', ['staff', 'lecturer', 'hod', 'dean', 'admin']);
        })->where('is_active', true)->get();

        $allowances = FinanceAllowance::where('is_active', true)->get();
        $deductions = FinanceDeduction::where('is_active', true)->get();

        return view('finance.payroll.create', compact('staff', 'allowances', 'deductions'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'staff_id' => 'required|exists:users,id',
            'month' => 'required|string',
            'year' => 'required|integer',
            'basic_salary' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Calculate allowances
        $totalAllowances = 0;
        if ($request->allowances && is_array($request->allowances)) {
            foreach ($request->allowances as $allowanceId => $amount) {
                $totalAllowances += $amount;
            }
        }

        // Calculate deductions
        $totalDeductions = 0;
        if ($request->deductions && is_array($request->deductions)) {
            foreach ($request->deductions as $deductionId => $amount) {
                $totalDeductions += $amount;
            }
        }

        $grossSalary = $request->basic_salary + $totalAllowances;
        $tax = $this->calculateTax($grossSalary);
        $pension = $request->basic_salary * 0.08; // 8% pension
        $netSalary = $grossSalary - $totalDeductions - $tax - $pension;

        $payroll = FinancePayroll::create([
            'staff_id' => $request->staff_id,
            'basic_salary' => $request->basic_salary,
            'total_allowances' => $totalAllowances,
            'total_deductions' => $totalDeductions,
            'gross_salary' => $grossSalary,
            'net_salary' => $netSalary,
            'tax_deducted' => $tax,
            'pension_deducted' => $pension,
            'status' => 'draft',
            'processed_by' => auth()->id(),
            'processed_at' => now(),
            ...$request->only(['month', 'year'])
        ]);

        // Save allowances
        if ($request->allowances && is_array($request->allowances)) {
            foreach ($request->allowances as $allowanceId => $amount) {
                FinanceStaffAllowance::create([
                    'payroll_id' => $payroll->id,
                    'allowance_id' => $allowanceId,
                    'amount' => $amount,
                ]);
            }
        }

        // Save deductions
        if ($request->deductions && is_array($request->deductions)) {
            foreach ($request->deductions as $deductionId => $amount) {
                FinanceStaffDeduction::create([
                    'payroll_id' => $payroll->id,
                    'deduction_id' => $deductionId,
                    'amount' => $amount,
                ]);
            }
        }

        AuditLog::log([
            'module' => 'finance',
            'action' => 'payroll_created',
            'description' => "Created payroll for staff: {$payroll->staff_id}, {$payroll->month}/{$payroll->year}",
            'entity_type' => 'finance_payroll',
            'entity_id' => $payroll->id,
        ]);

        return redirect()->route('finance.payroll.show', $payroll->id)
            ->with('success', 'Payroll created successfully');
    }

    public function show(FinancePayroll $payroll)
    {
        $payroll->load(['staff', 'allowances.allowance', 'deductions.deduction', 'processedBy']);

        return view('finance.payroll.show', compact('payroll'));
    }

    public function approve(FinancePayroll $payroll)
    {
        $payroll->update(['status' => 'approved']);

        return redirect()->back()->with('success', 'Payroll approved');
    }

    public function pay(FinancePayroll $payroll)
    {
        $payroll->update(['status' => 'paid']);

        AuditLog::log([
            'module' => 'finance',
            'action' => 'payroll_paid',
            'description' => "Marked payroll as paid: {$payroll->id}",
            'entity_type' => 'finance_payroll',
            'entity_id' => $payroll->id,
        ]);

        return redirect()->back()->with('success', 'Payment recorded');
    }

    private function calculateTax($grossSalary): float
    {
        // Simple tax calculation (Nigeria progressive tax)
        if ($grossSalary <= 30000) {
            return $grossSalary * 0.07;
        } elseif ($grossSalary <= 80000) {
            return $grossSalary * 0.11;
        } elseif ($grossSalary <= 140000) {
            return $grossSalary * 0.15;
        } elseif ($grossSalary <= 320000) {
            return $grossSalary * 0.19;
        } else {
            return $grossSalary * 0.22;
        }
    }
}