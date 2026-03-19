<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Field extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'field_group_id',
        'name',
        'code',
    ];

    public function fieldGroup(): BelongsTo
    {
        return $this->belongsTo(FieldGroup::class);
    }

    public function glossaries(): HasMany
    {
        return $this->hasMany(Glossary::class);
    }

    public function terms(): HasMany
    {
        return $this->hasMany(Term::class);
    }
}
