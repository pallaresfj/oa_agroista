<?php

namespace Agroista\Core\Institution;

use Illuminate\Support\Facades\Cache;
use Throwable;

class InstitutionContext
{
    public function __construct(private readonly InstitutionConfigClient $client)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function institution(): array
    {
        $ttl = max(30, (int) config('agroista-core.institution.cache_ttl', 300));
        $key = 'agroista-core.institution';
        $fallbackKey = 'agroista-core.institution.last_known';

        try {
            /** @var array<string, mixed> $data */
            $data = Cache::remember($key, $ttl, function () use ($fallbackKey): array {
                $fresh = $this->client->getInstitution();
                Cache::forever($fallbackKey, $fresh);

                return $fresh;
            });

            return $data;
        } catch (Throwable) {
            $cached = Cache::get($fallbackKey);

            if (is_array($cached)) {
                return $cached;
            }

            Cache::put($key, [], 30);

            return [];
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function apps(): array
    {
        $ttl = max(30, (int) config('agroista-core.institution.cache_ttl', 300));
        $key = 'agroista-core.institution.apps';
        $fallbackKey = 'agroista-core.institution.apps.last_known';

        try {
            /** @var array<int, array<string, mixed>> $data */
            $data = Cache::remember($key, $ttl, function () use ($fallbackKey): array {
                $fresh = $this->client->getApps();
                Cache::forever($fallbackKey, $fresh);

                return $fresh;
            });

            return $data;
        } catch (Throwable) {
            $cached = Cache::get($fallbackKey);

            if (is_array($cached)) {
                return $cached;
            }

            Cache::put($key, [], 30);

            return [];
        }
    }

    public function clear(): void
    {
        Cache::forget('agroista-core.institution');
        Cache::forget('agroista-core.institution.apps');
        Cache::forget('agroista-core.institution.last_known');
        Cache::forget('agroista-core.institution.apps.last_known');
    }
}
