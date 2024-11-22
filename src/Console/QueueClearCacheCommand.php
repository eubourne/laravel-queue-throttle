<?php

namespace EuBourne\LaravelQueueThrottle\Console;

class QueueClearCacheCommand extends CommandAbstract
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'queue:clear-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear the cache file for queue rate limiting';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->throttleManager->clear();

        $this->components->info('Queue rate limiting cache cleared');
    }
}
