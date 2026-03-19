<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Language extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'flag_path',
    ];

    public function sourcePairs(): HasMany
    {
        return $this->hasMany(LanguagePair::class, 'source_language_id');
    }

    public function targetPairs(): HasMany
    {
        return $this->hasMany(LanguagePair::class, 'target_language_id');
    }

    public function glossaryTranslations(): HasMany
    {
        return $this->hasMany(GlossaryTranslation::class);
    }

    public function termTranslations(): HasMany
    {
        return $this->hasMany(TermTranslation::class);
    }
}
