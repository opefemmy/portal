<?php

namespace App\Services\Hospital;

use App\Models\Hospital\HospitalDrug;
use App\Models\Hospital\HospitalInventoryMovement;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Centralised inventory operations for hospital drugs.
 *
 * Every stock change — receive (stock-in), dispense (sale to patient),
 * adjust (corrections) or expire (write-off) — funnels through this
 * service so the `current_stock` column and the
 * `hospital_inventory_movements` audit trail stay consistent.
 *
 * When a movement causes `current_stock <= reorder_level`, the service
 * notifies all pharmacists and store keepers via `Notification::notify()`.
 * The notification is de-duplicated per drug for a 24-hour window so a
 * flood of small dispenses does not spam the inbox.
 */
class InventoryService
{
    /** Movement type strings — match existing enum on the column. */
    public const TYPE_PURCHASE = 'purchase';
    public const TYPE_SALE = 'sale';
    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_EXPIRED = 'expired';
    public const TYPE_RETURNED = 'returned';
    public const TYPE_TRANSFER = 'transfer';

    /** Roles that receive low-stock alerts. */
    public const LOW_STOCK_ROLES = ['pharmacist', 'store_keeper'];

    /**
     * Stock-in: receive new stock from a supplier / opening balance.
     */
    public function receive(
        HospitalDrug $drug,
        int $quantity,
        string $reference,
        ?float $unitCost = null,
        ?int $batchId = null
    ): HospitalInventoryMovement {
        $this->assertPositive($quantity);

        return DB::transaction(function () use ($drug, $quantity, $reference, $unitCost, $batchId) {
            $locked = HospitalDrug::where('id', $drug->id)->lockForUpdate()->first();
            $before = (int) $locked->current_stock;
            $after = $before + $quantity;

            $locked->current_stock = $after;
            $locked->save();

            $movement = HospitalInventoryMovement::create([
                'drug_id'         => $locked->id,
                'batch_id'        => $batchId,
                'user_id'         => auth()->id(),
                'movement_type'   => self::TYPE_PURCHASE,
                'quantity'        => $quantity,
                'quantity_before' => $before,
                'quantity_after'  => $after,
                'unit_cost'       => $unitCost ?? $locked->cost_price,
                'reference'       => $reference,
                'created_at'      => now(),
            ]);

            // No low-stock alert on receive — only on stock-out.

            return $movement;
        });
    }

    /**
     * Stock-out: dispense to a patient.
     *
     * Refuses if `quantity` exceeds current stock.
     */
    public function dispense(
        HospitalDrug $drug,
        int $quantity,
        string $reference,
        ?int $prescriptionItemId = null
    ): HospitalInventoryMovement {
        $this->assertPositive($quantity);

        return DB::transaction(function () use ($drug, $quantity, $reference, $prescriptionItemId) {
            $locked = HospitalDrug::where('id', $drug->id)->lockForUpdate()->first();
            $before = (int) $locked->current_stock;

            if ($quantity > $before) {
                throw new \RuntimeException(sprintf(
                    'Insufficient stock for %s — requested %d, available %d.',
                    $locked->name,
                    $quantity,
                    $before
                ));
            }

            $after = $before - $quantity;
            $locked->current_stock = $after;
            $locked->save();

            $movement = HospitalInventoryMovement::create([
                'drug_id'         => $locked->id,
                'batch_id'        => null,
                'user_id'         => auth()->id(),
                'movement_type'   => self::TYPE_SALE,
                'quantity'        => $quantity,
                'quantity_before' => $before,
                'quantity_after'  => $after,
                'unit_cost'       => $locked->cost_price,
                'reference'       => $reference,
                'created_at'      => now(),
            ]);

            if ($locked->isLowStock()) {
                $this->notifyLowStock($locked->fresh());
            }

            return $movement;
        });
    }

