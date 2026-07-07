<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\FinanceInvoice;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = FinanceInvoice::with(['student', 'session']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->payment_type) {
            $query->where('payment_type', $request->payment_type);
        }

        if ($request->student_id) {
            $query->where('student_id', $request->student_id);
        }

        $invoices = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('finance.invoices.index', compact('invoices'));
    }

    public function create()
    {
        return view('finance.invoices.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:users,id',
            'payment_type' => 'required|string',
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $invoice = FinanceInvoice::create([
            'invoice_number' => FinanceInvoice::generateInvoiceNumber(),
            'generated_by' => auth()->id(),
            ...$request->all()
        ]);

        AuditLog::log([
            'module' => 'finance',
            'action' => 'invoice_created',
            'description' => "Created invoice: {$invoice->invoice_number}",
            'entity_type' => 'finance_invoices',
            'entity_id' => $invoice->id,
        ]);

        return redirect()->route('finance.invoices.show', $invoice->id)
            ->with('success', 'Invoice created successfully');
    }

    public function show(FinanceInvoice $invoice)
    {
        $invoice->load(['student', 'generatedBy', 'receipts']);

        return view('finance.invoices.show', compact('invoice'));
    }

    public function edit(FinanceInvoice $invoice)
    {
        return view('finance.invoices.edit', compact('invoice'));
    }

    public function update(Request $request, FinanceInvoice $invoice)
    {
        $validator = Validator::make($request->all(), [
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'nullable|date',
            'status' => 'required|in:pending,partial,paid,overdue,cancelled',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $invoice->update($request->all());

        AuditLog::log([
            'module' => 'finance',
            'action' => 'invoice_updated',
            'description' => "Updated invoice: {$invoice->invoice_number}",
            'entity_type' => 'finance_invoices',
            'entity_id' => $invoice->id,
        ]);

        return redirect()->route('finance.invoices.show', $invoice->id)
            ->with('success', 'Invoice updated successfully');
    }

    public function destroy(FinanceInvoice $invoice)
    {
        if ($invoice->status === 'paid') {
            return redirect()->back()->with('error', 'Cannot delete paid invoice');
        }

        $invoiceNumber = $invoice->invoice_number;

        AuditLog::log([
            'module' => 'finance',
            'action' => 'invoice_deleted',
            'description' => "Deleted invoice: {$invoiceNumber}",
            'entity_type' => 'finance_invoices',
            'entity_id' => $invoice->id,
            'old_values' => $invoice->toArray(),
        ]);

        $invoice->delete();

        return redirect()->route('finance.invoices.index')
            ->with('success', 'Invoice deleted successfully');
    }
}