<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $from;
    public $to;
    public $url;

    /**
     * Create a new event instance.
     */
    public function __construct($message, $from, $to, $url)
    {
        $this->from = $from;
        $this->to = $to;
        $this->message = $message;
        $this->url = $url;
    }
    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn()
    {
        return ['notification-channel'];
    }

    public function broadcastAs()
    {
        return 'notification-send';
    }


    public function broadcastWith()
    {
        return [
            'from' => $this->from,
            'to' => $this->to,
            'message' => $this->message,
            'url' => $this->url,
        ];
    }
}
