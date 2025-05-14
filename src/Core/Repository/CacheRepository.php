<?php

namespace AppTank\Horus\Core\Repository;

interface CacheRepository
{
    public function get(string $key): mixed;

    public function exists(string $key): bool;

    public function set(string $key, mixed $value, int $ttl): bool;

    public function delete(string $key): bool;

    function remember(string $key, int $ttl, callable $callback): mixed;
}
