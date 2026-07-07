<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Hospital\HospitalAppointment;
use App\Models\Hospital\HospitalLabRequest;
use App\Models\Hospital\HospitalPrescription;
use App\Models\Finance\FinanceInvoice;
use App\Models\Finance\FinanceReceipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Get unread notifications count
     */
    public function unreadCount()
    {
        $count = Notification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Get all notifications for current user
     */
    public function index(Request $request)
    {
        $notifications = Notification::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Notification $notification)
    {
        if ($notification->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $notification->markAsRead();

        if ($notification->link) {
            return response()->json(['redirect' => $notification->link]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        Notification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now()
            ]);

        return response()->json(['success' => true]);
    }

    /**
     * Delete a notification
     */
    public function destroy(Notification $notification)
    {
        if ($notification->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $notification->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Send appointment notification to patient
     */
    public static function notifyAppointmentUpdate($appointmentId, $status)
    {
        $appointment = HospitalAppointment::with('patient')->find($appointmentId);

        if ($appointment && $appointment->patient) {
            Notification::createNotification(
                $appointment->patient->user_id,
                'Appointment Update',
                "Your appointment on {$appointment->appointment_date->format('d M Y')} has been {$status}",
                'info',
                route('student.medical.appointments')
            );
        }
    }

    /**
     * Notify patient when lab results are ready
     */
    public static function notifyLabResultsReady($labRequestId)
    {
        $labRequest = HospitalLabRequest::with('patient')->find($labRequestId);

        if ($labRequest && $labRequest->patient && $labRequest->status === 'completed') {
            Notification::createNotification(
                $labRequest->patient->user_id,
                'Lab Results Ready',
                "Your lab test results are now available",
                'success',
                route('student.medical.lab-results')
            );
        }
    }

    /**
     * Notify patient when prescription is ready
     */
    public static function notifyPrescriptionReady($prescriptionId)
    {
        $prescription = HospitalPrescription::with('patient')->find($prescriptionId);

        if ($prescription && $prescription->patient && $prescription->status === 'dispensed') {
            Notification::createNotification(
                $prescription->patient->user_id,
                'Prescription Ready',
                "Your prescription has been dispensed and is ready for pickup",
                'info',
                route('student.medical.prescriptions')
            );
        }
    }

    /**
     * Notify student of new invoice
     */
    public static function notifyNewInvoice($invoiceId)
    {
        $invoice = FinanceInvoice::with('student')->find($invoiceId);

        if ($invoice && $invoice->student) {
            Notification::createNotification(
                $invoice->student_id,
                'New Invoice',
                "You have a new invoice: {$invoice->description} - ₦" . number_format($invoice->amount, 2),
                'warning',
                route('student.payments')
            );
        }
    }

    /**
     * Notify student of payment confirmation
     */
    public static function notifyPaymentConfirmed($receiptId)
    {
        $receipt = FinanceReceipt::with('student')->find($receiptId);

        if ($receipt && $receipt->student) {
            Notification::createNotification(
                $receipt->student_id,
                'Payment Successful',
                "Your payment of ₦" . number_format($receipt->amount, 2) . " has been confirmed. Receipt: {$receipt->receipt_number}",
                'success',
                route('student.payments')
            );
        }
    }
}