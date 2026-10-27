<?php

declare(strict_types=1);

namespace App\Infrastructure\Redis;

use Predis\Client;

readonly class RedisManager
{
    private Client $redis;

    public function __construct(string $connectionUrl)
    {
        $this->redis = new Client($connectionUrl);
    }

    public function get(string $key): null|int|string
    {
        return $this->redis->get($key);
    }

    public function set(string $key, string|int $value): void
    {
        $this->redis->set($key, $value);
    }

    public function del(string|array $keyOrKeyList): void
    {
        $this->redis->del($keyOrKeyList);
    }

    public function keys(string $filter = '*'): array
    {
        return $this->redis->keys($filter);
    }

    public function hGetAll(string $key): array
    {
        return $this->redis->hgetall($key);
    }

    public function hGet(string $key, string $field): null|string
    {
        return $this->redis->hget($key, $field);
    }

    public function hSet(string $key, string $field, int|string $value): void
    {
        $this->redis->hset($key, $field, $value);
    }

    public function hIncreaseBy(string $key, string $field, int $value): void
    {
        $this->redis->hincrby($key, $field, $value);
    }
}
