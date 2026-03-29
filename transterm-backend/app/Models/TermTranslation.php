<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TermTranslation extends Model
{
    use HasFactory;

    protected $table = 'term_translations';

    protected $fillable = [
        'term_id',
        'language_id',
        'title',
        'plural',
        'definition',
        'context',
        'synonym',
        'notes',
    ];

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    public function termReferences(): HasMany
    {
        return $this->hasMany(TermReference::class);
    }
}
