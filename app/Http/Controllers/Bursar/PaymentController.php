<?php

namespace App\Http\Controllers\Bursar;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Fee;
use App\Models\Applicant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with('student.user', 'fee');

        // Filter by status
        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by fee type
        if ($request->fee_id) {
            $query->where('fee_id', $request->fee_id);
        }

        // Filter by date range
        if ($request->start_date) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Filter by student
        if ($request->matric_number) {
            $student = Student::where('matric_number', 'like', '%' . $request->matric_number . '%')->first();
            if ($student) {
                $query->where('student_id', $student->id);
            }
        }

        $payments = $query->latest()->get();
        $fees = Fee::where('is_active', true)->orderBy('name')->get();

        return view('bursar.payments', compact('payments', 'fees'));
    }

    public function verify(Payment $payment)
    {
        $payment->update(['status' => 'completed']);
        return back()->with('success', 'Payment verified');
    }

    public function receipt(Payment $payment)
    {
        return view('bursar.receipt', compact('payment'));
    }

    /**
     * Show the external payment upload form
     */
    public function showUploadForm()
    {
        $fees = Fee::where('is_active', true)->orderBy('name')->get();
        return view('bursar.payments-upload', compact('fees'));
    }

    /**
     * Upload external payments from CSV/Excel
     */
    public function uploadPayments(Request $request)
    {
        $request->validate([
            'payment_file' => 'required|file|mimes:csv,xlsx,xls,txt',
            'fee_id' => 'required|exists:fees,id',
        ]);

        try {
            $file = $request->file('payment_file');
            $extension = $file->getClientOriginalExtension();

            $results = [
                'created' => 0,
                'updated' => 0,
                'errors' => [],
            ];

            if ($extension === 'csv' || $extension === 'txt') {
                $handle = fopen($file->getRealPath(), 'r');
                $header = fgetcsv($handle);

                // Normalize header
                $header = array_map('strtolower', array_map('trim', $header));

                while (($row = fgetcsv($handle)) !== false) {
                    $data = array_combine($header, $row);

                    try {
                        $result = $this->processExternalPayment($data, $request->fee_id);
                        if ($result['created']) {
                            $results['created']++;
                        } else {
                            $results['updated']++;
                        }
                    } catch (\Exception $e) {
                        $results['errors'][] = "Row error: " . $e->getMessage();
                    }
                }
                fclose($handle);
            } else {
                // For Excel files, we'll process as CSV
                $results['errors'][] = "Excel file upload is not yet supported. Please convert to CSV format.";
            }

            $message = "Upload complete! Created: {$results['created']}, Updated: {$results['updated']}";
            if (!empty($results['errors'])) {
                $message .= " Errors: " . count($results['errors']);
            }

            return back()->with('success', $message)->with('results', $results);

        } catch (\Exception $e) {
            Log::error('Payment upload error: ' . $e->getMessage());
            return back()->with('error', 'Error uploading payments: ' . $e->getMessage());
        }
    }

    /**
     * Process a single external payment record
     */
    private function processExternalPayment(array $data, int $feeId): array
    {
        // Find student by various identifiers
        $student = null;
        $applicant = null;

        // Try by matric_number
        if (!empty($data['matric_number'])) {
            $student = Student::where('matric_number', 'like', '%' . $data['matric_number'] . '%')->first();
        }

        // Try by application_number
        if (!$student && !empty($data['application_number'])) {
            $applicant = Applicant::where('application_number', $data['application_number'])->first();
            if ($applicant) {
                // Get the student if they've been created
                $student = Student::where('email', $applicant->email)->first();
            }
        }

        // Try by email
        if (!$student && !empty($data['email'])) {
            $student = Student::whereHas('user', function ($q) use ($data) {
                $q->where('email', $data['email']);
            })->first();
        }

        // Try by phone
        if (!$student && !empty($data['phone'])) {
            $applicant = Applicant::where('phone', $data['phone'])->first();
            if ($applicant) {
                $student = Student::where('email', $applicant->email)->first();
            }
        }

        if (!$student && !$applicant) {
            throw new \Exception("Student/Applicant not found");
        }

        // Get payment amount
        $amount = floatval($data['amount'] ?? 0);
        if ($amount <= 0) {
            throw new \Exception("Invalid amount");
        }

        // Get payment date
        $paymentDate = !empty($data['payment_date'])
            ? \Carbon\Carbon::parse($data['payment_date'])->format('Y-m-d')
            : now()->format('Y-m-d');

        // Get payment reference
        $paymentRef = $data['payment_ref'] ?? $data['reference'] ?? 'EXT-' . strtoupper(\Illuminate\Support\Str::random(10));

        // Check if payment already exists
        $existingPayment = null;
        if ($student) {
            $existingPayment = Payment::where('student_id', $student->id)
                ->where('fee_id', $feeId)
                ->where('payment_ref', $paymentRef)
                ->first();
        }

        if ($existingPayment) {
            // Update existing payment
            $existingPayment->update([
                'amount' => $amount,
                'status' => 'completed',
                'payment_date' => $paymentDate,
            ]);
            return ['created' => false, 'updated' => true];
        }

        // Create new payment
        if ($student) {
            Payment::create([
                'student_id' => $student->id,
                'fee_id' => $feeId,
                'amount' => $amount,
                'status' => 'completed',
                'payment_ref' => $paymentRef,
                'payment_date' => $paymentDate,
                'payment_method' => 'external',
            ]);
        } elseif ($applicant) {
            // Update applicant payment status
            $applicant->update([
                'payment_status' => 'completed',
                'payment_ref' => $paymentRef,
                'payment_amount' => $amount,
                'payment_date' => $paymentDate,
                'payment_transaction_id' => 'EXT-' . strtoupper(\Illuminate\Support\Str::random(12)),
            ]);
        }

        return ['created' => true, 'updated' => false];
    }
}