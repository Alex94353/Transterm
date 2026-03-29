<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Glossary extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'owner_id',
        'language_pair_id',
        'field_id',
        'approved',
        'is_public',
    ];

    protected $casts = [
        'approved' => 'boolean',
        'is_public' => 'boolean',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function languagePair(): BelongsTo
    {
        return $this->belongsTo(LanguagePair::class);
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(Field::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(GlossaryTranslation::class);
    }

    public function terms(): HasMany
    {
        return $this->hasMany(Term::class);
    }
}
