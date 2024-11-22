<?php

namespace EuBourne\LaravelQueueThrottle;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Facade;

class QueueServiceProvider extends \Illuminate\Queue\QueueServiceProvider
{
    public function register(): void
    {
        $this->registerThrottleLogger();
        $this->registerThrottleManager();
        parent::register();

        $this->registerCommands();
    }

    public function boot(): void
    {
        $this->optimizeCommands();
    }

    /**
     * Register the throttle logger.
     *
     * @return void
     */
    protected function registerThrottleLogger(): void
    {
        $this->app->singleton('queue.throttle-logger', function ($app) {
            return config('queue.throttle_logger')
                ? logger()->channel(config('queue.throttle_logger'))
                : null;
        });
    }

    /**
     * Register the throttle manager.
     *
     * @return void
     */
    protected function registerThrottleManager(): void
    {
        $this->app->singleton('queue.throttle', function ($app) {
            return new ThrottleManager(
                app: $app,
                rateLimits: $app['config']->get('queue.throttle'),
                logger: $app['queue.throttle-logger']
            );
        });
    }

    /**
     * Register the queue worker.
     *
     * @return void
     */
    public function registerWorker(): void
    {
        $this->app->singleton('queue.worker', function ($app) {
            $isDownForMaintenance = function () {
                return $this->app->isDownForMaintenance();
            };

            $resetScope = function () use ($app) {
                if (method_exists($app['log'], 'flushSharedContext')) {
                    $app['log']->flushSharedContext();
                }

                if (method_exists($app['log'], 'withoutContext')) {
                    $app['log']->withoutContext();
                }

                if (method_exists($app['db'], 'getConnections')) {
                    foreach ($app['db']->getConnections() as $connection) {
                        $connection->resetTotalQueryDuration();
                        $connection->allowQueryDurationHandlersToRunAgain();
                    }
                }

                $app->forgetScopedInstances();

                Facade::clearResolvedInstances();
            };

            return new RateLimitedWorker(
                manager: $app['queue'],
                events: $app['events'],
                exceptions: $app[ExceptionHandler::class],
                isDownForMaintenance: $isDownForMaintenance,
                rateLimiter: $app['queue.throttle'],
                logger: $app['queue.throttle-logger'],
                resetScope: $resetScope
            );
        });
    }

    /**
     * Register the queue commands.
     *
     * @return void
     */
    protected function registerCommands(): void
    {
        $this->commands([
            Console\QueueCacheCommand::class,
            Console\QueueClearCacheCommand::class,
            Console\QueueFillCommand::class,
            Console\QueueTestCommand::class,
            Console\QueueThrottleCommand::class,
        ]);
    }

    /**
     * Optimize the queue commands.
     *
     * @return void
     */
    protected function optimizeCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->optimizes(
                optimize: 'queue:cache',
                clear: 'queue:clear-cache',
                key: 'queue.throttle'
            );
        }
    }
}
