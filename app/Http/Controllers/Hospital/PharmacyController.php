<?php

namespace App\Http\Controllers\Hospital;

use App\Http\Controllers\Controller;
use App\Models\Hospital\HospitalDrug;
use App\Models\Hospital\HospitalDrugBatch;
use App\Models\Hospital\HospitalDrugCategory;
use App\Models\Hospital\HospitalSupplier;
use App\Models\Hospital\HospitalPrescription;
use App\Models\Hospital\HospitalPrescriptionItem;
use App\Models\Hospital\HospitalInventoryMovement;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PharmacyController extends Controller
{
    /**
     * Display drugs inventory.
     */
    public function drugs(Request $request)
    {
        $query = HospitalDrug::with('category');

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('generic_name', 'like', "%{$request->search}%")
                  ->orWhere('code', 'like', "%{$request->search}%");
            });
        }

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->low_stock) {
            $query->whereRaw('current_stock <= reorder_level');
        }

        $drugs = $query->orderBy('name')->paginate(20);
        $categories = HospitalDrugCategory::where('is_active', true)->get();

        return view('hospital.pharmacy.drugs', compact('drugs', 'categories'));
    }

    /**
     * Create new drug.
     */
    public function createDrug()
    {
        $categories = HospitalDrugCategory::where('is_active', true)->get();
        return view('hospital.pharmacy.drug-create', compact('categories'));
    }

    /**
     * Store new drug.
     */
    public function storeDrug(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'generic_name' => 'nullable|string|max:255',
            'code' => 'required|string|unique:hospital_drugs,code',
            'category_id' => 'nullable|exists:hospital_drug_categories,id',
            'form' => 'required|string',
            'strength' => 'nullable|string',
            'unit' => 'required|string',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'reorder_level' => 'required|integer|min:0',
            'requires_prescription' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $drug = HospitalDrug::create($request->all());

        AuditLog::log([
            'module' => 'hospital',
            'action' => 'drug_created',
            'description' => "Created new drug: {$drug->name}",
            'entity_type' => 'hospital_drugs',
            'entity_id' => $drug->id,
        ]);

        return redirect()->route('hospital.pharmacy.drugs')->with('success', 'Drug added successfully');
    }

    /**
     * Display pending prescriptions.
     */
    public function prescriptions()
    {
        $prescriptions = HospitalPrescription::with(['patient', 'doctor', 'items'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('hospital.pharmacy.prescriptions', compact('prescriptions'));
    }

    /**
     * Show prescription details.
     */
    public function showPrescription(HospitalPrescription $prescription)
    {
        $prescription->load(['patient', 'doctor', 'items.drug', 'dispensedBy']);

        return view('hospital.pharmacy.prescription-show', compact('prescription'));
    }

    /**
     * Dispense prescription.
     */
    public function dispense(Request $request, HospitalPrescription $prescription)
    {
        if ($prescription->status !== 'pending') {
            return redirect()->back()->with('error', 'Prescription already dispensed');
        }

        $validator = Validator::make($request->all(), [
            'items' => 'required|array',
            'items.*.dispensed' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $dispensedItems = [];
        $allDispensed = true;

        foreach ($prescription->items as $item) {
            $isDispensed = $request->items[$item->id]['dispensed'] ?? false;

            if ($isDispensed && $item->drug) {
                $drug = $item->drug;

                if ($drug->current_stock < ($item->quantity ?? 1)) {
                    return redirect()->back()->with('error', "Insufficient stock for {$drug->name}");
                }

                // Update drug stock
                $drug->decrement('current_stock', $item->quantity ?? 1);

                // Record inventory movement
                HospitalInventoryMovement::create([
                    'drug_id' => $drug->id,
                    'user_id' => auth()->id(),
                    'movement_type' => 'sale',
                    'quantity' => $item->quantity ?? 1,
                    'quantity_before' => $drug->current_stock + ($item->quantity ?? 1),
                    'quantity_after' => $drug->current_stock,
                    'unit_cost' => $drug->cost_price,
                    'reference' => "Prescription #{$prescription->id}",
                ]);

                $item->update(['is_dispensed' => true]);
            } elseif (!$isDispensed) {
                $allDispensed = false;
            }
        }

        $prescription->update([
            'status' => $allDispensed ? 'dispensed' : 'partially_dispensed',
            'dispensed_by' => auth()->user()->hospitalStaff->id ?? null,
            'dispensed_at' => now(),
            'notes' => $request->notes,
        ]);

        AuditLog::log([
            'module' => 'hospital',
            'action' => 'prescription_dispensed',
            'description' => "Dispensed prescription #{$prescription->id}",
            'entity_type' => 'hospital_prescriptions',
            'entity_id' => $prescription->id,
        ]);

        return redirect()->route('hospital.pharmacy.prescriptions')
            ->with('success', 'Prescription dispensed successfully');
    }

    /**
     * Get low stock drugs.
     */
    public function lowStock()
    {
        $drugs = HospitalDrug::whereRaw('current_stock <= reorder_level')
            ->orderBy('current_stock')
            ->get();

        return view('hospital.pharmacy.low-stock', compact('drugs'));
    }

    /**
     * Get expiring drugs.
     */
    public function expiring()
    {
        $expiringBatches = HospitalDrugBatch::where('status', 'active')
            ->whereDate('expiry_date', '<=', now()->addDays(30))
            ->whereDate('expiry_date', '>', now())
            ->with('drug')
            ->orderBy('expiry_date')
            ->get();

        return view('hospital.pharmacy.expiring', compact('expiringBatches'));
    }

    /**
     * Drug categories.
     */
    public function categories()
    {
        $categories = HospitalDrugCategory::orderBy('name')->get();
        return view('hospital.pharmacy.categories', compact('categories'));
    }

    /**
     * Store category.
     */
    public function storeCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:hospital_drug_categories,code',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        HospitalDrugCategory::create($request->all());

        return redirect()->back()->with('success', 'Category created successfully');
    }

    /**
     * Suppliers management.
     */
    public function suppliers()
    {
        $suppliers = HospitalSupplier::orderBy('name')->get();
        return view('hospital.pharmacy.suppliers', compact('suppliers'));
    }

    /**
     * Store supplier.
     */
    public function storeSupplier(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:hospital_suppliers,code',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        HospitalSupplier::create($request->all());

        return redirect()->back()->with('success', 'Supplier added successfully');
    }
}