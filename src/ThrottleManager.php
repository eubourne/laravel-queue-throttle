<?php

namespace EuBourne\LaravelQueueThrottle;

use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Psr\Log\LoggerInterface;

class ThrottleManager
{
    const string GROUP_CACHE_KEY = 'queue.rateLimitGroups';

    protected RateLimiter $rateLimiter;

    public function __construct(
        protected Application      $app,
        protected array            $rateLimits,
        protected ?LoggerInterface $logger = null
    )
    {
        $this->rateLimiter = app(RateLimiter::class);
    }

    /**
     * Attempt to execute an action if the rate limit allows it.
     *
     * @param string $queue
     * @param callable $onSuccess
     * @param callable $onLimitReached
     * @return Job|null
     */
    public function attempt(
        string   $queue,
        callable $onSuccess,
        callable $onLimitReached,
    ): Job|null
    {
        // Get throttling rules for the queue
        $throttling = $this->getQueueConfiguration($queue);

        if ($throttling) {
            ['group' => $group, 'allows' => $allows, 'every' => $every] = $throttling;

            if ($this->rateLimiter->tooManyAttempts($group, (int)$allows)) {
                $availableIn = $this->rateLimiter->availableIn($group);
                $this->app->call($onLimitReached, compact('availableIn', 'queue'));
                return null;
            }

            $this->rateLimiter->hit($group, (int)$every);
        }

        // There are no throttling rules for this queue.
        return $this->app->call($onSuccess);
    }

    /**
     * Get the rate limit group configuration for a given queue.
     *
     * @param string $group
     * @return Collection|null
     */
    public function getQueueConfiguration(string $group): ?Collection
    {
        $groupConfig = $this->getQueueConfigurations()->get($group);
        return $groupConfig ? collect($groupConfig) : null;
    }

    /**
     * Get the rate limit groups.
     *
     * @return Collection
     */
    public function getQueueConfigurations(): Collection
    {
        return $this->getLimitGroupsCached()
            ?: $this->buildQueueGroupsMap($this->rateLimits);
    }

    /**
     * Get the rate limit groups from the cache.
     * If the cache is empty, return null.
     *
     * @return Collection|null
     */
    protected function getLimitGroupsCached(): ?Collection
    {
        $groups = Cache::get(static::GROUP_CACHE_KEY);
        return $groups instanceof Collection ? $groups : null;
    }

    /**
     * Check if the rate limit groups are cached.
     *
     * @return bool
     */
    public function isCached(): bool
    {
        return Cache::has(static::GROUP_CACHE_KEY);
    }

    /**
     * Cache the rate limit groups.
     *
     * @return void
     */
    public function cache(): void
    {
        Cache::forever(static::GROUP_CACHE_KEY, $this->buildQueueGroupsMap($this->rateLimits));
    }

    /**
     * Clear the rate limit groups cache.
     *
     * @return void
     */
    public function clear(): void
    {
        Cache::forget(static::GROUP_CACHE_KEY);
    }

    /**
     * Build a collection that maps certain groups to corresponding rate limit groups.
     *
     * @param array $rateLimits The rate limits configuration array.
     * @return Collection A collection mapping queues to their respective rate limit groups.
     */
    protected function buildQueueGroupsMap(array $rateLimits): Collection
    {
        $this->logger?->debug('    Building queue groups map...');

        return collect(array_keys($rateLimits))
            ->flatMap(fn(string $group) => collect(explode(',', str_replace(':', ',', $group)))
                ->flatMap(fn(string $queue) => [
                    $queue => array_merge([
                        'group' => $group
                    ], $rateLimits[$group]),
                ])
            );
    }
}
