<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\FinanceBudget;
use App\Models\Finance\FinanceBudgetAllocation;
use App\Models\Finance\FinanceLedger;
use App\Models\Department;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        $query = FinanceBudget::with('department');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->fiscal_year) {
            $query->where('fiscal_year', $request->fiscal_year);
        }

        $budgets = $query->orderBy('fiscal_year', 'desc')->paginate(20);

        return view('finance.budgets.index', compact('budgets'));
    }

    public function create()
    {
        $departments = Department::all();
        $ledgers = FinanceLedger::where('is_active', true)->get();

        return view('finance.budgets.create', compact('departments', 'ledgers'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'fiscal_year' => 'required|string',
            'department_id' => 'nullable|exists:departments,id',
            'total_budget' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $budget = FinanceBudget::create([
            'balance' => $request->total_budget,
            ...$request->all()
        ]);

        // Create allocations if provided
        if ($request->allocations && is_array($request->allocations)) {
            foreach ($request->allocations as $allocation) {
                FinanceBudgetAllocation::create([
                    'budget_id' => $budget->id,
                    'ledger_id' => $allocation['ledger_id'],
                    'allocated_amount' => $allocation['amount'],
                    'balance' => $allocation['amount'],
                ]);
            }
        }

        AuditLog::log([
            'module' => 'finance',
            'action' => 'budget_created',
            'description' => "Created budget: {$budget->name}",
            'entity_type' => 'finance_budgets',
            'entity_id' => $budget->id,
        ]);

        return redirect()->route('finance.budgets.show', $budget->id)
            ->with('success', 'Budget created successfully');
    }

    public function show(FinanceBudget $budget)
    {
        $budget->load(['department', 'allocations.ledger']);

        return view('finance.budgets.show', compact('budget'));
    }

    public function approve(FinanceBudget $budget)
    {
        $budget->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Budget approved');
    }

    public function activate(FinanceBudget $budget)
    {
        if ($budget->status !== 'approved') {
            return redirect()->back()->with('error', 'Budget must be approved first');
        }

        $budget->update(['status' => 'active']);

        return redirect()->back()->with('success', 'Budget activated');
    }
}