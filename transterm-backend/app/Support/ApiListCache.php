<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ApiListCache
{
    private const GLOSSARIES_VERSION_KEY = 'api:list:version:glossaries';
    private const TERMS_VERSION_KEY = 'api:list:version:terms';

    public static function enabled(): bool
    {
        return (bool) config('api_cache.enabled', false);
    }

    public static function ttlSeconds(): int
    {
        $ttl = (int) config('api_cache.ttl', 60);

        return max(1, $ttl);
    }

    public static function glossariesKey(Request $request): string
    {
        return sprintf(
            'api:list:glossaries:v%s:%s:q:%s',
            self::versionsFingerprint(),
            self::scopeFingerprint($request),
            self::queryFingerprint($request)
        );
    }

    public static function termsKey(Request $request): string
    {
        return sprintf(
            'api:list:terms:v%s:%s:q:%s',
            self::versionsFingerprint(),
            self::scopeFingerprint($request),
            self::queryFingerprint($request)
        );
    }

    public static function bumpGlossaryAndTermVersions(): void
    {
        self::bumpVersion(self::GLOSSARIES_VERSION_KEY);
        self::bumpVersion(self::TERMS_VERSION_KEY);
    }

    private static function versionsFingerprint(): string
    {
        $glossariesVersion = self::currentVersion(self::GLOSSARIES_VERSION_KEY);
        $termsVersion = self::currentVersion(self::TERMS_VERSION_KEY);

        return "g{$glossariesVersion}:t{$termsVersion}";
    }

    private static function currentVersion(string $key): int
    {
        $value = Cache::get($key);

        if (! is_numeric($value) || (int) $value < 1) {
            Cache::forever($key, 1);

            return 1;
        }

        return (int) $value;
    }

    private static function bumpVersion(string $key): void
    {
        $nextVersion = self::currentVersion($key) + 1;
        Cache::forever($key, $nextVersion);
    }

    private static function scopeFingerprint(Request $request): string
    {
        $user = $request->user();
        if (! $user) {
            return 'scope:guest';
        }

        return 'scope:user:' . $user->id;
    }

    private static function queryFingerprint(Request $request): string
    {
        $normalizedQuery = self::normalizeQuery($request->query());

        return sha1(json_encode($normalizedQuery, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private static function normalizeQuery(array $input): array
    {
        ksort($input);

        foreach ($input as $key => $value) {
            if (is_array($value)) {
                $input[$key] = self::normalizeQuery($value);
            }
        }

        return $input;
    }
}

