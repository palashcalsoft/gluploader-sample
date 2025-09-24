<?php

namespace App\Services;

use App\Models\GLEntryDetail;
use App\Models\GLEntryMaster;
use App\Models\AccountMaster;
use App\Validators\GLEntryValidator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GLEntryUploadService
{
    public function __construct(private readonly GLEntryValidator $validator)
    {
    }

    /**
     * Process an uploaded GL CSV: parse, validate, persist.
     * Returns an array with keys: http_code, status, file, and additional fields based on status.
     */
    public function process(UploadedFile $file, string $loftUsername, string $uploadedBy, array $storedFileMeta = []): array
    {
        $contents = $this->readCsvLines($file);
        if (!$this->hasHeaderAndData($contents)) {
            return $this->responseUnprocessable('CSV must contain a header and at least one data row.', $storedFileMeta);
        }

        try {
            $columnIndex = $this->parseHeaderAndBuildIndex($contents);
        } catch (\Throwable $e) {
            return $this->responseUnprocessable($e->getMessage(), $storedFileMeta);
        }

        [$rows, $failed] = $this->extractRows($contents, $columnIndex);
        $validAccountLookup = $this->buildValidAccountLookup($rows);
        $this->applyRowValidations($rows, $validAccountLookup);

        $failed = array_values(array_filter($rows, function ($r) {
            return !empty($r['failure_reason']);
        }));

        try {
            $hasFailures = count($failed) > 0;
            $this->persistRows($rows, $loftUsername, $uploadedBy, $storedFileMeta['filename'] ?? null, $hasFailures);
        } catch (\Throwable $e) {
            Log::error('GL upload failed', ['error' => $e->getMessage()]);
            return $this->responseTechnicalError($rows, $e, $storedFileMeta);
        }

        if (count($failed) > 0) {
            return $this->responseFailedRows($rows, $failed, $storedFileMeta);
        }

        return $this->responseSuccess($storedFileMeta);
    }

    private function readCsvLines(UploadedFile $file): array
    {
        return file($file->getPathname(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    }

    private function hasHeaderAndData(array $contents): bool
    {
        return !empty($contents) && count($contents) >= 2;
    }

    private function parseHeaderAndBuildIndex(array &$contents): array
    {
        $headerRow = str_getcsv(array_shift($contents));
        $normalizedHeader = $this->validator->normalizeHeader($headerRow);
        return $this->validator->buildColumnIndex($normalizedHeader);
    }

    private function extractRows(array $contents, array $columnIndex): array
    {
        $rows = [];
        $rowNumber = 1;
        foreach ($contents as $line) {
            $rowNumber++;
            $data = str_getcsv($line);
            $accountNumber = $data[$columnIndex['accountnumber']] ?? null;
            $accountNumber = is_string($accountNumber) ? trim($accountNumber) : $accountNumber;
            $rows[] = [
                'row_number' => $rowNumber - 1,
                'posting_date' => $data[$columnIndex['posting date']] ?? null,
                'reference' => $data[$columnIndex['reference']] ?? null,
                'journal_code' => $data[$columnIndex['journal code']] ?? null,
                'account_number' => $accountNumber,
                'posting_description' => $data[$columnIndex['posting description']] ?? null,
                'debit' => $data[$columnIndex['debit']] ?? null,
                'credit' => $data[$columnIndex['credit']] ?? null,
                'failure_reason' => null,
            ];
        }
        return [$rows, []];
    }

    private function buildValidAccountLookup(array $rows): array
    {
        $allAccountNumbers = array_values(array_unique(array_filter(array_map(function ($r) {
            $acct = $r['account_number'] ?? null;
            return is_string($acct) ? trim($acct) : $acct;
        }, $rows), function ($v) {
            return $v !== null && $v !== '';
        })));

        if (empty($allAccountNumbers)) {
            return [];
        }

        $validAccountCodes = AccountMaster::whereIn('code', $allAccountNumbers)->pluck('code')->all();
        return array_fill_keys($validAccountCodes, true);
    }

    private function applyRowValidations(array &$rows, array $validAccountLookup): void
    {
        foreach ($rows as &$row) {
            $errors = $this->validator->validateRowAll($row['posting_date'], $row['account_number'], $row['debit'], $row['credit']);
            if (
                $row['account_number'] !== null &&
                $row['account_number'] !== '' &&
                !isset($validAccountLookup[$row['account_number']])
            ) {
                $errors[] = 'Invalid Account number';
            }
            $row['failure_reason'] = empty($errors) ? null : implode('; ', $errors);
        }
        unset($row);
    }

    private function persistRows(array $rows, string $loftUsername, string $uploadedBy, ?string $fileName = null, bool $hasFailures = false): void
    {
        DB::transaction(function () use ($rows, $loftUsername, $uploadedBy, $fileName, $hasFailures) {
            $master = GLEntryMaster::create([
                'uploaded_by' => $uploadedBy,
                'loft_username' => $loftUsername,
                'uploaded_at' => now(),
                'total_rows' => count($rows),
                'failed_rows' => $hasFailures ? count(array_filter($rows, fn($r)=>!empty($r['failure_reason']))) : 0,
                'status' => $hasFailures ? 'Failed' : 'Success',
                'file_name' => $fileName,
            ]);

            $payload = [];
            $now = now();
            foreach ($rows as $row) {
                $payload[] = [
                    'gl_entry_master_id' => $master->id,
                    'posting_date' => $row['failure_reason'] ? null : $this->validator->parseDate($row['posting_date']),
                    'reference' => $row['reference'],
                    'journal_code' => $row['journal_code'],
                    'account_number' => $row['account_number'],
                    'posting_description' => $row['posting_description'],
                    'debit' => $this->validator->parseMoney($row['debit']),
                    'credit' => $this->validator->parseMoney($row['credit']),
                    'row_number' => $row['row_number'],
                    'failure_reason' => $row['failure_reason'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($payload) === 1000) {
                    GLEntryDetail::insert($payload);
                    $payload = [];
                }
            }

            if (!empty($payload)) {
                GLEntryDetail::insert($payload);
            }
        });
    }

    private function responseUnprocessable(string $message, ?array $fileMeta = null): array
    {
        return [
            'http_code' => 422,
            'status' => 'failed',
            'message' => $message,
            'file' => $fileMeta,
        ];
    }

    private function responseFailedRows(array $rows, array $failed, ?array $fileMeta = null): array
    {
        return [
            'http_code' => 422,
            'status' => 'failed',
            'failed_records' => $failed,
            'total' => count($rows),
            'failed' => count($failed),
            'file' => $fileMeta,
        ];
    }

    private function responseTechnicalError(array $rows, \Throwable $e, ?array $fileMeta = null): array
    {
        $withFailure = array_map(function ($r) use ($e) {
            $r['failure_reason'] = 'Technical error: ' . $e->getMessage();
            return $r;
        }, $rows);

        return [
            'http_code' => 500,
            'status' => 'failed',
            'failed_records' => $withFailure,
            'total' => count($rows),
            'failed' => count($withFailure),
            'file' => $fileMeta,
        ];
    }

    private function responseSuccess(?array $fileMeta = null): array
    {
        return [
            'http_code' => 200,
            'status' => 'success',
            'message' => 'GL entries uploaded successfully.',
            'file' => $fileMeta,
        ];
    }
}



