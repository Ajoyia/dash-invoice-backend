<?php

namespace App\Services\Queue;

interface QueueServiceInterface
{
    public function pushToQueue(string $queueName, array $data): void;
}
