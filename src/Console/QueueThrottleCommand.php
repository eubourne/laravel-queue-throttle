<?php

namespace EuBourne\LaravelQueueThrottle\Console;

class QueueThrottleCommand extends CommandAbstract
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'queue:throttle';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'View the queue rate limiting';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->newLine();
        $this->displayCacheStatus();
        $this->newLine();

        $this->displayLimits();
        $this->newLine();
    }

    /**
     * Display queue rate limits.
     *
     * @return void
     */
    protected function displayLimits(): void
    {
        $limits = $this->throttleManager
            ->getQueueConfigurations();

        if ($limits->count()) {
            $this->components->twoColumnDetail(
                $this->format("Queue / Group", static::MUTED),
                $this->format("Limit", static::MUTED),
            );

            $limits->sortKeys()
                ->each(function ($limit, $queue) {
                    ['group' => $group, 'allows' => $allows, 'every' => $every] = $limit + ['group' => null, 'allows' => null, 'every' => null];

                    $leftColumn = $queue . (
                        $group
                            ? ($this->format(" / ", static::MUTED) . $this->format($group, static::GROUP))
                            : ''
                        );

                    $rightColumn = $allows
                        ? "{$allows} jobs every {$every} seconds"
                        : $this->format(text: 'No limit', color: static::COLOR_GREEN);

                    $this->components->twoColumnDetail($leftColumn, $rightColumn);
                });

            return;
        }

        $this->components->info('No rate limits have been set');
    }
}
