<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class OrderDeleted implements ShouldBroadcastNow
{
   use Dispatchable, InteractsWithSockets;

   public function __construct(
    public int $orderId,
    public int $roomId,
   ) {}

   public function broadcastOn(): array
   {
    return[
        new privateChannel('sala.' . $this->roomId)
    ];
   }

   public function broadcastAs(): string
   {
    return 'OrderDeleted';
   }

   public function broadcastWith(): array
   {
    return [
        'orderId' => $this->orderId
    ];
   }
   
}
