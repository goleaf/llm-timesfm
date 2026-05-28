<?php

namespace App\Services\Crypto;

use Closure;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CryptoCache
{
    private const VERSION_KEY = 'crypto:data-version';

    /**
     * @template TValue
     *
     * @param  Closure(): TValue  $callback
     * @return TValue
     */
    public function remember(string $key, string $ttlKey, Closure $callback): mixed
    {
        if (! (bool) config('crypto.cache.enabled', true)) {
            return $callback();
        }

        $repository = $this->repository();
        $cacheKey = $this->versionedKey($key);

        if ($repository->has($cacheKey)) {
            $value = $repository->get($cacheKey);

            if (! $this->containsIncompleteClass($value)) {
                return $value;
            }

            $repository->forget($cacheKey);
        }

        $value = $callback();
        $repository->put($cacheKey, $value, $this->seconds($ttlKey));

        return $value;
    }

    public function flush(): void
    {
        if (! (bool) config('crypto.cache.enabled', true)) {
            return;
        }

        $this->repository()->forever(self::VERSION_KEY, $this->version() + 1);
    }

    private function versionedKey(string $key): string
    {
        return 'crypto:v'.$this->version().':'.$key;
    }

    private function version(): int
    {
        $version = $this->repository()->get(self::VERSION_KEY);

        if (is_numeric($version)) {
            return (int) $version;
        }

        $this->repository()->forever(self::VERSION_KEY, 1);

        return 1;
    }

    private function seconds(string $ttlKey): int
    {
        return max((int) config("crypto.cache.ttl.{$ttlKey}", 1), 1);
    }

    private function repository(): Repository
    {
        $store = config('crypto.cache.store');

        return $store ? Cache::store((string) $store) : Cache::store();
    }

    private function containsIncompleteClass(mixed $value): bool
    {
        if ($value instanceof \__PHP_Incomplete_Class) {
            return true;
        }

        if ($value instanceof Collection) {
            return $value->contains(fn (mixed $item): bool => $this->containsIncompleteClass($item));
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                if ($this->containsIncompleteClass($item)) {
                    return true;
                }
            }
        }

        return false;
    }
}
