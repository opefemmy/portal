<?php

namespace App\Models\Finance;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancePurchaseOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'po_number', 'vendor_id', 'department_id', 'requested_by', 'approved_by',
        'order_date', 'expected_delivery', 'subtotal', 'tax', 'total', 'status', 'notes'
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery' => 'date',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(FinanceVendor::class, 'vendor_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(FinancePurchaseOrderItem::class, 'po_id');
    }

    public static function generatePONumber(): string
    {
        $year = date('Y');
        $lastPO = self::whereYear('created_at', $year)->orderBy('id', 'desc')->first();
        $number = $lastPO ? (int)substr($lastPO->po_number, -5) + 1 : 1;
        return 'PO-' . $year . '-' . str_pad($number, 5, '0', STR_PAD_LEFT);
    }
}