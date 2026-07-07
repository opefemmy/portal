<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\FinanceTransaction;
use App\Models\Finance\FinanceLedger;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = FinanceTransaction::with(['user']);

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->category) {
            $query->where('category', $request->category);
        }

        if ($request->date) {
            $query->whereDate('transaction_date', $request->date);
        }

        $transactions = $query->orderBy('transaction_date', 'desc')->paginate(30);

        return view('finance.transactions.index', compact('transactions'));
    }

    public function create()
    {
        $ledgers = FinanceLedger::where('is_active', true)
            ->where('allow_manual_entry', true)
            ->get();

        return view('finance.transactions.create', compact('ledgers'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:credit,debit',
            'category' => 'required|in:income,expense',
            'ledger_code' => 'required|exists:finance_ledgers,code',
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Get current balance for ledger
        $ledger = FinanceLedger::where('code', $request->ledger_code)->first();
        $currentBalance = $ledger ? $ledger->balance : 0;

        // Calculate new balance
        $newBalance = $request->type === 'credit'
            ? $currentBalance + $request->amount
            : $currentBalance - $request->amount;

        $transaction = FinanceTransaction::create([
            'transaction_number' => FinanceTransaction::generateTransactionNumber(),
            'user_id' => auth()->id(),
            'balance' => $newBalance,
            'status' => 'posted',
            ...$request->all()
        ]);

        // Update ledger balance
        if ($ledger) {
            $ledger->update(['balance' => $newBalance]);
        }

        AuditLog::log([
            'module' => 'finance',
            'action' => 'transaction_created',
            'description' => "Created transaction: {$transaction->transaction_number}",
            'entity_type' => 'finance_transactions',
            'entity_id' => $transaction->id,
        ]);

        return redirect()->route('finance.transactions.show', $transaction->id)
            ->with('success', 'Transaction recorded successfully');
    }

    public function show(FinanceTransaction $transaction)
    {
        $transaction->load(['user']);

        return view('finance.transactions.show', compact('transaction'));
    }
}