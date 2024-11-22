<?php

namespace EuBourne\LaravelQueueThrottle\Console;

use EuBourne\LaravelQueueThrottle\Jobs\TestQueueJob;
use Illuminate\Support\Collection;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\progress;
use function Laravel\Prompts\suggest;

class QueueTestCommand extends CommandAbstract
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:test
                            {queue? : The name of the queue to test}
                            {--timeout= : Timeout in seconds}
                            {--r|reset : Reset all test queue files}
                            ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test a queue worker';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $queue = $this->argument('queue');
        $timeout = $this->option('timeout') ?? 60;

        if ($this->option('reset')) {
            $this->resetFiles($queue);
            return;
        }

        if (!$queue) {
            $queue = suggest(
                label: 'What queue should the job be dispatched to?',
                options: $this->getRateLimitedQueues(),
                default: 'default',
            );
        }

        $this->performQueueTest($queue, $timeout);
    }

    /**
     * Reset the test queue file(s).
     *
     * @param string|null $queue
     * @return void
     */
    protected function resetFiles(?string $queue): void
    {
        TestQueueJob::reset($queue);

        $this->components->info($queue
            ? "Queue '{$queue}' test file has been reset"
            : "Queue test files have been reset");
    }

    /**
     * Perform the queue test.
     *
     * @param string $queue
     * @param int $timeout
     * @return void
     */
    protected function performQueueTest(string $queue, int $timeout): void
    {
        try {
            if (app()->isDownForMaintenance()) {
                $this->components->warn('Application is in maintenance mode. Queues will not work until queue worker is running with --force flag!');

                if (!confirm('Do you want to continue?')) {
                    return;
                }
            }

            $this->newLine();

            // Reset and dispatch job
            TestQueueJob::reset($queue);
            TestQueueJob::dispatch()->onQueue($queue);
            $success = false;

            // Progress bar
            $progress = progress(
                label: "Testing queue '{$queue}' worker",
                steps: $timeout,
                hint: "Please wait...",
            );

            $progress->start();

            while ($timeout > 0) {
                $progress->advance();

                if ($success = TestQueueJob::complete($queue)) {
                    break;
                }

                sleep(1);
                $timeout--;
            }
            $progress->finish();

            $success
                ? $this->components->success("Queue '{$queue}' is working fine")
                : $this->components->error("Queue '{$queue}' does not work");
        } catch (\Exception $e) {
            $this->components->error('EXCEPTION: ' . $e->getMessage());
        }
    }

    /**
     * Get the rate limited queues.
     *
     * @return Collection
     */
    protected function getRateLimitedQueues(): Collection
    {
        return $this->throttleManager->getQueueConfigurations()->keys()->sort();
    }
}
