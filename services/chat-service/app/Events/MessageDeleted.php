<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class MessageDeleted implements ShouldBroadcastNow
{
    public function __construct(public string $room, public int $id) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('room.'.$this->room)];
    }

    public function broadcastAs(): string
    {
        return 'message.deleted';
    }
}
