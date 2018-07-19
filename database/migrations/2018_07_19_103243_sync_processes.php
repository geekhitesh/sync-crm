<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SyncProcesses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('sync_processes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('total_records_processed');
            $table->integer('insert_success_count');
            $table->integer('insert_failed_count');
            $table->integer('update_success_count');
            $table->integer('update_failed_count');
            $table->integer('delete_success_count');
            $table->integer('delete_failed_count');        
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::drop('sync_processes');
    }
}
