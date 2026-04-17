<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogger
{
    public static function log(
        ?Request $request,
        ?User $actor,
        string $action,
        ?Model $auditable = null,
        ?User $targetUser = null,
        array $metadata = []
    ): void {
        AuditLog::query()->create([
            'actor_id' => $actor?->id,
            'action' => $action,
            'auditable_type' => $auditable ? $auditable::class : null,
            'auditable_id' => $auditable?->getKey(),
            'target_user_id' => $targetUser?->id,
            'ip_address' => $request?->ip(),
            'user_agent' => self::truncate($request?->userAgent()),
            'metadata' => $metadata !== [] ? $metadata : null,
        ]);
    }

    private static function truncate(?string $value, int $max = 255): ?string
    {
        if ($value === null) {
            return null;
        }

        return mb_strlen($value) > $max
            ? mb_substr($value, 0, $max)
            : $value;
    }
}
