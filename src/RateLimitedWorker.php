<?php

namespace EuBourne\LaravelQueueThrottle;

use EuBourne\LaravelQueueThrottle\Events\LimitExceeded;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\Factory as QueueManager;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\Worker;
use Illuminate\Support\Collection;
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
        protected bool             $debugToConsole = false,
        ?callable                  $resetScope = null,
    )
    {
        parent::__construct($manager, $events, $exceptions, $isDownForMaintenance, $resetScope);
    }

    /**
     * Get the next job from the queue connection.
     *
     * @param Queue $connection
     * @param string $queue
     * @return Job|null
     */
    protected function getNextJob($connection, $queue): Job|null
    {
        foreach ($this->getQueues($queue) as $specificQueue) {
            if ($job = $this->getNextJobFromQueue($connection, $specificQueue)) {
                return $job;
            }
        }

        return null;
    }

    /**
     * Get the next job from the specific queue if its rate limit allows it.
     *
     * @param Queue $connection
     * @param string $queue
     * @return Job|null
     */
    protected function getNextJobFromQueue(Queue $connection, string $queue): Job|null
    {
        $this->debug('Processing [' . $queue . '] queue');

        if ($jobsCount = $connection->size($queue)) {
            $this->debug('  Queue [' . $queue . '] has ' . $jobsCount . ' jobs');

            $this->debug('  Checking rate limit for queue [' . $queue . ']...');
            return $this->rateLimiter->attempt(
                queue: $queue,
                onSuccess: function () use ($connection, $queue) {
                    $job = parent::getNextJob($connection, $queue);

                    $job
                        ? $this->debug('    Running job ' . $job->getJobId() . ':' . $job->resolveName() . ' on queue [' . $queue . ']')
                        : $this->debug('    Cannot get a job from queue [' . $queue . '], it seems to be empty');

                    return $job;
                },
                onLimitReached: function (int $availableIn, string $queue) {
                    $this->debug('    Rate limit is reached for queue [' . $queue . ']. Next job will be started in ' . $availableIn . ' seconds');
                    $this->events->dispatch(new LimitExceeded($queue, $availableIn));
                }
            );
        } else {
            $this->debug('  Queue [' . $queue . '] is empty, skipping...');
        }

        return null;
    }

    /**
     * Get a collection of queues from the queue names, separated by comma.
     *
     * @param string $queue
     * @return Collection
     */
    protected function getQueues(string $queue): Collection
    {
        return new Collection(explode(',', $queue));
    }

    /**
     * Log a debug message.
     *
     * @param string $message
     * @return void
     */
    protected function debug(string $message): void
    {
        if ($this->debugToConsole) {
            echo "[DEBUG]: $message\n";
        }

        $this->logger?->debug($message);
    }
}
