<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\FinanceReceipt;
use App\Models\Finance\FinanceInvoice;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReceiptController extends Controller
{
    public function index(Request $request)
    {
        $query = FinanceReceipt::with(['student', 'invoice']);

        if ($request->status) {
            $query->where('is_verified', $request->status === 'verified');
        }

        if ($request->date) {
            $query->whereDate('payment_date', $request->date);
        }

        $receipts = $query->orderBy('payment_date', 'desc')->paginate(20);

        return view('finance.receipts.index', compact('receipts'));
    }

    public function create()
    {
        return view('finance.receipts.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0',
            'amount_received' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,bank_transfer,cheque,pos,online',
            'payment_date' => 'required|date',
            'reference_number' => 'nullable|string',
            'bank_name' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $changeGiven = $request->amount_received - $request->amount;

        $receipt = FinanceReceipt::create([
            'receipt_number' => FinanceReceipt::generateReceiptNumber(),
            'generated_by' => auth()->id(),
            'change_given' => max(0, $changeGiven),
            ...$request->all()
        ]);

        // Update invoice if linked
        if ($request->invoice_id) {
            $invoice = FinanceInvoice::find($request->invoice_id);
            if ($invoice) {
                $invoice->increment('amount_paid', $request->amount);
                $invoice->refresh();

                if ($invoice->amount_paid >= $invoice->amount) {
                    $invoice->update(['status' => 'paid', 'balance' => 0]);
                } else {
                    $invoice->update(['status' => 'partial', 'balance' => $invoice->amount - $invoice->amount_paid]);
                }
            }
        }

        AuditLog::log([
            'module' => 'finance',
            'action' => 'receipt_created',
            'description' => "Created receipt: {$receipt->receipt_number}",
            'entity_type' => 'finance_receipts',
            'entity_id' => $receipt->id,
        ]);

        return redirect()->route('finance.receipts.show', $receipt->id)
            ->with('success', 'Receipt generated successfully');
    }

    public function show(FinanceReceipt $receipt)
    {
        $receipt->load(['student', 'invoice', 'generatedBy', 'verifiedBy']);

        return view('finance.receipts.show', compact('receipt'));
    }

    public function verify(FinanceReceipt $receipt)
    {
        $receipt->update([
            'is_verified' => true,
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        AuditLog::log([
            'module' => 'finance',
            'action' => 'receipt_verified',
            'description' => "Verified receipt: {$receipt->receipt_number}",
            'entity_type' => 'finance_receipts',
            'entity_id' => $receipt->id,
        ]);

        return redirect()->back()->with('success', 'Receipt verified');
    }
}