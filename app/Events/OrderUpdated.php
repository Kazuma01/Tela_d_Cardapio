<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order->load('items.product');
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('sala.' . $this->order->room_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'OrderUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'order' => [
                'id' => $this->order->id,
                'identificacao' => $this->order->identificacao,
                'observacao' => $this->order->observacao,
                'status' => $this->order->status,
                'items' => $this->order->items->map(fn ($item) => [
                    'quantidade' => $item->quantidade,
                    'observacao' => $item->observacao,
                    'produto' => $item->product->nome,
                ]),
            ],
        ];
    }
}
