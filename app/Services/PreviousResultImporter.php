<?php

namespace App\Services;

use App\Models\PreviousResult;
use App\Models\Student;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

/**
 * Reads Excel (.xlsx) or CSV uploads of historical student results and
 * turns each row into a PreviousResult, validating along the way.
 *
 * Expected columns (case-insensitive, order-flexible):
 *   matric_number, course_code, course_title, units,
 *   session_name, semester, level,
 *   ca, test, assignment, exam, total_score,
 *   grade, grade_point, remarks, source_institution
 *
 * Rules:
 *   - matric_number is required (matches Student.matric_number OR User.email).
 *   - course_code + session_name + semester uniquely identify a row, so
 *     re-importing the same row is idempotent.
 *   - If total_score is present but grade is missing, we derive it from
 *     the existing Grade rules.
 */
class PreviousResultImporter
{
    /** Required columns the importer refuses to skip. */
    public const REQUIRED_COLUMNS = ['matric_number', 'course_code', 'session_name', 'total_score'];

    /**
     * Parse the upload and return both successfully imported rows and
     * a list of errors keyed by row number so the admin can fix and
     * re-upload.
     */
    public function import(UploadedFile $file, int $uploaderId): array
    {
        $rows = $this->parseFile($file);
        if (empty($rows)) {
            return ['imported' => 0, 'errors' => [['row' => 0, 'error' => 'File contained no rows.']]];
        }

        $errors = [];
        $imported = 0;

        foreach ($rows as $i => $row) {
            // Header row? Skip (parseFile already returns only data rows).
            $rowNumber = $i + 2; // +2 because row 1 is the header
            $normalized = $this->normalizeRow($row);

            // Skip fully-empty rows silently.
            if (count(array_filter($normalized, fn ($v) => $v !== null && $v !== '')) === 0) {
                continue;
            }

            $validator = Validator::make($normalized, [
                'matric_number'  => 'required|string',
                'course_code'    => 'required|string|max:50',
                'session_name'   => 'required|string|max:50',
                'total_score'    => 'required|numeric|min:0|max:100',
                'semester'       => 'nullable|in:first,second',
                'level'          => 'nullable|integer|min:1|max:10',
                'units'          => 'nullable|integer|min:0|max:20',
                'ca'             => 'nullable|numeric|min:0|max:100',
                'test'           => 'nullable|numeric|min:0|max:100',
                'assignment'     => 'nullable|numeric|min:0|max:100',
                'exam'           => 'nullable|numeric|min:0|max:100',
                'grade'          => 'nullable|string|max:5',
                'grade_point'    => 'nullable|numeric|min:0|max:5',
            ]);

            if ($validator->fails()) {
                $errors[] = ['row' => $rowNumber, 'error' => implode('; ', $validator->errors()->all())];
                continue;
            }

            // Resolve student.
            $student = Student::where('matric_number', $normalized['matric_number'])->first();
            if (!$student) {
                $errors[] = ['row' => $rowNumber, 'error' => "No student with matric number '{$normalized['matric_number']}'."];
                continue;
            }

            // Upsert by (student, course_code, session, semester). The first
            // import wins; subsequent rows overwrite — same as re-importing
            // a corrected CSV.
            $payload = array_merge($normalized, [
                'student_id'   => $student->id,
                'uploaded_by'  => $uploaderId,
                'uploaded_at'  => now(),
            ]);

            $row = PreviousResult::updateOrCreate(
                [
                    'student_id'  => $student->id,
                    'course_code' => $normalized['course_code'],
                    'session_name'=> $normalized['session_name'],
                    'semester'    => $normalized['semester'] ?? 'first',
                ],
                $payload,
            );
            $row->assignGrade();
            $row->save();
            $imported++;
        }

        return ['imported' => $imported, 'errors' => $errors];
    }

    /**
     * Generate a downloadable CSV template the admin can fill in.
     * Returned as a string the caller can stream with the right
     * Content-Type headers.
     */
    public function templateCsv(): string
    {
        $headers = [
            'matric_number', 'course_code', 'course_title', 'units',
            'session_name', 'semester', 'level',
            'ca', 'test', 'assignment', 'exam', 'total_score',
            'grade', 'grade_point', 'remarks', 'source_institution',
        ];
        $example = [
            'CSC/2022/001', 'CSC101', 'Introduction to Computing', '3',
            '2021/2022', 'first', '1',
            '15', '10', '5', '50', '80',
            'A', '4.0', 'Excellent', 'Ekiti State University',
        ];
        $output = implode(',', $headers) . "\n";
        $output .= implode(',', $example) . "\n";
        return $output;
    }

