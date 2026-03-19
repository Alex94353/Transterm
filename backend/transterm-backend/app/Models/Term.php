<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Term extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'glossary_id',
        'field_id',
        'created_by',
    ];

    public function glossary(): BelongsTo
    {
        return $this->belongsTo(Glossary::class);
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(Field::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(TermTranslation::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}
