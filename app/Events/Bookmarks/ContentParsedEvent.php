<?php

namespace App\Events\Bookmarks;

use App\Models\Bookmark;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContentParsedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Bookmark $bookmark
    )
    {}

//    /**
//     * Get the channels the event should broadcast on.
//     *
//     * @return array<int, Channel>
//     */
//    public function broadcastOn(): array
//    {
//        return [
//            new PrivateChannel('channel-name'),
//        ];
//    }
}