    /**
     * Read an uploaded file into an array of associative rows. Supports
     * both CSV (read line-by-line) and XLSX (parse the zip+xml inline
     * so we don't need a PhpSpreadsheet dependency).
     */
    private function parseFile(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if ($extension === 'csv' || $extension === 'txt') {
            return $this->parseCsv($file);
        }
        if (in_array($extension, ['xlsx', 'xlsm'], true)) {
            return $this->parseXlsx($file);
        }
        throw new \InvalidArgumentException("Unsupported file type: .{$extension}. Use CSV or XLSX.");
    }

    private function parseCsv(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        $rows = [];
        $header = null;
        if (($handle = fopen($path, 'r')) !== false) {
            while (($data = fgetcsv($handle, 0, ',')) !== false) {
                if (!$header) {
                    $header = array_map(fn ($h) => strtolower(trim($h)), $data);
                    continue;
                }
                // Skip rows that don't have the same column count.
                if (count($data) < count($header)) {
                    $data = array_pad($data, count($header), null);
                }
                $rows[] = array_combine($header, array_slice($data, 0, count($header)));
            }
            fclose($handle);
        }
        return $rows;
    }

    /**
     * Minimal XLSX reader: pulls the first worksheet's shared-strings
     * table and cell values, then maps them onto rows. Avoids pulling
     * in PhpSpreadsheet as a dependency.
     */
    private function parseXlsx(UploadedFile $file): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($file->getRealPath()) !== true) {
            throw new \RuntimeException('Could not open XLSX as a zip archive.');
        }

        // Shared strings live in xl/sharedStrings.xml.
        $sharedStrings = [];
        $ssXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($ssXml !== false) {
            $ssDom = new \DOMDocument();
            $ssDom->loadXML($ssXml);
            foreach ($ssDom->getElementsByTagName('si') as $si) {
                $text = '';
                foreach ($si->childNodes as $node) {
                    $text .= $node->textContent;
                }
                $sharedStrings[] = $text;
            }
        }

        // Find the first worksheet.
        $sheetXml = null;
        for ($i = 1; $i <= $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name && preg_match('#xl/worksheets/sheet\d+\.xml$#', $name)) {
                $sheetXml = $zip->getFromName($name);
                break;
            }
        }
        $zip->close();

        if (!$sheetXml) {
            throw new \RuntimeException('No worksheet found in XLSX.');
        }

        $dom = new \DOMDocument();
        $dom->loadXML($sheetXml);

        $rows = [];
        $header = null;
        foreach ($dom->getElementsByTagName('row') as $rowNode) {
            $cells = [];
            foreach ($rowNode->getElementsByTagName('c') as $cNode) {
                $ref = $cNode->getAttribute('r'); // e.g. A1
                $col = preg_replace('/\d+/', '', $ref);
                $type = $cNode->getAttribute('t');
                $vNode = $cNode->getElementsByTagName('v')->item(0);
                $raw = $vNode ? $vNode->textContent : null;
                $value = $type === 's' ? ($sharedStrings[(int) $raw] ?? null) : $raw;
                $cells[$col] = $value;
            }
            ksort($cells);
            $values = array_values($cells);
            if (!$header) {
                $header = array_map(fn ($h) => strtolower(trim((string) $h)), $values);
                continue;
            }
            // Pad short rows so array_combine doesn't fail.
            while (count($values) < count($header)) {
                $values[] = null;
            }
            $rows[] = array_combine($header, array_slice($values, 0, count($header)));
        }
        return $rows;
    }

    private function normalizeRow(array $row): array
    {
        $out = [];
        foreach ($row as $k => $v) {
            $key = strtolower(trim((string) $k));
            if ($v === '' || $v === null) {
                $out[$key] = null;
                continue;
            }
            // Strip any surrounding quotes a CSV may carry.
            $out[$key] = is_string($v) ? trim($v, " \t\n\r\0\x0B\"'") : $v;
        }
        // Defaults that the form-filler may have left blank.
        $out['semester'] = $out['semester'] ?? 'first';
        $out['units'] = isset($out['units']) && $out['units'] !== '' ? (int) $out['units'] : 0;
        $out['level'] = isset($out['level']) && $out['level'] !== '' ? (int) $out['level'] : null;
        return $out;
    }
}