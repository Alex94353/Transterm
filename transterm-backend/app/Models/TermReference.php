<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TermReference extends Model
{
    use HasFactory;

    protected $fillable = [
        'term_translation_id',
        'reference_id',
        'type',
    ];

    public function termTranslation(): BelongsTo
    {
        return $this->belongsTo(TermTranslation::class);
    }

    public function reference(): BelongsTo
    {
        return $this->belongsTo(Reference::class);
    }
}
