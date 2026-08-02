<?php

namespace App\Services\Hospital;

use App\Models\Hospital\HospitalPayment;
use App\Models\Hospital\HospitalServiceType;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Routes hospital payment notifications to the staff responsible for
 * delivering the paid service.
 *
 * The `HospitalServiceType.category` value (e.g. "Pharmacy", "Laboratory")
 * is mapped to one or more staff role slugs. Every active user with a
 * matching role gets a `Notification` row so the receiving office can
 * pick up the new request immediately.
 */
class HospitalPaymentNotificationService
{
    /**
     * Map of service category → role slugs that should be notified.
     *
     * Categories match `hospital_service_types.category`. Use lowercase
     * keys when matching; values are matched against `roles.slug`.
     */
    public const CATEGORY_TO_STAFF_TYPES = [
        'pharmacy'     => ['pharmacist', 'store_keeper'],
        'laboratory'   => ['lab_scientist'],
        'lab'          => ['lab_scientist'],
        'consultation' => ['doctor'],
        'admission'    => ['doctor', 'nurse'],
        'appointment'  => ['doctor', 'nurse', 'hospital_receptionist'],
        'procedure'    => ['doctor', 'nurse'],
        'other'        => ['cmd'],
    ];

    /**
     * Map of service category → office dashboard route name.
     * Used as the `link` on the notification.
     */
    public const CATEGORY_TO_DASHBOARD = [
        'pharmacy'     => 'hospital.pharmacy.dashboard',
        'laboratory'   => 'hospital.lab.dashboard',
        'lab'          => 'hospital.lab.dashboard',
        'consultation' => 'hospital.doctor.dashboard',
        'admission'    => 'hospital.doctor.dashboard',
        'appointment'  => 'hospital.appointments.index',
        'procedure'    => 'hospital.doctor.dashboard',
        'other'        => 'hospital.dashboard',
    ];

    /**
     * Notify the receiving office that this payment has just completed.
     *
     * Returns the number of notifications created.
     */
    public static function notifyPaymentCompleted(HospitalPayment $payment): int
    {
        if ($payment->status !== HospitalPayment::STATUS_COMPLETED) {
            return 0;
        }

        $serviceType = $payment->service_type_id
            ? HospitalServiceType::find($payment->service_type_id)
            : null;

        $category = $serviceType && $serviceType->category
            ? strtolower(trim($serviceType->category))
            : 'other';

        $staffTypes = self::CATEGORY_TO_STAFF_TYPES[$category] ?? ['cmd'];
        $dashboardRoute = self::CATEGORY_TO_DASHBOARD[$category] ?? 'hospital.dashboard';

        $users = User::query()
            ->where('is_active', true)
            ->whereHas('role', function ($q) use ($staffTypes) {
                $q->whereIn('slug', $staffTypes);
            })
            ->get();

        if ($users->isEmpty()) {
            Log::info('HospitalPaymentNotification: no matching staff for category.', [
                'payment_id' => $payment->id,
                'category'   => $category,
                'staff_types'=> $staffTypes,
            ]);
            return 0;
        }

        try {
            $link = route($dashboardRoute);
        } catch (\Exception $e) {
            $link = route('hospital.dashboard');
        }

        $title = 'New payment received';
        $body = sprintf(
            '%s paid ₦%s for %s (%s). Ref: %s.',
            $payment->patient_name,
            number_format((float) $payment->total_amount, 2),
            $payment->service_name ?? ($serviceType->name ?? 'service'),
            $payment->payment_method,
            $payment->payment_ref
        );

        $created = 0;
        foreach ($users as $user) {
            Notification::notify($user, $title, $body, 'info', $link);
            $created++;
        }

        Log::info('HospitalPaymentNotification: dispatched.', [
            'payment_id'      => $payment->id,
            'category'        => $category,
            'staff_types'     => $staffTypes,
            'recipients'      => $created,
        ]);

        return $created;
    }
}