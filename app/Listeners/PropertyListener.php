<?php

namespace App\Listeners;

use App\Events\PropertyPushed;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class PropertyListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  PropertyPushed  $event
     * @return void
     */
    public function handle(PropertyPushed $event)
    {
        //
    }
}
