<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

use App\Http\Services;
use App\StagingServer;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        // Commands\Inspire::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
         //         ->everyMinute();
        $schedule->call(function (){

           //$records =  StagingServer::all(array('request_id','request_input','request_status'));
           $records =  StagingServer::where('request_status','=','P')->get();
            if($records->count > 0)
            {
                $sync_crm_service = new Services\SyncCRMService();
                $sync_crm_service->syncProperties($records); 
            }
        })->everyMinute();
    }
}
