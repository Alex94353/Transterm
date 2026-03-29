<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'user_id',
        'expertise',
        'about',
        'website',
        'telephone',
        'facebook',
        'linkedin',
        'twitter',
    ];


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