    /**
     * Adjust stock by a signed delta (e.g. damage, recount correction).
     * Positive delta adds stock, negative removes.
     */
    public function adjust(
        HospitalDrug $drug,
        int $deltaQuantity,
        string $reason,
        string $reference
    ): HospitalInventoryMovement {
        return DB::transaction(function () use ($drug, $deltaQuantity, $reason, $reference) {
            $locked = HospitalDrug::where('id', $drug->id)->lockForUpdate()->first();
            $before = (int) $locked->current_stock;
            $after = $before + $deltaQuantity;

            if ($after < 0) {
                throw new \RuntimeException(sprintf(
                    'Adjustment would drive stock below zero (%s, current %d, delta %+d).',
                    $locked->name,
                    $before,
                    $deltaQuantity
                ));
            }

            $locked->current_stock = $after;
            $locked->save();

            $movement = HospitalInventoryMovement::create([
                'drug_id'         => $locked->id,
                'batch_id'        => null,
                'user_id'         => auth()->id(),
                'movement_type'   => self::TYPE_ADJUSTMENT,
                'quantity'        => abs($deltaQuantity),
                'quantity_before' => $before,
                'quantity_after'  => $after,
                'unit_cost'       => $locked->cost_price,
                'reference'       => $reference,
                'notes'           => $reason,
                'created_at'      => now(),
            ]);

            if ($deltaQuantity < 0 && $locked->isLowStock()) {
                $this->notifyLowStock($locked->fresh());
            }

            return $movement;
        });
    }

    /**
     * Write off expired stock (movement_type = 'expired').
     */
    public function expire(
        HospitalDrug $drug,
        int $quantity,
        string $reference
    ): HospitalInventoryMovement {
        $this->assertPositive($quantity);

        return DB::transaction(function () use ($drug, $quantity, $reference) {
            $locked = HospitalDrug::where('id', $drug->id)->lockForUpdate()->first();
            $before = (int) $locked->current_stock;

            if ($quantity > $before) {
                throw new \RuntimeException(sprintf(
                    'Expiry write-off exceeds stock for %s — requested %d, available %d.',
                    $locked->name,
                    $quantity,
                    $before
                ));
            }

            $after = $before - $quantity;
            $locked->current_stock = $after;
            $locked->save();

            $movement = HospitalInventoryMovement::create([
                'drug_id'         => $locked->id,
                'batch_id'        => null,
                'user_id'         => auth()->id(),
                'movement_type'   => self::TYPE_EXPIRED,
                'quantity'        => $quantity,
                'quantity_before' => $before,
                'quantity_after'  => $after,
                'unit_cost'       => $locked->cost_price,
                'reference'       => $reference,
                'created_at'      => now(),
            ]);

            if ($locked->isLowStock()) {
                $this->notifyLowStock($locked->fresh());
            }

            return $movement;
        });
    }

    /**
     * Notify pharmacists + store keepers that a drug is at or below
     * its reorder level. De-duplicated within a 24-hour window per drug.
     *
     * Public so it can be called from controllers / cron sweeps.
     */
    public function notifyLowStock(HospitalDrug $drug): int
    {
        if (!$drug->isLowStock()) {
            return 0;
        }

        $users = User::query()
            ->where('is_active', true)
            ->whereHas('role', function ($q) {
                $q->whereIn('slug', self::LOW_STOCK_ROLES);
            })
            ->get();

        if ($users->isEmpty()) {
            return 0;
        }

        $threshold = now()->subHours(24);

        // Avoid duplicate notifications within the last 24h for this drug.
        $alreadyNotified = Notification::whereIn('user_id', $users->pluck('id'))
            ->where('title', 'Low stock alert')
            ->where('link', route('hospital.pharmacy.low-stock'))
            ->where('created_at', '>=', $threshold)
            ->where('message', 'like', '%' . $drug->name . '%')
            ->pluck('user_id')
            ->all();

        $created = 0;
        $link = route('hospital.pharmacy.low-stock');

        foreach ($users as $user) {
            if (in_array($user->id, $alreadyNotified, true)) {
                continue;
            }

            $severity = $drug->isOutOfStock() ? 'danger' : 'warning';

            Notification::notify(
                $user,
                'Low stock alert',
                sprintf(
                    '%s — %d left (reorder level: %d).',
                    $drug->name,
                    (int) $drug->current_stock,
                    (int) $drug->reorder_level
                ),
                $severity,
                $link
            );
            $created++;
        }

        return $created;
    }

    /**
     * Assert a quantity is a positive integer.
     */
    protected function assertPositive(int $quantity): void
    {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('Quantity must be at least 1.');
        }
    }
}