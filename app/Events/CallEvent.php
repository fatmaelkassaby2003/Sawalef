<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $data;

    /**
     * Create a new event instance.
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Get the channels the event should broadcast on.
     * We use a private channel for the receiver.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->data['receiver_id']),
        ];
    }

    /**
     * The event name to listen for in the frontend (Pusher)
     */
    public function broadcastAs()
    {
        return 'incoming.call';
    }
}
