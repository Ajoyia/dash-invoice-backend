<?php

namespace App\Services\Queue;

use Illuminate\Support\Facades\Redis;

class RedisQueueService implements QueueServiceInterface
{
    private Redis $redis;

    public function __construct()
    {
        $this->redis = new Redis();
        $host = config('authredis.connection.host') ?: env('REDIS_HOST', '127.0.0.1');
        $port = config('authredis.connection.port') ?: env('REDIS_PORT', 6379);
        $password = config('authredis.connection.password') ?: env('REDIS_PASSWORD', null);

        $this->redis->connect($host, $port);
        
        if ($password) {
            $this->redis->auth($password);
        }
    }

    public function pushToQueue(string $queueName, array $data): void
    {
        $this->redis->lPush($queueName, json_encode($data));
    }
}
