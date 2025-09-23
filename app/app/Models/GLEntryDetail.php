<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GLEntryDetail extends Model
{
    use HasFactory;

    protected $table = 'gl_entry_details';

    protected $fillable = [
        'gl_entry_master_id',
        'posting_date',
        'reference',
        'journal_code',
        'account_number',
        'posting_description',
        'debit',
        'credit',
        'row_number',
        'failure_reason',
    ];

    protected $casts = [
        'posting_date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    public function master(): BelongsTo
    {
        return $this->belongsTo(GLEntryMaster::class, 'gl_entry_master_id');
    }
}


