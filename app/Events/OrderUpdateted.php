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

    public function broadcaston ():array{
        retrun [
             new privateChannel('.sala' . $this->roomid)
        ];
    }

    public function broadcastAs ():string{
        return "OrderUpdateted";
    }
    
    public function broadcastWith():array{
        return[
            'orderId' => $this->orderId
        ];
    }
    

}
