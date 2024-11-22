<?php

namespace EuBourne\LaravelQueueThrottle\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

class TestJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        public string $queueName = 'default'
    )
    {
    }

    public function handle(): void
    {
        logger()->debug("Test job executed. Queue: {$this->queueName}");
    }
}
