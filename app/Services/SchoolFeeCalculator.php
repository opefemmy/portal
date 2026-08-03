<?php

namespace App\Services;

use App\Models\Fee;
use App\Models\Payment;
use App\Models\Student;

/**
 * Single source of truth for school-fee percent payment logic.
 *
 * A student may pay a fee in one of two ways:
 *   - 100% up front  → fully cleared for both semesters + exam clearance.
 *   - 60% first installment → first semester only; the remaining 40% is
 *     payable in a separate transaction once the first is complete.
 *
 * `canRegisterSemester()` reads this state and the course-registration
 * controller calls it to gate which semester(s) the student may register
 * for.
 */
class SchoolFeeCalculator
{
    public const PERCENT_FULL = 100;
    public const PERCENT_FIRST = 60;
    public const PERCENT_SECOND = 40;

    /**
     * The percent options a brand-new student may pick. Once they have
     * paid the first installment, the only remaining option is the
     * second-installment slice — see availablePercents().
     */
    public static function percentOptions(): array
    {
        return [self::PERCENT_FULL, self::PERCENT_FIRST];
    }

    /**
     * Percent options still available to this student on this fee.
     *   - already fully paid → empty (nothing left to pay)
     *   - already paid the first installment → only the 40% remainder
     *   - nothing paid yet → full or first installment
     */
    public static function availablePercents(Student $student, Fee $fee): array
    {
        $paid = self::totalPercentPaid($student, $fee);
        if ($paid >= self::PERCENT_FULL) {
            return [];
        }
        if ($paid >= self::PERCENT_FIRST) {
            return [self::PERCENT_SECOND];
        }
        return self::percentOptions();
    }

    /**
     * Sum of percent_paid across all completed payments for the student
     * on this fee. The installment_label column is a redundant constraint
     * we keep for reporting — the math just reads percent_paid.
     */
    public static function totalPercentPaid(Student $student, Fee $fee): int
    {
        return (int) Payment::where('student_id', $student->id)
            ->where('fee_id', $fee->id)
            ->where('status', 'completed')
            ->sum('percent_paid');
    }

    /**
     * Naira amount the student owes when choosing a percent.
     *
     *   full   → 100% of priceFor(category) + portal_charge
     *            (portal charge is only added on a 100% payment)
     *   first  →  60% of priceFor(category)
     *   second →  40% of priceFor(category)
     */
    public static function payable(Student $student, Fee $fee, int $percent): float
    {
        $category = self::categoryFor($student);
        $price = $fee->priceFor($category);

        if ($percent === self::PERCENT_FULL) {
            return $price + (float) $fee->portal_charge;
        }

        return round($price * ($percent / 100), 2);
    }

    /**
     * May this student register courses for the given semester?
     *   - any percent ≥ 100% → both semesters allowed.
     *   - percent ≥ 60% AND semester == 'first' → allowed.
     *   - everything else → blocked.
     */
    public static function canRegisterSemester(Student $student, string $semester): bool
    {
        $paid = self::maxPercentPaidAcrossRequiredFees($student);
        if ($paid >= self::PERCENT_FULL) {
            return true;
        }
        if ($paid >= self::PERCENT_FIRST && $semester === 'first') {
            return true;
        }
        return false;
    }

    /**
     * Highest percent_paid sum seen across any of the student's required
     * fees. We treat the max, not the average, so paying 100% of one fee
     * fully clears that fee even if another has not been touched yet.
     */
    public static function maxPercentPaidAcrossRequiredFees(Student $student): int
    {
        return (int) Payment::where('student_id', $student->id)
            ->where('status', 'completed')
            ->selectRaw('fee_id, SUM(percent_paid) as total_percent')
            ->groupBy('fee_id')
            ->get()
            ->max('total_percent') ?? 0;
    }

    public static function installmentLabel(int $percent): string
    {
        return match ($percent) {
            self::PERCENT_FULL   => 'full',
            self::PERCENT_FIRST  => 'first',
            self::PERCENT_SECOND => 'second',
            default              => 'full',
        };
    }

    private static function categoryFor(Student $student): string
    {
        // Delegate to IndigeneResolver so the Ekiti keyword test lives in
        // one place. User model already wires isIndigene() through it.
        return $student->user?->isIndigene() ? 'indigene' : 'non_indigene';
    }
}