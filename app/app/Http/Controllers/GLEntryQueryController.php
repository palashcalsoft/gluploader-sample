<?php

namespace App\Http\Controllers;

use App\Models\GLEntryDetail;
use App\Models\GLEntryMaster;
use App\Validators\GLEntryValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GLEntryQueryController extends Controller
{
    public function masters(Request $request)
    {
        $masters = GLEntryMaster::orderByDesc('id')->paginate(10);
        return response()->json($masters);
    }

    public function details(int $id)
    {
        $master = GLEntryMaster::findOrFail($id);
        $failed = GLEntryDetail::where('gl_entry_master_id', $id)
            ->whereNotNull('failure_reason')
            ->orderBy('row_number')
            ->get();
        $success = GLEntryDetail::where('gl_entry_master_id', $id)
            ->whereNull('failure_reason')
            ->orderBy('row_number')
            ->get();
        return response()->json([
            'master' => $master,
            'failed' => $failed,
            'success' => $success,
        ]);
    }

    public function retryFailed(int $id, Request $request, GLEntryValidator $validator)
    {
        $rules = [
            'rows' => ['required', 'array'],
            'rows.*.row_number' => ['required', 'integer'],
            'rows.*.posting_date' => ['required', 'string'],
            'rows.*.reference' => ['nullable', 'string'],
            'rows.*.journal_code' => ['nullable', 'string'],
            'rows.*.account_number' => ['required', 'string'],
            'rows.*.posting_description' => ['nullable', 'string'],
            'rows.*.debit' => ['nullable'],
            'rows.*.credit' => ['nullable'],
        ];

        $inputRows = $request->input('rows', []);
        $v = Validator::make($request->all(), $rules);
        if ($v->fails()) {
            $errorsByIndex = [];
            $labels = [
                'posting_date' => 'Posting date',
                'reference' => 'Reference',
                'journal_code' => 'Journal code',
                'account_number' => 'Account#',
                'posting_description' => 'Posting description',
                'debit' => 'Debit',
                'credit' => 'Credit',
            ];

            foreach ($v->errors()->messages() as $key => $messages) {
                if (preg_match('/rows\.(\d+)\.(\w+)/', $key, $m)) {
                    $idx = (int) $m[1];
                    $field = $m[2];
                    $rowNumber = $inputRows[$idx]['row_number'] ?? ($idx + 1);
                    $label = $labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
                    foreach ($messages as $msg) {
                        $msgLower = strtolower($msg);
                        $reason = str_contains($msgLower, 'required')
                            ? ($label . ' is required')
                            : ($label . ' is invalid');
                        $errorsByIndex[$idx][] = 'Row ' . $rowNumber . ' ' . $reason;
                    }
                }
            }

            $failed = [];
            foreach ($errorsByIndex as $idx => $reasons) {
                $row = $inputRows[$idx] ?? ['row_number' => $idx + 1];
                $row['failure_reason'] = implode('; ', array_unique($reasons));
                $failed[] = $row;
            }

            return response()->json(['status' => 'failed', 'failed_records' => $failed], 422);
        }

        $rows = $v->validated()['rows'];

        $failed = [];
        foreach ($rows as $row) {
            $reason = $validator->validateRow($row['posting_date'], $row['account_number'], $row['debit'] ?? 0, $row['credit'] ?? 0);
            if ($reason) {
                $row['failure_reason'] = $reason;
                $failed[] = $row;
                continue;
            }
            GLEntryDetail::where('gl_entry_master_id', $id)
                ->where('row_number', $row['row_number'])
                ->update([
                    'posting_date' => $validator->parseDate($row['posting_date']),
                    'reference' => $row['reference'] ?? null,
                    'journal_code' => $row['journal_code'] ?? null,
                    'account_number' => $row['account_number'],
                    'posting_description' => $row['posting_description'] ?? null,
                    'debit' => $validator->parseMoney($row['debit'] ?? 0),
                    'credit' => $validator->parseMoney($row['credit'] ?? 0),
                    'failure_reason' => null,
                ]);
        }

        // Update master status if all rows are valid now
        $remaining = GLEntryDetail::where('gl_entry_master_id', $id)->whereNotNull('failure_reason')->count();
        if ($remaining === 0) {
            GLEntryMaster::where('id', $id)->update(['status' => 'Success']);
        }

        if (!empty($failed)) {
            return response()->json(['status' => 'failed', 'failed_records' => $failed], 422);
        }
        return response()->json(['status' => 'success']);
    }
}


