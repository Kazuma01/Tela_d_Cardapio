<?php

namespace App\Events;


use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastnow;
use Illuminate\Foundation\Events\Dispatchable;


class OrderUpdateted implements ShouldBroadcastnow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public int $orderId,
    ) {}
}
