<?php

namespace App\Http\Controllers\Bursar;

use App\Http\Controllers\Controller;
use App\Models\ExternalPayment;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Validation\Rule;

class PaymentSyncController extends Controller
{
    /**
     * Show payment synchronization page
     */
    public function index()
    {
        $recentImports = ExternalPayment::orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        $stats = [
            'total' => ExternalPayment::count(),
            'used' => ExternalPayment::where('is_used', true)->count(),
            'unused' => ExternalPayment::where('is_used', false)->count(),
            'completed' => ExternalPayment::where('payment_status', 'completed')->count(),
            'pending' => ExternalPayment::where('payment_status', 'pending')->count(),
            'failed' => ExternalPayment::where('payment_status', 'failed')->count(),
        ];

        return view('bursar.payment-sync', compact('recentImports', 'stats'));
    }

    /**
     * Show upload form
     */
    public function showUploadForm()
    {
        return view('bursar.payment-sync-upload');
    }

    /**
     * Preview uploaded file
     */
    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx|max:5120', // 5MB max
        ]);

        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();

        // Read file data
        if ($extension === 'csv') {
            $data = Excel::toArray([], $file)[0] ?? [];
        } else {
            $data = Excel::toArray([], $file)[0] ?? [];
        }

        if (empty($data)) {
            return back()->with('error', 'The uploaded file is empty.');
        }

        // Get header row
        $headers = array_shift($data);
        $headers = array_map('strtolower', $headers);
        $headers = array_map('trim', $headers);

        // Map columns
        $columnMap = $this->mapColumns($headers);

        if (!$columnMap) {
            return back()->with('error', 'Invalid file format. Required columns: Transaction ID, Applicant Name, Email, Amount, Payment Date, Payment Status, Payment Channel');
        }

        // Validate rows
        $rows = [];
        $errors = [];
        $duplicateCheck = [];

        foreach ($data as $index => $row) {
            if (empty(array_filter($row))) continue;

            $rowNum = $index + 2; // +2 because of header and 0-index

            $transactionId = isset($columnMap['transaction_id']) ? ($row[$columnMap['transaction_id']] ?? '') : '';
            $transactionId = strtoupper(trim($transactionId));

            // Validate transaction ID
            if (empty($transactionId)) {
                $errors[] = "Row {$rowNum}: Transaction ID is required";
                continue;
            }

            // Check for duplicates in file
            if (isset($duplicateCheck[$transactionId])) {
                $errors[] = "Row {$rowNum}: Duplicate Transaction ID '{$transactionId}' in file (also in row {$duplicateCheck[$transactionId]})";
                $duplicateCheck[$transactionId] = $rowNum;
                continue;
            }
            $duplicateCheck[$transactionId] = $rowNum;

            // Check if already exists in database
            $exists = ExternalPayment::where('transaction_id', $transactionId)->exists();
            if ($exists) {
                $errors[] = "Row {$rowNum}: Transaction ID '{$transactionId}' already exists in database";
            }

            $rowData = [
                'row' => $rowNum,
                'transaction_id' => $transactionId,
                'applicant_name' => isset($columnMap['applicant_name']) ? ($row[$columnMap['applicant_name']] ?? '') : '',
                'email' => isset($columnMap['email']) ? ($row[$columnMap['email']] ?? '') : '',
                'amount' => isset($columnMap['amount']) ? ($row[$columnMap['amount']] ?? 0) : 0,
                'payment_date' => isset($columnMap['payment_date']) ? ($row[$columnMap['payment_date']] ?? '') : '',
                'payment_status' => isset($columnMap['payment_status']) ? ($row[$columnMap['payment_status']] ?? 'pending') : 'pending',
                'payment_channel' => isset($columnMap['payment_channel']) ? ($row[$columnMap['payment_channel']] ?? '') : '',
            ];

            $rows[] = $rowData;
        }

        // Store preview data in session
        session()->put('payment_sync_preview', [
            'rows' => $rows,
            'errors' => $errors,
            'headers' => $headers,
            'filename' => $file->getClientOriginalName(),
        ]);

        return redirect()->route('bursar.payments.sync.preview');
    }

    /**
     * Show preview results
     */
    public function previewResults()
    {
        $preview = session()->get('payment_sync_preview');

        if (!$preview) {
            return redirect()->route('bursar.payments.sync.index')
                ->with('error', 'No preview data found. Please upload a file first.');
        }

        return view('bursar.payment-sync-preview', $preview);
    }

    /**
     * Process the import
     */
    public function import(Request $request)
    {
        $request->validate([
            'skip_duplicates' => 'boolean',
        ]);

        $preview = session()->get('payment_sync_preview');

        if (!$preview) {
            return redirect()->route('bursar.payments.sync.index')
                ->with('error', 'No preview data found. Please upload a file first.');
        }

        $skipDuplicates = $request->boolean('skip_duplicates', true);
        $rows = $preview['rows'];
        $fileName = $preview['filename'];

        $imported = 0;
        $skipped = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                // Check if already exists
                if ($skipDuplicates && ExternalPayment::where('transaction_id', $row['transaction_id'])->exists()) {
                    $skipped++;
                    continue;
                }

                // Parse payment date
                $paymentDate = $this->parseDate($row['payment_date']);

                ExternalPayment::create([
                    'transaction_id' => $row['transaction_id'],
                    'applicant_name' => $row['applicant_name'],
                    'email' => $row['email'],
                    'amount' => $row['amount'],
                    'payment_date' => $paymentDate,
                    'payment_status' => strtolower($row['payment_status']),
                    'payment_channel' => $row['payment_channel'],
                    'imported_by' => Auth::id(),
                ]);

                $imported++;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $errors[] = 'Import failed: ' . $e->getMessage();
        }

        // Clear preview session
        session()->forget('payment_sync_preview');

        // Log the import
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'payment_import',
            'description' => "Imported {$imported} payment records from {$fileName}",
            'metadata' => json_encode([
                'filename' => $fileName,
                'imported' => $imported,
                'skipped' => $skipped,
                'skip_duplicates' => $skipDuplicates,
            ]),
        ]);

        return redirect()->route('bursar.payments.sync.index')
            ->with('success', "Import completed! {$imported} records imported, {$skipped} skipped.")
            ->with('import_result', [
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => $errors,
                'filename' => $fileName,
            ]);
    }

    /**
     * Map CSV/Excel columns to our fields
     */
    private function mapColumns(array $headers): ?array
    {
        $mapping = [];
        $required = ['transaction_id', 'email', 'amount', 'payment_date', 'payment_status'];

        foreach ($headers as $index => $header) {
            $header = strtolower(trim($header));

            if (in_array($header, ['transaction_id', 'transactionid', 'transaction ref', 'ref', 'reference', 'payment_ref', 'paymentreference'])) {
                $mapping['transaction_id'] = $index;
            } elseif (in_array($header, ['applicant_name', 'applicantname', 'name', 'customer_name', 'customername', 'payer_name', 'payername'])) {
                $mapping['applicant_name'] = $index;
            } elseif (in_array($header, ['email', 'email_address', 'emailaddress', 'payer_email', 'payeremail'])) {
                $mapping['email'] = $index;
            } elseif (in_array($header, ['amount', 'amount_paid', 'amountpaid', 'payment_amount', 'paymentamount'])) {
                $mapping['amount'] = $index;
            } elseif (in_array($header, ['payment_date', 'paymentdate', 'date', 'transaction_date', 'transactiondate', 'paid_date', 'paiddate'])) {
                $mapping['payment_date'] = $index;
            } elseif (in_array($header, ['payment_status', 'paymentstatus', 'status', 'transaction_status', 'transactionstatus'])) {
                $mapping['payment_status'] = $index;
            } elseif (in_array($header, ['payment_channel', 'paymentchannel', 'channel', 'payment_method', 'paymentmethod'])) {
                $mapping['payment_channel'] = $index;
            }
        }

        // Check if required columns are mapped
        foreach ($required as $req) {
            if (!isset($mapping[$req])) {
                return null;
            }
        }

        return $mapping;
    }

    /**
     * Parse date from various formats
     */
    private function parseDate($date)
    {
        if (empty($date)) {
            return now();
        }

        // Try different date formats
        $formats = [
            'Y-m-d H:i:s',
            'Y-m-d',
            'd/m/Y',
            'd/m/Y H:i:s',
            'm/d/Y',
            'm/d/Y H:i:s',
            'd-m-Y',
            'd-m-Y H:i:s',
        ];

        foreach ($formats as $format) {
            $dateTime = \DateTime::createFromFormat($format, $date);
            if ($dateTime !== false) {
                return $dateTime;
            }
        }

        // Try strtotime as fallback
        $timestamp = strtotime($date);
        if ($timestamp !== false) {
            return \Carbon\Carbon::parse($date);
        }

        return now();
    }

    /**
     * Download import template
     */
    public function downloadTemplate()
    {
        $headers = ['Transaction ID', 'Applicant Name', 'Email', 'Amount', 'Payment Date', 'Payment Status', 'Payment Channel'];
        $sampleData = [
            ['TXN001', 'John Doe', 'john@example.com', 5000.00, '2026-07-22 10:30:00', 'completed', 'card'],
            ['TXN002', 'Jane Smith', 'jane@example.com', 5000.00, '2026-07-22 11:00:00', 'completed', 'bank_transfer'],
        ];

        return Excel::download(new \App\Exports\TemplateExport($headers, $sampleData), 'payment_import_template.xlsx');
    }

    /**
     * View import logs
     */
    public function logs()
    {
        $logs = ActivityLog::where('action', 'payment_import')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return view('bursar.payment-sync-logs', compact('logs'));
    }
}
