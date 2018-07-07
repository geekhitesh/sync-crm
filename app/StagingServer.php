<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StagingServer extends Model
{
    //
    protected $primaryKey = 'request_id';
    protected $table = 'staging_server';
}
