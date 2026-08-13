<?php

namespace App\Http\Controllers\Hospital;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\EnforcesPermission;
use App\Models\Hospital\HospitalDrug;
use App\Services\Hospital\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Inventory operations: receive stock, adjust stock, write off expired stock.
 *
 * Each action is gated by `EnforcesPermission` (pharmacy.receive /
 * pharmacy.adjust / pharmacy.expire) and writes the movement through
 * `InventoryService` so the audit trail stays consistent.
 */
class InventoryController extends Controller
{
    use EnforcesPermission;

    public function __construct(protected InventoryService $inventory)
    {
    }

    /**
     * Show the receive-stock form.
     */
    public function showReceive()
    {
        $this->requirePermission('pharmacy.receive');
        $drugs = HospitalDrug::where('is_active', true)->orderBy('name')->get();
        return view('hospital.pharmacy.receive', compact('drugs'));
    }

    /**
     * Stock-in: record received quantities.
     */
    public function receive(Request $request)
    {
        $this->requirePermission('pharmacy.receive');

        $data = $request->validate([
            'drug_id'    => 'required|exists:hospital_drugs,id',
            'quantity'   => 'required|integer|min:1',
            'unit_cost'  => 'nullable|numeric|min:0',
            'reference'  => 'required|string|max:255',
        ]);

        $drug = HospitalDrug::findOrFail($data['drug_id']);

        try {
            $this->inventory->receive(
                $drug,
                (int) $data['quantity'],
                $data['reference'],
                isset($data['unit_cost']) ? (float) $data['unit_cost'] : null
            );
        } catch (\Throwable $e) {
            Log::error('Inventory receive failed', ['error' => $e->getMessage()]);
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('hospital.pharmacy.drugs')
            ->with('success', sprintf(
                'Received %d unit(s) of %s. Stock now %d.',
                $data['quantity'],
                $drug->name,
                (int) $drug->fresh()->current_stock
            ));
    }

    /**
     * Show the adjust-stock form.
     */
    public function showAdjust()
    {
        $this->requirePermission('pharmacy.adjust');
        $drugs = HospitalDrug::where('is_active', true)->orderBy('name')->get();
        return view('hospital.pharmacy.adjust', compact('drugs'));
    }

    /**
     * Adjust stock by a signed delta (corrections / damage).
     */
    public function adjust(Request $request)
    {
        $this->requirePermission('pharmacy.adjust');

        $data = $request->validate([
            'drug_id'   => 'required|exists:hospital_drugs,id',
            'delta'     => 'required|integer|not_in:0',
            'reason'    => 'required|string|max:255',
            'reference' => 'required|string|max:255',
        ]);

        $drug = HospitalDrug::findOrFail($data['drug_id']);

        try {
            $this->inventory->adjust(
                $drug,
                (int) $data['delta'],
                $data['reason'],
                $data['reference']
            );
        } catch (\Throwable $e) {
            Log::error('Inventory adjust failed', ['error' => $e->getMessage()]);
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('hospital.pharmacy.drugs')
            ->with('success', sprintf(
                'Adjusted %s by %+d. New stock: %d.',
                $drug->name,
                $data['delta'],
                (int) $drug->fresh()->current_stock
            ));
    }

    /**
     * Show the expire-stock form.
     */
    public function showExpire()
    {
        $this->requirePermission('pharmacy.expire');
        $drugs = HospitalDrug::where('is_active', true)->orderBy('name')->get();
        return view('hospital.pharmacy.expire', compact('drugs'));
    }

    /**
     * Write off expired stock.
     */
    public function expire(Request $request)
    {
        $this->requirePermission('pharmacy.expire');

        $data = $request->validate([
            'drug_id'   => 'required|exists:hospital_drugs,id',
            'quantity'  => 'required|integer|min:1',
            'reference' => 'required|string|max:255',
        ]);

        $drug = HospitalDrug::findOrFail($data['drug_id']);

        try {
            $this->inventory->expire(
                $drug,
                (int) $data['quantity'],
                $data['reference']
            );
        } catch (\Throwable $e) {
            Log::error('Inventory expire failed', ['error' => $e->getMessage()]);
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('hospital.pharmacy.drugs')
            ->with('success', sprintf(
                'Wrote off %d unit(s) of %s as expired. New stock: %d.',
                $data['quantity'],
                $drug->name,
                (int) $drug->fresh()->current_stock
            ));
    }
}