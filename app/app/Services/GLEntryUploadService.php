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
     * Returns an array with keys: http_code, status, and additional fields based on status.
     */
    public function process(UploadedFile $file, string $loftUsername, string $uploadedBy): array
    {
        $contents = $this->readCsvLines($file);
        if (!$this->hasHeaderAndData($contents)) {
            return $this->responseUnprocessable('CSV must contain a header and at least one data row.');
        }

        try {
            $columnIndex = $this->parseHeaderAndBuildIndex($contents);
        } catch (\Throwable $e) {
            return $this->responseUnprocessable($e->getMessage());
        }

        [$rows, $failed] = $this->buildRowsAndValidate($contents, $columnIndex);

        if (count($failed) > 0) {
            return $this->responseFailedRows($rows, $failed);
        }

        try {
            $this->persistRows($rows, $loftUsername, $uploadedBy);
        } catch (\Throwable $e) {
            Log::error('GL upload failed', ['error' => $e->getMessage()]);
            return $this->responseTechnicalError($rows, $e);
        }

        return $this->responseSuccess();
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

    private function buildRowsAndValidate(array $contents, array $columnIndex): array
    {
        $rows = [];
        $failed = [];
        $rowNumber = 1;

        // Prefetch valid account codes to avoid per-row queries
        $allAccountNumbers = [];
        foreach ($contents as $lineForAccounts) {
            $dataForAccounts = str_getcsv($lineForAccounts);
            $acct = $dataForAccounts[$columnIndex['accountnumber']] ?? null;
            $acct = is_string($acct) ? trim($acct) : $acct;
            if ($acct !== null && $acct !== '') {
                $allAccountNumbers[] = $acct;
            }
        }
        $allAccountNumbers = array_values(array_unique($allAccountNumbers));
        $validAccountCodes = [];
        if (!empty($allAccountNumbers)) {
            $validAccountCodes = AccountMaster::whereIn('code', $allAccountNumbers)->pluck('code')->all();
        }
        $validAccountLookup = array_fill_keys($validAccountCodes, true);

        foreach ($contents as $line) {
            $rowNumber++;
            $data = str_getcsv($line);

            $postingDate = $data[$columnIndex['posting date']] ?? null;
            $reference = $data[$columnIndex['reference']] ?? null;
            $journalCode = $data[$columnIndex['journal code']] ?? null;
            $accountNumber = $data[$columnIndex['accountnumber']] ?? null;
            $accountNumber = is_string($accountNumber) ? trim($accountNumber) : $accountNumber;
            $postingDescription = $data[$columnIndex['posting description']] ?? null;
            $debit = $data[$columnIndex['debit']] ?? null;
            $credit = $data[$columnIndex['credit']] ?? null;

            $failureReason = $this->validator->validateRow($postingDate, $accountNumber, $debit, $credit);

            if (!$failureReason && $accountNumber !== null && $accountNumber !== '' && !isset($validAccountLookup[$accountNumber])) {
                $failureReason = 'Account number not found in Account Master';
            }

            $row = [
                'row_number' => $rowNumber - 1,
                'posting_date' => $postingDate,
                'reference' => $reference,
                'journal_code' => $journalCode,
                'account_number' => $accountNumber,
                'posting_description' => $postingDescription,
                'debit' => $debit,
                'credit' => $credit,
                'failure_reason' => $failureReason,
            ];

            $rows[] = $row;
            if ($failureReason) {
                $failed[] = $row;
            }
        }

        return [$rows, $failed];
    }

    private function persistRows(array $rows, string $loftUsername, string $uploadedBy): void
    {
        DB::transaction(function () use ($rows, $loftUsername, $uploadedBy) {
            $master = GLEntryMaster::create([
                'uploaded_by' => $uploadedBy,
                'loft_username' => $loftUsername,
                'uploaded_at' => now(),
                'total_rows' => count($rows),
                'failed_rows' => 0,
            ]);

            foreach ($rows as $row) {
                GLEntryDetail::create([
                    'gl_entry_master_id' => $master->id,
                    'posting_date' => $this->validator->parseDate($row['posting_date']),
                    'reference' => $row['reference'],
                    'journal_code' => $row['journal_code'],
                    'account_number' => $row['account_number'],
                    'posting_description' => $row['posting_description'],
                    'debit' => $this->validator->parseMoney($row['debit']),
                    'credit' => $this->validator->parseMoney($row['credit']),
                    'row_number' => $row['row_number'],
                    'failure_reason' => null,
                ]);
            }
        });
    }

    private function responseUnprocessable(string $message): array
    {
        return [
            'http_code' => 422,
            'status' => 'failed',
            'message' => $message,
        ];
    }

    private function responseFailedRows(array $rows, array $failed): array
    {
        return [
            'http_code' => 422,
            'status' => 'failed',
            'failed_records' => $failed,
            'total' => count($rows),
            'failed' => count($failed),
        ];
    }

    private function responseTechnicalError(array $rows, \Throwable $e): array
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
        ];
    }

    private function responseSuccess(): array
    {
        return [
            'http_code' => 200,
            'status' => 'success',
            'message' => 'GL entries uploaded successfully.',
        ];
    }
}



