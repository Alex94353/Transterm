<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * @var array<int, string>
     */
    public const BASE_ROLE_NAMES = ['User', 'Student', 'Teacher'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'username',
        'name',
        'surname',
        'email',
        'password',
        'language',
        'activated',
        'banned',
        'ban_reason',
        'country_id',
        'visible',
        'last_login',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login' => 'datetime',
            'activated' => 'boolean',
            'banned' => 'boolean',
            'visible' => 'boolean',
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function ownedGlossaries(): HasMany
    {
        return $this->hasMany(Glossary::class, 'owner_id');
    }

    public function createdTerms(): HasMany
    {
        return $this->hasMany(Term::class, 'created_by');
    }

    public function references(): HasMany
    {
        return $this->hasMany(Reference::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_id');
    }

    public function auditTargets(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'target_user_id');
    }

    public static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    public static function supportsStudentRoleForEmail(string $email): bool
    {
        return str_ends_with(self::normalizeEmail($email), '@student.ukf.sk');
    }

    public static function supportsTeacherRoleForEmail(string $email): bool
    {
        $normalized = self::normalizeEmail($email);

        return ! self::supportsStudentRoleForEmail($normalized)
            && str_ends_with($normalized, '@ukf.sk');
    }

    public static function resolveBaseRoleByEmail(string $email): string
    {
        if (self::supportsStudentRoleForEmail($email)) {
            return 'Student';
        }

        if (self::supportsTeacherRoleForEmail($email)) {
            return 'Teacher';
        }

        return 'User';
    }

    public static function supportsBaseRoleForEmail(string $baseRole, string $email): bool
    {
        return match ($baseRole) {
            'User' => true,
            'Student' => self::supportsStudentRoleForEmail($email),
            'Teacher' => self::supportsTeacherRoleForEmail($email),
            default => false,
        };
    }

    public function supportsBaseRole(string $baseRole): bool
    {
        return self::supportsBaseRoleForEmail($baseRole, (string) $this->email);
    }

    public function resolveCurrentBaseRoleName(): ?string
    {
        $roleNames = $this->relationLoaded('roles')
            ? $this->roles->pluck('name')
            : $this->roles()->pluck('name');

        foreach (self::BASE_ROLE_NAMES as $baseRoleName) {
            if ($roleNames->contains($baseRoleName)) {
                return $baseRoleName;
            }
        }

        return null;
    }

    public function supportsStudentRoleByEmail(): bool
    {
        return self::supportsStudentRoleForEmail((string) $this->email);
    }

    public function supportsTeacherRoleByEmail(): bool
    {
        return self::supportsTeacherRoleForEmail((string) $this->email);
    }

}
