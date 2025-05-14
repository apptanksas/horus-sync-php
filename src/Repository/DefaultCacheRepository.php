<?php

namespace AppTank\Horus\Repository;

use AppTank\Horus\Core\Repository\CacheRepository;
use Illuminate\Support\Facades\Cache;
class DefaultCacheRepository implements CacheRepository
{
    public function get(string $key): mixed
    {
        return Cache::get($this->createCacheKey($key));
    }

    public function set(string $key, mixed $value, int $ttl): bool
    {
        return Cache::put($this->createCacheKey($key), $value, $ttl);
    }

    public function delete(string $key): bool
    {
        return Cache::delete($this->createCacheKey($key));
    }

    public function exists(string $key): bool
    {
        return Cache::has($this->createCacheKey($key));
    }

    function remember(string $key, int $ttl, callable $callback): mixed
    {
        $key = $this->createCacheKey($key);

        if ($this->exists($key)) {
            return $this->get($key);
        }

        $result = $callback();

        $this->set($key, $result, $ttl);

        return $result;
    }

    private function createCacheKey(string $key): string
    {
        return md5($key);
    }
}