<?php

namespace EuBourne\LaravelQueueThrottle;

use EuBourne\LaravelQueueThrottle\Events\LimitExceeded;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\Factory as QueueManager;
use Illuminate\Queue\Worker;
use Illuminate\Queue\WorkerOptions;
use Psr\Log\LoggerInterface;

class RateLimitedWorker extends Worker
{
    public function __construct(
        QueueManager               $manager,
        Dispatcher                 $events,
        ExceptionHandler           $exceptions,
        callable                   $isDownForMaintenance,
        protected ThrottleManager  $rateLimiter,
        protected ?LoggerInterface $logger,
        ?callable                  $resetScope = null,
    )
    {
        parent::__construct($manager, $events, $exceptions, $isDownForMaintenance, $resetScope);
    }

    /**
     * Process the next job on the queue.
     *
     * @param string $connectionName
     * @param string $queue
     * @param WorkerOptions $options
     * @return void
     */
    public function runNextJob($connectionName, $queue, WorkerOptions $options): void
    {
        // There might be several queues specified for the worker. We need to process each of them
        // separately to check rate limits for each queue.
        foreach (explode(',', $queue) as $specificQueue) {
            $this->logger?->debug('Checking queue "' . $specificQueue . '" for jobs...');

            if ($this->manager->connection($connectionName)->size($specificQueue)) {
                $this->logger?->debug('  Queue "' . $specificQueue . '" has jobs');

                if ($this->runNextJobFromQueue($connectionName, $specificQueue, $options)) {
                    $this->logger?->debug('  Successfully processed job on queue "' . $specificQueue . '". There might be more.');
                    return;
                }

                $this->logger?->debug('  Queue has reached the rate limit. Moving to the next queue (if any).');
            } else {
                $this->logger?->debug('  Queue "' . $specificQueue . '" is empty. Moving to the next queue (if any).');
            }
        }

        // If there were no jobs found in any worker's queue then sleep for the specified time.
        $this->logger?->debug('All the queues are empty. Sleeping for ' . $options->sleep . ' seconds.');
        $this->sleep($options->sleep);
    }

    /**
     * Process the next job on the specific queue.
     * Despite the runNextJob() method, that can accept a set of queues to process (like: mail-priority,mail), this
     * method accepts only a single queue name at a time.
     *
     * Returning true means that there was a successful job processing attempt. If the queue hit the rate limit, then
     * the method will return false.
     *
     * @param string $connectionName
     * @param string $queue
     * @param WorkerOptions $options
     * @return bool
     */
    protected function runNextJobFromQueue(string $connectionName, string $queue, WorkerOptions $options): bool
    {
        $this->logger?->debug('  Checking rate limit for queue "' . $queue . '"...');

        return $this->rateLimiter->attempt(
            queue: $queue,
            action: function () use ($connectionName, $queue, $options) {
                $this->logger?->debug('    Running job on queue "' . $queue . '"');
                parent::runNextJob($connectionName, $queue, $options);
            },
            onLimitReached: function (int $availableIn, string $queue) {
                $this->logger?->debug('    Rate limit is reached for queue "' . $queue . '". Next job will be started in ' . $availableIn . ' seconds');
                $this->events->dispatch(new LimitExceeded($queue, $availableIn));
            }
        );
    }
}
