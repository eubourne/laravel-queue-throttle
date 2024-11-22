<?php

namespace EuBourne\LaravelQueueThrottle\Console;

use EuBourne\LaravelQueueThrottle\ThrottleManager;
use EuBourne\LaravelQueueThrottle\Traits\SupportsFormatting;
use Illuminate\Console\Command;

abstract class CommandAbstract extends Command
{
    use SupportsFormatting;

    /**
     * Define output color scheme
     */
    const string MUTED = CommandAbstract::COLOR_GRAY;
    const string GROUP = CommandAbstract::COLOR_VIOLET;

    /**
     * The throttle manager instance.
     *
     * @var ThrottleManager
     */
    protected ThrottleManager $throttleManager;

    public function __construct()
    {
        parent::__construct();

        $this->throttleManager = app('queue.throttle');
    }

    /**
     * Display the cache status.
     *
     * @return void
     */
    protected function displayCacheStatus(): void
    {
        $this->components->twoColumnDetail('Throttle cache', $this->throttleManager->isCached()
            ? $this->format(text: 'CACHED', color: static::COLOR_GREEN, bold: true)
            : $this->format(text: 'NOT CACHED', color: static::COLOR_YELLOW, bold: true)
        );
    }
}
