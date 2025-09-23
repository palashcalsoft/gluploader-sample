<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GLEntryMaster extends Model
{
    use HasFactory;

    protected $table = 'gl_entry_masters';

    protected $fillable = [
        'uploaded_by',
        'loft_username',
        'uploaded_at',
        'total_rows',
        'failed_rows',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(GLEntryDetail::class, 'gl_entry_master_id');
    }
}


