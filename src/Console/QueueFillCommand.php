<?php

namespace EuBourne\LaravelQueueThrottle\Console;

use EuBourne\LaravelQueueThrottle\Jobs\TestJob;

class QueueFillCommand extends CommandAbstract
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'queue:fill
                            {count? : Number of jobs to dispatch}
                            {--queue= : Queue name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fill the queue with jobs';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $count = $this->argument('count') ?: 1;
        $queue = $this->option('queue') ?: 'default';

        foreach (range(1, $count) as $i) {
            TestJob::dispatch()->onQueue($queue);
        }

        $this->comment('Queue: ' . $queue);
        $this->comment('Dispatched ' . $count . ' jobs');
    }
}
