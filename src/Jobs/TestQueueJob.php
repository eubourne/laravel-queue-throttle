<?php

namespace EuBourne\LaravelQueueThrottle\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\File;

class TestQueueJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    const string FILEPATH = 'framework/testing/queue__*.ok';

    public function handle(): void
    {
        File::put(static::filePath($this->queue), '');
        logger()->info("Queued job was processed successfully on the '{$this->queue}' queue. Job timezone: " . date_default_timezone_get());
    }

    /**
     * Reset the test queue file(s).
     *
     * @param string|null $queue
     * @return void
     */
    public static function reset(string $queue = null): void
    {
        if (is_null($queue)) {
            File::delete(File::glob(storage_path(static::FILEPATH)));
        } else {
            File::delete(static::filePath($queue));
        }
    }

    /**
     * Check if the test queue file exists.
     *
     * @param string $queue
     * @return bool
     */
    public static function complete(string $queue): bool
    {
        if (File::exists(static::filePath($queue))) {
            static::reset($queue);
            return true;
        }

        return false;
    }

    /**
     * Get the test queue file path.
     *
     * @param string $queue
     * @return string
     */
    public static function filePath(string $queue): string
    {
        return storage_path(str_replace('*', $queue, static::FILEPATH));
    }
}
