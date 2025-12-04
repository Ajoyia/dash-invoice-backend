<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Constants;
use Redis;

class EnqueuePermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'enqueue:permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enqueue permissions from Constants.php to Redis queue';

    /**
     * Redis instance.
     *
     * @var \Redis
     */
    protected $redis;

    /**
     * Redis connection configuration.
     *
     * @var array
     */
    protected $config;

    /**
     * Maximum number of connection attempts. Set to null for infinite retries.
     *
     * @var int|null
     */
    protected $maxRetries = null;

    /**
     * Sleep duration between retries in seconds.
     *
     * @var int
     */
    protected $retrySleepSeconds = 5;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        // Load Redis Sender configuration
        $this->config = config('authredis.connection');

    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        // Initialize Redis
        $this->redis = new Redis();

        $attempt = 0;

        while (true) {
            try {
                $attempt++;
                echo "Attempting to connect to Redis (Attempt: {$attempt})...\n";

                // Connect to Redis
                $this->redis->connect(
                    $this->config['host'],
                    $this->config['port'],
                    $this->config['timeout']
                );

                // Authenticate if password is provided
                if (!empty($this->config['password'])) {
                    $this->redis->auth($this->config['password']);
                }

                // Optionally set persistent connection
                if (!empty($this->config['persistent']) && $this->config['persistent']) {
                    $this->redis->pconnect(
                        $this->config['host'],
                        $this->config['port'],
                        $this->config['timeout']
                    );

                    if (!empty($this->config['password'])) {
                        $this->redis->auth($this->config['password']);
                    }
                }

                echo "Connected to Redis at {$this->config['host']}:{$this->config['port']}\n";
                break; // Exit the loop on successful connection

            } catch (\Exception $e) {
                echo "Failed to connect to Redis: " . $e->getMessage() . "\n";

                if ($this->maxRetries !== null && $attempt >= $this->maxRetries) {
                    echo "Reached maximum number of retries ({$this->maxRetries}). Exiting.\n";
                    exit(1);
                }

                echo "Retrying in {$this->retrySleepSeconds} seconds...\n";
                sleep($this->retrySleepSeconds);
            }
        }
        
        echo "Starting to enqueue permissions.\n";

        try {
            $queueName = config('authredis.queue', 'permissions_queue');

            foreach (Constants::PERMISSIONS as $value => $title) {
                $permissionData = [
                    'title' => $title,
                    'value' => $value,
                    'scope' => 'dash_invoice',
                    'active' => 1,
                ];

                $permissionJson = json_encode($permissionData);

                // Enqueue the permission JSON to Redis
                $this->redis->rPush($queueName, $permissionJson);

                echo "Enqueued permission: {$title} (Value: {$value})\n";
            }

            echo "All permissions have been enqueued successfully.\n";

        } catch (\Exception $e) {
            echo "Failed to enqueue permissions: " . $e->getMessage() . "\n";
            return 1; // Non-zero exit code indicates failure
        }

        return 0; // Zero exit code indicates success
    }
}
