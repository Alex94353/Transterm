<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GlossaryTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'glossary_id',
        'language_id',
        'title',
        'description',
    ];

    public function glossary(): BelongsTo
    {
        return $this->belongsTo(Glossary::class);
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
