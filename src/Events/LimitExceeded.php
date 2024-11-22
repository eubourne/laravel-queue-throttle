<?php

namespace EuBourne\LaravelQueueThrottle\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LimitExceeded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $connection,
        public string $queue
    )
    {
    }
}
