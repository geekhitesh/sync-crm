<?php

namespace App\Events;

use App\Events\Event;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\StagingServer;
use Log;

class PropertyPushed extends Event
{
    use SerializesModels;

    public $records;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(StagingServer $record)
    {
        //
        $records[0] = $record;
        Log::info('New Property Pushed');
    }

    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return [];
    }
}
