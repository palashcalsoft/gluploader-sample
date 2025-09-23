<?php

namespace App\Validators;

use Illuminate\Support\Str;

class GLEntryValidator
{
    public const EXPECTED_COLUMNS = [
        'posting date',
        'reference',
        'journal code',
        'accountnumber',
        'posting description',
        'debit',
        'credit',
        'posting date',
    ];

    /**
     * Normalize header values to lowercase and canonical forms.
     */
    public function normalizeHeader(array $headerRow): array
    {
        return array_map(function ($h) {
            return Str::of($h)
                ->lower()
                ->replace(['#', '  '], ['number', ' '])
                ->replaceMatches('/\s+/', ' ')
                ->trim()
                ->toString();
        }, $headerRow);
    }

    /**
     * Build map of expected column name to its index; throws if missing.
     */
    public function buildColumnIndex(array $normalizedHeader): array
    {
        $columnIndex = [];
        foreach (self::EXPECTED_COLUMNS as $name) {
            $idx = array_search($name, $normalizedHeader);
            if ($idx === false) {
                throw new \InvalidArgumentException('CSV header missing required column: ' . $name);
            }
            $columnIndex[$name] = $idx;
        }
        return $columnIndex;
    }

    /**
     * Validate a single CSV row. Returns failure reason string or null if valid.
     */
    public function validateRow($postingDate, $accountNumber, $debit, $credit): ?string
    {
        $errors = $this->validateRowAll($postingDate, $accountNumber, $debit, $credit);
        return empty($errors) ? null : implode('; ', $errors);
    }

    /**
     * Validate a single CSV row and return all error messages.
     */
    public function validateRowAll($postingDate, $accountNumber, $debit, $credit): array
    {
        $errors = [];

        if (!$postingDate) {
            $errors[] = 'Posting date is required';
        } else {
            try {
                $this->parseDate($postingDate);
            } catch (\Throwable $e) {
                $errors[] = 'Invalid posting date';
            }
        }

        if (!$accountNumber) {
            $errors[] = 'Invalid account number';
        }

        $debitAmount = $this->parseMoney($debit);
        $creditAmount = $this->parseMoney($credit);
        if ($debitAmount < 0 || $creditAmount < 0) {
            $errors[] = 'Amounts cannot be negative';
        }
        if ($debitAmount > 0 && $creditAmount > 0) {
            $errors[] = 'Both debit and credit cannot be positive';
        }
        if ($debitAmount == 0 && $creditAmount == 0) {
            $errors[] = 'Either debit or credit required';
        }

        return $errors;
    }

    /**
     * Parse date into Y-m-d or throw on failure.
     */
    public function parseDate($value): string
    {
        $value = trim((string)$value);
        $formats = ['Y-m-d', 'm/d/Y', 'd/m/Y'];
        foreach ($formats as $fmt) {
            $dt = \DateTime::createFromFormat($fmt, $value);
            if ($dt && $dt->format($fmt) === $value) {
                return $dt->format('Y-m-d');
            }
        }
        $ts = strtotime($value);
        if ($ts !== false) return date('Y-m-d', $ts);
        throw new \InvalidArgumentException('Invalid date');
    }

    /**
     * Parse a money string into float.
     */
    public function parseMoney($value): float
    {
        $normalized = str_replace([',', ' '], ['', ''], (string)$value);
        $normalized = preg_replace('/[^0-9.\-]/', '', $normalized);
        return (float)$normalized;
    }
}



