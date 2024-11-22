<?php

namespace EuBourne\LaravelQueueThrottle\Console;

class QueueCacheCommand extends CommandAbstract
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'queue:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a cache file for queue rate limiting';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->callSilent('queue:clear-cache');

        $this->throttleManager->cache();

        $this->components->info('Queue rate limiting cache created');
    }
}
